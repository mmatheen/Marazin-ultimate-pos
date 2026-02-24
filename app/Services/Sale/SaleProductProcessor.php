<?php

namespace App\Services\Sale;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\ImeiNumber;
use App\Models\LocationBatch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleImei;
use App\Models\SalesProduct;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleProductProcessor
{
    protected $saleValidationService;

    public function __construct(SaleValidationService $saleValidationService)
    {
        $this->saleValidationService = $saleValidationService;
    }

    /**
     * Process all product/stock operations for a sale (create or update).
     * Restores old stock on update, deducts new stock, clears cache.
     */
    public function process(
        Sale    $sale,
        Request $request,
        bool    $isUpdate,
        ?string $oldStatus,
        string  $newStatus,
        string  $transactionType
    ): void {
        // ----- Products Logic (allow multiple for jobticket) -----
        $originalProducts = [];
        if ($isUpdate) {
            // Store original quantities for stock validation during update (paid + free)
            foreach ($sale->products as $product) {
                if (!isset($originalProducts[$product->product_id][$product->batch_id])) {
                    $originalProducts[$product->product_id][$product->batch_id] = [
                        'quantity' => 0,
                        'free_quantity' => 0
                    ];
                }
                $originalProducts[$product->product_id][$product->batch_id]['quantity'] += $product->quantity;
                $originalProducts[$product->product_id][$product->batch_id]['free_quantity'] += ($product->free_quantity ?? 0);

                if (in_array($oldStatus, ['final', 'suspend'])) {
                    $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                } else {
                    // For non-final statuses, still need to restore IMEI numbers
                    $this->restoreImeiNumbers($product);
                }
                $product->delete();
            }
        }

        // Batch load all products to avoid N+1 queries
        // Don't use cached data for stock-critical operations
        $productIds = collect($request->products)->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)
            ->select('id', 'product_name', 'sku', 'stock_alert', 'unit_id')
            ->with('unit:id,allow_decimal')
            ->get()->keyBy('id');

        foreach ($request->products as $productData) {
            $product = $products[$productData['product_id']] ?? null;
            if (!$product) {
                throw new \Exception("Product ID {$productData['product_id']} not found");
            }

            // *** CRITICAL SECURITY FIX: Validate price integrity during edit mode ***
            if ($isUpdate) {
                $this->saleValidationService->validateEditModePrice($productData, $sale);
            }

            // Warn if product has stock_alert=0 but no batches exist (data integrity issue)
            if ($product->stock_alert === 0) {
                $this->processUnlimitedStockProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE);
            } else {
                // For updates, check stock availability considering the original sale quantities
                if ($isUpdate && in_array($newStatus, ['final', 'suspend'])) {
                    $this->saleValidationService->validateStockForUpdate($productData, $request->location_id, $originalProducts);
                }

                // For Sale Orders: Deduct stock but use special stock type for allocation tracking
                if ($transactionType === 'sale_order') {
                    $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE_ORDER, 'sale_order');
                }
                // Always process sale for final/suspend status
                elseif (in_array($newStatus, ['final', 'suspend'])) {
                    $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE, $newStatus);
                } else {
                    $this->simulateBatchSelection($productData, $sale->id, $request->location_id, $newStatus);
                }
            }
        }

        // *** CRITICAL FIX: Always recalculate customer balance after sale edits ***
        if ($isUpdate && $request->customer_id != 1) {
            $this->recalculateCustomerBalance($request->customer_id);
        }

        // Clear product cache after stock changes to prevent stale data
        Cache::forget('all_products');
        foreach ($productIds as $productId) {
            Cache::forget("product_stock_{$productId}");
            Cache::forget("product_batches_{$productId}");
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers — moved verbatim from SaleController
    // -------------------------------------------------------------------------

    private function processProductSale($productData, $saleId, $locationId, $stockType, $newStatus)
    {
        // CRITICAL FIX: Log product sale processing for debugging
        $freeQuantity = floatval($productData['free_quantity'] ?? 0);
        // Total quantity to deduct from inventory = paid + free
        $totalQuantity = $productData['quantity'] + $freeQuantity;
        $remainingQuantity = $totalQuantity;

        // We'll store info about each batch deduction
        $batchDeductions = [];

        if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
            $batch = Batch::findOrFail($productData['batch_id']);
            $locationBatch = LocationBatch::where('batch_id', $batch->id)
                ->where('location_id', $locationId)
                ->firstOrFail();

            // Check both paid and free stock separately
            $availablePaidStock = $locationBatch->qty ?? 0;
            $availableFreeStock = $locationBatch->free_qty ?? 0;

            if ($availablePaidStock < $productData['quantity']) {
                throw new \Exception("Batch ID {$productData['batch_id']} does not have enough paid stock. Available: {$availablePaidStock}, Requested: {$productData['quantity']}");
            }

            if ($availableFreeStock < $freeQuantity) {
                throw new \Exception("Batch ID {$productData['batch_id']} does not have enough free stock. Available: {$availableFreeStock}, Requested: {$freeQuantity}");
            }

            $this->deductBatchStock($productData['batch_id'], $locationId, $totalQuantity, $stockType, $productData['quantity'], $freeQuantity);
            $batchDeductions[] = [
                'batch_id' => $batch->id,
                'quantity' => $remainingQuantity,
                'paid_qty' => $productData['quantity'],
                'free_qty' => $freeQuantity
            ];
        } else {

            // Track remaining paid and free quantities separately
            $remainingPaidQty = $productData['quantity'];
            $remainingFreeQty = $freeQuantity;

            // FIFO batch selection — lockForUpdate prevents concurrent stock races
            $batches = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productData['product_id'])
                ->where('location_batches.location_id', $locationId)
                ->where(function ($q) {
                    $q->where('location_batches.qty', '>', 0)
                      ->orWhere('location_batches.free_qty', '>', 0);
                })
                ->orderBy('batches.created_at')
                ->select('location_batches.batch_id', 'location_batches.qty', 'location_batches.free_qty', 'location_batches.id as loc_batch_id')
                ->lockForUpdate()
                ->get();


            foreach ($batches as $batch) {
                if ($remainingPaidQty <= 0 && $remainingFreeQty <= 0) break;

                // Deduct paid quantity from this batch (FIFO)
                $deductPaidQty = min($batch->qty ?? 0, $remainingPaidQty);
                // Deduct free quantity from this batch (FIFO) - Try free stock first
                $deductFreeQty = min($batch->free_qty ?? 0, $remainingFreeQty);
                $deductTotalQty = $deductPaidQty + $deductFreeQty;

                if ($deductTotalQty <= 0) continue; // Skip batches with no stock to deduct


                $this->deductBatchStock($batch->batch_id, $locationId, $deductTotalQty, $stockType, $deductPaidQty, $deductFreeQty);
                $batchDeductions[] = [
                    'batch_id' => $batch->batch_id,
                    'quantity' => $deductTotalQty,
                    'paid_qty' => $deductPaidQty,
                    'free_qty' => $deductFreeQty
                ];

                $remainingPaidQty -= $deductPaidQty;
                $remainingFreeQty -= $deductFreeQty;
            }

            // Fallback: if free stock ran out, use paid stock to cover the shortage
            if ($remainingFreeQty > 0 && in_array($newStatus, ['final', 'suspend'])) {
                foreach ($batches as $batch) {
                    if ($remainingFreeQty <= 0) break;

                    // Check if this batch has available paid stock (after previous deductions)
                    $batchRemainingPaid = $batch->qty ?? 0;
                    // Subtract what we already deducted in the first loop
                    foreach ($batchDeductions as &$deduction) {
                        if ($deduction['batch_id'] === $batch->batch_id) {
                            $batchRemainingPaid -= $deduction['paid_qty'];
                        }
                    }
                    unset($deduction); // FIX: remove dangling reference to prevent last array element corruption

                    if ($batchRemainingPaid <= 0) continue;

                    // Deduct from paid stock to cover free quantity shortage
                    $deductFromPaidForFree = min($batchRemainingPaid, $remainingFreeQty);

                    $this->deductBatchStock($batch->batch_id, $locationId, $deductFromPaidForFree, $stockType, $deductFromPaidForFree, 0);

                    // Update or add to batch deductions
                    $existingIndex = array_search($batch->batch_id, array_column($batchDeductions, 'batch_id'));
                    if ($existingIndex !== false) {
                        $batchDeductions[$existingIndex]['paid_qty'] += $deductFromPaidForFree;
                        $batchDeductions[$existingIndex]['quantity'] += $deductFromPaidForFree;
                    } else {
                        $batchDeductions[] = [
                            'batch_id' => $batch->batch_id,
                            'quantity' => $deductFromPaidForFree,
                            'paid_qty' => $deductFromPaidForFree,
                            'free_qty' => 0
                        ];
                    }

                    $remainingFreeQty -= $deductFromPaidForFree;
                }
            }

            // Only validate stock if the sale status is final/suspend
            if (in_array($newStatus, ['final', 'suspend'])) {
                // Check total shortage (paid + remaining free that couldn't be covered)
                $totalShortage = $remainingPaidQty + $remainingFreeQty;

                if ($totalShortage > 0) {
                    Log::error("❌ INSUFFICIENT TOTAL STOCK across all batches", [
                        'product_id' => $productData['product_id'],
                        'location_id' => $locationId,
                        'requested_paid' => $productData['quantity'],
                        'requested_free' => $freeQuantity,
                        'unfulfilled_paid' => $remainingPaidQty,
                        'unfulfilled_free' => $remainingFreeQty,
                        'total_shortage' => $totalShortage,
                        'batches_checked' => $batches->count()
                    ]);
                    throw new \Exception("Not enough stock across all batches to fulfill the sale. Product ID: {$productData['product_id']}, Total Required: " . ($productData['quantity'] + $freeQuantity) . ", Short: {$totalShortage}");
                }
            }
        }

        // Loop through batch deductions
        foreach ($batchDeductions as $deduction) {
            // Create sales_product record for this batch
            // Note: subtotal = quantity × price (calculated, not stored)
            // FIX: Use the already-correct paid_qty and free_qty from the FIFO deduction loop
            // instead of recalculating proportionally (which caused fractional qty splits).
            $proportionalFreeQty = $deduction['free_qty'];
            $paidQtyForBatch = $deduction['paid_qty'];

            $saleProduct = SalesProduct::create([
                'sale_id' => $saleId,
                'product_id' => $productData['product_id'],
                'custom_name' => $productData['custom_name'] ?? null,
                'quantity' => $paidQtyForBatch,
                'free_quantity' => $proportionalFreeQty,
                'price' => $productData['unit_price'], // price column stores unit price
                'batch_id' => $deduction['batch_id'],
                'location_id' => $locationId,
                'price_type' => $productData['price_type'],
                'discount_amount' => $productData['discount_amount'] ?? 0,
                'discount_type' => $productData['discount_type'] ?? 'fixed',
                'tax' => $productData['tax'] ?? 0,
            ]);

            // PERFORMANCE FIX: Optimized IMEI processing
            if (!empty($productData['imei_numbers']) && is_array($productData['imei_numbers'])) {
                $requiredImeiCount = min(count($productData['imei_numbers']), $deduction['quantity']);
                $imeiNumbers = array_slice($productData['imei_numbers'], 0, $requiredImeiCount);

                if (!empty($imeiNumbers)) {
                    // Single batch update for IMEI status
                    ImeiNumber::whereIn('imei_number', $imeiNumbers)
                        ->where('product_id', $productData['product_id'])
                        ->where('batch_id', $deduction['batch_id'])
                        ->where('location_id', $locationId)
                        ->update(['status' => 'sold']);

                    // Prepare batch insert data
                    $saleImeiInserts = array_map(function($imei) use ($saleId, $saleProduct, $productData, $deduction, $locationId) {
                        return [
                            'sale_id' => $saleId,
                            'sale_product_id' => $saleProduct->id,
                            'product_id' => $productData['product_id'],
                            'batch_id' => $deduction['batch_id'],
                            'location_id' => $locationId,
                            'imei_number' => $imei,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $imeiNumbers);

                    // Single batch insert for sale IMEIs
                    SaleImei::insert($saleImeiInserts);
                }
            }
        }
    }

    private function deductBatchStock($batchId, $locationId, $quantity, $stockType, $paidQty = null, $freeQty = null)
    {
        if ($paidQty === null && $freeQty === null) {
            $paidQty = $quantity;
            $freeQty = 0;
        }

        // Row-level lock prevents concurrent stock races (already inside outer DB::transaction)
        $locationBatch = DB::table('location_batches')
            ->where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if (!$locationBatch) {
            throw new \Exception("Batch ID $batchId not found at location $locationId");
        }

        $currentPaidStock = round((float) $locationBatch->qty, 4);
        $currentFreeStock = round((float) ($locationBatch->free_qty ?? 0), 4);
        $requestedPaidQty = round((float) $paidQty, 4);
        $requestedFreeQty = round((float) $freeQty, 4);

        // Float tolerance of 0.0001 guards against rounding drift
        if (($currentPaidStock + 0.0001) < $requestedPaidQty) {
            Log::error('Insufficient paid stock', [
                'batch_id' => $batchId, 'location_id' => $locationId,
                'available' => $currentPaidStock, 'requested' => $requestedPaidQty,
            ]);
            throw new \Exception("Insufficient paid stock in batch ID $batchId at location $locationId. Available: $currentPaidStock, Requested: $requestedPaidQty");
        }

        if (($currentFreeStock + 0.0001) < $requestedFreeQty) {
            Log::error('Insufficient free stock', [
                'batch_id' => $batchId, 'location_id' => $locationId,
                'available' => $currentFreeStock, 'requested' => $requestedFreeQty,
            ]);
            throw new \Exception("Insufficient free stock in batch ID $batchId at location $locationId. Available: $currentFreeStock, Requested: $requestedFreeQty");
        }

        $affected = DB::table('location_batches')
            ->where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->update([
                'qty'      => DB::raw("qty - $requestedPaidQty"),
                'free_qty' => DB::raw("free_qty - $requestedFreeQty"),
            ]);

        if ($affected === 0) {
            throw new \Exception("Failed to update stock for batch ID $batchId at location $locationId");
        }

        if ($locationBatch) {
            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity'     => -$quantity,
                'stock_type'   => $stockType,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        return $locationBatch;
    }

    private function processUnlimitedStockProductSale($productData, $saleId, $locationId, $stockType)
    {
        // Record the sales product for unlimited stock product
        // Note: subtotal = quantity * price (calculated, not stored)
        SalesProduct::create([
            'sale_id' => $saleId,
            'product_id' => $productData['product_id'],
            'custom_name' => $productData['custom_name'] ?? null,
            'quantity' => $productData['quantity'],
            'price' => $productData['unit_price'], // price column stores unit price
            'batch_id' => null,
            'location_id' => $locationId,
            'price_type' => $productData['price_type'],
            'discount_amount' => $productData['discount_amount'] ?? 0,
            'discount_type' => $productData['discount_type'] ?? 'fixed',
            'tax' => $productData['tax'] ?? 0,
        ]);

        // Add stock history for unlimited stock product (for reporting purposes only)
        StockHistory::create([
            'loc_batch_id' => null,
            'quantity' => -$productData['quantity'],
            'stock_type' => $stockType,
        ]);
    }

    private function simulateBatchSelection($productData, $saleId, $locationId, $newStatus)
    {
        $totalQuantity = $productData['quantity'];
        $remainingQuantity = $totalQuantity;

        $batchDeductions = [];

        if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
            $batch = Batch::findOrFail($productData['batch_id']);
            $locationBatch = LocationBatch::where('batch_id', $batch->id)
                ->where('location_id', $locationId)
                ->first();

            // For draft/quotation, allow any quantity, even if it exceeds stock
            if (in_array($newStatus, ['draft', 'quotation', 'jobticket'])) {
                $batchDeductions[] = [
                    'batch_id' => $batch->id,
                    'quantity' => $remainingQuantity,
                ];
            }
            else {
                // Only check stock for final/suspend status
                if ($locationBatch && $locationBatch->qty >= $remainingQuantity) {
                    $batchDeductions[] = [
                        'batch_id' => $batch->id,
                        'quantity' => $remainingQuantity
                    ];
                } else {
                    throw new \Exception("Not enough stock in selected batch.");
                }
            }
        } else {
            // For "all" batches, allow any quantity for draft/quotation
            if (in_array($newStatus, ['draft', 'quotation', 'jobticket'])) {
                // Just assign all to a pseudo batch (or null)
                $batchDeductions[] = [
                    'batch_id' => null,
                    'quantity' => $remainingQuantity
                ];
            } else {
                $batches = DB::table('location_batches')
                    ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                    ->where('batches.product_id', $productData['product_id'])
                    ->where('location_batches.location_id', $locationId)
                    ->where('location_batches.qty', '>', 0)
                    ->orderBy('batches.created_at')
                    ->select('location_batches.batch_id', 'location_batches.qty')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingQuantity <= 0) break;
                    $deductQuantity = min($batch->qty, $remainingQuantity);
                    $batchDeductions[] = [
                        'batch_id' => $batch->batch_id,
                        'quantity' => $deductQuantity
                    ];
                    $remainingQuantity -= $deductQuantity;
                }

                // Only validate stock if the sale status is final/suspend
                if (in_array($newStatus, ['final', 'suspend'])) {
                    if ($remainingQuantity > 0) {
                        throw new \Exception("Not enough stock across all batches to fulfill the sale.");
                    }
                }
            }
        }

        foreach ($batchDeductions as $deduction) {
            SalesProduct::create([
                'sale_id' => $saleId,
                'product_id' => $productData['product_id'],
                'custom_name' => $productData['custom_name'] ?? null,
                'quantity' => $deduction['quantity'],
                'price' => $productData['unit_price'],
                'batch_id' => $deduction['batch_id'],
                'location_id' => $locationId,
                'price_type' => $productData['price_type'],
                'discount_amount' => $productData['discount_amount'] ?? 0,
                'discount_type' => $productData['discount_type'] ?? 'fixed',
                'tax' => $productData['tax'] ?? 0,
            ]);
        }
    }

    public function restoreStock($product, $stockType)
    {
        $paidQuantity = floatval($product->quantity ?? 0);
        $freeQuantity = floatval($product->free_quantity ?? 0);
        $totalQuantityToRestore = $paidQuantity + $freeQuantity;

        // Unlimited stock products have no batch — skip stock update
        if (is_null($product->batch_id)) {
            return;
        }

        // FIX: Restore paid and free quantities separately
        $affected = DB::table('location_batches')
            ->where('batch_id', $product->batch_id)
            ->where('location_id', $product->location_id)
            ->update([
                'qty' => DB::raw("qty + {$paidQuantity}"),
                'free_qty' => DB::raw("free_qty + {$freeQuantity}")
            ]);

        if ($affected > 0) {
            $locationBatch = LocationBatch::where('batch_id', $product->batch_id)
                ->where('location_id', $product->location_id)
                ->first();

            if ($locationBatch) {
                StockHistory::create([
                    'loc_batch_id' => $locationBatch->id,
                    'quantity'     => $totalQuantityToRestore,
                    'stock_type'   => $stockType,
                ]);
            } else {
                Log::warning('LocationBatch not found after successful stock update', [
                    'batch_id'    => $product->batch_id,
                    'location_id' => $product->location_id,
                ]);
            }
        } else {
            Log::error('Failed to restore stock — no rows affected', [
                'batch_id'    => $product->batch_id,
                'location_id' => $product->location_id,
                'paid_qty'    => $paidQuantity,
                'free_qty'    => $freeQuantity,
            ]);
        }

        if ($stockType === StockHistory::STOCK_TYPE_SALE_REVERSAL) {
            $this->restoreImeiNumbers($product);
        }
    }

    private function restoreImeiNumbers($salesProduct)
    {
        $saleImeis = SaleImei::where('sale_product_id', $salesProduct->id)->get();

        if ($saleImeis->isNotEmpty()) {
            // Batch update IMEI statuses
            $imeiNumbers = $saleImeis->pluck('imei_number')->toArray();

            $updated = ImeiNumber::whereIn('imei_number', $imeiNumbers)
                ->where('product_id', $salesProduct->product_id)
                ->where('batch_id', $salesProduct->batch_id)
                ->where('location_id', $salesProduct->location_id)
                ->update(['status' => 'available']);

            SaleImei::where('sale_product_id', $salesProduct->id)->delete();
        }
    }

    private function recalculateCustomerBalance($customerId)
    {
        try {
            // Calculate total outstanding dues from all final sales
            $totalDue = Sale::withoutGlobalScopes()
                ->where('customer_id', $customerId)
                ->where('status', 'final')
                ->sum('total_due');

            // Update customer balance
            Customer::withoutGlobalScopes()
                ->where('id', $customerId)
                ->update(['current_balance' => $totalDue]);

        } catch (\Exception $e) {
            Log::error('Failed to recalculate customer balance', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception to avoid blocking sale edit, just log the error
        }
    }
}
