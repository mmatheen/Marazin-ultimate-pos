<?php
namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\LocationBatch;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseProduct;
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
        // Validate the request data
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
            'products.*.batch_id' => 'nullable|integer|exists:batches,id',

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            DB::transaction(function () use ($request) {
                $attachDocument = $this->handleAttachedDocument($request);
                $referenceNo = $this->generateReferenceNo();

                // Create the purchase return record
                $purchaseReturn = PurchaseReturn::create([
                    'supplier_id' => $request->supplier_id,
                    'reference_no' => $referenceNo,
                    'location_id' => $request->location_id,
                    'return_date' => $request->return_date,
                    'attach_document' => $attachDocument,
                ]);

                // Process each returned product
               // Validate products belong to the supplier's purchases
               foreach ($request->products as $productData) {
                $validProduct = PurchaseProduct::where('product_id', $productData['product_id'])
                    ->whereHas('purchase', function ($query) use ($request) {
                        $query->where('supplier_id', $request->supplier_id);
                    })->exists();

                if (!$validProduct) {
                    throw new \Exception("Product ID {$productData['product_id']} does not belong to the selected supplier's purchase.");
                }

                $this->processProductReturn($productData, $purchaseReturn->id, $request->location_id);
            }
        });


            return response()->json(['status' => 200, 'message' => 'Purchase return recorded successfully!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    private function handleAttachedDocument($request)
    {
        if ($request->hasFile('attach_document')) {
            return $request->file('attach_document')->store('documents');
        }
        return null;
    }

     private $lastReferenceNo = 0; // This should be persisted in a real application

        private function generateReferenceNo()
        {
            $this->lastReferenceNo++; // Increment the reference number
            $formattedNumber = str_pad($this->lastReferenceNo, 3, '0', STR_PAD_LEFT); // Format to 3 digits
            return 'PRT' . $formattedNumber;
        }


    private function processProductReturn($productData, $purchaseReturnId, $locationId)
    {
        $quantityToReturn = $productData['quantity'];
        $batchId = $productData['batch_id'];

        
        if (empty($batchId)) {
            // Handle FIFO return (no specific batch)
            $this->reduceStockFIFO($productData['product_id'], $locationId, $quantityToReturn);
        } else {
            // Handle batch-specific return
            $this->reduceBatchStock($batchId, $locationId, $quantityToReturn);
        }

        // Create purchase return product record
        PurchaseReturnProduct::create([
            'purchase_return_id' => $purchaseReturnId,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'unit_price' => $productData['unit_price'],
            'subtotal' => $productData['subtotal'],
            'batch_no' => $batchId,
        ]);
    }

    private function reduceBatchStock($batchId, $locationId, $quantity)
    {
        $batch = Batch::findOrFail($batchId);
        $locationBatch = LocationBatch::where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->firstOrFail();

        if ($locationBatch->qty < $quantity) {
            throw new \Exception('Insufficient stock to complete the return.');
        }

        $locationBatch->decrement('qty', $quantity);
        $batch->decrement('qty', $quantity);

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => -$quantity,
            'stock_type' => StockHistory::STOCK_TYPE_PURCHASE_RETURN,
        ]);
    }

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
            $locationBatch = LocationBatch::where('batch_id', $batch->id)
                ->where('location_id', $locationId)
                ->firstOrFail();

            if ($quantity <= 0) {
                break;
            }

            $deductQuantity = min($quantity, $locationBatch->qty);

            if ($deductQuantity <= 0) {
                continue;
            }

            $batch->decrement('qty', $deductQuantity);
            $locationBatch->decrement('qty', $deductQuantity);
            $quantity -= $deductQuantity;

            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity' => -$deductQuantity,
                'stock_type' => StockHistory::STOCK_TYPE_PURCHASE_RETURN,
            ]);
        }

        if ($quantity > 0) {
            throw new \Exception('Insufficient stock to complete the return.');
        }
    }

    public function getAllPurchaseReturns()
    {
        $purchasesReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product'])->get();

        if ($purchasesReturn->isEmpty()) {
            return response()->json(['message' => 'No purchases found.'], 404);
        }

        return response()->json(['purchases_Return' => $purchasesReturn], 200);
    }

    public function getPurchaseReturns($id)
    {
        $purchaseReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product'])->findOrFail($id);

        return response()->json(['purchase_return' => $purchaseReturn], 200);
    }

    public function edit($id)
    {
        $purchaseReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product.batches'])->findOrFail($id);

        if (request()->ajax() || request()->is('api/*')) {
            return response()->json(['purchase_return' => $purchaseReturn], 200);
        }

        return view('purchase.add_purchase_return', compact('purchaseReturn'));
    }


    public function getProductDetails($purchaseId, $supplierId)
    {
        try {
            $purchaseReturn = PurchaseReturn::with(['purchaseReturnProducts.product.batch' => function ($query) use ($supplierId) {
                $query->whereHas('purchase', function ($query) use ($supplierId) {
                    $query->where('supplier_id', $supplierId);
                });
            }])->findOrFail($purchaseId);

            return response()->json([
                'status' => 200,
                'purchase_return' => $purchaseReturn
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Purchase return not found'
            ]);
        }
    }


    public function update(Request $request, $id)
{
    // Validate the request data
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
        'products.*.batch_id' => 'nullable|integer|exists:batches,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => 400, 'errors' => $validator->messages()]);
    }

    try {
        DB::transaction(function () use ($request, $id) {
            $purchaseReturn = PurchaseReturn::findOrFail($id);

            // Reverse the stock changes for the existing return
            foreach ($purchaseReturn->purchaseReturnProducts as $returnProduct) {
                if ($returnProduct->batch_no) {
                    $this->increaseBatchStock($returnProduct->batch_no, $returnProduct->quantity);
                } else {
                    $this->increaseStockFIFO($returnProduct->product_id, $purchaseReturn->location_id, $returnProduct->quantity);
                }
            }

            $attachDocument = $this->handleAttachedDocument($request);

            // Update the purchase return record
            $purchaseReturn->update([
                'supplier_id' => $request->supplier_id,
                'location_id' => $request->location_id,
                'return_date' => $request->return_date,
                'attach_document' => $attachDocument,
            ]);

            // Delete existing return products
            $purchaseReturn->purchaseReturnProducts()->delete();

            // Process each returned product
            foreach ($request->products as $productData) {
                $validProduct = PurchaseProduct::where('product_id', $productData['product_id'])
                    ->whereHas('purchase', function ($query) use ($request) {
                        $query->where('supplier_id', $request->supplier_id);
                    })->exists();

                if (!$validProduct) {
                    throw new \Exception("Product ID {$productData['product_id']} does not belong to the selected supplier's purchase.");
                }

                $this->processProductReturn($productData, $purchaseReturn->id, $request->location_id);
            }
        });

        return response()->json(['status' => 200, 'message' => 'Purchase return updated successfully!']);
    } catch (\Exception $e) {
        return response()->json(['status' => 400, 'message' => $e->getMessage()]);
    }
}

// Function to increase batch stock (used during update)
private function increaseBatchStock($batchId, $quantity)
{
    $batch = Batch::findOrFail($batchId);
    $batch->increment('qty', $quantity);
}

// Function to increase stock via FIFO (used during update)
private function increaseStockFIFO($productId, $locationId, $quantity)
{
    $batches = Batch::where('product_id', $productId)
        ->whereHas('locationBatches', function ($query) use ($locationId) {
            $query->where('location_id', $locationId);
        })
        ->orderBy('created_at')
        ->get();

    foreach ($batches as $batch) {
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->firstOrFail();

        $incrementQuantity = min($quantity, $locationBatch->qty);
        $batch->increment('qty', $incrementQuantity);
        $locationBatch->increment('qty', $incrementQuantity);
        $quantity -= $incrementQuantity;

        if ($quantity <= 0) {
            break;
        }
    }
}
}

