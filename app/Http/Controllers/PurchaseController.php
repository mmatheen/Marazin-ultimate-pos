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
        return view('purchase.list_purchase');
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

            // Handle ledger entries properly
            // Handle ledger recording/updating
            if ($isUpdate) {
                // For updates, use updatePurchase method to handle cleanup and recreation
                $this->unifiedLedgerService->updatePurchase($purchase);
            } else {
                // Record purchase in ledger for new purchases
                $this->unifiedLedgerService->recordPurchase($purchase);
            }

            // Handle payment if paid_amount is provided
            if ($request->paid_amount > 0) {
                $this->handlePayment($request, $purchase);
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

        collect($request->products)->chunk(100)->each(function ($productsChunk) use ($purchase, $existingProducts, $request) {
            foreach ($productsChunk as $productData) {
                $productId = $productData['product_id'];

                if ($existingProducts->has($productId)) {
                    // Update existing product
                    $existingProduct = $existingProducts->get($productId);
                    $quantityDifference = $productData['quantity'] - $existingProduct->quantity;
                    $this->updateProductStock($existingProduct, $quantityDifference, $request->location_id);

                    $existingProduct->update([
                        'quantity' => $productData['quantity'],
                        'unit_cost' => $productData['unit_cost'],
                        'wholesale_price' => $productData['wholesale_price'],
                        'special_price' => $productData['special_price'],
                        'retail_price' => $productData['retail_price'],
                        'max_retail_price' => $productData['max_retail_price'],
                        'total' => $productData['total'],
                    ]);
                } else {
                    // Add new product
                    $this->addNewProductToPurchase($purchase, $productData, $request->location_id);
                }
            }
        });

        // Remove products not present in the request
        $requestProductIds = collect($request->products)->pluck('product_id')->toArray();
        $productsToRemove = $existingProducts->whereNotIn('product_id', $requestProductIds);

        foreach ($productsToRemove as $productToRemove) {
            $this->removeProductFromPurchase($productToRemove, $request->location_id);
        }
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
        $batch = Batch::find($existingProduct->batch_id);
        $locationBatch = LocationBatch::firstOrCreate(
            ['batch_id' => $batch->id, 'location_id' => $locationId],
            ['qty' => 0]
        );

        if ($locationBatch->qty + $quantityDifference < 0) {
            throw new Exception("Stock quantity cannot be reduced below zero");
        }
        $locationBatch->increment('qty', $quantityDifference);

        if ($batch->qty + $quantityDifference < 0) {
            throw new Exception("Batch stock quantity cannot be reduced below zero");
        }
        $batch->increment('qty', $quantityDifference);

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $quantityDifference,
            'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
        ]);
    }

    private function addNewProductToPurchase($purchase, $productData, $locationId)
    {
        $batch = Batch::firstOrCreate(
            [
                'batch_no' => $productData['batch_no'] ?? Batch::generateNextBatchNo(),
                'product_id' => $productData['product_id'],
                'unit_cost' => $productData['unit_cost'],
                'expiry_date' => $productData['expiry_date'],
            ],
            [
                'qty' => $productData['quantity'],
                'wholesale_price' => $productData['wholesale_price'],
                'special_price' => $productData['special_price'],
                'retail_price' => $productData['retail_price'],
                'max_retail_price' => $productData['max_retail_price'],
            ]
        );

        $locationBatch = LocationBatch::firstOrCreate(
            ['batch_id' => $batch->id, 'location_id' => $locationId],
            ['qty' => 0]
        );
        $locationBatch->increment('qty', $productData['quantity']);

        $purchase->purchaseProducts()->updateOrCreate(
            ['product_id' => $productData['product_id'], 'batch_id' => $batch->id, 'purchase_id' => $purchase->id],
            [
                'quantity' => $productData['quantity'],
                'unit_cost' => $productData['unit_cost'],
                'wholesale_price' => $productData['wholesale_price'],
                'special_price' => $productData['special_price'],
                'retail_price' => $productData['retail_price'],
                'max_retail_price' => $productData['max_retail_price'],
                'total' => $productData['total'],
                'location_id' => $locationId,
            ]
        );

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $productData['quantity'],
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
            'cheque_received_date' => $request->cheque_received_date,
            'cheque_valid_date' => $request->cheque_valid_date,
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

    public function getAllPurchase()
    {
        try {
            // Fetch all purchases with related products and payment info
            $purchases = Purchase::with(['supplier', 'location', 'purchaseProducts', 'payments', 'user'])->get();

            // $purchases = Purchase::with(['purchaseProducts'])->get();

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
            return response()->json(['status' => 200, 'purchase' => $purchase], 200);
        }

        return view('purchase.add_purchase', ['purchase' => $purchase]);
    }
}
