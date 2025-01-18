<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\LocationBatch;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnProduct;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function purchaseReturn()
    {
        return view('purchase.purchase_return');
    }

    public function addPurchaseReturn()
    {
        return view('purchase.add_purchase_return');
    }

    public function store(Request $request)
            {
                $validator = Validator::make($request->all(), [
                    'supplier_id' => 'required|integer|exists:suppliers,id',
                    'location_id' => 'required|integer|exists:locations,id',
                    'return_date' => 'required|date',
                    'attach_document' => 'nullable|file|max:5120|mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png',
                    'products' => 'required|array',
                    'products.*.product_id' => 'required|integer|exists:products,id',
                    'products.*.quantity' => 'required|integer|min:1',
                    'products.*.unit_price' => 'required|numeric|min:0',
                    'products.*.subtotal' => 'required|numeric|min:0',
                    'products.*.batch_no' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    return response()->json(['status' => 400, 'errors' => $validator->messages()]);
                }

                DB::transaction(function () use ($request) {
                    $attachDocument = $this->handleAttachedDocument($request);
                    $referenceNo = $this->generateReferenceNo();

                    // Create or update the purchase return record
                    $purchaseReturn = PurchaseReturn::create([
                        'supplier_id' => $request->supplier_id,
                        'reference_no' => $referenceNo,
                        'location_id' => $request->location_id,
                        'return_date' => $request->return_date,
                        'attach_document' => $attachDocument,
                    ]);

                    // Process each returned product
                    foreach ($request->products as $productData) {
                        $this->processProductReturn($productData, $purchaseReturn->id, $request->location_id);
                    }
                });

                return response()->json(['status' => 200, 'message' => 'Purchase return recorded successfully!']);
            }

            /**
             * Handle file upload for attached documents.
             */
            private function handleAttachedDocument($request)
            {
                if ($request->hasFile('attach_document')) {
                    return $request->file('attach_document')->store('documents');
                }
                return null;
            }

            /**
             * Generate a unique reference number for the purchase return.
             */
            private function generateReferenceNo()
            {
                return 'PRT-' . now()->format('YmdHis') . '-' . strtoupper(uniqid());
            }

            /**
             * Process a single product return, handling batch-wise reductions.
             */
            private function processProductReturn($productData, $purchaseReturnId, $locationId)
            {
                $product = Product::find($productData['product_id']);
                $quantityToReturn = $productData['quantity'];

                if (!empty($productData['batch_no'])) {
                    // Handle batch-specific return
                    $this->reduceBatchStock($productData['batch_no'], $locationId, $quantityToReturn);
                } else {
                    // Handle FIFO return
                    $this->reduceStockFIFO($productData['product_id'], $locationId, $quantityToReturn);
                }


                PurchaseReturnProduct::create(
                    [
                        'purchase_return_id' => $purchaseReturnId,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'unit_price' => $productData['unit_price'],
                        'subtotal' => $productData['subtotal'],
                        'batch_no' => $productData['batch_no'],
                    ]
                );
            }

            /**
             * Reduce stock for a specific batch.
             */
            private function reduceBatchStock($batchNo, $locationId, $quantity)
            {
                $batch = Batch::where('batch_no', $batchNo)->firstOrFail();

                // Use updateOrCreate for location batches
                $locationBatch = LocationBatch::updateOrCreate(
                    ['batch_id' => $batch->id, 'location_id' => $locationId],
                    ['qty' => DB::raw("GREATEST(qty - $quantity, 0)")]
                );

                $batch->decrement('qty', $quantity);

                StockHistory::create([
                    'loc_batch_id' => $locationBatch->id,
                    'quantity' => -$quantity,
                    'stock_type' => StockHistory::STOCK_TYPE_PURCHASE_RETURN,
                ]);
            }

            /**
             * Reduce stock using FIFO (First In, First Out) method.
             */
            private function reduceStockFIFO($productId, $locationId, $quantity)
            {
                $batches = Batch::where('product_id', $productId)
                    ->whereHas('locationBatches', function ($query) use ($locationId) {
                        $query->where('location_id', $locationId)->where('qty', '>', 0);
                    })
                    ->orderBy('created_at')
                    ->get();

                if ($batches->isEmpty()) {
                    throw new \Exception('No batches found for this product in the selected location.');
                }

                foreach ($batches as $batch) {
                    $locationBatch = LocationBatch::firstOrCreate(
                        ['batch_id' => $batch->id, 'location_id' => $locationId],
                        ['qty' => 0]
                    );

                    if ($quantity <= 0) {
                        break;
                    }

                    // Calculate how much to deduct
                    $deductQuantity = min($quantity, $locationBatch->qty);

                    // If no stock left in the batch
                    if ($deductQuantity <= 0) {
                        continue; // Skip to the next batch if current batch has no stock to reduce
                    }

                    // Reduce stock in batch and location batch
                    $batch->decrement('qty', $deductQuantity);
                    $locationBatch->decrement('qty', $deductQuantity);
                    $quantity -= $deductQuantity;

                    // Log stock history for deduction
                    StockHistory::create([
                        'loc_batch_id' => $locationBatch->id,
                        'quantity' => -$deductQuantity,
                        'stock_type' => 'Purchase Return',
                    ]);
                }

                // If quantity is still left to deduct, it means stock was insufficient
                if ($quantity > 0) {
                    throw new \Exception('Insufficient stock to complete the return.');
                }
            }




public function getAllPurchaseReturns()
{
    // Fetch all purchases with related products and payment info
    $purchasesReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product'])
    ->get();

    // Check if purchases are found
    if ($purchasesReturn->isEmpty()) {
        return response()->json(['message' => 'No purchases found.'], 404);
    }

    // Return the purchases along with related purchase products and payment info
    return response()->json(['purchases_Return' => $purchasesReturn], 200);
}


    public function getPurchaseReturns($id)
    {
        $purchaseReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product'])->findOrFail($id);

        return response()->json(['purchase_return' => $purchaseReturn], 200);
    }


public function edit($id)
{
    // Fetch the purchase return data using the ID
    $purchaseReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product'])->findOrFail($id);

    if (request()->ajax() || request()->is('api/*')) {
        return response()->json(['purchase_return' => $purchaseReturn], 200);
    }
    // Return the view with the purchase return data
    return view('purchase.add_purchase_return', compact('purchaseReturn'));
}


// public function update(Request $request, $id)
// {
//     // Validate the request
//     $request->validate([
//         'reference_no' => 'required|string',
//         'supplier_name' => 'nullable|string',
//         'location_name' => 'required|string',
//         'return_date' => 'required|date',
//         'document' => 'nullable|file|mimes:png,jpg,pdf|max:2048',
//         'products' => 'required|array',
//         'products.*.product_id' => 'required|integer|exists:products,id',
//         'products.*.quantity' => 'required|numeric|min:1',
//         'products.*.unit_price' => 'required|numeric|min:0',
//     ]);

//     // Find the purchase return
//     $purchaseReturn = PurchaseReturn::findOrFail($id);

//     // Update basic fields
//     $purchaseReturn->reference_no = $request->input('reference_no');
//     $purchaseReturn->supplier_name = $request->input('supplier_name');
//     $purchaseReturn->location_name = $request->input('location_name');
//     $purchaseReturn->return_date = $request->input('return_date');

//     // Update the document if uploaded
//     if ($request->hasFile('document')) {
//         $document = $request->file('document');
//         $documentPath = $document->store('documents', 'public');
//         $purchaseReturn->document = $documentPath;
//     }

//     $purchaseReturn->save();

//     // Update products
//     $purchaseReturn->products()->delete(); // Clear old products
//     foreach ($request->input('products') as $product) {
//         $purchaseReturn->products()->create([
//             'product_id' => $product['product_id'],
//             'quantity' => $product['quantity'],
//             'unit_price' => $product['unit_price'],
//             'subtotal' => $product['quantity'] * $product['unit_price'],
//         ]);
//     }

//     return response()->json([
//         'message' => 'Purchase return updated successfully!',
//         'data' => $purchaseReturn->load('products'),
//     ]);
// }



}
