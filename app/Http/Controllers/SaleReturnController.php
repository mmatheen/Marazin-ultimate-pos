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

class SalesReturnController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|integer|exists:sales,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'return_date' => 'required|date',
            'status' => 'required|string',
            'reason' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.batch_no' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        DB::transaction(function () use ($request) {
            // Create sales return record
            $salesReturn = SalesReturn::create([
                'sale_id' => $request->sale_id,
                'customer_id' => $request->customer_id,
                'return_date' => $request->return_date,
                'status' => $request->status,
                'reason' => $request->reason,
                'total_amount' => $request->total_amount,
            ]);

            // Process each returned product
            foreach ($request->products as $productData) {
                $this->processProductReturn($productData, $salesReturn->id, $request->location_id);
            }
        });

        return response()->json(['status' => 200, 'message' => 'Sales return recorded successfully!']);
    }

    private function processProductReturn($productData, $returnId, $locationId)
    {
        $quantityToAdd = $productData['quantity'];
        $saleProduct = SalesProduct::where('sale_id', $returnId)->where('product_id', $productData['product_id'])->first();

        if (!$saleProduct) {
            throw new \Exception("Sale product not found for return.");
        }
        // $priceType = $saleProduct->price_type; // Removed unused variable
        $price = $saleProduct->unit_price;

        if (!empty($productData['batch_no'])) {
            // Add stock to a specific batch
            $this->addBatchStock($productData['batch_no'], $locationId, $quantityToAdd);
            $batch = Batch::where('batch_no', $productData['batch_no'])->first();
            $batchId = $batch ? $batch->id : null;
        } else {
            // Add stock using FIFO
            $this->addStockFIFO($productData['product_id'], $locationId, $quantityToAdd);
            $batchId = null;
        }

        // Create sales return product record
        SaleReturnProduct::create([
            'return_id' => $returnId,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'price' => $price,
            'subtotal' => $price * $productData['quantity'],
            'batch_id' => $batchId,
            'location_id' => $locationId,
        ]);
    }

    private function addBatchStock($batchNo, $locationId, $quantity)
    {
        $batch = Batch::where('batch_no', $batchNo)->firstOrFail();

        $locationBatch = LocationBatch::updateOrCreate(
            ['batch_id' => $batch->id, 'location_id' => $locationId],
            ['qty' => DB::raw("qty + $quantity")]
        );

        $batch->increment('qty', $quantity);

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $quantity,
            'stock_type' => 'Return',
        ]);
    }

    private function addStockFIFO($productId, $locationId, $quantity)
    {
        $batches = Batch::where('product_id', $productId)
            ->whereHas('locationBatches', function ($query) use ($locationId) {
                $query->where('location_id', $locationId);
            })
            ->orderBy('created_at')
            ->get();

        foreach ($batches as $batch) {
            $locationBatch = LocationBatch::firstOrCreate(
                ['batch_id' => $batch->id, 'location_id' => $locationId],
                ['qty' => 0]
            );

            if ($quantity <= 0) {
                break;
            }

            $addQuantity = min($quantity, $locationBatch->qty);

            $batch->increment('qty', $addQuantity);
            $locationBatch->increment('qty', $addQuantity);
            $quantity -= $addQuantity;

            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity' => $addQuantity,
                'stock_type' => 'Return',
            ]);
        }
    }
}
