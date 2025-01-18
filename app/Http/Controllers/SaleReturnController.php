<?php

namespace App\Http\Controllers;

use App\Models\SalesReturn;
use App\Models\SalesReturnProduct;
use App\Models\Batch;
use App\Models\SaleReturnProduct;
use App\Models\SalesProduct;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\LocationBatch;
use App\Models\StockHistory;

class SaleReturnController extends Controller
{
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'sale_id' => 'required|integer|exists:sales,id',
    //         'customer_id' => 'required|integer|exists:customers,id',
    //         'return_date' => 'required|date',
    //         'status' => 'required|string',
    //         'reason' => 'nullable|string',
    //         'total_amount' => 'required|numeric|min:0',
    //         'products' => 'required|array',
    //         'products.*.product_id' => 'required|integer|exists:products,id',
    //         'products.*.quantity' => 'required|integer|min:1',
    //         'products.*.batch_no' => 'nullable|string|max:255',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'errors' => $validator->messages()]);
    //     }

    //     DB::transaction(function () use ($request) {
    //         // Create sales return record
    //         $salesReturn = SalesReturn::create([
    //             'sale_id' => $request->sale_id,
    //             'customer_id' => $request->customer_id,
    //             'return_date' => $request->return_date,
    //             'status' => $request->status,
    //             'reason' => $request->reason,
    //             'total_amount' => $request->total_amount,
    //         ]);

    //         // Process each returned product
    //         foreach ($request->products as $productData) {
    //             $this->processProductReturn($productData, $salesReturn->id, $request->location_id);
    //         }
    //     });

    //     return response()->json(['status' => 200, 'message' => 'Sales return recorded successfully!']);
    // }

    // private function processProductReturn($productData, $returnId, $locationId)
    // {
    //     $quantityToAdd = $productData['quantity'];
    //     $saleProduct = SalesProduct::where('sale_id', $returnId)->where('product_id', $productData['product_id'])->first();

    //     if (!$saleProduct) {
    //         throw new \Exception("Sale product not found for return.");
    //     }
    //     // $priceType = $saleProduct->price_type; // Removed unused variable
    //     $price = $saleProduct->unit_price;

    //     if (!empty($productData['batch_no'])) {
    //         // Add stock to a specific batch
    //         $this->addBatchStock($productData['batch_no'], $locationId, $quantityToAdd);
    //         $batch = Batch::where('batch_no', $productData['batch_no'])->first();
    //         $batchId = $batch ? $batch->id : null;
    //     } else {
    //         // Add stock using FIFO
    //         $this->addStockFIFO($productData['product_id'], $locationId, $quantityToAdd);
    //         $batchId = null;
    //     }

    //     // Create sales return product record
    //     SaleReturnProduct::create([
    //         'return_id' => $returnId,
    //         'product_id' => $productData['product_id'],
    //         'quantity' => $productData['quantity'],
    //         'price' => $price,
    //         'subtotal' => $price * $productData['quantity'],
    //         'batch_id' => $batchId,
    //         'location_id' => $locationId,
    //     ]);
    // }

    // private function addBatchStock($batchNo, $locationId, $quantity)
    // {
    //     $batch = Batch::where('batch_no', $batchNo)->firstOrFail();

    //     $locationBatch = LocationBatch::updateOrCreate(
    //         ['batch_id' => $batch->id, 'location_id' => $locationId],
    //         ['qty' => DB::raw("qty + $quantity")]
    //     );

    //     $batch->increment('qty', $quantity);

    //     StockHistory::create([
    //         'loc_batch_id' => $locationBatch->id,
    //         'quantity' => $quantity,
    //         'stock_type' => 'Return',
    //     ]);
    // }

    // private function addStockFIFO($productId, $locationId, $quantity)
    // {
    //     $batches = Batch::where('product_id', $productId)
    //         ->whereHas('locationBatches', function ($query) use ($locationId) {
    //             $query->where('location_id', $locationId);
    //         })
    //         ->orderBy('created_at')
    //         ->get();

    //     foreach ($batches as $batch) {
    //         $locationBatch = LocationBatch::firstOrCreate(
    //             ['batch_id' => $batch->id, 'location_id' => $locationId],
    //             ['qty' => 0]
    //         );

    //         if ($quantity <= 0) {
    //             break;
    //         }

    //         $addQuantity = min($quantity, $locationBatch->qty);

    //         $batch->increment('qty', $addQuantity);
    //         $locationBatch->increment('qty', $addQuantity);
    //         $quantity -= $addQuantity;

    //         StockHistory::create([
    //             'loc_batch_id' => $locationBatch->id,
    //             'quantity' => $addQuantity,
    //             'stock_type' => 'Return',
    //         ]);
    //     }
    // }

    public function addSaleReturn()
    {
        return view('saleReturn.sale_return');
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'location_id' => 'required|exists:locations,id',
            'return_date' => 'required|date',
            'return_total' => 'required|numeric',
            'notes' => 'nullable|string',
            'is_defective' => 'required|boolean',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric',
            'products.*.subtotal' => 'required|numeric',
            'products.*.batch_id' => 'nullable|exists:batches,id',
            'products.*.price_type' => 'required|string',
            'products.*.discount' => 'nullable|numeric',
            'products.*.tax' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        $salesReturn = DB::transaction(function () use ($request) {
            $salesReturn = SalesReturn::create([
                'sale_id' => $request->sale_id,
                'customer_id' => $request->customer_id,
                'location_id' => $request->location_id,
                'return_date' => $request->return_date,
                'return_total' => $request->return_total,
                'notes' => $request->notes,
                'is_defective' => $request->is_defective,
            ]);

            foreach ($request->products as $productData) {
                $this->processProductReturn($productData, $salesReturn->id, $request->location_id);
            }

            return $salesReturn;
        });

        return response()->json(['status' => 200, 'message' => 'Sales return recorded successfully!']);
    }

    /**
     * Handle product return with batch-wise or FIFO stock restocking.
     */
    private function processProductReturn($productData, $salesReturnId, $locationId)
    {
        $quantityToRestock = $productData['quantity'];

        if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
            // Restock specific batch
            $this->restockBatchStock($productData['batch_id'], $locationId, $quantityToRestock);
            $batchId = $productData['batch_id'];
        } else {
            // Restock using FIFO
            $this->restockStockFIFO($productData['product_id'], $locationId, $quantityToRestock);
            $batchId = null;
        }

        // Create sales return product record
        SalesReturnProduct::create([
            'sales_return_id' => $salesReturnId,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'unit_price' => $productData['unit_price'],
            'subtotal' => $productData['subtotal'],
            'batch_id' => $batchId,
            'location_id' => $locationId,
            'price_type' => $productData['price_type'],
            'discount' => $productData['discount'],
            'tax' => $productData['tax'],
        ]);
    }

    /**
     * Restock stock to a specific batch.
     */
    private function restockBatchStock($batchId, $locationId, $quantity)
    {
        $batch = Batch::findOrFail($batchId);
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->firstOrFail();

        // Restock stock to location batch
        $locationBatch->increment('qty', $quantity);

        // Restock stock to batch
        $batch->increment('qty', $quantity);

        // Create stock history record
        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $quantity,
            'stock_type' => 'Return',
        ]);
    }

    /**
     * Restock stock using FIFO.
     */
    private function restockStockFIFO($productId, $locationId, $quantity)
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

            if ($quantity <= 0) {
                break;
            }

            $restockQuantity = min($quantity, $locationBatch->qty);

            // Restock stock to batch and location batch
            $batch->increment('qty', $restockQuantity);
            $locationBatch->increment('qty', $restockQuantity);

            // Create stock history record
            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity' => $restockQuantity,
                'stock_type' => 'Return',
            ]);

            $quantity -= $restockQuantity;
        }
    }
}
