<?php

namespace App\Http\Controllers;

use App\Models\SalesReturn;
use App\Models\SalesReturnProduct;
use App\Models\Batch;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\LocationBatch;
use App\Models\StockHistory;

class SaleReturnController extends Controller
{
    /**
     * Show the form for adding a new sale return.
     */
    public function addSaleReturn()
    {
        return view('saleReturn.sale_return');
    }

    /**
     * Store a newly created sale return in the database.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'location_id' => 'required|exists:locations,id',
            'return_date' => 'required|date',
            'return_total' => 'required|numeric',
            'notes' => 'nullable|string',
            'is_defective' => 'nullable|boolean',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.original_price' => 'required|numeric',
            'products.*.return_price' => 'required|numeric',
            'products.*.subtotal' => 'required|numeric',
            'products.*.batch_id' => 'nullable|exists:batches,id',
            'products.*.price_type' => 'required|string',
            'products.*.discount' => 'nullable|numeric',
            'products.*.tax' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        // Check if the return quantity is greater than the sale quantity
        $sale = Sale::find($request->sale_id);
        $errors = [];
        if ($sale) {
            foreach ($request->products as $productData) {
                $soldProduct = $sale->products->firstWhere('product_id', $productData['product_id']);
                if ($soldProduct && $productData['quantity'] > $soldProduct->quantity) {
                    $errors[] = "Return quantity for product ID {$productData['product_id']} exceeds sold quantity.";
                }
            }
        }

        if (!empty($errors)) {
            return response()->json(['status' => 400, 'errors' => $errors]);
        }

        // Use transaction to ensure atomicity
        DB::transaction(function () use ($request) {

            $stockType = $request->sale_id ? 'with_bill' : 'without_bill';
            // Create the sales return
            $salesReturn = SalesReturn::create([
                'sale_id' => $request->sale_id,
                'customer_id' => $request->customer_id,
                'location_id' => $request->location_id,
                'return_date' => $request->return_date,
                'return_total' => $request->return_total,
                'notes' => $request->notes,
                'is_defective' => $request->is_defective,
                'stock_type' => $stockType,
            ]);

            // Process each returned product
            foreach ($request->products as $productData) {
                $this->processProductReturn($productData, $salesReturn->id, $request->location_id,$stockType);
            }

            return $salesReturn;
        });

        return response()->json(['status' => 200, 'message' => 'Sales return recorded successfully!']);
    }
    private function processProductReturn($productData, $salesReturnId, $locationId,$stockType)
    {
        $quantityToRestock = $productData['quantity'];
        $batchId = $productData['batch_id'];

        if (empty($batchId)) {
            // Create new batch if no batch ID is provided (for returns without a bill)
            $batch = Batch::create([
                'batch_no' => Batch::generateNextBatchNo(),
                'product_id' => $productData['product_id'],
                'unit_cost' => $productData['original_price'],
                'qty' => $quantityToRestock,
                'wholesale_price' => $productData['return_price'],
                'special_price' => $productData['return_price'],
                'retail_price' => $productData['return_price'],
                'max_retail_price' => $productData['return_price'],
                'expiry_date' => now()->addYear(), // Assuming 1 year expiry
            ]);

            // Create location batch record
            $locationBatch = LocationBatch::create([
                'batch_id' => $batch->id,
                'location_id' => $locationId,
                'qty' => $quantityToRestock,
            ]);

            $batchId = $batch->id;

            // Create stock history record
            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity' => $quantityToRestock,
                'stock_type' => $stockType === 'with_bill' ? 'sales_return_with_bill' : 'sales_return_without_bill',
            ]);
        } else {
            // Restock specific batch
            $this->restockBatchStock($batchId, $locationId, $quantityToRestock,$stockType);
        }

        // Create sales return product record
        SalesReturnProduct::create([
            'sales_return_id' => $salesReturnId,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'original_price' => $productData['original_price'],
            'return_price' => $productData['return_price'],
            'subtotal' => $productData['subtotal'],
            'batch_id' => $batchId,
            'location_id' => $locationId,
            'price_type' => $productData['price_type'],
            'discount' => $productData['discount'],
            'tax' => $productData['tax'],
        ]);
    }

    private function restockBatchStock($batchId, $locationId, $quantity,$stockType)
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
            'stock_type' => $stockType === 'with_bill' ? 'sales_return_with_bill' : 'sales_return_without_bill',
        ]);
    }
}
