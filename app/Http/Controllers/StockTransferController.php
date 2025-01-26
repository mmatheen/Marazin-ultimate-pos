<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferProduct;
use App\Models\Batch;
use App\Models\LocationBatch;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function stockTranfer()
    {
        return view('stock_tranfer.stock_transfer');
    }

    public function addStockTransfer()
    {
        return view('stock_tranfer.add_stock_transfer');
    }

    private function generateReferenceNumber()
    {
        $latestTransfer = StockTransfer::latest()->first();
        if ($latestTransfer) {
            $lastRefNumber = intval(substr($latestTransfer->reference_no, 3));
            $newRefNumber = $lastRefNumber + 1;
        } else {
            $newRefNumber = 1;
        }
        return 'REF' . str_pad($newRefNumber, 3, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'from_location_id' => 'required|exists:locations,id|different:to_location_id',
            'to_location_id' => 'required|exists:locations,id',
            'transfer_date' => 'required|date',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.batch_id' => 'required|exists:batches,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.sub_total' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // Generate the reference number
                $referenceNo = $this->generateReferenceNumber();

                // Calculate the final total
                $finalTotal = array_reduce($request->products, function ($carry, $product) {
                    return $carry + $product['sub_total'];
                }, 0);

                // Create the stock transfer
                $stockTransfer = StockTransfer::create([
                    'from_location_id' => $request->from_location_id,
                    'to_location_id' => $request->to_location_id,
                    'transfer_date' => $request->transfer_date,
                    'reference_no' => $referenceNo,
                    'final_total' => $finalTotal,
                ]);

                // Iterate through the products and update the quantities
                foreach ($request->products as $product) {
                    // Check the quantity of the batch at the source location
                    $fromLocationBatch = LocationBatch::where('batch_id', $product['batch_id'])
                        ->where('location_id', $request->from_location_id)
                        ->firstOrFail();

                    if ($fromLocationBatch->qty < $product['quantity']) {
                        // Throw an exception to rollback the transaction and return an error message
                        throw new \Exception('The selected batch does not have enough quantity at the source location. Please select another batch or adjust the quantity.');
                    }

                    // Create StockTransferProduct entry
                    StockTransferProduct::create([
                        'stock_transfer_id' => $stockTransfer->id,
                        'product_id' => $product['product_id'],
                        'batch_id' => $product['batch_id'],
                        'quantity' => $product['quantity'],
                        'unit_price' => $product['unit_price'],
                        'sub_total' => $product['sub_total'],
                    ]);

                    // Update source location batch quantity
                    $fromLocationBatch->decrement('qty', $product['quantity']);

                    // Update destination location batch quantity or create if it doesn't exist
                    $toLocationBatch = LocationBatch::firstOrCreate(
                        ['batch_id' => $product['batch_id'], 'location_id' => $request->to_location_id],
                        ['qty' => 0]
                    );
                    $toLocationBatch->increment('qty', $product['quantity']);

                    // Update Batch table
                    $batch = Batch::find($product['batch_id']);
                    $batch->decrement('qty', $product['quantity']);
                    $batch->increment('qty', $product['quantity']);

                    // Create StockHistory entries
                    StockHistory::create([
                        'loc_batch_id' => $fromLocationBatch->id,
                        'quantity' => $product['quantity'],
                        'stock_type' => StockHistory::STOCK_TYPE_TRANSFER_OUT,
                    ]);

                    StockHistory::create([
                        'loc_batch_id' => $toLocationBatch->id,
                        'quantity' => $product['quantity'],
                        'stock_type' => StockHistory::STOCK_TYPE_TRANSFER_IN,
                    ]);
                }
            });

            return response()->json(['message' => 'Stock transfer created and updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
