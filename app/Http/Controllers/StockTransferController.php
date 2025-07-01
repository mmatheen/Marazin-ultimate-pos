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
    function __construct()
    {
        $this->middleware('permission:view stock-transfer', ['only' => ['index', 'stockTransfer']]);
        $this->middleware('permission:add stock-transfer', ['only' => ['addStockTransfer']]);
        $this->middleware('permission:create stock-transfer', ['only' => ['storeOrUpdate']]);
        $this->middleware('permission:edit stock-transfer', ['only' => ['edit']]);
        $this->middleware('permission:delete stock-transfer', ['only' => ['destroy']]);
    }

    // Fetch all stock transfers
    public function index()
    {
        $stockTransfers = StockTransfer::with(['fromLocation', 'toLocation', 'stockTransferProducts.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'stockTransfers' => $stockTransfers,
        ]);
    }

    public function stockTransfer()
    {
        return view('stock_transfer.stock_transfer');
    }

    public function addStockTransfer()
    {
        return view('stock_transfer.add_stock_transfer');
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

    public function storeOrUpdate(Request $request, $id = null)
    {
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
            DB::transaction(function () use ($request, $id) {
                // Determine if we are updating or creating a new stock transfer
                $stockTransfer = $id ? StockTransfer::findOrFail($id) : new StockTransfer();

                // Generate the reference number if creating a new transfer
                $referenceNo = $id ? $stockTransfer->reference_no : $this->generateReferenceNumber();

                // Calculate the final total
                $finalTotal = array_reduce($request->products, function ($carry, $product) {
                    return $carry + $product['sub_total'];
                }, 0);

                // Update or create the stock transfer
                $stockTransfer->fill([
                    'from_location_id' => $request->from_location_id,
                    'to_location_id' => $request->to_location_id,
                    'transfer_date' => $request->transfer_date,
                    'reference_no' => $referenceNo,
                    'final_total' => $finalTotal,
                    'note' => $request->note,
                    'status' => $request->status,
                ])->save();

                // Delete existing transfer products if updating
                if ($id) {
                    $stockTransfer->stockTransferProducts()->delete();
                }

                // Iterate through the products and update the quantities
                foreach ($request->products as $product) {
                    // Check the quantity of the batch at the source location
                    $fromLocationBatch = LocationBatch::where('batch_id', $product['batch_id'])
                        ->where('location_id', $request->from_location_id)
                        ->firstOrFail();

                    if ($fromLocationBatch->qty < $product['quantity']) {
                        throw new \Exception('The selected batch does not have enough quantity at the source location.');
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

            return response()->json(['message' => $id ? 'Stock transfer updated successfully.' : 'Stock transfer created successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // Edit a stock transfer

    public function edit($id)
    {
        $stockTransfer = StockTransfer::with('stockTransferProducts.product.batches', 'fromLocation', 'toLocation')->findOrFail($id);

        if (request()->ajax() || request()->is('api/*')) {
            return response()->json([
                'status' => 200,
                'stockTransfer' => $stockTransfer,
            ]);
        }

        return view('stock_transfer.add_stock_transfer');
    }


    // Delete a stock transfer

    public function destroy($id)
    {
        try {
            $stockTransfer = StockTransfer::findOrFail($id);
            $stockTransfer->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Stock transfer deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete stock transfer.',
            ]);
        }
    }

    // Fetch stock transfer products for a specific transfer
    public function getStockTransferWithActivityLog($id)
    {
        $stockTransfer = StockTransfer::with([
            'fromLocation',
            'toLocation',
            'stockTransferProducts.product.batches'
        ])->findOrFail($id);

        // Assuming you have an ActivityLog model and a relation or query to fetch logs for this transfer
        $activityLogs = \Spatie\Activitylog\Models\Activity::forSubject($stockTransfer)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'stockTransfer' => $stockTransfer,
            'activityLogs' => $activityLogs,
        ]);
    }
}
