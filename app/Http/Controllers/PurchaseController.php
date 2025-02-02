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
use Exception;

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

    //     public function store(Request $request)
    //     {
    //         $validator = Validator::make($request->all(), [
    //             'supplier_id' => 'required|integer|exists:suppliers,id',
    //             'purchase_date' => 'required|date',
    //             'purchasing_status' => 'required|in:Received,Pending,Ordered',
    //             'location_id' => 'required|integer|exists:locations,id',
    //             'pay_term' => 'nullable|integer|min:0',
    //             'pay_term_type' => 'nullable|in:days,months',
    //             'attached_document' => 'nullable|file|max:5120|mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png',
    //             'discount_type' => 'nullable|in:percent,fixed',
    //             'discount_amount' => 'nullable|numeric|min:0',
    //             'total' => 'required|numeric|min:0',
    //             'final_total' => 'required|numeric|min:0',
    //             'payment_status' => 'nullable|in:Paid,Due,Partial',
    //             'products' => 'required|array',
    //             'products.*.product_id' => 'required|integer|exists:products,id',
    //             'products.*.quantity' => 'required|integer|min:1',
    //             'products.*.unit_cost' => 'required|numeric|min:0',
    //             'products.*.wholesale_price' => 'required|numeric|min:0',
    //             'products.*.special_price' => 'required|numeric|min:0',
    //             'products.*.retail_price' => 'required|numeric|min:0',
    //             'products.*.max_retail_price' => 'required|numeric|min:0',
    //             'products.*.price' => 'required|numeric|min:0',
    //             'products.*.total' => 'required|numeric|min:0',
    //             'products.*.batch_no' => 'nullable|string|max:255',
    //             'products.*.expiry_date' => 'nullable|date',
    //             'advance_balance' => 'nullable|numeric|min:0',
    //             'payment_method' => 'nullable|string',
    //             'payment_account' => 'nullable|string',
    //             'payment_note' => 'nullable|string',
    //             'paid_date' => 'nullable|date',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => 400, 'errors' => $validator->messages()]);
    //         }

    //         DB::transaction(function () use ($request) {
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
    //                 'payment_status' => 'Due', // Initial status
    //             ]);

    //             // Process each product in the purchase
    //             foreach ($request->products as $productData) {
    //                 // Fetch the product details
    //                 $product = Product::find($productData['product_id']);
    //                 $batch = Batch::firstOrCreate(
    //                     [
    //                         'batch_no' => $productData['batch_no'] ?? Batch::generateNextBatchNo(),
    //                         'product_id' => $productData['product_id'],
    //                         'unit_cost' => $productData['unit_cost'],
    //                         'expiry_date' => $productData['expiry_date'],
    //                     ],
    //                     [
    //                         'qty' => $productData['quantity'],
    //                         'wholesale_price' => $productData['wholesale_price'],
    //                         'special_price' => $productData['special_price'],
    //                         'retail_price' => $productData['retail_price'],
    //                         'max_retail_price' => $productData['max_retail_price'],
    //                     ]
    //                 );

    //                 // Update the quantity in the batch table
    //                 if (!$batch->wasRecentlyCreated) {
    //                     $batch->increment('qty', $productData['quantity']);
    //                 }

    //                 // Check if the location batch already exists or create a new one
    //                 $locationBatch = LocationBatch::firstOrCreate(
    //                     [
    //                         'batch_id' => $batch->id,
    //                         'location_id' => $request->location_id,
    //                     ],
    //                     [
    //                         'qty' => $productData['quantity'],
    //                     ]
    //                 );

    //                 // Update the quantity in the location batch if it already exists
    //                 if (!$locationBatch->wasRecentlyCreated) {
    //                     $locationBatch->increment('qty', $productData['quantity']);
    //                 }

    //                 // Update location_product table
    //                 $product->locations()->updateExistingPivot($request->location_id, ['qty' => DB::raw('qty + ' . $productData['quantity'])]);

    //                 // Record stock history as purchase stock
    //                 StockHistory::create([
    //                     'loc_batch_id' => $locationBatch->id,
    //                     'batch_id' => $batch->id, // Add batch_id to stock history
    //                     'quantity' => $productData['quantity'],
    //                     'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
    //                 ]);

    //                 // Create the purchase product record
    //                 PurchaseProduct::create([
    //                     'purchase_id' => $purchase->id,
    //                     'product_id' => $productData['product_id'],
    //                     'batch_id' => $batch->id,
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

    //                 // Handle initial payment if provided
    //                 if ($request->advance_balance > 0) {
    //                     PurchasePayment::create([
    //                         'purchase_id' => $purchase->id,
    //                         'supplier_id' => $request->supplier_id,
    //                         'amount' => $request->advance_balance,
    //                         'payment_method' => $request->payment_method,
    //                         'payment_account' => $request->payment_account,
    //                         'payment_date' => $request->paid_date,
    //                         'payment_note' => $request->payment_note,
    //                     ]);
    //                 }
    //             }

    //             // Update purchase payment status and due amount
    //             $purchase->updatePaymentStatus();
    //         });

    //         return response()->json(['status' => 200, 'message' => 'Purchase recorded successfully!']);
    //     }

    //     private function generateReferenceNo()
    //     {
    //         return 'PUR-' . now()->format('YmdHis') . '-' . strtoupper(uniqid());
    //     }

    //     private function handleAttachedDocument($request)
    //     {
    //         $fileName = null;
    //         if ($request->hasFile('attached_document')) {
    //             $file = $request->file('attached_document');
    //             $fileName = time() . '.' . $file->getClientOriginalExtension();
    //             $file->move(public_path('/assets/documents'), $fileName);
    //             return  $fileName;
    //         }
    //         return null;
    //     }

    //     // private function generateBatchNo($productId)
    //     // {
    //     //     return 'batch-' . ($productId + 1);
    //     // }

    //     public function update(Request $request, $purchaseId)
    //     {
    //         // Validation
    //         $validator = Validator::make($request->all(), [
    //             'supplier_id' => 'required|integer|exists:suppliers,id',
    //             'purchase_date' => 'required|date',
    //             'purchasing_status' => 'required|in:Received,Pending,Ordered',
    //             'location_id' => 'required|integer|exists:locations,id',
    //             'pay_term' => 'nullable|integer|min:0',
    //             'pay_term_type' => 'nullable|in:days,months',
    //             'attached_document' => 'nullable|file|max:5120|mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png',
    //             'discount_type' => 'nullable|in:percent,fixed',
    //             'discount_amount' => 'nullable|numeric|min:0',
    //             'total' => 'required|numeric|min:0',
    //             'final_total' => 'required|numeric|min:0',
    //             'payment_status' => 'nullable|in:Paid,Due,Partial',
    //             'products' => 'required|array',
    //             'products.*.product_id' => 'required|integer|exists:products,id',
    //             'products.*.quantity' => 'required|integer|min:1',
    //             'products.*.unit_cost' => 'required|numeric|min:0',
    //             'products.*.wholesale_price' => 'required|numeric|min:0',
    //             'products.*.special_price' => 'required|numeric|min:0',
    //             'products.*.retail_price' => 'required|numeric|min:0',
    //             'products.*.max_retail_price' => 'required|numeric|min:0',
    //             'products.*.price' => 'required|numeric|min:0',
    //             'products.*.total' => 'required|numeric|min:0',
    //             'products.*.batch_no' => 'nullable|string|max:255',
    //             'products.*.expiry_date' => 'nullable|date',
    //             'advance_balance' => 'nullable|numeric|min:0',
    //             'payment_method' => 'nullable|string',
    //             'payment_account' => 'nullable|string',
    //             'payment_note' => 'nullable|string',
    //             'paid_date' => 'nullable|date',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => 400, 'errors' => $validator->messages()]);
    //         }

    //         DB::transaction(function () use ($request, $purchaseId) {
    //             $purchase = Purchase::findOrFail($purchaseId);
    //             $existingProducts = $purchase->purchaseProducts->keyBy('product_id');

    //             // Update purchase details
    //             $purchase->update([
    //                 'supplier_id' => $request->supplier_id,
    //                 'purchase_date' => $request->purchase_date,
    //                 'purchasing_status' => $request->purchasing_status,
    //                 'location_id' => $request->location_id,
    //                 'pay_term' => $request->pay_term,
    //                 'pay_term_type' => $request->pay_term_type,
    //                 'attached_document' => $this->handleAttachedDocument($request),
    //                 'total' => $request->total,
    //                 'discount_type' => $request->discount_type,
    //                 'discount_amount' => $request->discount_amount,
    //                 'final_total' => $request->final_total,
    //                 'payment_status' => $request->payment_status,
    //             ]);

    //             // Process products in chunks (if needed)
    //             collect($request->products)->chunk(100)->each(function ($productsChunk) use ($purchase, $existingProducts, $request) {
    //                 foreach ($productsChunk as $productData) {
    //                     $productId = $productData['product_id'];
    //                     $existingProduct = $existingProducts->get($productId);

    //                     if ($existingProduct) {
    //                         // Update existing product
    //                         $quantityDifference = $productData['quantity'] - $existingProduct->quantity;
    //                         $this->updateProductStock($existingProduct, $quantityDifference, $request->location_id);

    //                         $existingProduct->update([
    //                             'quantity' => $productData['quantity'],
    //                             'unit_cost' => $productData['unit_cost'],
    //                             'wholesale_price' => $productData['wholesale_price'],
    //                             'special_price' => $productData['special_price'],
    //                             'retail_price' => $productData['retail_price'],
    //                             'max_retail_price' => $productData['max_retail_price'],
    //                             'price' => $productData['price'],
    //                             'total' => $productData['total'],
    //                         ]);
    //                     } else {
    //                         // Add new product
    //                         $this->addNewProductToPurchase($purchase, $productData, $request->location_id);
    //                     }
    //                 }
    //             });

    //             // Remove products not present in the request
    //             $requestProductIds = collect($request->products)->pluck('product_id')->toArray();
    //             $productsToRemove = $existingProducts->whereNotIn('product_id', $requestProductIds);

    //             foreach ($productsToRemove as $productToRemove) {
    //                 $this->removeProductFromPurchase($productToRemove, $request->location_id);
    //             }

    //             // Handle advance balance payment
    //             if ($request->advance_balance > 0) {
    //                 PurchasePayment::updateOrCreate(
    //                     ['purchase_id' => $purchase->id],
    //                     [
    //                         'supplier_id' => $request->supplier_id,
    //                         'amount' => $request->advance_balance,
    //                         'payment_method' => $request->payment_method,
    //                         'payment_account' => $request->payment_account,
    //                         'payment_date' => $request->paid_date,
    //                         'payment_note' => $request->payment_note,
    //                     ]
    //                 );
    //             }

    //             $purchase->updatePaymentStatus();
    //         });

    //         return response()->json(['status' => 200, 'message' => 'Purchase updated successfully!']);
    //     }

    //     /**
    //      * Update product stock when quantity changes.
    //      */
    //     private function updateProductStock($existingProduct, $quantityDifference, $locationId)
    //     {
    //         $batch = Batch::find($existingProduct->batch_id);
    //         $locationBatch = LocationBatch::where('batch_id', $batch->id)
    //             ->where('location_id', $locationId)
    //             ->first();

    //         if ($locationBatch) {
    //             if ($locationBatch->qty + $quantityDifference < 0) {
    //                 throw new Exception("Stock quantity cannot be reduced below zero");
    //             }
    //             $locationBatch->increment('qty', $quantityDifference);
    //         }

    //         if ($batch->qty + $quantityDifference < 0) {
    //             throw new Exception("Batch stock quantity cannot be reduced below zero");
    //         }
    //         $batch->increment('qty', $quantityDifference);

    //         StockHistory::create([
    //             'loc_batch_id' => $locationBatch->id,
    //             'quantity' => $quantityDifference,
    //             'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
    //         ]);
    //     }

    //     /**
    //      * Add a new product to the purchase.
    //      */
    // private function addNewProductToPurchase($purchase, $productData, $locationId)
    // {
    //     $product = Product::find($productData['product_id']);
    //     $batch = Batch::create([
    //         'product_id' => $product->id,
    //         'qty' => $productData['quantity'],
    //         'batch_no' => $productData['batch_no'] ?? null,
    //         'expiry_date' => $productData['expiry_date'] ?? null,
    //         'unit_cost' => $productData['unit_cost'], // Include unit_cost here
    //         'wholesale_price' => $productData['wholesale_price'],
    //         'special_price' => $productData['special_price'],
    //         'retail_price' => $productData['retail_price'],
    //         'max_retail_price' => $productData['max_retail_price'],
    //     ]);

    //     $locationBatch = LocationBatch::create([
    //         'batch_id' => $batch->id,
    //         'location_id' => $locationId,
    //         'qty' => $productData['quantity'],
    //     ]);

    //     $purchase->purchaseProducts()->create([
    //         'product_id' => $product->id,
    //         'quantity' => $productData['quantity'],
    //         'unit_cost' => $productData['unit_cost'],
    //         'wholesale_price' => $productData['wholesale_price'],
    //         'special_price' => $productData['special_price'],
    //         'retail_price' => $productData['retail_price'],
    //         'max_retail_price' => $productData['max_retail_price'],
    //         'price' => $productData['price'],
    //         'total' => $productData['total'],
    //         'batch_id' => $batch->id,
    //         'location_id' => $locationId, // Include location_id here
    //     ]);

    //     StockHistory::create([
    //         'loc_batch_id' => $locationBatch->id,
    //         'quantity' => $productData['quantity'],
    //         'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
    //     ]);
    // }

    //     /**
    //      * Remove a product from the purchase.
    //      */
    //     private function removeProductFromPurchase($productToRemove, $locationId)
    //     {
    //         $batch = Batch::find($productToRemove->batch_id);
    //         $locationBatch = LocationBatch::where('batch_id', $batch->id)
    //             ->where('location_id', $locationId)
    //             ->first();

    //         if ($locationBatch) {
    //             $locationBatch->decrement('qty', $productToRemove->quantity);
    //         }

    //         $batch->decrement('qty', $productToRemove->quantity);

    //         StockHistory::create([
    //             'loc_batch_id' => $locationBatch->id,
    //             'quantity' => -$productToRemove->quantity,
    //             'stock_type' => StockHistory::STOCK_TYPE_PURCHASE,
    //         ]);

    //         $productToRemove->delete();
    //     }

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
            $attachedDocument = $this->handleAttachedDocument($request);

            $purchase = Purchase::updateOrCreate(
                ['id' => $purchaseId],
                [
                    'supplier_id' => $request->supplier_id,
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
                    'payment_status' => $purchaseId ? $request->payment_status : 'Due',
                ]
            );

            // Process products
            $this->processProducts($request, $purchase);

            // Handle advance balance payment
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

            $purchase->updatePaymentStatus();
        });

        return response()->json(['status' => 200, 'message' => 'Purchase ' . ($purchaseId ? 'updated' : 'recorded') . ' successfully!']);
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
                        'price' => $productData['price'],
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
                'price' => $productData['price'],
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

    public function getAllPurchase()
    {
        // Fetch all purchases with related products and payment info
        $purchases = Purchase::with(['supplier', 'location', 'purchaseProducts', 'payments'])->get();

        // Check if purchases are found
        if ($purchases->isEmpty()) {
            return response()->json(['message' => 'No purchases found.'], 404);
        }

        // Return the purchases along with related purchase products and payment info
        return response()->json(['purchases' => $purchases], 200);
    }

    public function getAllPurchasesProduct(int $id)
    {
        // Fetch the specific purchase by ID with related products and payment info
        $purchase = Purchase::with(['supplier', 'location', 'purchaseProducts.product', 'payments'])->find($id);

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
            $purchases = Purchase::with(['purchaseProducts.product', 'purchaseProducts.batch'])
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
                        'price' => $purchaseProduct->price,
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
