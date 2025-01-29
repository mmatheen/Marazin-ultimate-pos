<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\Product;
use App\Models\PaymentInfo;  // Ensure this is included at the top of your controller
use App\Models\Batch;
use App\Models\Stock;
use App\Models\PurchaseProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\LocationBatch;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnProduct;
use App\Models\StockHistory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    public function listPurchase()
    {
        return view('purchase.list_purchase');
    }

    public function AddPurchase()
    {
        return view('purchase.add_purchase');
    }

    public function store(Request $request)
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
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_cost' => 'required|numeric|min:0',
            'products.*.wholesale_price' => 'required|numeric|min:0',
            'products.*.special_price' => 'required|numeric|min:0',
            'products.*.retail_price' => 'required|numeric|min:0',
            'products.*.max_retail_price' => 'required|numeric|min:0',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.total' => 'required|numeric|min:0',
            'products.*.batch_no' => 'nullable|string|max:255',
            'products.*.expiry_date' => 'nullable|date',
            'advance_balance' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'payment_account' => 'nullable|string',
            'payment_note' => 'nullable|string',
            'paid_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        DB::transaction(function () use ($request) {
            $attachedDocument = $this->handleAttachedDocument($request);
            $referenceNo = $this->generateReferenceNo();

            // Create the purchase record
            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'reference_no' => $referenceNo,
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
                'payment_status' => 'Due', // Initial status
            ]);

            // Process each product in the purchase
            foreach ($request->products as $productData) {
                // Fetch the product details
                $product = Product::find($productData['product_id']);
                $batch = Batch::firstOrCreate(
                    [
                        'batch_no' => $productData['batch_no'] ?? $this->generateBatchNo($productData['product_id']),
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

                // Update the quantity in the batch table
                if (!$batch->wasRecentlyCreated) {
                    $batch->increment('qty', $productData['quantity']);
                }

                // Check if the location batch already exists or create a new one
                $locationBatch = LocationBatch::firstOrCreate(
                    [
                        'batch_id' => $batch->id,
                        'location_id' => $request->location_id,
                    ],
                    [
                        'qty' => $productData['quantity'],
                    ]
                );

                // Update the quantity in the location batch if it already exists
                if (!$locationBatch->wasRecentlyCreated) {
                    $locationBatch->increment('qty', $productData['quantity']);
                }

                // Update location_product table
                $product->locations()->updateExistingPivot($request->location_id, ['qty' => DB::raw('qty + ' . $productData['quantity'])]);

                // Record stock history as purchase stock
                StockHistory::create([
                    'loc_batch_id' => $locationBatch->id,
                    'batch_id' => $batch->id, // Add batch_id to stock history
                    'quantity' => $productData['quantity'],
                    'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
                ]);

                // Create the purchase product record
                PurchaseProduct::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'batch_id' => $batch->id,
                    'location_id' => $request->location_id,
                    'quantity' => $productData['quantity'],
                    'unit_cost' => $productData['unit_cost'],
                    'wholesale_price' => $productData['wholesale_price'],
                    'special_price' => $productData['special_price'],
                    'retail_price' => $productData['retail_price'],
                    'max_retail_price' => $productData['max_retail_price'],
                    'price' => $productData['price'],
                    'total' => $productData['total'],
                ]);

                // Handle initial payment if provided
                if ($request->advance_balance > 0) {
                    PurchasePayment::create([
                        'purchase_id' => $purchase->id,
                        'supplier_id' => $request->supplier_id,
                        'amount' => $request->advance_balance,
                        'payment_method' => $request->payment_method,
                        'payment_account' => $request->payment_account,
                        'payment_date' => $request->paid_date,
                        'payment_note' => $request->payment_note,
                    ]);
                }
            }

            // Update purchase payment status and due amount
            $purchase->updatePaymentStatus();
        });

        return response()->json(['status' => 200, 'message' => 'Purchase recorded successfully!']);
    }

    private function generateReferenceNo()
    {
        return 'PUR-' . now()->format('YmdHis') . '-' . strtoupper(uniqid());
    }

    private function handleAttachedDocument($request)
    {
        if ($request->hasFile('attached_document')) {
            return $request->file('attached_document')->store('documents');
        }
        return null;
    }

    private function generateBatchNo($productId)
    {
        return 'batch-' . $productId + 1;
    }

    public function update(Request $request, $purchaseId)
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
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_cost' => 'required|numeric|min:0',
            'products.*.wholesale_price' => 'required|numeric|min:0',
            'products.*.special_price' => 'required|numeric|min:0',
            'products.*.retail_price' => 'required|numeric|min:0',
            'products.*.max_retail_price' => 'required|numeric|min:0',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.total' => 'required|numeric|min:0',
            'products.*.batch_no' => 'nullable|string|max:255',
            'products.*.expiry_date' => 'nullable|date',
            'advance_balance' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'payment_account' => 'nullable|string',
            'payment_note' => 'nullable|string',
            'paid_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        DB::transaction(function () use ($request, $purchaseId) {
            // Fetch the existing purchase
            $purchase = Purchase::findOrFail($purchaseId);

            // Fetch existing purchase products
            $existingProducts = $purchase->purchaseProducts;  // Use the correct relationship

            // Update the purchase record
            $purchase->update([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'purchasing_status' => $request->purchasing_status,
                'location_id' => $request->location_id,
                'pay_term' => $request->pay_term,
                'pay_term_type' => $request->pay_term_type,
                'attached_document' => $this->handleAttachedDocument($request),
                'total' => $request->total,
                'discount_type' => $request->discount_type,
                'discount_amount' => $request->discount_amount,
                'final_total' => $request->final_total,
                'payment_status' => $request->payment_status,
            ]);

            // Process each product in the purchase
            foreach ($request->products as $productData) {
                $product = Product::find($productData['product_id']);
                $existingProduct = $existingProducts->where('product_id', $productData['product_id'])->first();

                if ($existingProduct) {
                    // Calculate the difference in quantity
                    $quantityDifference = $productData['quantity'] - $existingProduct->quantity;

                    // Fetch the batch and location batch
                    $batch = Batch::find($existingProduct->batch_id);
                    $locationBatch = LocationBatch::where('batch_id', $batch->id)
                        ->where('location_id', $request->location_id)
                        ->first();

                    if ($locationBatch) {
                        // Adjust the stock based on the difference
                        $locationBatch->increment('qty', $quantityDifference);
                    }

                    // Update the batch quantity if it already exists
                    // $model->increment('column', amount);
                    $batch->increment('qty', $quantityDifference);

                    // Update the product location quantity
                    $product->locations()->updateExistingPivot($request->location_id, ['qty' => DB::raw('qty + ' . $quantityDifference)]);

                    // Record stock history as purchase stock
                    StockHistory::create([
                        'loc_batch_id' => $locationBatch->id,
                        'quantity' => $quantityDifference,
                        'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
                    ]);

                    // Update the existing purchase product record
                    $existingProduct->update([
                        'quantity' => $productData['quantity'],
                        'unit_cost' => $productData['unit_cost'],
                        'wholesale_price' => $productData['wholesale_price'],
                        'special_price' => $productData['special_price'],
                        'retail_price' => $productData['retail_price'],
                        'max_retail_price' => $productData['max_retail_price'],
                        'price' => $productData['price'],
                        'total' => $productData['total'],
                    ]);
                } else {
                    // Handle new product addition
                    $batch = Batch::firstOrCreate(
                        [
                            'batch_no' => $productData['batch_no'] ?? $this->generateBatchNo($productData['product_id']),
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

                    // Update the quantity in the batch table
                    if (!$batch->wasRecentlyCreated) {
                        $batch->increment('qty', $productData['quantity']);
                    }

                    $locationBatch = LocationBatch::firstOrCreate(
                        [
                            'batch_id' => $batch->id,
                            'location_id' => $request->location_id,
                        ],
                        [
                            'qty' => $productData['quantity'],
                        ]
                    );

                    if (!$locationBatch->wasRecentlyCreated) {
                        $locationBatch->increment('qty', $productData['quantity']);
                    }

                    $product->locations()->updateExistingPivot($request->location_id, ['qty' => DB::raw('qty + ' . $productData['quantity'])]);

                    // Record stock history as purchase stock
                    StockHistory::create([
                        'loc_batch_id' => $locationBatch->id,
                        'quantity' => $productData['quantity'],
                        'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
                    ]);

                    PurchaseProduct::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $productData['product_id'],
                        'batch_id' => $batch->id,
                        'location_id' => $request->location_id,
                        'quantity' => $productData['quantity'],
                        'unit_cost' => $productData['unit_cost'],
                        'wholesale_price' => $productData['wholesale_price'],
                        'special_price' => $productData['special_price'],
                        'retail_price' => $productData['retail_price'],
                        'max_retail_price' => $productData['max_retail_price'],
                        'price' => $productData['price'],
                        'total' => $productData['total'],
                    ]);
                }
            }

            // Handle payment updates if necessary
            if ($request->advance_balance > 0) {
                PurchasePayment::updateOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'supplier_id' => $request->supplier_id,
                        'amount' => $request->advance_balance,
                        'payment_method' => $request->payment_method,
                        'payment_account' => $request->payment_account,
                        'payment_date' => $request->paid_date,
                        'payment_note' => $request->payment_note,
                    ]
                );
            }

            // Update purchase payment status and due amount
            $purchase->updatePaymentStatus();
        });

        return response()->json(['status' => 200, 'message' => 'Purchase updated successfully!']);
    }

    public function getAllPurchase()
    {
        // Fetch all purchases with related products and payment info
        $purchases = Purchase::with(['supplier', 'location', 'purchaseProducts', 'purchasePayment'])->get();

        // Check if purchases are found
        if ($purchases->isEmpty()) {
            return response()->json(['message' => 'No purchases found.'], 404);
        }

        // Return the purchases along with related purchase products and payment info
        return response()->json(['purchases' => $purchases], 200);
    }

    public function getAllPurchaseProduct(int $id)
    {
        // Fetch the specific purchase by ID with related products and payment info
        $purchase = Purchase::with(['supplier', 'location', 'purchaseProducts.product', 'purchasePayment'])->find($id);

        // Check if the purchase is found
        if (!$purchase) {
            return response()->json(['message' => 'No purchase product found.'], 404);
        }

        // Return the purchase along with related purchase products and payment info
        return response()->json(['purchase' => $purchase], 200);
    }


    public function getPurchaseProductsBySupplier($supplierId)
    {
        try {
            // Fetch purchases related to the supplier with their products and specific batches
            $purchases = Purchase::with(['purchaseProducts.product', 'purchaseProducts.batch'])
                ->where('supplier_id', $supplierId)
                ->get();

            // Check if any purchases are found
            if ($purchases->isEmpty()) {
                return response()->json(['message' => 'No purchases found for this supplier.'], 404);
            }

            // Return the purchases along with related purchase products and batches
            return response()->json(['purchases' => $purchases], 200);
        } catch (\Exception $e) {
            // Return a JSON response with the error message
            return response()->json(['message' => 'An error occurred while fetching purchase products.'], 500);
        }
    }
}
