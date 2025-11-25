<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferProduct;
use App\Models\Batch;
use App\Models\LocationBatch;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockTransferController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view stock-transfer', ['only' => ['index', 'stockTransfer', 'show']]);
        $this->middleware('permission:create stock-transfer', ['only' => ['addStockTransfer', 'store', 'storeOrUpdate']]);
        $this->middleware('permission:edit stock-transfer', ['only' => ['edit', 'update', 'storeOrUpdate']]);
        $this->middleware('permission:delete stock-transfer', ['only' => ['destroy']]);
    }

    // Fetch all stock transfers
    public function index()
    {
        $stockTransfers = StockTransfer::with(['fromLocation', 'toLocation', 'stockTransferProducts.product'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc') // Add secondary sort by ID for consistent ordering
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
        // Use database transaction with locking to prevent race conditions
        return DB::transaction(function () {
            // Find the highest reference number to avoid duplicates
            $maxRef = DB::table('stock_transfers')
                ->where('reference_no', 'like', 'REF%')
                ->lockForUpdate()
                ->orderByRaw('CAST(REGEXP_REPLACE(reference_no, "[^0-9]", "") AS UNSIGNED) DESC')
                ->first();
                
            if ($maxRef) {
                // Extract only the numeric part from reference (REF124, REF124_DUP1 -> 124)
                preg_match('/REF(\d+)/', $maxRef->reference_no, $matches);
                $lastRefNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $newRefNumber = $lastRefNumber + 1;
            } else {
                $newRefNumber = 1;
            }
            
            $newReference = 'REF' . str_pad($newRefNumber, 3, '0', STR_PAD_LEFT);
            
            // Final safety check - if reference exists, keep incrementing
            while (DB::table('stock_transfers')->where('reference_no', $newReference)->exists()) {
                $newRefNumber++;
                $newReference = 'REF' . str_pad($newRefNumber, 3, '0', STR_PAD_LEFT);
            }
            
            return $newReference;
        });
    }

    public function storeOrUpdate(Request $request, $id = null)
    {
        // Log the request for debugging
        Log::info('Stock Transfer storeOrUpdate called', [
            'id' => $id,
            'method' => $request->method(),
            'data' => $request->all()
        ]);

        $request->validate([
            'from_location_id' => 'required|exists:locations,id|different:to_location_id',
            'to_location_id' => 'required|exists:locations,id',
            'transfer_date' => 'required|date',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.batch_id' => 'required|exists:batches,id',
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
            'products.*.sub_total' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                // Determine if we are updating or creating a new stock transfer
                $stockTransfer = $id ? StockTransfer::with('stockTransferProducts')->findOrFail($id) : new StockTransfer();

                // Log the stock transfer being updated
                if ($id) {
                    Log::info('Updating existing stock transfer', ['id' => $id, 'reference_no' => $stockTransfer->reference_no]);
                }

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

                // Reverse previous stock movements if updating
                if ($id) {
                    Log::info('Reversing previous stock movements for transfer', ['id' => $id]);
                    $this->reverseStockTransferMovements($stockTransfer);
                    $stockTransfer->stockTransferProducts()->delete();
                    Log::info('Previous stock movements reversed and products deleted');
                }

                // Iterate through the products and update the quantities
                foreach ($request->products as $index => $product) {
                    // Check the quantity of the batch at the source location
                    $fromLocationBatch = LocationBatch::where('batch_id', $product['batch_id'])
                        ->where('location_id', $request->from_location_id)
                        ->first();

                    if (!$fromLocationBatch) {
                        throw new \Exception("Batch not found at source location for product {$index}");
                    }

                    if ($fromLocationBatch->qty < $product['quantity']) {
                        $productName = \App\Models\Product::find($product['product_id'])->product_name ?? 'Unknown Product';
                        throw new \Exception("Insufficient stock for {$productName}. Available: {$fromLocationBatch->qty}, Required: {$product['quantity']}");
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
                    // Use a transaction-safe approach to prevent duplicates
                    $toLocationBatch = LocationBatch::where('batch_id', $product['batch_id'])
                        ->where('location_id', $request->to_location_id)
                        ->lockForUpdate()
                        ->first();
                        
                    if ($toLocationBatch) {
                        $toLocationBatch->increment('qty', $product['quantity']);
                    } else {
                        // Create new location batch
                        $toLocationBatch = LocationBatch::create([
                            'batch_id' => $product['batch_id'],
                            'location_id' => $request->to_location_id,
                            'qty' => $product['quantity']
                        ]);
                    }

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

            $message = $id ? 
                "Stock transfer updated successfully at " . now()->format('Y-m-d H:i:s') : 
                "Stock transfer created successfully at " . now()->format('Y-m-d H:i:s');
                
            return response()->json(['message' => $message], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // Edit a stock transfer

    public function edit($id)
    {
        try {
            $stockTransfer = StockTransfer::with([
                'stockTransferProducts.product.batches.locationBatches', 
                'fromLocation', 
                'toLocation'
            ])->findOrFail($id);

            Log::info('Loading stock transfer for edit', [
                'id' => $id,
                'reference_no' => $stockTransfer->reference_no,
                'products_count' => $stockTransfer->stockTransferProducts->count()
            ]);

            if (request()->ajax() || request()->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'stockTransfer' => $stockTransfer,
                ]);
            }

            return view('stock_transfer.add_stock_transfer');
        } catch (\Exception $e) {
            Log::error('Error loading stock transfer for edit', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            if (request()->ajax() || request()->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error loading stock transfer: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Stock transfer not found.');
        }
    }


    // Delete a stock transfer

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $stockTransfer = StockTransfer::with('stockTransferProducts')->findOrFail($id);
                
                // Reverse all stock movements before deleting
                $this->reverseStockTransferMovements($stockTransfer);
                
                // Delete the stock transfer (products will be deleted via cascade)
                $stockTransfer->delete();
            });

            return response()->json([
                'status' => 200,
                'message' => 'Stock transfer deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete stock transfer: ' . $e->getMessage(),
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

    /**
     * Reverse stock movements for a stock transfer
     * This is used when updating or deleting a transfer
     */
    private function reverseStockTransferMovements(StockTransfer $stockTransfer)
    {
        Log::info('Starting reversal of stock transfer movements', [
            'transfer_id' => $stockTransfer->id,
            'reference_no' => $stockTransfer->reference_no
        ]);

        foreach ($stockTransfer->stockTransferProducts as $transferProduct) {
            $batchId = $transferProduct->batch_id;
            $quantity = $transferProduct->quantity;
            $fromLocationId = $stockTransfer->from_location_id;
            $toLocationId = $stockTransfer->to_location_id;

            Log::info('Reversing transfer product', [
                'product_id' => $transferProduct->product_id,
                'batch_id' => $batchId,
                'quantity' => $quantity,
                'from_location' => $fromLocationId,
                'to_location' => $toLocationId
            ]);

            // Reverse the movement: Add back to source location
            $fromLocationBatch = LocationBatch::where('batch_id', $batchId)
                ->where('location_id', $fromLocationId)
                ->first();

            if ($fromLocationBatch) {
                $fromLocationBatch->increment('qty', $quantity);
                Log::info('Added quantity back to source location', [
                    'location_batch_id' => $fromLocationBatch->id,
                    'added_quantity' => $quantity,
                    'new_quantity' => $fromLocationBatch->fresh()->qty
                ]);
            } else {
                // Create the location batch if it doesn't exist
                $fromLocationBatch = LocationBatch::create([
                    'batch_id' => $batchId,
                    'location_id' => $fromLocationId,
                    'qty' => $quantity
                ]);
                Log::info('Created new location batch for source', [
                    'location_batch_id' => $fromLocationBatch->id,
                    'quantity' => $quantity
                ]);
            }

            // Log the reversal in stock history
            StockHistory::create([
                'loc_batch_id' => $fromLocationBatch->id,
                'quantity' => $quantity,
                'stock_type' => StockHistory::STOCK_TYPE_ADJUSTMENT,
            ]);

            // Reverse the movement: Remove from destination location
            $toLocationBatch = LocationBatch::where('batch_id', $batchId)
                ->where('location_id', $toLocationId)
                ->first();

            if ($toLocationBatch) {
                // Prevent negative quantities
                if ($toLocationBatch->qty >= $quantity) {
                    $toLocationBatch->decrement('qty', $quantity);
                    
                    Log::info('Removed quantity from destination location', [
                        'location_batch_id' => $toLocationBatch->id,
                        'removed_quantity' => $quantity,
                        'new_quantity' => $toLocationBatch->fresh()->qty
                    ]);

                    // Log the reversal
                    StockHistory::create([
                        'loc_batch_id' => $toLocationBatch->id,
                        'quantity' => -$quantity, // Negative because it's being removed
                        'stock_type' => StockHistory::STOCK_TYPE_ADJUSTMENT,
                    ]);
                    
                    // Delete the location_batch if quantity becomes 0
                    if ($toLocationBatch->fresh()->qty == 0) {
                        $toLocationBatch->delete();
                        Log::info('Deleted empty location batch', ['location_batch_id' => $toLocationBatch->id]);
                    }
                } else {
                    $availableQty = $toLocationBatch->qty;
                    Log::warning("Cannot reverse transfer: Insufficient stock at destination location", [
                        'batch_id' => $batchId,
                        'location_id' => $toLocationId,
                        'available_qty' => $availableQty,
                        'required_qty' => $quantity
                    ]);
                    
                    // Partial reversal - remove what's available
                    if ($availableQty > 0) {
                        $toLocationBatch->update(['qty' => 0]);
                        StockHistory::create([
                            'loc_batch_id' => $toLocationBatch->id,
                            'quantity' => -$availableQty,
                            'stock_type' => StockHistory::STOCK_TYPE_ADJUSTMENT,
                        ]);
                        $toLocationBatch->delete();
                        Log::info('Partial reversal completed', [
                            'batch_id' => $batchId,
                            'location_id' => $toLocationId,
                            'removed_qty' => $availableQty
                        ]);
                    }
                }
            } else {
                Log::info('No stock found at destination location to reverse', [
                    'batch_id' => $batchId,
                    'location_id' => $toLocationId
                ]);
            }
        }

        Log::info('Completed reversal of stock transfer movements', [
            'transfer_id' => $stockTransfer->id
        ]);
    }
}
