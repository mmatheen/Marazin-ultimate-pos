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
use App\Services\Inventory\BackorderService;
use App\Services\PosVatCalculatorService;
use App\Services\TaxConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleProductProcessor
{
    private const EPSILON = 0.0001;

    protected $saleValidationService;
    protected $backorderService;

    public function __construct(SaleValidationService $saleValidationService, BackorderService $backorderService)
    {
        $this->saleValidationService = $saleValidationService;
        $this->backorderService = $backorderService;
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
        $saleLock = Cache::lock("sale_process_{$sale->id}", 30);
        if (!$saleLock->get()) {
            throw new \RuntimeException("Sale {$sale->id} is already being processed. Please retry.");
        }

        try {
            DB::transaction(function () use ($sale, $request, $isUpdate, $oldStatus, $newStatus, $transactionType): void {

            // ----- Products Logic (allow multiple for jobticket) -----
            $originalProducts = [];
            if ($isUpdate) {
                $this->restoreAndDeleteExistingSaleProducts($sale, $oldStatus, $originalProducts);
            }

            // Batch load all products to avoid N+1 queries
            // Don't use cached data for stock-critical operations
            $productIds = collect($request->products)->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)
                ->select('id', 'product_name', 'sku', 'stock_alert', 'unit_id', 'original_price', 'tax_percent', 'selling_price_tax_type', 'is_imei_or_serial_no')
                ->with('unit:id,allow_decimal')
                ->get()->keyBy('id');

            foreach ($request->products as $productData) {
                $product = $products[$productData['product_id']] ?? null;
                if (!$product) {
                    throw new \Exception("Product ID {$productData['product_id']} not found");
                }

            $paidQtyInput = $this->f($productData['quantity'] ?? 0);
            $freeQtyInput = $this->f($productData['free_quantity'] ?? 0);
            if ($paidQtyInput < 0 || $freeQtyInput < 0 || $this->f($paidQtyInput + $freeQtyInput) <= 0) {
                throw new \Exception("Invalid quantity for product ID {$productData['product_id']}. Paid and free quantities must be non-negative, and total must be greater than 0.");
            }

            if (!isset($productData['unit_price']) || !is_numeric($productData['unit_price']) || (float) $productData['unit_price'] < 0) {
                throw new \Exception("Invalid unit price for product ID {$productData['product_id']}");
            }

            $productData['tax_percent'] = TaxConfigurationService::resolveTaxPercent(
                $productData['tax_percent'] ?? null,
                $product->tax_percent ?? null
            );
            $productData['selling_price_tax_type'] = TaxConfigurationService::resolveSellingPriceTaxType(
                $product->selling_price_tax_type ?? null,
                $productData['selling_price_tax_type'] ?? null
            );
            // *** CRITICAL SECURITY FIX: Validate price integrity during edit mode ***
            if ($isUpdate) {
                $this->saleValidationService->validateEditModePrice($productData, $sale);
            }

            // VAT/profit snapshot must be calculated from the final validated unit price.
            $saleVatMetrics = PosVatCalculatorService::forSale(
                (float) ($productData['unit_price'] ?? 0),
                (float) $productData['tax_percent'],
                (float) ($product->original_price ?? 0),
                (float) ($productData['quantity'] ?? 0),
                (string) $productData['selling_price_tax_type']
            );
            $productData['sale_vat_per_unit'] = $saleVatMetrics['vat_per_unit'];
            $productData['sale_excl_vat_per_unit'] = $saleVatMetrics['sale_excl_vat_per_unit'];
            $productData['sale_profit_per_unit'] = $saleVatMetrics['profit_per_unit'];

                // Unlimited-stock products: only post stock history for committed stock-impacting statuses.
                if ($product->stock_alert === 0) {
                    if ($transactionType === 'sale_order') {
                        $this->processUnlimitedStockProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE_ORDER);
                    } elseif (in_array($newStatus, ['final', 'suspend'])) {
                        $this->processUnlimitedStockProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE);
                    } else {
                        $this->simulateBatchSelection($productData, $sale->id, $request->location_id, $newStatus);
                    }
                } else {
                // For updates, check stock availability considering the original sale quantities
                if ($isUpdate && in_array($newStatus, ['final', 'suspend'])) {
                    $this->saleValidationService->validateStockForUpdate($productData, $request->location_id, $originalProducts);
                }

                // For Sale Orders: Deduct stock but use special stock type for allocation tracking
                if ($transactionType === 'sale_order') {
                    $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE_ORDER, 'sale_order', $product);
                }
                // Always process sale for final/suspend status
                elseif (in_array($newStatus, ['final', 'suspend'])) {
                    $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE, $newStatus, $product);
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

            }, 3);
        } catch (\Throwable $e) {
            Cache::put("sale_process_failed_{$sale->id}", [
                'error' => $e->getMessage(),
                'at' => now()->toDateTimeString(),
            ], now()->addHours(12));
            throw $e;
        } finally {
            $saleLock->release();
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers — moved verbatim from SaleController
    // -------------------------------------------------------------------------

    private function processProductSale($productData, $saleId, $locationId, $stockType, $newStatus, ?Product $product = null)
    {
        $freeQuantity = $this->f($productData['free_quantity'] ?? 0);
        $allocationResult = $this->allocateStock($productData, $saleId, $locationId, $stockType, $newStatus, $freeQuantity, $product);
        $batchDeductions = $allocationResult['deductions'];
        $shortagePaidQty = $this->f($allocationResult['shortage_paid'] ?? 0);
        $shortageFreeQty = $this->f($allocationResult['shortage_free'] ?? 0);

        $remainingImeis = $this->normalizeImeiNumbers($productData['imei_numbers'] ?? []);
        foreach ($batchDeductions as $deduction) {
            $saleProduct = $this->createSalesProduct($saleId, $locationId, $productData, $deduction);
            $this->handleImeis($saleId, $locationId, $productData, $deduction, $saleProduct, $remainingImeis, $product);
        }

        if ($shortagePaidQty > 0 || $shortageFreeQty > 0) {
            $backorderLine = [
                'batch_id' => null,
                'quantity' => $shortagePaidQty,
                'paid_qty' => $shortagePaidQty,
                'free_qty' => $shortageFreeQty,
                'fulfilled_paid_qty' => 0,
                'fulfilled_free_qty' => 0,
                'backordered_paid_qty' => $shortagePaidQty,
                'backordered_free_qty' => $shortageFreeQty,
                'fulfillment_status' => 'pending',
            ];

            $saleProduct = $this->createSalesProduct($saleId, $locationId, $productData, $backorderLine);

            $this->backorderService->recordShortage(
                (int) $saleProduct->id,
                (int) $locationId,
                $shortagePaidQty,
                $shortageFreeQty
            );
        }
    }

    private function allocateStock($productData, $saleId, $locationId, $stockType, $newStatus, $freeQuantity, ?Product $product = null): array
    {
        $totalQuantity = $this->f(($productData['quantity'] ?? 0) + $freeQuantity);
        $batchDeductions = [];
        $shortagePaidQty = 0;
        $shortageFreeQty = 0;
        $backorderAllowed = $this->backorderService->canBackorderProduct($product);
        // Business rule: only sale_order can create shortage backorders.
        $allowsShortageBackorder = ($newStatus === 'sale_order');

        if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
            $selectedBatchId = (int) $productData['batch_id'];
            $locationBatch = LocationBatch::where('batch_id', $selectedBatchId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->firstOrFail();
            $batch = Batch::whereKey($selectedBatchId)->lockForUpdate()->firstOrFail();

            $availablePaidStock = $this->f($locationBatch->qty ?? 0);
            $availableFreeStock = $this->f($locationBatch->free_qty ?? 0);
            $totalAvailable = $this->f($availablePaidStock + $availableFreeStock);
            $requiredPaid = $this->f($productData['quantity']);
            $requiredFree = $this->f($freeQuantity);

            // Allow cross-pool usage for sellability:
            // paid qty can fallback to free pool, free qty can fallback to paid pool.
            if ($totalAvailable < $this->f($requiredPaid + $requiredFree)) {
                if ($backorderAllowed && $allowsShortageBackorder) {
                    $remainingPaidQty = $requiredPaid;
                    $remainingFreeQty = $requiredFree;

                    $paidFromPaid = min($availablePaidStock, $remainingPaidQty);
                    $remainingPaidQty = $this->f($remainingPaidQty - $paidFromPaid);

                    $paidFromFree = min($availableFreeStock, $remainingPaidQty);
                    $remainingPaidQty = $this->f($remainingPaidQty - $paidFromFree);

                    $remainingFreePool = $this->f(max(0, $availableFreeStock - $paidFromFree));
                    $freeFromFree = min($remainingFreePool, $remainingFreeQty);
                    $remainingFreeQty = $this->f($remainingFreeQty - $freeFromFree);

                    $remainingPaidPool = $this->f(max(0, $availablePaidStock - $paidFromPaid));
                    $freeFromPaid = min($remainingPaidPool, $remainingFreeQty);
                    $remainingFreeQty = $this->f($remainingFreeQty - $freeFromPaid);

                    $paidUsedQty = $this->f($requiredPaid - $remainingPaidQty);
                    $freeUsedQty = $this->f($requiredFree - $remainingFreeQty);

                    if ($paidUsedQty > self::EPSILON || $freeUsedQty > self::EPSILON) {
                        $this->deductBatchStock(
                            $productData['batch_id'],
                            $locationId,
                            $this->f($paidUsedQty + $freeUsedQty),
                            $stockType,
                            $paidUsedQty,
                            $freeUsedQty
                        );

                        $batchDeductions[] = [
                            'batch_id' => $batch->id,
                            'quantity' => $paidUsedQty,
                            'paid_qty' => $paidUsedQty,
                            'free_qty' => $freeUsedQty,
                        ];
                    }

                    return [
                        'deductions' => $batchDeductions,
                        'shortage_paid' => $remainingPaidQty,
                        'shortage_free' => $remainingFreeQty,
                    ];
                }

                $productLabel = $product
                    ? trim(($product->product_name ?? 'Unknown Product') . ($product->sku ? " (SKU: {$product->sku})" : ''))
                    : "Product ID {$productData['product_id']}";
                $batchLabel = trim(($batch->batch_no ? "Batch: {$batch->batch_no}" : 'Batch') . " (ID: {$productData['batch_id']})");

                throw new \Exception("{$productLabel} — {$batchLabel} does not have enough stock. Paid available: {$availablePaidStock}, Paid requested: {$requiredPaid}, Total available: {$totalAvailable}, Total requested: " . $this->f($requiredPaid + $requiredFree));
            }

            $this->deductBatchStock($productData['batch_id'], $locationId, $totalQuantity, $stockType, $productData['quantity'], $freeQuantity);
            $paidQty = $this->f($productData['quantity']);
            $batchDeductions[] = [
                'batch_id' => $batch->id,
                'quantity' => $paidQty,
                'paid_qty' => $paidQty,
                'free_qty' => $freeQuantity,
            ];

            return [
                'deductions' => $batchDeductions,
                'shortage_paid' => 0,
                'shortage_free' => 0,
            ];
        }

        $remainingPaidQty = $this->f($productData['quantity']);
        $remainingFreeQty = $freeQuantity;

        // Enforce lock order: location_batches -> batches.
        $locationBatches = DB::table('location_batches')
            ->where('location_batches.location_id', $locationId)
            ->whereIn('location_batches.batch_id', function ($q) use ($productData) {
                $q->select('id')
                    ->from('batches')
                    ->where('product_id', $productData['product_id']);
            })
            ->where(function ($q) {
                $q->where('location_batches.qty', '>', 0)
                  ->orWhere('location_batches.free_qty', '>', 0);
            })
            ->orderBy('location_batches.batch_id')
            ->select('location_batches.batch_id', 'location_batches.qty', 'location_batches.free_qty', 'location_batches.id as loc_batch_id')
            ->lockForUpdate()
            ->get();

        $batchIds = $locationBatches->pluck('batch_id')->unique()->values();
        $batchMeta = Batch::whereIn('id', $batchIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'created_at'])
            ->keyBy('id');

        $batches = $locationBatches
            ->sortBy(function ($row) use ($batchMeta) {
                $createdAt = optional($batchMeta->get($row->batch_id))->created_at;
                return [
                    $createdAt ? $createdAt->getTimestamp() : PHP_INT_MAX,
                    (int) $row->batch_id,
                ];
            })
            ->values();

        foreach ($batches as $batch) {
            if ($remainingPaidQty <= 0 && $remainingFreeQty <= 0) break;

            $availablePaid = $this->f($batch->qty ?? 0);
            $availableFree = $this->f($batch->free_qty ?? 0);

            $paidFromPaid = min($availablePaid, $remainingPaidQty);
            $remainingPaidQty = $this->f($remainingPaidQty - $paidFromPaid);

            // Paid sale qty fallback from free pool (business requirement).
            $paidFromFree = min($availableFree, $remainingPaidQty);
            $remainingPaidQty = $this->f($remainingPaidQty - $paidFromFree);

            $remainingFreePool = $this->f(max(0, $availableFree - $paidFromFree));
            $freeFromFree = min($remainingFreePool, $remainingFreeQty);
            $remainingFreeQty = $this->f($remainingFreeQty - $freeFromFree);

            // Free-qty fallback: use remaining paid pool only for the free request shortfall.
            $remainingPaidPool = $this->f(max(0, $availablePaid - $paidFromPaid));
            $freeFromPaid = min($remainingPaidPool, $remainingFreeQty);
            $remainingFreeQty = $this->f($remainingFreeQty - $freeFromPaid);

            $paidUsedQty = $this->f($paidFromPaid + $paidFromFree);
            $freeUsedQty = $this->f($freeFromFree + $freeFromPaid);
            $deductTotalQty = $this->f($paidUsedQty + $freeUsedQty);

            if ($deductTotalQty <= self::EPSILON) continue;

            Log::info('Batch Deduction', [
                'batch_id' => $batch->batch_id,
                'paid_used' => $paidUsedQty,
                'free_used' => $freeUsedQty,
            ]);

            $this->deductBatchStock(
                $batch->batch_id,
                $locationId,
                $deductTotalQty,
                $stockType,
                $paidUsedQty,
                $freeUsedQty
            );

            $batchDeductions[] = [
                'batch_id' => $batch->batch_id,
                'quantity' => $paidUsedQty,
                'paid_qty' => $paidUsedQty,
                'free_qty' => $freeUsedQty,
            ];
        }

        $totalShortage = $this->f($remainingPaidQty + $remainingFreeQty);
        if ($totalShortage > 0) {
            if (!$allowsShortageBackorder) {
                $productLabel = $product
                    ? trim(($product->product_name ?? 'Unknown Product') . ($product->sku ? " (SKU: {$product->sku})" : ''))
                    : "Product ID {$productData['product_id']}";

                throw new \Exception(
                    "{$productLabel} does not have enough stock across all batches. " .
                    "Requested: " . $this->f($productData['quantity'] + $freeQuantity) . ", " .
                    "Short: {$totalShortage}"
                );
            }

            if ($allowsShortageBackorder) {
                Log::error("❌ INSUFFICIENT TOTAL STOCK across all batches", [
                    'sale_id' => $saleId,
                    'product_id' => $productData['product_id'],
                    'location_id' => $locationId,
                    'requested_paid' => $productData['quantity'],
                    'requested_free' => $freeQuantity,
                    'unfulfilled_paid' => $remainingPaidQty,
                    'unfulfilled_free' => $remainingFreeQty,
                    'total_shortage' => $totalShortage,
                    'batches_checked' => $batches->count()
                ]);

                if (!$backorderAllowed) {
                    throw new \Exception("Not enough stock across all batches to fulfill the sale. Product ID: {$productData['product_id']}, Total Required: " . ($productData['quantity'] + $freeQuantity) . ", Short: {$totalShortage}");
                }

                $shortagePaidQty = $remainingPaidQty;
                $shortageFreeQty = $remainingFreeQty;
            }
        }

        return [
            'deductions' => $batchDeductions,
            'shortage_paid' => $shortagePaidQty,
            'shortage_free' => $shortageFreeQty,
        ];
    }

    private function createSalesProduct($saleId, $locationId, $productData, $deduction): SalesProduct
    {
        $freeQtyForBatch = $deduction['free_qty'];
        $paidQtyForBatch = $deduction['paid_qty'];
        $fulfilledPaidQty = $this->f($deduction['fulfilled_paid_qty'] ?? $paidQtyForBatch);
        $fulfilledFreeQty = $this->f($deduction['fulfilled_free_qty'] ?? $freeQtyForBatch);
        $backorderedPaidQty = $this->f($deduction['backordered_paid_qty'] ?? 0);
        $backorderedFreeQty = $this->f($deduction['backordered_free_qty'] ?? 0);
        $fulfillmentStatus = $deduction['fulfillment_status'] ?? (($backorderedPaidQty > 0 || $backorderedFreeQty > 0) ? 'partial' : 'fulfilled');

        return SalesProduct::create([
            'sale_id' => $saleId,
            'product_id' => $productData['product_id'],
            'custom_name' => $productData['custom_name'] ?? null,
            'quantity' => $paidQtyForBatch,
            'free_quantity' => $freeQtyForBatch,
            'price' => $productData['unit_price'],
            'batch_id' => $deduction['batch_id'],
            'location_id' => $locationId,
            'price_type' => $productData['price_type'],
            'discount_amount' => $productData['discount_amount'] ?? 0,
            'discount_type' => $productData['discount_type'] ?? 'fixed',
            'tax' => $productData['tax'] ?? 0,
            'tax_percent' => $productData['tax_percent'] ?? 0,
            'fulfilled_quantity' => $fulfilledPaidQty,
            'fulfilled_free_quantity' => $fulfilledFreeQty,
            'backordered_quantity' => $backorderedPaidQty,
            'backordered_free_quantity' => $backorderedFreeQty,
            'fulfillment_status' => $fulfillmentStatus,
            'vat_per_unit' => $productData['sale_vat_per_unit'] ?? 0,
            'vat_total' => round(($productData['sale_vat_per_unit'] ?? 0) * $paidQtyForBatch, 2),
            'sale_excl_vat_per_unit' => $productData['sale_excl_vat_per_unit'] ?? 0,
            'profit_per_unit' => $productData['sale_profit_per_unit'] ?? 0,
            'profit_total' => round(($productData['sale_profit_per_unit'] ?? 0) * $paidQtyForBatch, 2),
        ]);
    }

    private function handleImeis($saleId, $locationId, $productData, $deduction, SalesProduct $saleProduct, array &$remainingImeis, ?Product $product = null): void
    {
        $isImeiEnabled = (bool) ($product?->is_imei_or_serial_no ?? false);
        if (!$isImeiEnabled) {
            return;
        }

        $requiredPaidImeiCount = (int) floor((float) ($deduction['paid_qty'] ?? 0));
        if ($requiredPaidImeiCount <= 0) {
            return;
        }

        if (empty($remainingImeis)) {
            throw new \Exception(
                "IMEI numbers are required for product ID {$productData['product_id']} (batch {$deduction['batch_id']}). " .
                "Required: {$requiredPaidImeiCount}, Provided: 0."
            );
        }

        $imeiNumbers = $this->reserveImeisForBatch(
            $remainingImeis,
            $requiredPaidImeiCount,
            (int) $productData['product_id'],
            (int) $deduction['batch_id'],
            (int) $locationId
        );

        if (count($imeiNumbers) < $requiredPaidImeiCount) {
            throw new \Exception(
                "IMEI count mismatch for product ID {$productData['product_id']} in batch {$deduction['batch_id']}. " .
                "Required: {$requiredPaidImeiCount}, Provided batch-matching: " . count($imeiNumbers) . "."
            );
        }

        if (empty($imeiNumbers)) {
            return;
        }

        $updatedRows = ImeiNumber::whereIn('imei_number', $imeiNumbers)
            ->where('product_id', $productData['product_id'])
            ->where('batch_id', $deduction['batch_id'])
            ->where('location_id', $locationId)
            ->where('status', 'available')
            ->update(['status' => 'sold']);

        if ($updatedRows !== count($imeiNumbers)) {
            throw new \Exception(
                "Failed to mark IMEIs as sold for product ID {$productData['product_id']} in batch {$deduction['batch_id']}. " .
                "Expected to update " . count($imeiNumbers) . ", updated {$updatedRows}."
            );
        }

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

        SaleImei::insert($saleImeiInserts);
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
            $batch = \App\Models\Batch::select(['id', 'batch_no', 'product_id'])->find($batchId);
            $product = $batch
                ? \App\Models\Product::select(['id', 'product_name', 'sku'])->find($batch->product_id)
                : null;

            $productLabel = $product
                ? trim(($product->product_name ?? 'Unknown Product') . ($product->sku ? " (SKU: {$product->sku})" : ''))
                : ($batch ? "Product ID {$batch->product_id}" : 'Unknown Product');

            $batchLabel = $batch
                ? trim(($batch->batch_no ? "Batch: {$batch->batch_no}" : 'Batch') . " (ID: {$batchId})")
                : "Batch ID {$batchId}";

            throw new \Exception("{$productLabel} — {$batchLabel} not found at location {$locationId}");
        }

        $currentPaidInt = $this->qInt($locationBatch->qty);
        $currentFreeInt = $this->qInt($locationBatch->free_qty ?? 0);
        $requestedPaidInt = $this->qInt($paidQty);
        $requestedFreeInt = $this->qInt($freeQty);

        // Allow bidirectional controlled fallback to maximize sellability:
        // 1) paid qty: paid pool then free pool
        // 2) free qty: remaining free pool then remaining paid pool
        $paidFromPaidPoolInt = min($currentPaidInt, $requestedPaidInt);
        $remainingPaidRequestInt = $requestedPaidInt - $paidFromPaidPoolInt;

        $paidFromFreePoolInt = min($currentFreeInt, $remainingPaidRequestInt);
        $unfulfilledPaidInt = $remainingPaidRequestInt - $paidFromFreePoolInt;

        $remainingFreePoolAfterPaidInt = max(0, $currentFreeInt - $paidFromFreePoolInt);
        $freeFromFreePoolInt = min($remainingFreePoolAfterPaidInt, $requestedFreeInt);
        $remainingFreeRequestInt = $requestedFreeInt - $freeFromFreePoolInt;

        $remainingPaidPoolAfterPaidInt = max(0, $currentPaidInt - $paidFromPaidPoolInt);
        $freeFromPaidPoolInt = min($remainingPaidPoolAfterPaidInt, $remainingFreeRequestInt);

        $unfulfilledFreeInt = $remainingFreeRequestInt - $freeFromPaidPoolInt;

        if ($unfulfilledPaidInt > 0 || $unfulfilledFreeInt > 0) {
            Log::error('Insufficient stock for combined paid/free allocation', [
                'batch_id' => $batchId, 'location_id' => $locationId,
                'available_paid' => $this->qFloat($currentPaidInt),
                'available_free' => $this->qFloat($currentFreeInt),
                'requested_paid' => $this->qFloat($requestedPaidInt),
                'requested_free' => $this->qFloat($requestedFreeInt),
                'unfulfilled_paid' => $this->qFloat($unfulfilledPaidInt),
                'unfulfilled_free' => $this->qFloat($unfulfilledFreeInt),
            ]);
            throw new \Exception("Insufficient stock in batch ID $batchId at location $locationId. Paid requested: {$this->qFloat($requestedPaidInt)}, Free requested: {$this->qFloat($requestedFreeInt)}, Paid short: {$this->qFloat($unfulfilledPaidInt)}, Free short: {$this->qFloat($unfulfilledFreeInt)}");
        }

        $qtyDeductionInt = $paidFromPaidPoolInt + $freeFromPaidPoolInt;
        $freeDeductionInt = $paidFromFreePoolInt + $freeFromFreePoolInt;

        $qtyDeduction = $this->qSql($qtyDeductionInt);
        $freeDeduction = $this->qSql($freeDeductionInt);

        $affected = DB::table('location_batches')
            ->where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->where('qty', '>=', $this->qFloat($qtyDeductionInt))
            ->where('free_qty', '>=', $this->qFloat($freeDeductionInt))
            ->update([
                'qty'      => DB::raw("qty - $qtyDeduction"),
                'free_qty' => DB::raw("free_qty - $freeDeduction"),
            ]);

        if ($affected === 0) {
            Log::error('Atomic stock update failed (possible concurrent modification or insufficient guarded stock)', [
                'batch_id' => $batchId,
                'location_id' => $locationId,
                'qty_deduction' => $this->f($qtyDeduction),
                'free_qty_deduction' => $this->f($freeDeduction),
            ]);
            throw new \Exception("Failed to update stock for batch ID $batchId at location $locationId due to insufficient guarded stock.");
        }

        if ($locationBatch) {
            $effectiveDeduction = $this->qFloat($requestedPaidInt + $requestedFreeInt);
            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity'     => -$effectiveDeduction,
                'stock_type'   => $stockType,
                // Audit: paid_qty/free_qty represent pool-level deduction, not invoice line split.
                'paid_qty' => $this->qFloat($qtyDeductionInt),
                'free_qty' => $this->qFloat($freeDeductionInt),
                'source_pool' => ($paidFromFreePoolInt > 0 || $freeFromPaidPoolInt > 0) ? 'mixed_pool_fallback' : 'separate_pool',
                'movement_type' => 'deduction',
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
            'free_quantity' => $productData['free_quantity'] ?? 0,
            'price' => $productData['unit_price'], // price column stores unit price
            'batch_id' => null,
            'location_id' => $locationId,
            'price_type' => $productData['price_type'],
            'discount_amount' => $productData['discount_amount'] ?? 0,
            'discount_type' => $productData['discount_type'] ?? 'fixed',
            'tax' => $productData['tax'] ?? 0,
            'tax_percent' => $productData['tax_percent'] ?? 0,
            'vat_per_unit' => $productData['sale_vat_per_unit'] ?? 0,
            'vat_total' => round(($productData['sale_vat_per_unit'] ?? 0) * (float) ($productData['quantity'] ?? 0), 2),
            'sale_excl_vat_per_unit' => $productData['sale_excl_vat_per_unit'] ?? 0,
            'profit_per_unit' => $productData['sale_profit_per_unit'] ?? 0,
            'profit_total' => round(($productData['sale_profit_per_unit'] ?? 0) * (float) ($productData['quantity'] ?? 0), 2),
        ]);

        // Add stock history for unlimited stock product (for reporting purposes only)
        $totalQty = $this->f(($productData['quantity'] ?? 0) + ($productData['free_quantity'] ?? 0));
        StockHistory::create([
            'loc_batch_id' => null,
            'quantity' => -$totalQty,
            'stock_type' => StockHistory::STOCK_TYPE_VIRTUAL_SALE,
            'paid_qty' => $this->f($productData['quantity'] ?? 0),
            'free_qty' => $this->f($productData['free_quantity'] ?? 0),
            'source_pool' => 'virtual_pool',
            'movement_type' => 'virtual_sale',
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
                    ->orderBy('location_batches.batch_id')
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
                'tax_percent' => $productData['tax_percent'] ?? 0,
                'vat_per_unit' => $productData['sale_vat_per_unit'] ?? 0,
                'vat_total' => round(($productData['sale_vat_per_unit'] ?? 0) * (float) ($deduction['quantity'] ?? 0), 2),
                'sale_excl_vat_per_unit' => $productData['sale_excl_vat_per_unit'] ?? 0,
                'profit_per_unit' => $productData['sale_profit_per_unit'] ?? 0,
                'profit_total' => round(($productData['sale_profit_per_unit'] ?? 0) * (float) ($deduction['quantity'] ?? 0), 2),
            ]);
        }
    }

    public function restoreStock($product, $stockType)
    {
        $paidQuantity = $this->f($product->quantity ?? 0);
        $freeQuantity = $this->f($product->free_quantity ?? 0);

        // Sale-order cancellation must restore only actually fulfilled quantities
        // for partially allocated backorder lines. Otherwise a line like 7 ordered,
        // 6 fulfilled would incorrectly restore 7.
        if ($stockType === StockHistory::STOCK_TYPE_SALE_ORDER_REVERSAL) {
            $fulfilledPaid = $this->f($product->fulfilled_quantity ?? 0);
            $fulfilledFree = $this->f($product->fulfilled_free_quantity ?? 0);
            $backorderedPaid = $this->f($product->backordered_quantity ?? 0);
            $backorderedFree = $this->f($product->backordered_free_quantity ?? 0);

            $isPartiallyFulfilledBackorder =
                ($backorderedPaid > 0 || $backorderedFree > 0)
                && ($fulfilledPaid < $paidQuantity || $fulfilledFree < $freeQuantity);

            if ($isPartiallyFulfilledBackorder) {
                $paidQuantity = $fulfilledPaid;
                $freeQuantity = $fulfilledFree;
            }
        }

        $totalQuantityToRestore = $this->f($paidQuantity + $freeQuantity);

        // Unlimited stock products have no batch — skip stock update
        if (is_null($product->batch_id)) {
            return;
        }

        // Restore paid and free quantities back to their respective pools.
        $qtyRestore = $this->fSql($paidQuantity);
        $freeQtyRestore = $this->fSql($freeQuantity);
        $affected = DB::table('location_batches')
            ->where('batch_id', $product->batch_id)
            ->where('location_id', $product->location_id)
            ->update([
                'qty' => DB::raw("qty + {$qtyRestore}"),
                'free_qty' => DB::raw("free_qty + {$freeQtyRestore}"),
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
                    'paid_qty' => $paidQuantity,
                    'free_qty' => $freeQuantity,
                    'source_pool' => 'separate_pool',
                    'movement_type' => 'restoration',
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
        $imeiNumbers = SaleImei::where('sale_product_id', $salesProduct->id)
            ->pluck('imei_number');

        if ($imeiNumbers->isNotEmpty()) {
            ImeiNumber::whereIn('imei_number', $imeiNumbers)
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

            Cache::forget("customer_balance_sync_pending_{$customerId}");

        } catch (\Exception $e) {
            Cache::put("customer_balance_sync_pending_{$customerId}", true, now()->addHours(12));
            Log::error('Failed to recalculate customer balance', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception to avoid blocking sale edit, just log the error
        }
    }

    private function restoreAndDeleteExistingSaleProducts(Sale $sale, ?string $oldStatus, array &$originalProducts): void
    {
        $existingProducts = $sale->products()
            ->lockForUpdate()
            ->get();

        foreach ($existingProducts as $product) {
            if (!isset($originalProducts[$product->product_id][$product->batch_id])) {
                $originalProducts[$product->product_id][$product->batch_id] = [
                    'quantity' => 0,
                    'free_quantity' => 0,
                ];
            }

            $originalProducts[$product->product_id][$product->batch_id]['quantity'] += $product->quantity;
            $originalProducts[$product->product_id][$product->batch_id]['free_quantity'] += ($product->free_quantity ?? 0);

            if (in_array($oldStatus, ['final', 'suspend'])) {
                $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
            } else {
                $this->restoreImeiNumbers($product);
            }
        }

        foreach ($existingProducts as $product) {
            $product->delete();
        }
    }

    private function normalizeImeiNumbers($imeiNumbers): array
    {
        if (!is_array($imeiNumbers)) {
            return [];
        }

        $normalized = [];
        foreach ($imeiNumbers as $imei) {
            $imei = trim((string) $imei);
            if ($imei === '') {
                continue;
            }
            $normalized[] = $imei;
        }

        return array_values(array_unique($normalized));
    }

    private function reserveImeisForBatch(array &$preferredImeis, int $requiredCount, int $productId, int $batchId, int $locationId): array
    {
        if ($requiredCount <= 0) {
            return [];
        }

        $query = ImeiNumber::where('product_id', $productId)
            ->where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->where('status', 'available')
            ->orderBy('imei_number')
            ->lockForUpdate();

        if (!empty($preferredImeis)) {
            $query->whereIn('imei_number', $preferredImeis);
        }

        $allocated = $query
            ->limit($requiredCount)
            ->pluck('imei_number')
            ->all();

        if (!empty($preferredImeis)) {
            $allocatedLookup = array_flip($allocated);
            $preferredImeis = array_values(array_filter($preferredImeis, function ($imei) use ($allocatedLookup) {
                return !isset($allocatedLookup[$imei]);
            }));
        }

        return $allocated;
    }

    private function qInt($value): int
    {
        return (int) round(((float) $value) * 10000);
    }

    private function qFloat(int $value): float
    {
        return round($value / 10000, 4);
    }

    private function qSql(int $value): string
    {
        return number_format($this->qFloat($value), 4, '.', '');
    }

    private function f($value): float
    {
        return round((float) $value, 4);
    }

    private function fSql($value): string
    {
        return number_format($this->f($value), 4, '.', '');
    }
}
