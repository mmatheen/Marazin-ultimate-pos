<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Batch;
use App\Models\Ledger;
use App\Models\ImeiNumber;
use App\Services\UnifiedLedgerService;
use Illuminate\Http\Request;
use App\Models\LocationBatch;
use App\Models\Payment;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    protected $unifiedLedgerService;

    function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
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
            'purchase_date' => 'required|date',
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
                'min:0.0001',
                function ($attribute, $value, $fail) use ($request) {
                    // Extract the index from the attribute, e.g., products.0.quantity => 0
                    if (preg_match('/products\.(\d+)\.quantity/', $attribute, $matches)) {
                        $index = $matches[1];
                        $productData = $request->input("products.$index");
                        if ($productData && isset($productData['product_id'])) {
                            $product = \App\Models\Product::find($productData['product_id']);
                            if ($product && $product->unit && !$product->unit->allow_decimal && floor($value) != $value) {
                                $fail("The quantity must be an integer for this unit.");
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
            'paid_date' => 'nullable|date',
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

            $purchase = Purchase::updateOrCreate(
                ['id' => $purchaseId],
                [
                    'supplier_id' => $request->supplier_id,
                    'user_id' => auth()->id(),
                    'reference_no' => $purchaseId ? Purchase::find($purchaseId)->reference_no : $this->generateReferenceNo(),
                    'purchase_date' => $request->purchase_date,
                    'purchasing_status' => $request->purchasing_status,
                    'location_id' => $request->location_id,
                    'pay_term' => $request->pay_term,
                    'pay_term_type' => $request->pay_term_type,
                    'attached_document' => $attachedDocument,
                    'total' => $request->total,
                    'discount_type' => $request->discount_type,
                    'discount_amount' => $request->discount_amount,
                    'final_total' => $request->final_total,
                ]
            );

            // Process products
            $this->processProducts($request, $purchase);

            // --- Server-side authoritative total calculation ---
            // Calculate sum of product totals stored on the purchase (prevent client manipulation)
            try {
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
            } catch (\Exception $e) {
                Log::error('Error calculating server-side purchase totals: ' . $e->getMessage());
                // If calculation fails, fall back to client provided totals (already saved earlier)
            }

            // ✅ CRITICAL FIX: Add safety flag to prevent duplicate ledger entries during same request
            static $processedPurchases = [];
            $purchaseKey = 'purchase_' . $purchase->id;
            
            if (!isset($processedPurchases[$purchaseKey])) {
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

            // Handle payment if paid_amount is provided
            if ($request->paid_amount > 0) {
                // ✅ CRITICAL FIX: Add safety flag for payments too
                $paymentKey = 'payment_' . $purchase->id . '_' . $request->paid_amount;
                if (!isset($processedPurchases[$paymentKey])) {
                    $this->handlePayment($request, $purchase);
                    $processedPurchases[$paymentKey] = true;
                }
            }

            // Note: UnifiedLedgerService automatically handles balance calculations
        });

        return response()->json(['status' => 200, 'message' => 'Purchase ' . ($purchaseId ? 'updated' : 'recorded') . ' successfully!']);
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
                        // ✅ ENHANCED: Update existing product with better handling
                        $existingProduct = $existingProducts->get($productId);
                        $oldQuantity = $existingProduct->quantity;
                        $newQuantity = $productData['quantity'];
                        $quantityDifference = $newQuantity - $oldQuantity;
                        
                        Log::info('Purchase edit: Processing existing product', [
                            'product_id' => $productId,
                            'old_quantity' => $oldQuantity,
                            'new_quantity' => $newQuantity,
                            'quantity_difference' => $quantityDifference,
                            'purchase_id' => $purchase->id,
                            'batch_id' => $existingProduct->batch_id
                        ]);

                        // ONLY update stock if there's actually a quantity change
                        if ($quantityDifference != 0) {
                            $this->updateProductStock($existingProduct, $quantityDifference, $request->location_id);
                        }

                        // Update purchase product record with all new data
                        $existingProduct->update([
                            'quantity' => $newQuantity,
                            'price' => $productData['price'] ?? $productData['unit_cost'],
                            'discount_percent' => $productData['discount_percent'] ?? 0,
                            'unit_cost' => $productData['unit_cost'],
                            'wholesale_price' => $productData['wholesale_price'],
                            'special_price' => $productData['special_price'],
                            'retail_price' => $productData['retail_price'],
                            'max_retail_price' => $productData['max_retail_price'],
                            'total' => $productData['total'],
                        ]);

                        // ✅ ENHANCED: Update batch prices (but NOT quantity - that's handled by updateProductStock)
                        $batch = Batch::find($existingProduct->batch_id);
                        if ($batch) {
                            $batch->update([
                                'wholesale_price' => $productData['wholesale_price'],
                                'special_price' => $productData['special_price'],
                                'retail_price' => $productData['retail_price'],
                                'max_retail_price' => $productData['max_retail_price'],
                                // Update batch number and expiry date if provided
                                'batch_no' => $productData['batch_no'] ?? $batch->batch_no,
                                'expiry_date' => $productData['expiry_date'] ?? $batch->expiry_date,
                            ]);
                            
                            Log::info('Updated batch prices and details for existing product', [
                                'batch_id' => $batch->id,
                                'product_id' => $productId,
                                'new_batch_no' => $productData['batch_no'] ?? $batch->batch_no,
                                'retail_price' => $productData['retail_price'],
                                'expiry_date' => $productData['expiry_date'] ?? $batch->expiry_date,
                            ]);
                        } else {
                            Log::error('Batch not found for existing product during edit', [
                                'batch_id' => $existingProduct->batch_id,
                                'product_id' => $productId,
                                'purchase_id' => $purchase->id
                            ]);
                        }
                    } else {
                        // ✅ ENHANCED: Add new product to purchase with unique batch creation
                        Log::info('Purchase edit: Adding new product to existing purchase', [
                            'product_id' => $productId,
                            'quantity' => $productData['quantity'],
                            'purchase_id' => $purchase->id,
                            'purchase_reference_no' => $purchase->reference_no
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

    private function updateProductStock($existingProduct, $quantityDifference, $locationId)
    {
        // CRITICAL FIX: Add validation to prevent double processing
        if ($quantityDifference == 0) {
            Log::info('No stock update needed - quantity difference is zero', [
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
            ['qty' => 0]
        );

        // Store original quantities for logging
        $originalLocationQty = $locationBatch->qty;
        $originalBatchQty = $batch->qty;

        // Validate stock reduction
        if ($originalLocationQty + $quantityDifference < 0) {
            throw new \Exception("Stock quantity cannot be reduced below zero. Location stock: {$originalLocationQty}, trying to change by: {$quantityDifference}");
        }
        
        if ($originalBatchQty + $quantityDifference < 0) {
            throw new \Exception("Batch stock quantity cannot be reduced below zero. Batch stock: {$originalBatchQty}, trying to change by: {$quantityDifference}");
        }

        // CRITICAL: Use DB transaction to ensure atomicity
        DB::transaction(function () use ($locationBatch, $batch, $quantityDifference, $originalLocationQty, $originalBatchQty, $existingProduct) {
            // Update location batch quantity
            $locationBatch->increment('qty', $quantityDifference);
            
            // Update batch quantity
            $batch->increment('qty', $quantityDifference);

            // Create stock history record
            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity' => $quantityDifference,
                'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
                'reference_id' => $existingProduct->purchase_id,
                'reference_type' => 'purchase_edit'
            ]);

            Log::info('Stock updated successfully', [
                'product_id' => $existingProduct->product_id,
                'batch_id' => $batch->id,
                'quantity_difference' => $quantityDifference,
                'location_stock_before' => $originalLocationQty,
                'location_stock_after' => $locationBatch->qty,
                'batch_stock_before' => $originalBatchQty,
                'batch_stock_after' => $batch->qty
            ]);
        });
    }

    private function addNewProductToPurchase($purchase, $productData, $locationId)
    {
        // ✅ CRITICAL FIX: Always create NEW batch for each purchase to maintain proper inventory tracking
        // This ensures proper batch separation and traceability
        
        $batchNo = $productData['batch_no'] ?? null;
        
        // If no batch number provided, generate a unique one
        if (empty($batchNo)) {
            $batchNo = Batch::generateNextBatchNo();
        }
        
        // ✅ ALWAYS CREATE NEW BATCH - Do not reuse existing batches
        // This prevents stock mixing and ensures proper purchase tracking
        $batch = Batch::create([
            'batch_no' => $batchNo . '-' . $purchase->reference_no, // Make it unique per purchase
            'product_id' => $productData['product_id'],
            'unit_cost' => $productData['unit_cost'],
            'expiry_date' => $productData['expiry_date'],
            'qty' => $productData['quantity'],
            'wholesale_price' => $productData['wholesale_price'],
            'special_price' => $productData['special_price'], 
            'retail_price' => $productData['retail_price'],
            'max_retail_price' => $productData['max_retail_price'],
        ]);
        
        Log::info('✅ Created NEW batch for purchase', [
            'batch_id' => $batch->id,
            'batch_no' => $batch->batch_no,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'purchase_ref' => $purchase->reference_no,
            'unit_cost' => $productData['unit_cost'],
            'retail_price' => $productData['retail_price'],
        ]);

        // Create location batch entry
        $locationBatch = LocationBatch::create([
            'batch_id' => $batch->id, 
            'location_id' => $locationId,
            'qty' => $productData['quantity']
        ]);

        // Create purchase product entry
        $purchase->purchaseProducts()->create([
            'product_id' => $productData['product_id'],
            'batch_id' => $batch->id,
            'quantity' => $productData['quantity'],
            'price' => $productData['price'] ?? $productData['unit_cost'],
            'discount_percent' => $productData['discount_percent'] ?? 0,
            'unit_cost' => $productData['unit_cost'],
            'wholesale_price' => $productData['wholesale_price'],
            'special_price' => $productData['special_price'],
            'retail_price' => $productData['retail_price'],
            'max_retail_price' => $productData['max_retail_price'],
            'total' => $productData['total'],
            'location_id' => $locationId,
        ]);

        // Create stock history entry
        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $productData['quantity'],
            'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
        ]);

        // Handle IMEI numbers if provided
        if (isset($productData['imei_numbers']) && !empty($productData['imei_numbers'])) {
            $this->processImeiNumbers($productData, $batch->id, $locationId);
        }
        
        Log::info('✅ Successfully added new product to purchase with new batch', [
            'product_id' => $productData['product_id'],
            'batch_id' => $batch->id,
            'quantity' => $productData['quantity'],
            'location_batch_qty' => $locationBatch->qty
        ]);
    }

    private function removeProductFromPurchase($productToRemove, $locationId)
    {
        $batch = Batch::find($productToRemove->batch_id);
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->first();

        if ($locationBatch) {
            $locationBatch->decrement('qty', $productToRemove->quantity);
        }

        $batch->decrement('qty', $productToRemove->quantity);

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => -$productToRemove->quantity,
            'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
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

    private function handlePayment($request, $purchase)
    {
        // Calculate the total due and total paid for the purchase
        $totalPaid = Payment::where('reference_id', $purchase->id)
            ->where('payment_type', 'purchase')
            ->sum('amount');
        $totalDue = $purchase->final_total - $totalPaid;

        // Validate payment amount
        if ($request->paid_amount <= 0) {
            throw new Exception('Payment amount must be greater than zero.');
        }

        // Handle overpayment scenario
        $paidAmount = $this->validatePaymentAmount($request->paid_amount, $totalDue, $purchase);

        $paymentDate = $request->paid_date ? Carbon::parse($request->paid_date) : now();

        // Parse cheque dates properly using the same flexible date parser as PaymentController
        $chequeReceivedDate = $request->cheque_received_date ? $this->parseFlexibleDate($request->cheque_received_date) : null;
        $chequeValidDate = $request->cheque_valid_date ? $this->parseFlexibleDate($request->cheque_valid_date) : null;

        $payment = Payment::create([
            'payment_date' => $paymentDate,
            'amount' => $paidAmount,
            'payment_method' => $request->payment_method,
            'reference_no' => $purchase->reference_no,
            'notes' => $request->payment_note,
            'payment_type' => 'purchase',
            'reference_id' => $purchase->id,
            'supplier_id' => $purchase->supplier_id,
            'card_number' => $request->card_number,
            'card_holder_name' => $request->card_holder_name,
            'card_expiry_month' => $request->card_expiry_month,
            'card_expiry_year' => $request->card_expiry_year,
            'card_security_code' => $request->card_security_code,
            'cheque_number' => $request->cheque_number,
            'cheque_bank_branch' => $request->cheque_bank_branch,
            'cheque_received_date' => $chequeReceivedDate,
            'cheque_valid_date' => $chequeValidDate,
            'cheque_given_by' => $request->cheque_given_by,
        ]);

        // Record payment in ledger using the unified service
        $this->unifiedLedgerService->recordPurchasePayment($payment, $purchase);

        // Update the total_paid field and recalculate payment status
        $purchase->updateTotalDue();
        
        // Refresh the purchase to get updated total_paid
        $purchase->refresh();
        
        // Update payment status based on the updated total_paid
        $this->updatePurchasePaymentStatus($purchase);
    }

    /**
     * Validate payment amount and handle overpayment scenarios
     */
    private function validatePaymentAmount($requestedAmount, $totalDue, $purchase)
    {
        // Configuration option - you can change this behavior
        $allowOverpayment = true; // Set to false to restrict overpayments
        
        if ($requestedAmount > $totalDue) {
            if ($allowOverpayment) {
                // Log a warning about overpayment but allow the transaction
                Log::warning("Overpayment detected for purchase {$purchase->reference_no}. Total due: {$totalDue}, Payment amount: {$requestedAmount}");
                return $requestedAmount; // Allow full amount
            } else {
                // Restrict to total due amount
                Log::info("Payment amount restricted to total due for purchase {$purchase->reference_no}. Requested: {$requestedAmount}, Limited to: {$totalDue}");
                return $totalDue;
            }
        }
        
        return $requestedAmount;
    }

    /**
     * Update purchase payment status based on total paid
     */
    private function updatePurchasePaymentStatus($purchase)
    {
        $totalPaid = $purchase->total_paid;
        $finalTotal = $purchase->final_total;
        
        // Use small tolerance for floating point comparison
        $tolerance = 0.01;
        
        if (($finalTotal - $totalPaid) <= $tolerance) {
            $purchase->payment_status = 'Paid';
        } elseif ($totalPaid > $tolerance) {
            $purchase->payment_status = 'Partial';
        } else {
            $purchase->payment_status = 'Due';
        }

        $purchase->save();
    }

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
            $this->updatePurchasePaymentStatus($purchase);

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
                $this->updatePurchasePaymentStatus($purchase);
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
            // Start with base query
            $query = Purchase::with(['supplier', 'location', 'purchaseProducts', 'payments', 'user']);
            
            // Filter by supplier_id if provided (like sales controller)
            if ($request->has('supplier_id') && $request->supplier_id) {
                $query->where('supplier_id', $request->supplier_id);
            }
            
            // Fetch purchases with related products and payment info
            $purchases = $query->get();

            // Check if purchases are found
            if ($purchases->isEmpty()) {
                return response()->json(['message' => 'No purchases found.'], 404);
            }

            // Return the purchases along with related purchase products and payment info
            return response()->json(['purchases' => $purchases], 200);
        } catch (\Exception $e) {
            // Log the exception and return a generic error message
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
        // ✅ OPTIMIZED: Fast purchase data for AJAX calls
        $purchase = Purchase::with([
            'supplier:id,first_name,last_name,mobile_no,email', // Fix: Use actual supplier table columns
            'location:id,name',
            'payments:id,reference_id,payment_method,amount,reference_no' // Fix: Use correct payment columns
        ])->select([
            'id', 'reference_no', 'supplier_id', 'location_id', 
            'purchase_date', 'total_amount', 'discount_amount', 
            'final_total', 'payment_status', 'notes'
        ])->find($id);

        if (!$purchase) {
            return response()->json(['message' => 'Purchase not found.'], 404);
        }

        return response()->json([
            'status' => 200,
            'purchase' => $purchase,
            'meta' => ['load_time' => microtime(true) - LARAVEL_START]
        ]);
    }

    /**
     * ✅ NEW: Fast AJAX endpoint for getting purchase products for editing
     * Optimized for speed with minimal database queries
     */
    public function getPurchaseProductsForEdit($id)
    {
        $startTime = microtime(true);
        
        // Get purchase with only essential data
        $purchase = Purchase::select(['id', 'reference_no', 'location_id'])->find($id);
        
        if (!$purchase) {
            return response()->json(['message' => 'Purchase not found.'], 404);
        }

        // ✅ SINGLE OPTIMIZED QUERY: Get all purchase products with necessary relations
        $purchaseProducts = \App\Models\PurchaseProduct::with([
            'product:id,product_name,sku', // Fix: Use actual product table column
            'product.unit:id,short_name,allow_decimal',
            'batch:id,batch_no,expiry_date'
        ])->where('purchase_id', $id)
        ->select([
            'id', 'purchase_id', 'product_id', 'batch_id', 'quantity',
            'price', 'unit_cost', 'discount_percent', 'total',
            'wholesale_price', 'special_price', 'retail_price', 'max_retail_price'
        ])->get();

        // ✅ BATCH QUERY: Get all location batch quantities at once
        $batchIds = $purchaseProducts->pluck('batch_id')->unique()->toArray();
        $locationBatches = [];
        
        if (!empty($batchIds)) {
            $locationBatches = \App\Models\LocationBatch::where('location_id', $purchase->location_id)
                ->whereIn('batch_id', $batchIds)
                ->pluck('qty', 'batch_id')
                ->toArray();
        }

        // ✅ ENHANCE DATA: Add computed fields efficiently
        $enhancedProducts = $purchaseProducts->map(function($product) use ($locationBatches) {
            return [
                'id' => $product->id,
                'product_id' => $product->product_id,
                'product_name' => $product->product?->product_name, // Fix: Use actual column name
                'product_sku' => $product->product?->sku,
                'batch_id' => $product->batch_id,
                'batch_number' => $product->batch?->batch_no ?? '',
                'expiry_date' => $product->batch?->expiry_date ? 
                    \Carbon\Carbon::parse($product->batch->expiry_date)->format('Y-m-d') : null,
                'quantity' => $product->quantity,
                'price' => $product->price,
                'unit_cost' => $product->unit_cost,
                'discount_percent' => $product->discount_percent,
                'wholesale_price' => $product->wholesale_price,
                'special_price' => $product->special_price,
                'retail_price' => $product->retail_price,
                'max_retail_price' => $product->max_retail_price,
                'total' => $product->total,
                'current_stock' => $locationBatches[$product->batch_id] ?? 0,
                'allow_decimal' => $product->product?->unit?->allow_decimal ?? true,
                'unit_name' => $product->product?->unit?->short_name
            ];
        });

        $loadTime = microtime(true) - $startTime;

        return response()->json([
            'status' => 200,
            'products' => $enhancedProducts,
            'meta' => [
                'count' => $enhancedProducts->count(),
                'load_time' => round($loadTime, 4),
                'purchase_reference' => $purchase->reference_no
            ]
        ]);
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
        try {
            // ✅ OPTIMIZED: Single query with all necessary eager loading
            $purchase = Purchase::with([
                'supplier:id,first_name,last_name,mobile_no,email', // Fix: Use actual supplier table columns
                'location:id,name', // Only load needed location fields
                'purchaseProducts.batch:id,batch_no,expiry_date,product_id',
                'purchaseProducts.product:id,product_name,sku', // Fix: Use actual product table column
                'purchaseProducts.product.unit:id,short_name,allow_decimal', // Unit information
                'payments:id,reference_id,payment_method,amount,reference_no' // Fix: Use correct payment columns
            ])->find($id);

            if (!$purchase) {
                return response()->json(['message' => 'Purchase not found.'], 404);
            }

        // ✅ PERFORMANCE FIX: Single query to get all location batches at once
        $batchIds = $purchase->purchaseProducts->pluck('batch_id')->unique()->toArray();
        $locationBatches = [];
        
        if (!empty($batchIds)) {
            $locationBatches = \App\Models\LocationBatch::where('location_id', $purchase->location_id)
                ->whereIn('batch_id', $batchIds)
                ->pluck('qty', 'batch_id') // Create array with batch_id as key
                ->toArray();
        }

        // ✅ ENHANCED: Add computed fields efficiently with proper date formatting
        $purchase->purchaseProducts->each(function($purchaseProduct) use ($locationBatches) {
            // Get current stock from pre-loaded location batches
            $purchaseProduct->current_stock = $locationBatches[$purchaseProduct->batch_id] ?? 0;
            
            // Add batch information with proper null handling
            $purchaseProduct->batch_number = $purchaseProduct->batch?->batch_no ?? '';
            $purchaseProduct->expiry_date = $purchaseProduct->batch?->expiry_date ? 
                $purchaseProduct->batch->expiry_date->format('Y-m-d') : null;
            
            // Add unit validation information
            $purchaseProduct->allow_decimal = $purchaseProduct->product?->unit?->allow_decimal ?? true;
            
            // Add computed totals for frontend
            $purchaseProduct->line_total = $purchaseProduct->quantity * $purchaseProduct->price;
        });

        // ✅ FORMAT DATES: Ensure all dates are properly formatted for frontend
        $formattedPurchase = $purchase->toArray();
        $formattedPurchase['purchase_date'] = $purchase->purchase_date ? 
            $purchase->purchase_date->format('Y-m-d') : date('Y-m-d');
        
        // Format payment dates if they exist
        if (isset($formattedPurchase['payments'])) {
            foreach ($formattedPurchase['payments'] as &$payment) {
                $payment['payment_date'] = isset($payment['payment_date']) ? 
                    \Carbon\Carbon::parse($payment['payment_date'])->format('Y-m-d') : null;
                $payment['cheque_received_date'] = isset($payment['cheque_received_date']) ? 
                    \Carbon\Carbon::parse($payment['cheque_received_date'])->format('Y-m-d') : null;
                $payment['cheque_valid_date'] = isset($payment['cheque_valid_date']) ? 
                    \Carbon\Carbon::parse($payment['cheque_valid_date'])->format('Y-m-d') : null;
            }
        }

        // ✅ FAST RESPONSE: Return properly formatted data structure
        if (request()->ajax() || request()->is('api/*')) {
            return response()->json([
                'status' => 200, 
                'purchase' => $formattedPurchase,
                'meta' => [
                    'products_count' => $purchase->purchaseProducts->count(),
                    'total_amount' => $purchase->final_total,
                    'payment_status' => $purchase->payment_status,
                    'load_time' => microtime(true) - LARAVEL_START,
                    'formatted_dates' => true,
                    'null_checks_applied' => true
                ]
            ], 200);
        }

        return view('purchase.add_purchase', ['purchase' => $formattedPurchase]);
        
        } catch (\Exception $e) {
            \Log::error('Error in editPurchase: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (request()->ajax() || request()->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error loading purchase data: ' . $e->getMessage(),
                    'debug' => config('app.debug') ? [
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ] : null
                ], 500);
            }
            
            return back()->with('error', 'Error loading purchase data.');
        }
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
     * Parse date string with flexible format support
     */
    private function parseFlexibleDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        // Try different date formats
        $formats = [
            'Y-m-d',      // HTML5 date input format (2024-01-15)
            'd-m-Y',      // Legacy format (15-01-2024)
            'm/d/Y',      // US format (01/15/2024)
            'd/m/Y',      // European format (15/01/2024)
            'Y/m/d',      // ISO-like format (2024/01/15)
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date && $date->format($format) === $dateString) {
                    return $date->format('Y-m-d');
                }
            } catch (Exception $e) {
                continue;
            }
        }

        // If no specific format works, try Carbon's general parsing
        try {
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (Exception $e) {
            Log::warning('Could not parse date: ' . $dateString);
            return null;
        }
    }
}