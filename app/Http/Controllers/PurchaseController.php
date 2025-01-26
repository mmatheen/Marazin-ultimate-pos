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




    // public function store(Request $request)
    // {
    //     // Validate the request
    //     $validated = $request->validate([
    //         'purchase_date' => 'required|date_format:Y-m-d',
    //         'supplier_id' => 'required|exists:suppliers,id',
    //         'location_id' => 'required|exists:locations,id',
    //         'discount_type' => 'nullable|in:percent,fixed',
    //         'discount_amount' => 'nullable|numeric',
    //         'payment_method' => 'nullable|string',
    //         'payment_note' => 'nullable|string',
    //         'attach_document' => 'nullable|mimes:jpeg,png,jpg,gif,pdf|max:5120',

    //         'products' => 'required|array',
    //         'products.*.product_id' => 'required|exists:products,id',
    //         'products.*.quantity' => 'required|integer',
    //         'products.*.price' => 'required|numeric',
    //         'products.*.total' => 'required|numeric',
    //         'products.*.expiry_date' => 'nullable|date_format:Y-m-d',
    //         'products.*.batch_id' => 'nullable|string|max:255',
    //     ]);

    //     // Generate the reference number in the format REF-#####
    //     $referenceNo = 'REF-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    //     $total = array_sum(array_column($validated['products'], 'total'));
    //     $finalTotal = $total - ($validated['discount_amount'] ?? 0);

    //     // Handle file upload
    //     $fileName = $request->hasFile('attach_document') ? time() . '.' . $request->file('attach_document')->extension() : null;
    //     if ($fileName) {
    //         $request->file('attach_document')->move(public_path('/assets/documents'), $fileName);
    //     }

    //     $purchaseDate = \Carbon\Carbon::createFromFormat('Y-m-d', $validated['purchase_date'])->format('Y-m-d');

    //     // Create purchase record
    //     $purchase = Purchase::create([
    //         'reference_no' => $referenceNo,
    //         'purchase_date' =>$purchaseDate,
    //         'supplier_id' => $validated['supplier_id'],
    //         'location_id' => $validated['location_id'],
    //         'discount_type' => $validated['discount_type'] ?? null,
    //         'discount_amount' => $validated['discount_amount'] ?? 0,
    //         'attached_document' => $fileName,
    //         'total' => $total,
    //         'final_total' => $finalTotal,
    //         'payment_status' => 'Due',
    //     ]);

    //     foreach ($validated['products'] as $product) {
    //         $batchId = $product['batch_id'] ?? null;
    //         $expiryDate = $product['expiry_date'] ?? null;
    //         $batch = null;

    //         if ($batchId) {
    //             // Check if batch exists
    //             $batch = Batch::where('batch_id', $batchId)
    //                           ->where('product_id', $product['product_id'])
    //                           ->where('price', $product['price'])
    //                           ->first();

    //             if ($batch) {
    //                 // Update existing batch
    //                 $batch->quantity += $product['quantity'];
    //                 $batch->price = $product['price'];
    //                 $batch->expiry_date = $expiryDate ?? $batch->expiry_date;
    //                 $batch->save();
    //             } else {
    //                 // Create new batch
    //                 $batch = Batch::create([
    //                     'batch_id' => $batchId,
    //                     'product_id' => $product['product_id'],
    //                     'price' => $product['price'],
    //                     'quantity' => $product['quantity'],
    //                     'expiry_date' => $expiryDate ?? null,
    //                 ]);
    //             }
    //         } else {
    //             // Create batch with null batch_id
    //             $batch = Batch::create([
    //                 'batch_id' => null,
    //                 'product_id' => $product['product_id'],
    //                 'price' => $product['price'],
    //                 'quantity' => $product['quantity'],
    //                 'expiry_date' => $expiryDate ?? null,
    //             ]);
    //         }

    //         $purchaseProduct = [
    //             'purchase_id' => $purchase->id,
    //             'product_id' => $product['product_id'],
    //             'location_id' => $validated['location_id'],
    //             'quantity' => $product['quantity'],
    //             'price' => $product['price'],
    //             'total' => $product['total'],
    //         ];

    //         if ($batch) {
    //             $purchaseProduct['batch_id'] = $batch->batch_id;
    //         }

    //         PurchaseProduct::create($purchaseProduct);

    //         // Update or Create Stock
    //         $stock = Stock::firstOrNew([
    //             'product_id' => $product['product_id'],
    //             'location_id' => $validated['location_id'],
    //             'batch_id' => $batch ? $batch->batch_id : null,
    //             'stock_type' => "Purchase Stock"
    //         ]);

    //         $stock->quantity += $product['quantity'];
    //         $stock->save();
    //     }

    //     // Create Purchase Payment
    //     PurchasePayment::create([
    //         'purchase_id' => $purchase->id,
    //         'supplier_id' => $validated['supplier_id'],
    //         'payment_method' => $validated['payment_method'],
    //         'payment_account' => $request->payment_account,
    //         'amount' => $finalTotal,
    //         'payment_date' => now(), // Assuming payment is made immediately
    //         'payment_note' => $validated['payment_note'],
    //     ]);

    //     return response()->json(['message' => 'Purchase added successfully!', 'purchase' => $purchase], 201);
    // }

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
            'payment_status' => 'required|in:Paid,Due,Partial',
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
                if (!$batch->wasRecentlyCreated) {
                    $batch->increment('qty', $productData['quantity']);
                }
                // Update the quantity in the location batch if it already exists
                if (!$locationBatch->wasRecentlyCreated) {
                    $locationBatch->increment('qty', $productData['quantity']);
                }

                // Update location_product table
                $product->locations()->updateExistingPivot($request->location_id, ['qty' => DB::raw('qty + ' . $productData['quantity'])]);

                // Record stock history as purchase stock
                StockHistory::create([
                    'loc_batch_id' => $locationBatch->id,
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
            }
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





    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'supplier_id' => 'required|integer|exists:suppliers,id',
    //         'purchase_date' => 'required|date',
    //         'purchasing_status' => 'required|in:Received,Pending,Ordered',
    //         'location_id' => 'required|integer|exists:locations,id',
    //         'pay_term' => 'nullable|integer|min:0',
    //         'pay_term_type' => 'nullable|in:days,months',
    //         'attached_document' => 'nullable|file|max:5120|mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png',
    //         'discount_type' => 'nullable|in:percent,fixed',
    //         'discount_amount' => 'nullable|numeric|min:0',
    //         'total' => 'required|numeric|min:0',
    //         'final_total' => 'required|numeric|min:0',
    //         'payment_status' => 'required|in:Paid,Due,Partial',
    //         'products' => 'required|array',
    //         'products.*.product_id' => 'required|integer|exists:products,id',
    //         'products.*.quantity' => 'required|integer|min:1',
    //         'products.*.unit_cost' => 'required|numeric|min:0',
    //         'products.*.wholesale_price' => 'required|numeric|min:0',
    //         'products.*.special_price' => 'required|numeric|min:0',
    //         'products.*.retail_price' => 'required|numeric|min:0',
    //         'products.*.max_retail_price' => 'required|numeric|min:0',
    //         'products.*.price' => 'required|numeric|min:0',
    //         'products.*.total' => 'required|numeric|min:0',
    //         'products.*.batch_no' => 'nullable|string|max:255',
    //         'products.*.expiry_date' => 'nullable|date',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'errors' => $validator->messages()]);
    //     }

    //     DB::transaction(function () use ($request) {
    //         try {
    //             $attachedDocument = $this->handleAttachedDocument($request);
    //             $referenceNo = $this->generateReferenceNo();

    //             // Create the purchase record
    //             $purchase = Purchase::create([
    //                 'supplier_id' => $request->supplier_id,
    //                 'reference_no' => $referenceNo,
    //                 'purchase_date' => $request->purchase_date,
    //                 'purchasing_status' => $request->purchasing_status,
    //                 'location_id' => $request->location_id,
    //                 'pay_term' => $request->pay_term,
    //                 'pay_term_type' => $request->pay_term_type,
    //                 'attached_document' => $attachedDocument,
    //                 'total' => $request->total,
    //                 'discount_type' => $request->discount_type,
    //                 'discount_amount' => $request->discount_amount,
    //                 'final_total' => $request->final_total,
    //                 'payment_status' => $request->payment_status,
    //             ]);

    //             foreach ($request->products as $productData) {
    //                 $batchId = $this->updateBatchStock($productData, $request->location_id);

    //                 // Get the location batch associated with the batch ID and location ID
    //                 $locationBatch = LocationBatch::where('batch_id', $batchId)
    //                     ->where('location_id', $request->location_id)
    //                     ->first();

    //                 if (!$locationBatch) {
    //                     throw new \Exception('Location batch not found for batch_id: ' . $batchId);
    //                 }

    //                 StockHistory::create([
    //                     'loc_batch_id' => $locationBatch->id,
    //                     'quantity' => $productData['quantity'],
    //                     'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
    //                 ]);

    //                 PurchaseProduct::create([
    //                     'purchase_id' => $purchase->id,
    //                     'product_id' => $productData['product_id'],
    //                     'batch_id' => $batchId,
    //                     'location_id' => $request->location_id,
    //                     'quantity' => $productData['quantity'],
    //                     'unit_cost' => $productData['unit_cost'],
    //                     'wholesale_price' => $productData['wholesale_price'],
    //                     'special_price' => $productData['special_price'],
    //                     'retail_price' => $productData['retail_price'],
    //                     'max_retail_price' => $productData['max_retail_price'],
    //                     'price' => $productData['price'],
    //                     'total' => $productData['total'],
    //                 ]);
    //             }
    //         } catch (\Exception $e) {
    //             // Log the error for debugging
    //             Log::error('Error in Purchase Store: ' . $e->getMessage());
    //             throw $e; // Rethrow the exception to rollback the transaction
    //         }
    //     });

    //     return response()->json(['status' => 200, 'message' => 'Purchase recorded successfully!']);
    // }


    // /**
    //  * Handle the attached document upload.
    //  */


    // /**
    //  * Generate a unique reference number for the purchase.
    //  */
    // private function generateReferenceNo()
    // {
    //     return 'PUR-' . now()->format('YmdHis') . '-' . strtoupper(uniqid());
    // }

    // /**
    //  * Update the batch and stock quantities.
    //  */
    // private function updateBatchStock($productData, $locationId)
    // {
    //     $batch = Batch::firstOrCreate(
    //         [
    //             'batch_no' => $productData['batch_no'] ?? $this->generateBatchNo($productData['product_id']),
    //             'product_id' => $productData['product_id'],
    //             'unit_cost' => $productData['unit_cost'],
    //             'expiry_date' => $productData['expiry_date'],
    //         ],
    //         [
    //             'qty' => $productData['quantity'],
    //             'wholesale_price' => $productData['wholesale_price'],
    //             'special_price' => $productData['special_price'],
    //             'retail_price' => $productData['retail_price'],
    //             'max_retail_price' => $productData['max_retail_price'],
    //         ]
    //     );

    //     if (!$batch->wasRecentlyCreated) {
    //         $batch->increment('qty', $productData['quantity']);
    //     }

    //     $locationBatch = LocationBatch::firstOrCreate(
    //         [
    //             'batch_id' => $batch->id,
    //             'location_id' => $locationId,
    //         ],
    //         ['qty' => $productData['quantity']]
    //     );

    //     if (!$locationBatch->wasRecentlyCreated) {
    //         $locationBatch->increment('qty', $productData['quantity']);
    //     }

    //     return $batch->id;
    // }

    /** Generate a batch number.  */




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
