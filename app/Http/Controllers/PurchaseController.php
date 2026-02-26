<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Batch;
use App\Models\Ledger;
use App\Models\ImeiNumber;
use App\Services\UnifiedLedgerService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use App\Models\LocationBatch;
use App\Models\Payment;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    protected $unifiedLedgerService;
    protected $paymentService;

    function __construct(UnifiedLedgerService $unifiedLedgerService, PaymentService $paymentService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        $this->paymentService = $paymentService;
        $this->middleware('permission:view purchase', ['only' => ['listPurchase', 'index', 'show']]);
        $this->middleware('permission:create purchase', ['only' => ['AddPurchase', 'store', 'storeOrUpdate']]);
        $this->middleware('permission:edit purchase', ['only' => ['editPurchase', 'update', 'storeOrUpdate']]);
        $this->middleware('permission:delete purchase', ['only' => ['destroy']]);
    }

    public function listPurchase()
    {
        $locations = \App\Models\Location::all();
        $suppliers = \App\Models\Supplier::orderBy('first_name')->get();

        return view('purchase.list_purchase', compact('locations', 'suppliers'));
    }

    public function AddPurchase()
    {
        return view('purchase.add_purchase');
    }


    public function storeOrUpdate(Request $request, $purchaseId = null)
    {

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'purchase_date' => 'required|string', // Allow string format, we'll parse it
            'purchasing_status' => 'required|in:Received,Pending,Ordered',
            'location_id' => 'required|integer|exists:locations,id',
            'pay_term' => 'nullable|integer|min:0',
            'pay_term_type' => 'nullable|in:days,months',
            'attached_document' => 'nullable|file|max:5120|mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_amount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'final_total' => 'required|numeric|min:0',
            'payment_status' => 'nullable|in:Paid,Due,Partial',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.quantity' => [
                'required',
                'numeric',
                // Standalone claims record a promise only — quantity can be 0
                $request->input('purchase_type') === 'free_claim_standalone' ? 'min:0' : 'min:0.0001',
                function ($attribute, $value, $fail) use ($request) {
                    // Extract the index from the attribute, e.g., products.0.quantity => 0
                    if (preg_match('/products\.(\d+)\.quantity/', $attribute, $matches)) {
                        $index = $matches[1];
                        $productData = $request->input("products.$index");
                        if ($productData && isset($productData['product_id'])) {
                            $product = \App\Models\Product::find($productData['product_id']);
                            // ✅ FIX: Improved decimal validation - allow valid decimal numbers like 5.00
                            if ($product && $product->unit && !$product->unit->allow_decimal) {
                                // Check if the value is actually a decimal (has non-zero decimal places)
                                $hasDecimals = (float)$value != (int)$value;
                                if ($hasDecimals) {
                                    $fail("The quantity must be a whole number for this unit type.");
                                }
                            }
                        }
                    }
                },
            ],
            'products.*.free_quantity' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    // Extract the index from the attribute
                    if (preg_match('/products\.(\d+)\.free_quantity/', $attribute, $matches)) {
                        $index = $matches[1];
                        $productData = $request->input("products.$index");
                        if ($productData && isset($productData['product_id'])) {
                            $product = \App\Models\Product::find($productData['product_id']);
                            // Validate decimal for free quantity
                            if ($product && $product->unit && !$product->unit->allow_decimal) {
                                $hasDecimals = (float)$value != (int)$value;
                                if ($hasDecimals) {
                                    $fail("The free quantity must be a whole number for this unit type.");
                                }
                            }
                            // Reasonable limit: free quantity should not exceed paid quantity * 2
                            if ($value > 0 && isset($productData['quantity'])) {
                                if ($value > ($productData['quantity'] * 2)) {
                                    $fail("Free quantity ({$value}) seems unusually high compared to purchase quantity ({$productData['quantity']}). Please verify.");
                                }
                            }
                        }
                    }
                },
            ],
            'products.*.claim_free_quantity' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if (preg_match('/products\.(\d+)\.claim_free_quantity/', $attribute, $matches)) {
                        $index = $matches[1];
                        $productData = $request->input("products.$index");
                        if ($productData && isset($productData['product_id'])) {
                            $product = \App\Models\Product::find($productData['product_id']);
                            if ($product && $product->unit && !$product->unit->allow_decimal) {
                                $hasDecimals = (float)$value != (int)$value;
                                if ($hasDecimals) {
                                    $fail('The claim free quantity must be a whole number for this unit type.');
                                }
                            }
                        }
                    }
                },
            ],
            'products.*.price' => 'nullable|numeric|min:0',
            'products.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'products.*.unit_cost' => 'required|numeric|min:0',
            'products.*.wholesale_price' => 'required|numeric|min:0',
            'products.*.special_price' => 'required|numeric|min:0',
            'products.*.retail_price' => 'required|numeric|min:0',
            'products.*.max_retail_price' => 'required|numeric|min:0',
            'products.*.total' => 'required|numeric|min:0',
            'products.*.batch_no' => 'nullable|string|max:255',
            'products.*.expiry_date' => 'nullable|date',
            'products.*.imei_numbers' => 'nullable|json', // Add IMEI validation
            'paid_amount' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value !== null && $value > 0) {
                        $finalTotal = $request->input('final_total', 0);
                        // Only warn if payment is more than 200% of total (very unusual)
                        if ($value > ($finalTotal * 2)) {
                            $fail("Payment amount ({$value}) is significantly higher than the purchase total ({$finalTotal}). If this is intentional (e.g., advance payment), please verify the amount is correct.");
                        }
                    }
                }
            ],
            'payment_method' => 'nullable|string',
            'payment_account' => 'nullable|string',
            'payment_note' => 'nullable|string',
            'paid_date' => 'nullable|string', // Allow string format, we'll parse it flexibly
            // Cheque specific fields - use string validation since we handle multiple date formats
            'cheque_number' => 'nullable|string|max:255',
            'cheque_bank_branch' => 'nullable|string|max:255',
            'cheque_received_date' => 'nullable|string', // Allow string format, we'll parse it
            'cheque_valid_date' => 'nullable|string', // Allow string format, we'll parse it
            'cheque_given_by' => 'nullable|string|max:255',
            // Card specific fields
            'card_number' => 'nullable|string|max:255',
            'card_holder_name' => 'nullable|string|max:255',
            'card_expiry_month' => 'nullable|integer|min:1|max:12',
            'card_expiry_year' => 'nullable|integer|min:2023',
            'card_security_code' => 'nullable|string|max:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        DB::transaction(function () use ($request, $purchaseId) {
            $attachedDocument = $this->handleAttachedDocument($request);
            $isUpdate = !is_null($purchaseId);
            $oldPurchase = null;

            if ($isUpdate) {
                $oldPurchase = Purchase::find($purchaseId);
            }

            $purchaseType = $request->input('purchase_type', 'regular');
            $claimReferenceId = $request->input('claim_reference_id');

            // For free_claim receipts, force totals to zero
            $finalTotal = in_array($purchaseType, ['free_claim', 'free_claim_standalone'])
                ? 0
                : $request->final_total;
            $total = in_array($purchaseType, ['free_claim', 'free_claim_standalone'])
                ? 0
                : $request->total;

            $purchase = Purchase::updateOrCreate(
                ['id' => $purchaseId],
                [
                    'supplier_id' => $request->supplier_id,
                    'user_id' => auth()->id(),
                    'reference_no' => $purchaseId ? Purchase::find($purchaseId)->reference_no : $this->generateReferenceNo(),
                    'purchase_date' => $request->purchase_date ? Carbon::parse($request->purchase_date)->format('Y-m-d') : now()->format('Y-m-d'),
                    'purchasing_status' => $request->purchasing_status,
                    'location_id' => $request->location_id,
                    'pay_term' => $request->pay_term,
                    'pay_term_type' => $request->pay_term_type,
                    'attached_document' => $attachedDocument,
                    'total' => $total,
                    'discount_type' => $request->discount_type,
                    'discount_amount' => $request->discount_amount,
                    'final_total' => $finalTotal,
                    'purchase_type' => $purchaseType,
                    'claim_reference_id' => $claimReferenceId ?: null,
                ]
            );

            // Process products
            $this->processProducts($request, $purchase);

            // --- Sync claim_status on this purchase ---
            // If any product has a claim_free_quantity, mark this purchase as a claim origin
            if ($purchaseType === 'regular' || $purchaseType === 'free_claim_standalone') {
                $totalClaimed = $purchase->purchaseProducts()->sum('claim_free_quantity');
                if ($totalClaimed > 0) {
                    // Calculate how much has already been received via linked free_claim receipts
                    $totalReceived = \App\Models\PurchaseProduct::whereIn(
                        'purchase_id',
                        $purchase->claimReceipts()->pluck('id')
                    )->sum('quantity');

                    if ($totalReceived <= 0) {
                        $purchase->update(['claim_status' => 'pending']);
                    } elseif ($totalReceived < $totalClaimed) {
                        $purchase->update(['claim_status' => 'partial']);
                    } else {
                        $purchase->update(['claim_status' => 'fulfilled']);
                    }
                } else {
                    $purchase->update(['claim_status' => null]);
                }
            }

            // If this is a free_claim receipt, update the original purchase's claim_status
            if ($purchaseType === 'free_claim' && $claimReferenceId) {
                $this->syncClaimStatus($claimReferenceId);
            }

            // --- Server-side authoritative total calculation ---
            // Calculate sum of product totals stored on the purchase (prevent client manipulation)
            // Skip this for free claim receipts - their total is always 0
            try {
                if (!in_array($purchaseType, ['free_claim', 'free_claim_standalone'])) {
                $calculatedTotal = (float) $purchase->purchaseProducts()->sum(DB::raw('COALESCE(total,0)'));

                // Compute discount amount based on discount_type (if provided)
                $discountAmount = 0.0;
                $discountType = $request->input('discount_type');
                $discountValue = (float) ($request->input('discount_amount') ?? 0);
                if ($discountType === 'fixed') {
                    $discountAmount = $discountValue;
                } elseif ($discountType === 'percent' || $discountType === 'percentage') {
                    $discountAmount = ($calculatedTotal * $discountValue) / 100.0;
                }

                // Compute tax if provided (supporting common codes used in front-end)
                $taxAmount = 0.0;
                $taxType = $request->input('tax_type');
                if ($taxType === 'vat10' || $taxType === 'cgst10') {
                    $taxAmount = ($calculatedTotal - $discountAmount) * 0.10;
                }

                $serverFinalTotal = $calculatedTotal - $discountAmount + $taxAmount;

                // If discrepancy exists between client and server (> small tolerance), log it and override
                $clientFinal = (float) ($request->input('final_total') ?? 0);
                if (abs($clientFinal - $serverFinalTotal) > 0.5) {
                    Log::warning('Final total mismatch on purchase store/update', [
                        'purchase_id' => $purchase->id,
                        'client_final_total' => $clientFinal,
                        'server_calculated_total' => $serverFinalTotal,
                        'calculated_total' => $calculatedTotal,
                        'discount_type' => $discountType,
                        'discount_amount' => $discountValue,
                        'tax_type' => $taxType,
                        'tax_amount' => $taxAmount,
                        'request_products_count' => is_array($request->input('products')) ? count($request->input('products')) : 0,
                    ]);
                }

                // Persist authoritative totals to the purchase record
                $purchase->update([
                    'total' => $calculatedTotal,
                    'discount_type' => $discountType,
                    'discount_amount' => $discountValue,
                    'final_total' => $serverFinalTotal,
                ]);
                } // end if not free_claim
            } catch (\Exception $e) {
                Log::error('Error calculating server-side purchase totals: ' . $e->getMessage());
                // If calculation fails, fall back to client provided totals (already saved earlier)
            }

            // ✅ CRITICAL FIX: Add safety flag to prevent duplicate ledger entries during same request
            static $processedPurchases = [];
            $purchaseKey = 'purchase_' . $purchase->id;

            // Free claim receipts have zero value — skip ledger and payment entirely
            $isFreeClaim = in_array($purchaseType, ['free_claim', 'free_claim_standalone']);

            if (!$isFreeClaim && !isset($processedPurchases[$purchaseKey])) {
                // Handle ledger recording/updating
                if ($isUpdate) {
                    // For updates, use updatePurchase method to handle cleanup and recreation
                    $this->unifiedLedgerService->updatePurchase($purchase);
                } else {
                    // Record purchase in ledger for new purchases
                    $this->unifiedLedgerService->recordPurchase($purchase);
                }

                $processedPurchases[$purchaseKey] = true;
            }

            // Handle payment if paid_amount is provided (skip for free claim receipts)
            if (!$isFreeClaim && $request->paid_amount > 0) {
                // ✅ CLEAN: Use PaymentService to handle all payment logic
                $paymentData = $this->preparePaymentData($request);
                $this->paymentService->handlePurchasePayment($paymentData, $purchase);
            }

            // Note: UnifiedLedgerService automatically handles balance calculations
        });

        return response()->json(['status' => 200, 'message' => 'Purchase ' . ($purchaseId ? 'updated' : 'recorded') . ' successfully!']);
    }

    /**
     * Prepare payment data array from request
     *
     * @param Request $request
     * @return array
     */
    private function preparePaymentData(Request $request): array
    {
        return [
            'amount' => $request->paid_amount,
            'payment_method' => $request->payment_method ?? 'cash',
            'payment_date' => $request->paid_date,
            'notes' => $request->payment_note,
            // Card details
            'card_number' => $request->card_number,
            'card_holder_name' => $request->card_holder_name,
            'card_expiry_month' => $request->card_expiry_month,
            'card_expiry_year' => $request->card_expiry_year,
            'card_security_code' => $request->card_security_code,
            // Cheque details
            'cheque_number' => $request->cheque_number,
            'cheque_bank_branch' => $request->cheque_bank_branch,
            'cheque_received_date' => $request->cheque_received_date,
            'cheque_valid_date' => $request->cheque_valid_date,
            'cheque_given_by' => $request->cheque_given_by,
        ];
    }

    // Helper method to update supplier's current balance
    private function updateSupplierBalance($supplierId)
    {
        // Note: UnifiedLedgerService automatically handles balance calculations
        // No manual recalculation needed
    }

    private function processProducts($request, $purchase)
    {
        $existingProducts = $purchase->purchaseProducts->keyBy('product_id');

        // CRITICAL FIX: Add transaction and validation to prevent double processing
        DB::transaction(function () use ($request, $purchase, $existingProducts) {

            collect($request->products)->chunk(100)->each(function ($productsChunk) use ($purchase, $existingProducts, $request) {
                foreach ($productsChunk as $productData) {
                    $productId = $productData['product_id'];

                    // Validate product data before processing
                    if (!isset($productData['quantity']) || $productData['quantity'] < 0) {
                        throw new \Exception("Invalid quantity for product ID {$productId}");
                    }

                    if ($existingProducts->has($productId)) {
                        // Update existing product - CRITICAL FIX for double stock addition
                        $existingProduct = $existingProducts->get($productId);
                        $oldQuantity = $existingProduct->quantity;
                        $oldFreeQuantity = $existingProduct->free_quantity ?? 0;
                        $newQuantity = $productData['quantity'];
                        $newFreeQuantity = $productData['free_quantity'] ?? 0;
                        $quantityDifference = $newQuantity - $oldQuantity;
                        $freeQuantityDifference = $newFreeQuantity - $oldFreeQuantity;

                        Log::info('Purchase edit: Updating existing product stock', [
                            'product_id' => $productId,
                            'old_quantity' => $oldQuantity,
                            'new_quantity' => $newQuantity,
                            'quantity_difference' => $quantityDifference,
                            'old_free_quantity' => $oldFreeQuantity,
                            'new_free_quantity' => $newFreeQuantity,
                            'free_quantity_difference' => $freeQuantityDifference,
                            'purchase_id' => $purchase->id
                        ]);

                        // ONLY update stock if there's actually a quantity change
                        if ($quantityDifference != 0 || $freeQuantityDifference != 0) {
                            $this->updateProductStock($existingProduct, $quantityDifference, $freeQuantityDifference, $request->location_id);
                        }

                        // Update purchase product record with all new data
                        $existingProduct->update([
                            'quantity' => $newQuantity,
                            'free_quantity' => $newFreeQuantity,
                            'price' => $productData['price'] ?? $productData['unit_cost'],
                            'discount_percent' => $productData['discount_percent'] ?? 0,
                            'unit_cost' => $productData['unit_cost'],
                            'wholesale_price' => $productData['wholesale_price'],
                            'special_price' => $productData['special_price'],
                            'retail_price' => $productData['retail_price'],
                            'max_retail_price' => $productData['max_retail_price'],
                            'total' => $productData['total'],
                        ]);

                        // Update batch prices (but NOT quantity - that's handled by updateProductStock)
                        $batch = Batch::find($existingProduct->batch_id);
                        if ($batch) {
                            $batch->update([
                                'wholesale_price' => $productData['wholesale_price'],
                                'special_price' => $productData['special_price'],
                                'retail_price' => $productData['retail_price'],
                                'max_retail_price' => $productData['max_retail_price'],
                                // IMPORTANT: Do NOT update batch qty here - it's handled by updateProductStock
                            ]);

                            Log::info('Updated batch prices for existing product', [
                                'batch_id' => $batch->id,
                                'product_id' => $productId,
                                'wholesale_price' => $productData['wholesale_price'],
                                'retail_price' => $productData['retail_price'],
                            ]);
                        } else {
                            Log::error('Batch not found for existing product', [
                                'batch_id' => $existingProduct->batch_id,
                                'product_id' => $productId
                            ]);
                        }
                    } else {
                        // Add new product to purchase
                        Log::info('Purchase edit: Adding new product', [
                            'product_id' => $productId,
                            'quantity' => $productData['quantity'],
                            'purchase_id' => $purchase->id
                        ]);

                        $this->addNewProductToPurchase($purchase, $productData, $request->location_id);
                    }
                }
            });

            // Remove products not present in the request
            $requestProductIds = collect($request->products)->pluck('product_id')->toArray();
            $productsToRemove = $existingProducts->whereNotIn('product_id', $requestProductIds);

            foreach ($productsToRemove as $productToRemove) {
                Log::info('Purchase edit: Removing product', [
                    'product_id' => $productToRemove->product_id,
                    'quantity' => $productToRemove->quantity,
                    'purchase_id' => $purchase->id
                ]);

                $this->removeProductFromPurchase($productToRemove, $request->location_id);
            }
        });
    }

    /**
     * Recompute and persist the claim_status on a purchase that has claim_free_quantity products.
     * Called after a free_claim receipt is created/updated to keep the status in sync.
     */
    private function syncClaimStatus(int $originalPurchaseId): void
    {
        $originalPurchase = Purchase::with('purchaseProducts', 'claimReceipts')->find($originalPurchaseId);
        if (!$originalPurchase) {
            return;
        }

        $totalClaimed = $originalPurchase->purchaseProducts->sum('claim_free_quantity');
        if ($totalClaimed <= 0) {
            return;
        }

        $receiptPurchaseIds = $originalPurchase->claimReceipts()->pluck('id');
        $totalReceived = \App\Models\PurchaseProduct::whereIn('purchase_id', $receiptPurchaseIds)
            ->sum('quantity');

        if ($totalReceived <= 0) {
            $status = 'pending';
        } elseif ((float)$totalReceived < (float)$totalClaimed) {
            $status = 'partial';
        } else {
            $status = 'fulfilled';
        }

        $originalPurchase->update(['claim_status' => $status]);

        Log::info('Claim status synced', [
            'original_purchase_id' => $originalPurchaseId,
            'total_claimed' => $totalClaimed,
            'total_received' => $totalReceived,
            'new_status' => $status,
        ]);
    }

    private function generateReferenceNo()
    {
        // Fetch the last reference number from the database
        $lastReference = Purchase::orderBy('id', 'desc')->first();
        $lastReferenceNo = $lastReference ? intval(substr($lastReference->reference_no, 3)) : 0;

        // Increment the reference number
        $newReferenceNo = $lastReferenceNo + 1;

        // Format the new reference number to 3 digits
        $formattedNumber = str_pad($newReferenceNo, 3, '0', STR_PAD_LEFT);

        return 'PUR' . $formattedNumber;
    }

    private function handleAttachedDocument($request)
    {
        if ($request->hasFile('attached_document')) {
            $file = $request->file('attached_document');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('/assets/documents'), $fileName);
            return $fileName;
        }
        return null;
    }

    private function updateProductStock($existingProduct, $quantityDifference, $freeQuantityDifference, $locationId)
    {
        // CRITICAL FIX: Add validation to prevent double processing
        if ($quantityDifference == 0 && $freeQuantityDifference == 0) {
            Log::info('No stock update needed - quantity differences are zero', [
                'product_id' => $existingProduct->product_id,
                'batch_id' => $existingProduct->batch_id
            ]);
            return;
        }

        $batch = Batch::find($existingProduct->batch_id);
        if (!$batch) {
            throw new \Exception("Batch not found for product stock update: {$existingProduct->batch_id}");
        }

        $locationBatch = LocationBatch::firstOrCreate(
            ['batch_id' => $batch->id, 'location_id' => $locationId],
            ['qty' => 0, 'free_qty' => 0]
        );

        // Store original quantities for logging
        $originalLocationPaidQty = $locationBatch->qty;
        $originalLocationFreeQty = $locationBatch->free_qty ?? 0;
        $originalBatchPaidQty = $batch->qty;
        $originalBatchFreeQty = $batch->free_qty ?? 0;

        // Validate stock reduction for paid quantities
        if ($originalLocationPaidQty + $quantityDifference < 0) {
            throw new \Exception("Paid stock quantity cannot be reduced below zero. Location paid stock: {$originalLocationPaidQty}, trying to change by: {$quantityDifference}");
        }

        if ($originalBatchPaidQty + $quantityDifference < 0) {
            throw new \Exception("Batch paid stock quantity cannot be reduced below zero. Batch paid stock: {$originalBatchPaidQty}, trying to change by: {$quantityDifference}");
        }

        // Validate stock reduction for free quantities
        if ($originalLocationFreeQty + $freeQuantityDifference < 0) {
            throw new \Exception("Free stock quantity cannot be reduced below zero. Location free stock: {$originalLocationFreeQty}, trying to change by: {$freeQuantityDifference}");
        }

        if ($originalBatchFreeQty + $freeQuantityDifference < 0) {
            throw new \Exception("Batch free stock quantity cannot be reduced below zero. Batch free stock: {$originalBatchFreeQty}, trying to change by: {$freeQuantityDifference}");
        }

        // CRITICAL: Use DB transaction to ensure atomicity
        DB::transaction(function () use ($locationBatch, $batch, $quantityDifference, $freeQuantityDifference, $existingProduct) {
            // Update location batch paid and free quantities separately
            $locationBatch->increment('qty', $quantityDifference); // Paid qty
            $locationBatch->increment('free_qty', $freeQuantityDifference); // Free qty

            // Update batch paid and free quantities separately
            $batch->increment('qty', $quantityDifference); // Paid qty
            $batch->increment('free_qty', $freeQuantityDifference); // Free qty

            // Create stock history record for total change
            $totalQuantityDifference = $quantityDifference + $freeQuantityDifference;
            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity' => $totalQuantityDifference,
                'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
                'reference_id' => $existingProduct->purchase_id,
                'reference_type' => 'purchase_edit'
            ]);

            Log::info('Stock updated successfully with paid and free quantities separately', [
                'product_id' => $existingProduct->product_id,
                'batch_id' => $batch->id,
                'paid_quantity_difference' => $quantityDifference,
                'free_quantity_difference' => $freeQuantityDifference,
                'location_paid_stock_before' => $locationBatch->qty - $quantityDifference,
                'location_paid_stock_after' => $locationBatch->qty,
                'location_free_stock_before' => ($locationBatch->free_qty ?? 0) - $freeQuantityDifference,
                'location_free_stock_after' => $locationBatch->free_qty,
                'batch_stock_before' => $originalBatchQty,
                'batch_stock_after' => $batch->qty
            ]);
        });
    }

    private function addNewProductToPurchase($purchase, $productData, $locationId)
    {
        // First check if batch already exists with same batch_no and product_id
        $batchNo = $productData['batch_no'] ?? Batch::generateNextBatchNo();
        $freeQuantity = $productData['free_quantity'] ?? 0;
        $totalQuantity = $productData['quantity'] + $freeQuantity;

        $batch = Batch::where([
            'batch_no' => $batchNo,
            'product_id' => $productData['product_id'],
        ])->first();

        if ($batch) {
            // Update existing batch with new prices and add quantities separately
            $batch->update([
                'wholesale_price' => $productData['wholesale_price'],
                'special_price' => $productData['special_price'],
                'retail_price' => $productData['retail_price'],
                'max_retail_price' => $productData['max_retail_price'],
                // Note: Don't update unit_cost and expiry_date as they should remain from original batch
            ]);
            $batch->increment('qty', $productData['quantity']); // Paid quantity only
            $batch->increment('free_qty', $freeQuantity); // Free quantity separately

            Log::info('Updated existing batch with new prices and quantities (paid + free separately)', [
                'batch_id' => $batch->id,
                'batch_no' => $batchNo,
                'product_id' => $productData['product_id'],
                'added_paid_quantity' => $productData['quantity'],
                'added_free_quantity' => $freeQuantity,
                'new_paid_qty' => $batch->qty,
                'new_free_qty' => $batch->free_qty,
                'retail_price' => $productData['retail_price'],
            ]);
        } else {
            // Create new batch with paid and free quantities stored separately
            $batch = Batch::create([
                'batch_no' => $batchNo,
                'product_id' => $productData['product_id'],
                'unit_cost' => $productData['unit_cost'],
                'expiry_date' => $productData['expiry_date'],
                'qty' => $productData['quantity'], // Paid quantity only
                'free_qty' => $freeQuantity, // Free quantity separately
                'wholesale_price' => $productData['wholesale_price'],
                'special_price' => $productData['special_price'],
                'retail_price' => $productData['retail_price'],
                'max_retail_price' => $productData['max_retail_price'],
            ]);

            Log::info('Created new batch with paid and free quantities separately', [
                'batch_id' => $batch->id,
                'batch_no' => $batchNo,
                'product_id' => $productData['product_id'],
                'paid_quantity' => $productData['quantity'],
                'free_quantity' => $freeQuantity,
                'unit_cost' => $productData['unit_cost'],
                'retail_price' => $productData['retail_price'],
                'wholesale_price' => $productData['wholesale_price'],
            ]);
        }

        // Update location batch with paid and free quantities separately
        $locationBatch = LocationBatch::firstOrCreate(
            ['batch_id' => $batch->id, 'location_id' => $locationId],
            ['qty' => 0, 'free_qty' => 0]
        );
        $locationBatch->increment('qty', $productData['quantity']); // Paid quantity
        $locationBatch->increment('free_qty', $freeQuantity); // Free quantity

        // Create purchase product record with both quantities tracked
        $purchase->purchaseProducts()->updateOrCreate(
            ['product_id' => $productData['product_id'], 'batch_id' => $batch->id, 'purchase_id' => $purchase->id],
            [
                'quantity' => $productData['quantity'],
                'free_quantity' => $freeQuantity,
                'claim_free_quantity' => $productData['claim_free_quantity'] ?? 0,
                'price' => $productData['price'] ?? $productData['unit_cost'],
                'discount_percent' => $productData['discount_percent'] ?? 0,
                'unit_cost' => $productData['unit_cost'],
                'wholesale_price' => $productData['wholesale_price'],
                'special_price' => $productData['special_price'],
                'retail_price' => $productData['retail_price'],
                'max_retail_price' => $productData['max_retail_price'],
                'total' => $productData['total'], // Total cost is only for paid items
                'location_id' => $locationId,
            ]
        );

        // Create stock history for the combined quantity
        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $totalQuantity,
            'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
        ]);

        // Handle IMEI numbers if provided
        if (isset($productData['imei_numbers']) && !empty($productData['imei_numbers'])) {
            $this->processImeiNumbers($productData, $batch->id, $locationId);
        }
    }

    private function removeProductFromPurchase($productToRemove, $locationId)
    {
        $batch = Batch::find($productToRemove->batch_id);
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->first();

        // Remove paid and free quantities separately
        $paidQuantityToRemove = $productToRemove->quantity;
        $freeQuantityToRemove = $productToRemove->free_quantity ?? 0;
        $totalQuantityToRemove = $paidQuantityToRemove + $freeQuantityToRemove;

        if ($locationBatch) {
            $locationBatch->decrement('qty', $paidQuantityToRemove); // Paid qty
            $locationBatch->decrement('free_qty', $freeQuantityToRemove); // Free qty
        }

        $batch->decrement('qty', $paidQuantityToRemove); // Paid qty
        $batch->decrement('free_qty', $freeQuantityToRemove); // Free qty

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => -$totalQuantityToRemove,
            'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
        ]);

        Log::info('Removed product from purchase with paid and free quantities separately', [
            'product_id' => $productToRemove->product_id,
            'paid_quantity_removed' => $paidQuantityToRemove,
            'free_quantity_removed' => $freeQuantityToRemove,
            'total_quantity_removed' => $totalQuantityToRemove,
        ]);

        $productToRemove->delete();
    }

    private function processImeiNumbers($productData, $batchId, $locationId)
    {
        $imeiNumbers = json_decode($productData['imei_numbers'], true);

        if (is_array($imeiNumbers) && !empty($imeiNumbers)) {
            foreach ($imeiNumbers as $imeiNumber) {
                if (!empty(trim($imeiNumber))) {
                    ImeiNumber::create([
                        'imei_number' => trim($imeiNumber),
                        'product_id' => $productData['product_id'],
                        'location_id' => $locationId,
                        'batch_id' => $batchId,
                        'status' => 'available', // Default status for newly purchased IMEI
                    ]);
                }
            }

            Log::info('IMEI numbers processed for product', [
                'product_id' => $productData['product_id'],
                'imei_count' => count($imeiNumbers),
                'batch_id' => $batchId,
                'location_id' => $locationId
            ]);
        }
    }

    // ✅ REMOVED: handlePayment method moved to PaymentService to eliminate duplication

    // ✅ REMOVED: validatePaymentAmount method moved to PaymentService to eliminate duplication

    // ✅ REMOVED: updatePurchasePaymentStatus method moved to PaymentService to eliminate duplication

    /**
     * Recalculate total_paid for a specific purchase
     * Useful for fixing any inconsistencies
     */
    public function recalculatePurchaseTotal($purchaseId)
    {
        try {
            $purchase = Purchase::find($purchaseId);
            if (!$purchase) {
                return response()->json(['status' => 404, 'message' => 'Purchase not found.']);
            }

            $purchase->updateTotalDue();
            $this->paymentService->updatePurchasePaymentStatus($purchase);

            return response()->json([
                'status' => 200,
                'message' => 'Purchase totals recalculated successfully.',
                'total_paid' => $purchase->total_paid,
                'payment_status' => $purchase->payment_status
            ]);
        } catch (Exception $e) {
            Log::error('Error recalculating purchase total: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Error recalculating purchase total.']);
        }
    }

    /**
     * Recalculate total_paid for all purchases
     * Use this to fix any existing data inconsistencies
     */
    public function recalculateAllPurchaseTotals()
    {
        try {
            $purchases = Purchase::all();
            $updated = 0;

            foreach ($purchases as $purchase) {
                $purchase->updateTotalDue();
                $this->paymentService->updatePurchasePaymentStatus($purchase);
                $updated++;
            }

            return response()->json([
                'status' => 200,
                'message' => "Successfully recalculated totals for {$updated} purchases."
            ]);
        } catch (Exception $e) {
            Log::error('Error recalculating all purchase totals: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Error recalculating purchase totals.']);
        }
    }

    public function getAllPurchase(Request $request)
    {
        try {
            // Build cache key based on filters
            $cacheKey = 'purchases_all_' . md5(json_encode([
                'supplier_id' => $request->supplier_id,
                'location_id' => $request->location_id,
                'purchasing_status' => $request->purchasing_status,
                'payment_status' => $request->payment_status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]));

            // Cache ALL purchases for 5 minutes (fast loading + instant pagination)
            $purchases = Cache::remember($cacheKey, 300, function() use ($request) {
                $query = DB::table('purchases as p')
                    ->select([
                        'p.id',
                        'p.supplier_id',
                        'p.location_id',
                        'p.user_id',
                        'p.reference_no',
                        'p.purchase_date',
                        'p.purchasing_status',
                        'p.payment_status',
                        'p.final_total',
                        'p.total_due',
                        'p.claim_status',
                        DB::raw("CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) as supplier_name"),
                        'l.name as location_name',
                        'u.user_name'
                    ])
                    ->leftJoin('suppliers as s', 'p.supplier_id', '=', 's.id')
                    ->leftJoin('locations as l', 'p.location_id', '=', 'l.id')
                    ->leftJoin('users as u', 'p.user_id', '=', 'u.id')
                    // Exclude claim receipt records — they belong in the Supplier Claims module only
                    ->where(function ($q) {
                        $q->where('p.purchase_type', 'regular')
                          ->orWhereNull('p.purchase_type');
                    });

                // Apply filters
                if ($request->filled('supplier_id')) {
                    $query->where('p.supplier_id', $request->supplier_id);
                }
                if ($request->filled('location_id')) {
                    $query->where('p.location_id', $request->location_id);
                }
                if ($request->filled('purchasing_status')) {
                    $query->where('p.purchasing_status', $request->purchasing_status);
                }
                if ($request->filled('payment_status')) {
                    $query->where('p.payment_status', $request->payment_status);
                }
                if ($request->filled('start_date')) {
                    $query->whereDate('p.purchase_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $query->whereDate('p.purchase_date', '<=', $request->end_date);
                }

                $query->orderBy('p.id', 'desc');

                return $query->get();
            });

            // Format data
            $formattedPurchases = $purchases->map(function($purchase) {
                return [
                    'id' => $purchase->id,
                    'supplier_id' => $purchase->supplier_id,
                    'location_id' => $purchase->location_id,
                    'user_id' => $purchase->user_id,
                    'reference_no' => $purchase->reference_no,
                    'purchase_date' => $purchase->purchase_date,
                    'purchasing_status' => $purchase->purchasing_status,
                    'payment_status' => $purchase->payment_status,
                    'final_total' => $purchase->final_total,
                    'total_due' => $purchase->total_due,
                    'claim_status' => $purchase->claim_status,
                    'supplier' => [
                        'first_name' => explode(' ', $purchase->supplier_name)[0] ?? '',
                        'last_name' => explode(' ', $purchase->supplier_name, 2)[1] ?? ''
                    ],
                    'location' => [
                        'name' => $purchase->location_name
                    ],
                    'user' => [
                        'user_name' => $purchase->user_name
                    ]
                ];
            });

            return response()->json([
                'purchases' => $formattedPurchases
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching purchases: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching purchases. Please try again later.'], 500);
        }
    }

    public function getAllPurchasesProduct($id)
    {
        $purchase = Purchase::with(['supplier', 'location', 'purchaseProducts.product', 'payments'])->find($id);

        if (!$purchase) {
            return response()->json(['message' => 'No purchase product found.'], 404);
        }

        return response()->json(['purchase' => $purchase], 200);
    }

    public function getPurchase($id)
    {
        $purchase = Purchase::with('supplier', 'location', 'payments')->find($id);

        if (!$purchase) {
            return response()->json(['message' => 'Purchase not found.'], 404);
        }

        return response()->json($purchase);
    }

    public function getPurchaseProductsBySupplier($supplierId, Request $request)
    {
        try {
            $locationId = $request->get('location_id');

            $query = Purchase::with(['purchaseProducts.product.unit', 'purchaseProducts.batch'])
                ->where('supplier_id', $supplierId);

            // Filter by location if provided
            if ($locationId) {
                $query->where('location_id', $locationId);
            }

            $purchases = $query->get();

            if ($purchases->isEmpty()) {
                return response()->json(['message' => 'No purchases found for this supplier and location.'], 404);
            }

            $products = [];

            foreach ($purchases as $purchase) {
                foreach ($purchase->purchaseProducts as $purchaseProduct) {
                    $productId = $purchaseProduct->product_id;

                    // Get current stock from LocationBatch for the specific location
                    $currentStock = 0;
                    if ($purchaseProduct->batch_id) {
                        $locationBatch = \App\Models\LocationBatch::where('batch_id', $purchaseProduct->batch_id)
                            ->where('location_id', $purchase->location_id)
                            ->first();
                        $currentStock = $locationBatch ? $locationBatch->qty : 0;
                    }

                    if (!isset($products[$productId])) {
                        $products[$productId] = [
                            'product' => $purchaseProduct->product,
                            'unit' => $purchaseProduct->product->unit ?? null,
                            'purchases' => []
                        ];
                    }

                    // Only include products that have current stock > 0
                    if ($currentStock > 0) {
                        $products[$productId]['purchases'][] = [
                            'purchase_id' => $purchase->id,
                            'batch_id' => $purchaseProduct->batch_id,
                            'quantity' => $currentStock, // Use current stock instead of purchased quantity
                            'unit_cost' => $purchaseProduct->unit_cost,
                            'wholesale_price' => $purchaseProduct->wholesale_price,
                            'special_price' => $purchaseProduct->special_price,
                            'retail_price' => $purchaseProduct->retail_price,
                            'max_retail_price' => $purchaseProduct->max_retail_price,
                            'total' => $purchaseProduct->total,
                            'batch' => $purchaseProduct->batch,
                        ];
                    }
                }
            }

            // Filter out products with no available stock
            $filteredProducts = array_filter($products, function($product) {
                return !empty($product['purchases']);
            });

            return response()->json(['products' => array_values($filteredProducts)], 200);
        } catch (\Exception $e) {
            Log::error('Error in getPurchaseProductsBySupplier: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching purchase products.'], 500);
        }
    }

    public function editPurchase($id)
    {
        $purchase = Purchase::with([
            'supplier',
            'location',
            'purchaseProducts.batch', // Corrected relationship
            'purchaseProducts.product', // Load the product relationship
            'payments'
        ])->find($id);

        if (!$purchase) {
            return response()->json(['message' => 'Purchase not found.'], 404);
        }

        if (request()->ajax() || request()->is('api/*')) {
            // Add debug logging to ensure supplier_id is present
            Log::info('Purchase edit response', [
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'supplier_name' => $purchase->supplier ? $purchase->supplier->first_name : 'N/A',
                'reference_no' => $purchase->reference_no
            ]);

            return response()->json(['status' => 200, 'purchase' => $purchase], 200);
        }

        return view('purchase.add_purchase', ['purchase' => $purchase]);
    }

    /**
     * Get IMEI-enabled products from a specific purchase
     */
    public function getPurchaseImeiProducts($purchaseId)
    {
        try {
            $purchase = Purchase::with([
                'purchaseProducts' => function($query) {
                    $query->whereHas('product', function($q) {
                        $q->where('is_imei_or_serial_no', 1);
                    });
                },
                'purchaseProducts.product',
                'purchaseProducts.batch',
                'location'
            ])->find($purchaseId);

            if (!$purchase) {
                return response()->json(['message' => 'Purchase not found.'], 404);
            }

            $imeiProducts = [];

            foreach ($purchase->purchaseProducts as $purchaseProduct) {
                if ($purchaseProduct->product->is_imei_or_serial_no) {
                    // Count existing IMEI numbers for this batch
                    $existingImeiCount = ImeiNumber::where([
                        'product_id' => $purchaseProduct->product_id,
                        'batch_id' => $purchaseProduct->batch_id,
                        'location_id' => $purchase->location_id
                    ])->count();

                    // Get existing IMEI numbers
                    $existingImeis = ImeiNumber::where([
                        'product_id' => $purchaseProduct->product_id,
                        'batch_id' => $purchaseProduct->batch_id,
                        'location_id' => $purchase->location_id
                    ])->get();

                    $imeiProducts[] = [
                        'purchase_product_id' => $purchaseProduct->id,
                        'product_id' => $purchaseProduct->product_id,
                        'product_name' => $purchaseProduct->product->product_name,
                        'batch_id' => $purchaseProduct->batch_id,
                        'batch_no' => $purchaseProduct->batch->batch_no,
                        'quantity_purchased' => $purchaseProduct->quantity,
                        'existing_imei_count' => $existingImeiCount,
                        'missing_imei_count' => max(0, $purchaseProduct->quantity - $existingImeiCount),
                        'existing_imeis' => $existingImeis->map(function($imei) {
                            return [
                                'id' => $imei->id,
                                'imei_number' => $imei->imei_number,
                                'status' => $imei->status
                            ];
                        }),
                        'location_id' => $purchase->location_id,
                        'unit_cost' => $purchaseProduct->unit_cost,
                        'retail_price' => $purchaseProduct->retail_price
                    ];
                }
            }

            return response()->json([
                'status' => 200,
                'purchase' => [
                    'id' => $purchase->id,
                    'reference_no' => $purchase->reference_no,
                    'purchase_date' => $purchase->purchase_date,
                    'supplier' => $purchase->supplier,
                    'location' => $purchase->location
                ],
                'imei_products' => $imeiProducts
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching purchase IMEI products: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching IMEI products.'], 500);
        }
    }

    /**
     * Add IMEI numbers to a specific purchase product
     */
    public function addImeiToPurchaseProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_product_id' => 'required|integer|exists:purchase_products,id',
            'imei_numbers' => 'required|array|min:1',
            'imei_numbers.*' => 'required|string|regex:/^\d{10,17}$/|unique:imei_numbers,imei_number'
        ], [
            'imei_numbers.*.regex' => 'Each IMEI number must be 10-17 digits.',
            'imei_numbers.*.unique' => 'IMEI number :input already exists in the system.'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            DB::transaction(function () use ($request) {
                $purchaseProduct = \App\Models\PurchaseProduct::with(['product', 'purchase'])->find($request->purchase_product_id);

                if (!$purchaseProduct) {
                    throw new \Exception('Purchase product not found.');
                }

                if (!$purchaseProduct->product->is_imei_or_serial_no) {
                    throw new \Exception('This product is not configured for IMEI/Serial numbers.');
                }

                // Check current IMEI count
                $currentImeiCount = ImeiNumber::where([
                    'product_id' => $purchaseProduct->product_id,
                    'batch_id' => $purchaseProduct->batch_id,
                    'location_id' => $purchaseProduct->purchase->location_id
                ])->count();

                $newImeiCount = count($request->imei_numbers);
                $totalAfterAddition = $currentImeiCount + $newImeiCount;

                if ($totalAfterAddition > $purchaseProduct->quantity) {
                    throw new \Exception("Cannot add {$newImeiCount} IMEI numbers. Maximum allowed: " . ($purchaseProduct->quantity - $currentImeiCount));
                }

                // Add IMEI numbers
                foreach ($request->imei_numbers as $imeiNumber) {
                    ImeiNumber::create([
                        'imei_number' => trim($imeiNumber),
                        'product_id' => $purchaseProduct->product_id,
                        'location_id' => $purchaseProduct->purchase->location_id,
                        'batch_id' => $purchaseProduct->batch_id,
                        'status' => 'available'
                    ]);
                }

                Log::info('Added IMEI numbers to purchase product', [
                    'purchase_product_id' => $request->purchase_product_id,
                    'imei_count' => $newImeiCount,
                    'total_imei_count' => $totalAfterAddition
                ]);
            });

            return response()->json([
                'status' => 200,
                'message' => 'IMEI numbers added successfully!',
                'added_count' => count($request->imei_numbers)
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding IMEI numbers: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove IMEI numbers from a purchase product
     */
    public function removeImeiFromPurchaseProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imei_ids' => 'required|array|min:1',
            'imei_ids.*' => 'required|integer|exists:imei_numbers,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            DB::transaction(function () use ($request) {
                // Verify all IMEI numbers are available (not sold)
                $imeiNumbers = ImeiNumber::whereIn('id', $request->imei_ids)->get();

                $unavailableImeis = $imeiNumbers->where('status', '!=', 'available');
                if ($unavailableImeis->count() > 0) {
                    $unavailableList = $unavailableImeis->pluck('imei_number')->join(', ');
                    throw new \Exception("Cannot remove IMEI numbers that are not available: {$unavailableList}");
                }

                // Delete IMEI numbers
                ImeiNumber::whereIn('id', $request->imei_ids)->delete();

                Log::info('Removed IMEI numbers from purchase', [
                    'imei_ids' => $request->imei_ids,
                    'count' => count($request->imei_ids)
                ]);
            });

            return response()->json([
                'status' => 200,
                'message' => 'IMEI numbers removed successfully!',
                'removed_count' => count($request->imei_ids)
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing IMEI numbers: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update IMEI number for purchase product
     */
    public function updateImeiForPurchaseProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imei_id' => 'required|integer|exists:imei_numbers,id',
            'imei_number' => 'required|string|regex:/^\d{10,17}$/|unique:imei_numbers,imei_number'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            DB::transaction(function () use ($request) {
                $imeiRecord = ImeiNumber::findOrFail($request->imei_id);

                // Check if IMEI is available for editing
                if ($imeiRecord->status !== 'available') {
                    throw new \Exception("Cannot update IMEI number. Only available IMEI numbers can be edited.");
                }

                $oldImeiNumber = $imeiRecord->imei_number;

                // Update the IMEI number
                $imeiRecord->update([
                    'imei_number' => $request->imei_number
                ]);

                Log::info('Updated IMEI number', [
                    'imei_id' => $request->imei_id,
                    'old_imei' => $oldImeiNumber,
                    'new_imei' => $request->imei_number,
                    'batch_id' => $imeiRecord->batch_id,
                    'location_id' => $imeiRecord->location_id
                ]);
            });

            return response()->json([
                'status' => 200,
                'message' => 'IMEI number updated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating IMEI number: ' . $e->getMessage());

            // Check if it's a duplicate IMEI error
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json(['status' => 400, 'message' => 'This IMEI number already exists in the system.']);
            }

            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Bulk add IMEI numbers from text input
     */
    public function bulkAddImeiToPurchaseProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_product_id' => 'required|integer|exists:purchase_products,id',
            'imei_text' => 'required|string',
            'separator' => 'nullable|string|in:newline,comma,space'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            // Parse IMEI numbers from text
            $separator = $request->separator ?? 'newline';
            $imeiText = trim($request->imei_text);

            $imeiNumbers = [];
            switch ($separator) {
                case 'comma':
                    $imeiNumbers = explode(',', $imeiText);
                    break;
                case 'space':
                    $imeiNumbers = explode(' ', $imeiText);
                    break;
                default: // newline
                    $imeiNumbers = explode("\n", $imeiText);
                    break;
            }

            // Clean and validate IMEI numbers
            $cleanedImeis = [];
            $errors = [];

            foreach ($imeiNumbers as $index => $imei) {
                $cleanedImei = trim($imei);
                if (empty($cleanedImei)) continue;

                // Validate IMEI format
                if (!preg_match('/^\d{10,17}$/', $cleanedImei)) {
                    $errors[] = "Line " . ($index + 1) . ": Invalid IMEI format '{$cleanedImei}' (must be 10-17 digits)";
                    continue;
                }

                // Check for duplicates in the system
                if (ImeiNumber::where('imei_number', $cleanedImei)->exists()) {
                    $errors[] = "Line " . ($index + 1) . ": IMEI '{$cleanedImei}' already exists in the system";
                    continue;
                }

                // Check for duplicates within the input
                if (in_array($cleanedImei, $cleanedImeis)) {
                    $errors[] = "Line " . ($index + 1) . ": Duplicate IMEI '{$cleanedImei}' found in input";
                    continue;
                }

                $cleanedImeis[] = $cleanedImei;
            }

            if (!empty($errors)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Validation errors found',
                    'errors' => $errors,
                    'valid_count' => count($cleanedImeis),
                    'error_count' => count($errors)
                ]);
            }

            if (empty($cleanedImeis)) {
                return response()->json(['status' => 400, 'message' => 'No valid IMEI numbers found in the input.']);
            }

            // Add the valid IMEI numbers
            $addRequest = new Request([
                'purchase_product_id' => $request->purchase_product_id,
                'imei_numbers' => $cleanedImeis
            ]);

            return $this->addImeiToPurchaseProduct($addRequest);

        } catch (\Exception $e) {
            Log::error('Error in bulk add IMEI: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Error processing bulk IMEI addition.']);
        }
    }

    /**
     * Clean up duplicate payments for a purchase (keep only the latest)
     */
    public function cleanupDuplicatePayments($purchaseId)
    {
        try {
            DB::transaction(function () use ($purchaseId) {
                $payments = Payment::where('reference_id', $purchaseId)
                    ->where('payment_type', 'purchase')
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($payments->count() > 1) {
                    // Keep the latest payment, mark others as inactive
                    $latestPayment = $payments->last();
                    $duplicatePayments = $payments->take($payments->count() - 1);

                    Log::info('Cleaning up duplicate payments', [
                        'purchase_id' => $purchaseId,
                        'total_payments' => $payments->count(),
                        'keeping_payment_id' => $latestPayment->id,
                        'removing_count' => $duplicatePayments->count()
                    ]);

                    foreach ($duplicatePayments as $duplicate) {
                        // Mark payment as inactive instead of deleting
                        $duplicate->update([
                            'status' => 'inactive',
                            'notes' => ($duplicate->notes ?: '') . ' [DUPLICATE REMOVED: ' . now()->format('Y-m-d H:i:s') . ']'
                        ]);

                        // Also handle ledger entries for this duplicate payment
                        $this->unifiedLedgerService->deletePaymentLedger($duplicate);
                    }
                }
            });

            return response()->json([
                'status' => 200,
                'message' => 'Duplicate payments cleaned up successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error cleaning up duplicate payments: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error cleaning up duplicate payments.'
            ]);
        }
    }
}
