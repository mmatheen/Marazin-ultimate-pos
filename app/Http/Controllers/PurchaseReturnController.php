<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Ledger;
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
    function __construct()
    {
        $this->middleware('permission:view purchase-return', ['only' => ['purchaseReturn']]);
        $this->middleware('permission:add purchase-return', ['only' => ['addPurchaseReturn']]);
        $this->middleware('permission:create purchase-return', ['only' => ['storeOrUpdate']]);
        $this->middleware('permission:edit purchase-return', ['only' => ['edit','storeOrUpdate']]);
    }

    public function purchaseReturn()
    {
        return view('purchase.purchase_return');
    }

    public function addPurchaseReturn()
    {
        return view('purchase.add_purchase_return');
    }

    public function storeOrUpdate(Request $request, $purchaseReturnId = null)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'location_id' => 'required|integer|exists:locations,id',
            'return_date' => 'required|date',
            'attach_document' => 'nullable|file|max:5120|mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png',
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
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.subtotal' => 'required|numeric|min:0',
            'products.*.batch_id' => 'nullable|integer|exists:batches,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            DB::transaction(function () use ($request, $purchaseReturnId) {
                $attachDocument = $this->handleAttachedDocument($request);
                $referenceNo = $purchaseReturnId ? PurchaseReturn::find($purchaseReturnId)->reference_no : $this->generateReferenceNo();

                $totalReturnAmount = collect($request->products)->sum(fn($product) => $product['subtotal']);

                // Create or update the purchase return record
                $purchaseReturn = PurchaseReturn::updateOrCreate(
                    ['id' => $purchaseReturnId],
                    [
                        'supplier_id' => $request->supplier_id,
                        'reference_no' => $referenceNo,
                        'location_id' => $request->location_id,
                        'return_date' => $request->return_date,
                        'attach_document' => $attachDocument,
                        'return_total' => $totalReturnAmount,
                        'total_paid' => 0,
                        'total_due' => $totalReturnAmount,
                        'payment_status' => 'Due',
                    ]
                );

                // If updating, reverse stock adjustments for existing products
                if ($purchaseReturnId) {
                    $existingProducts = $purchaseReturn->purchaseReturnProducts;
                    foreach ($existingProducts as $existingProduct) {
                        $quantity = $existingProduct->quantity;
                        $batchId = $existingProduct->batch_id;

                        // Restore batch or FIFO stock
                        $this->restoreStock($existingProduct->product_id, $purchaseReturn->location_id, $quantity, $batchId);

                        // Delete existing purchase return products
                        $existingProduct->delete();
                    }
                }

                // Process each product in the request
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

                // Insert ledger entry for the purchase return
                Ledger::create([
                    'transaction_date' => $request->return_date,
                    'reference_no' => $referenceNo,
                    'transaction_type' => 'purchase_return',
                    'debit' => 0,
                    'credit' => $totalReturnAmount,
                    'balance' => $this->calculateNewBalance($request->supplier_id, $totalReturnAmount, 'credit'),
                    'contact_type' => 'supplier',
                    'user_id' => $request->supplier_id,
                ]);
            });

            $message = $purchaseReturnId ? 'Purchase return updated successfully!' : 'Purchase return recorded successfully!';
            return response()->json(['status' => 200, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    private function calculateNewBalance($userId, $amount, $type)
    {
        $lastLedger = Ledger::where('user_id', $userId)->where('contact_type', 'supplier')->orderBy('transaction_date', 'desc')->first();
        $previousBalance = $lastLedger ? $lastLedger->balance : 0;

        return $type === 'debit' ? $previousBalance + $amount : $previousBalance - $amount;
    }

    private function restoreStock($productId, $locationId, $quantity, $batchId = null)
    {
        if (empty($batchId)) {
            $this->restoreStockFIFO($productId, $locationId, $quantity);
        } else {
            $this->restoreBatchStock($batchId, $locationId, $quantity);
        }
    }

    private function generateReferenceNo()
    {
        // Fetch the last reference number from the database
        $lastReference = PurchaseReturn::orderBy('id', 'desc')->first();
        $lastReferenceNo = $lastReference ? intval(substr($lastReference->reference_no, 3)) : 0;

        // Increment the reference number
        $newReferenceNo = $lastReferenceNo + 1;

        // Format the new reference number to 3 digits
        $formattedNumber = str_pad($newReferenceNo, 3, '0', STR_PAD_LEFT);

        return 'PRT' . $formattedNumber;
    }

    private function handleAttachedDocument($request)
    {
        if ($request->hasFile('attach_document')) {
            $file = $request->file('attach_document');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('/assets/documents'), $fileName);
            return $fileName;
        }
        return null;
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
            ->first();

        // Ensure the LocationBatch exists
        if (!$locationBatch) {
            throw new \Exception('LocationBatch record not found for batch ID ' . $batchId . ' and location ID ' . $locationId);
        }

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
                ->first();

            // Ensure the LocationBatch exists
            if (!$locationBatch) {
                continue; // Skip to next batch if LocationBatch does not exist
            }

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

    private function restoreBatchStock($batchId, $locationId, $quantity)
    {
        $batch = Batch::findOrFail($batchId);
        $locationBatch = LocationBatch::where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->firstOrFail();

        $locationBatch->increment('qty', $quantity);
        $batch->increment('qty', $quantity);

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $quantity,
            'stock_type' => StockHistory::STOCK_TYPE_PURCHASE_RETURN_REVERSAL,
        ]);
    }

    private function restoreStockFIFO($productId, $locationId, $quantity)
    {
        $batches = Batch::where('product_id', $productId)
            ->whereHas('locationBatches', function ($query) use ($locationId) {
                $query->where('location_id', $locationId)->where('qty', '>', 0);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($batches as $batch) {
            $locationBatch = LocationBatch::where('batch_id', $batch->id)
                ->where('location_id', $locationId)
                ->firstOrFail();

            if ($quantity <= 0) {
                break;
            }

            $restoreQuantity = min($quantity, $locationBatch->qty);

            if ($restoreQuantity <= 0) {
                continue;
            }

            $batch->increment('qty', $restoreQuantity);
            $locationBatch->increment('qty', $restoreQuantity);
            $quantity -= $restoreQuantity;

            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity' => $restoreQuantity,
                'stock_type' => StockHistory::STOCK_TYPE_PURCHASE_RETURN_REVERSAL,
            ]);
        }

        if ($quantity > 0) {
            throw new \Exception('Cannot fully restore the stock. Please check the inventory.');
        }
    }


    public function getAllPurchaseReturns()
    {
        $purchasesReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product','payments'])->get();

        if ($purchasesReturn->isEmpty()) {
            return response()->json(['message' => 'No purchases found.'], 404);
        }

        return response()->json(['purchases_Return' => $purchasesReturn], 200);
    }

    public function getPurchaseReturns($id)
    {
        $purchaseReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product','payments'])->findOrFail($id);

        return response()->json(['purchase_return' => $purchaseReturn], 200);
    }

    public function edit($id)
    {
        $purchaseReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.batch', 'purchaseReturnProducts.product'])->findOrFail($id);

        if (request()->ajax() || request()->is('api/*')) {
            return response()->json(['purchase_return' => $purchaseReturn], 200);
        }

        return view('purchase.add_purchase_return', compact('purchaseReturn'));
    }





}
