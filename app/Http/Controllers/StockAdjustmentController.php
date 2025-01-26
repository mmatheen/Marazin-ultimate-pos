<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\AdjustmentProduct;
use App\Models\Batch;
use App\Models\Location;
use App\Models\LocationBatch;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index()
    {
        // Fetch all stock adjustments with related products and location
        $stockAdjustments = StockAdjustment::with('adjustmentProducts.product', 'location')->get();

        return response()->json([
            'status' => 200,
            'stockAdjustment' => $stockAdjustments
        ]);
    }

    public function addStockAdjustment()
    {
        return view('stock_adjustment.add_stock_adjustment');
    }

    public function stockAdjustmentList()
    {
        return view('stock_adjustment.stock_adjustment');
    }

    // Store or update stock adjustment
    public function storeOrUpdate(Request $request, $id = null)
    {
        // Validate the request
        $request->validate([
            'date' => 'required|date',
            'location_id' => 'required|exists:locations,id',
            'adjustment_type' => 'required|in:increase,decrease',
            'total_amount_recovered' => 'nullable|numeric',
            'reason' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.batch_id' => 'required|exists:batches,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Use DB::transaction to handle the transaction
        DB::transaction(function () use ($request, $id) {
            // Determine if we are updating or creating a new stock adjustment
            $stockAdjustment = $id ? StockAdjustment::findOrFail($id) : new StockAdjustment();

            // Generate the reference number if creating a new adjustment
            $referenceNo = $id ? $stockAdjustment->reference_no : $this->generateReferenceNumber();

            // Update or create the stock adjustment
            $stockAdjustment->fill([
                'reference_no' => $referenceNo,
                'date' => $request->date,
                'location_id' => $request->location_id,
                'adjustment_type' => $request->adjustment_type,
                'total_amount_recovered' => $request->total_amount_recovered,
                'reason' => $request->reason,
            ])->save();

            // Delete existing adjustment products if updating
            if ($id) {
                $stockAdjustment->adjustmentProducts()->delete();
            }

            // Add products to the stock adjustment
            foreach ($request->products as $product) {
                $subtotal = $product['quantity'] * $product['unit_price'];

                AdjustmentProduct::create([
                    'stock_adjustment_id' => $stockAdjustment->id,
                    'product_id' => $product['product_id'],
                    'batch_id' => $product['batch_id'],
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                // Update stock history and location batch quantity
                $locationBatch = LocationBatch::where('batch_id', $product['batch_id'])
                    ->where('location_id', $request->location_id)
                    ->first();

                if ($locationBatch) {
                    $newQuantity = $request->adjustment_type === 'increase'
                        ? $locationBatch->qty + $product['quantity']
                        : $locationBatch->qty - $product['quantity'];

                    $locationBatch->update(['qty' => $newQuantity]);

                    // Record stock history
                    StockHistory::create([
                        'loc_batch_id' => $locationBatch->id,
                        'quantity' => -$product['quantity'],
                        'stock_type' => StockHistory::STOCK_TYPE_ADJUSTMENT,
                    ]);

                    // Update the total quantity in the batches table
                    $batch = Batch::find($product['batch_id']);
                    if ($batch) {
                        $newBatchQuantity = $request->adjustment_type === 'increase'
                            ? $batch->qty + $product['quantity']
                            : $batch->qty - $product['quantity'];

                        $batch->update(['qty' => $newBatchQuantity]);
                    }
                }
            }
        });

        // Return success response
        return response()->json([
            'status' => 200,
            'message' => $id ? 'Stock adjustment updated successfully!' : 'Stock adjustment created successfully!',
        ]);
    }

    private function generateReferenceNumber()
    {
        // Get the last stock adjustment
        $lastAdjustment = StockAdjustment::orderBy('id', 'desc')->first();

        // Extract the last reference number's numeric part
        $lastNumber = $lastAdjustment ? (int) substr($lastAdjustment->reference_no, 4) : 0;

        // Increment the number and pad it with leading zeros
        $nextNumber = $lastNumber + 1;
        $referenceNo = 'ADJ-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return $referenceNo;
    }

    // Show details of a specific stock adjustment
    public function show(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load('adjustmentProducts.product', 'location');
        return view('stock_adjustments.show', compact('stockAdjustment'));
    }

    public function edit($id)
    {
        // Find the stock adjustment by ID
        $stockAdjustment = StockAdjustment::with('adjustmentProducts.product', 'location')->findOrFail($id);

        if (request()->ajax() || request()->is('api/*')) {
            return response()->json([
                'status' => 200,
                'stockAdjustment' => $stockAdjustment,
            ]);
        }

        return view('stock_adjustment.add_stock_adjustment');
    }
}
