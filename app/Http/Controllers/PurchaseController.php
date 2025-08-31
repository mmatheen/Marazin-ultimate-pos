<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Batch;
use App\Models\Ledger;
use Illuminate\Http\Request;
use App\Models\LocationBatch;
use App\Models\Payment;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view purchase', ['only' => ['listPurchase']]);
        $this->middleware('permission:add purchase', ['only' => ['AddPurchase']]);
        $this->middleware('permission:create purchase', ['only' => ['storeOrUpdate']]);
        $this->middleware('permission:edit purchase', ['only' => ['editPurchase', 'storeOrUpdate']]);
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
            'paid_amount' => 'nullable|numeric|min:0',
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

            // Clean up existing ledger entries if this is an update
            if ($purchaseId) {
                Ledger::where('reference_no', $purchase->reference_no)
                    ->where('contact_type', 'supplier')
                    ->delete();
            }

            // Insert ledger entry for the purchase
            Ledger::create([
                'transaction_date' => $request->purchase_date,
                'reference_no' => $purchase->reference_no,
                'transaction_type' => 'purchase',
                'debit' => $request->final_total,
                'credit' => 0,
                'balance' => $this->calculateNewBalance($request->supplier_id, $request->final_total, 0),
                'contact_type' => 'supplier',
                'user_id' => $request->supplier_id,
            ]);

            // Handle payment if paid_amount is provided
            if ($request->paid_amount > 0) {
                $this->handlePayment($request, $purchase);
            }

            // Update supplier's balance
            $this->updateSupplierBalance($purchase->supplier_id);
        });

        return response()->json(['status' => 200, 'message' => 'Purchase ' . ($purchaseId ? 'updated' : 'recorded') . ' successfully!']);
    }

    private function calculateNewBalance($userId, $debitAmount, $creditAmount)
    {
        $lastLedger = Ledger::where('user_id', $userId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $previousBalance = $lastLedger ? $lastLedger->balance : 0;
        return $previousBalance + $debitAmount - $creditAmount;
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

    private function handlePayment($request, $purchase)
    {
        // Calculate the total due and total paid for the purchase
        $totalPaid = Payment::where('reference_id', $purchase->id)->sum('amount');
        $totalDue = $purchase->final_total - $totalPaid;

        // If the paid amount exceeds total due, adjust it
        $paidAmount = min($request->paid_amount, $totalDue);

        $payment = Payment::create([
            'payment_date' => $request->paid_date ? \Carbon\Carbon::parse($request->paid_date) : now(),
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

        // Create ledger entry for the payment
        Ledger::create([
            'transaction_date' => $payment->payment_date,
            'reference_no' => $purchase->reference_no,
            'transaction_type' => 'payments',
            'debit' => 0,
            'credit' => $paidAmount,
            'balance' => $this->calculateNewBalance($purchase->supplier_id, 0, $paidAmount),
            'contact_type' => 'supplier',
            'user_id' => $purchase->supplier_id,
        ]);

        // Update purchase payment status based on total due
        $newTotalPaid = $totalPaid + $paidAmount;
        if ($purchase->final_total - $newTotalPaid <= 0) {
            $purchase->payment_status = 'Paid';
        } elseif ($newTotalPaid > 0) {
            $purchase->payment_status = 'Partial';
        } else {
            $purchase->payment_status = 'Due';
        }

        $purchase->save();
    }

    private function updateSupplierBalance($supplierId)
    {
        $supplier = Supplier::find($supplierId);

        $totalPurchases = Purchase::where('supplier_id', $supplierId)->sum('final_total');
        $totalPayments = Payment::where('supplier_id', $supplierId)->where('payment_type', 'purchase')->sum('amount');

        $supplier->current_balance = $supplier->opening_balance + $totalPurchases - $totalPayments;
        $supplier->save();
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

    public function getPurchaseProductsBySupplier($supplierId)
    {
        try {
            $purchases = Purchase::with(['purchaseProducts.product.unit', 'purchaseProducts.batch'])
                ->where('supplier_id', $supplierId)
                ->get();

            if ($purchases->isEmpty()) {
                return response()->json(['message' => 'No purchases found for this supplier.'], 404);
            }

            $products = [];

            foreach ($purchases as $purchase) {
                foreach ($purchase->purchaseProducts as $purchaseProduct) {
                    $productId = $purchaseProduct->product_id;
                    if (!isset($products[$productId])) {
                        $products[$productId] = [
                            'product' => $purchaseProduct->product,
                            'unit' => $purchaseProduct->product->unit ?? null,
                            'purchases' => []
                        ];
                    }
                    $products[$productId]['purchases'][] = [
                        'purchase_id' => $purchase->id,
                        'batch_id' => $purchaseProduct->batch_id,
                        'quantity' => $purchaseProduct->quantity,
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

            return response()->json(['products' => array_values($products)], 200);
        } catch (\Exception $e) {
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
