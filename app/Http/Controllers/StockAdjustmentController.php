<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\AdjustmentProduct;
use App\Models\Batch;
use App\Models\User;
use App\Models\LocationBatch;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view stock-adjustment', ['only' => ['index', 'stockAdjustmentList', 'show']]);
        $this->middleware('permission:create stock-adjustment', ['only' => ['addStockAdjustment', 'store', 'storeOrUpdate']]);
        $this->middleware('permission:edit stock-adjustment', ['only' => ['edit', 'update', 'storeOrUpdate']]);
        $this->middleware('permission:delete stock-adjustment', ['only' => ['destroy']]);
    }

    public function index()
    {
        // Fetch all stock adjustments with related products, location, and user - select only needed columns
        $stockAdjustments = StockAdjustment::select('id', 'reference_no', 'date', 'location_id', 'adjustment_type', 'total_amount_recovered', 'reason', 'user_id', 'created_at')
            ->with([
                'adjustmentProducts:id,stock_adjustment_id,product_id,batch_id,quantity,unit_price,subtotal',
                'adjustmentProducts.product:id,product_name,sku',
                'location:id,name',
                'user:id,user_name,full_name'
            ])->get();

        // Return response as JSON
        return response()->json([
            'status' => 200,
            'stockAdjustment' => $stockAdjustments,
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
        ]);

        // Use DB::transaction to handle the transaction
        DB::transaction(function () use ($request, $id) {
            // Determine if we are updating or creating a new stock adjustment
            $stockAdjustment = $id ? StockAdjustment::findOrFail($id) : new StockAdjustment();

            // On update, reverse previously applied stock movements before re-applying new lines.
            // This keeps stock and stock_history consistent after edits.
            if ($id) {
                $stockAdjustment->load('adjustmentProducts');

                $reverseType = $stockAdjustment->adjustment_type === 'increase' ? 'decrease' : 'increase';
                foreach ($stockAdjustment->adjustmentProducts as $existingProduct) {
                    $this->applyStockAdjustmentMovement(
                        (int) $existingProduct->batch_id,
                        (int) $stockAdjustment->location_id,
                        (float) $existingProduct->quantity,
                        $reverseType
                    );
                }

                $stockAdjustment->adjustmentProducts()->delete();
            }

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
                'user_id' => auth()->id(),
            ])->save();

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

                $this->applyStockAdjustmentMovement(
                    (int) $product['batch_id'],
                    (int) $request->location_id,
                    (float) $product['quantity'],
                    (string) $request->adjustment_type
                );
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

    /**
     * Apply a single stock adjustment movement and write a matching stock_history row.
     */
    private function applyStockAdjustmentMovement(int $batchId, int $locationId, float $quantity, string $adjustmentType): void
    {
        $locationBatch = LocationBatch::where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if (!$locationBatch) {
            if ($adjustmentType === 'increase') {
                $locationBatch = LocationBatch::create([
                    'batch_id' => $batchId,
                    'location_id' => $locationId,
                    'qty' => 0,
                    'free_qty' => 0,
                ]);
            } else {
                throw new \Exception("Location batch not found for decrease adjustment. Batch ID: {$batchId}, Location ID: {$locationId}");
            }
        }

        $batch = Batch::where('id', $batchId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($adjustmentType === 'increase') {
            $locationBatch->increment('qty', $quantity);
            $batch->increment('qty', $quantity);
            $signedQuantity = $quantity;
        } else {
            if ((float) $locationBatch->qty < $quantity) {
                throw new \Exception("Insufficient location stock for decrease adjustment. Available: {$locationBatch->qty}, Requested: {$quantity}");
            }

            if ((float) $batch->qty < $quantity) {
                throw new \Exception("Insufficient batch stock for decrease adjustment. Available: {$batch->qty}, Requested: {$quantity}");
            }

            $locationBatch->decrement('qty', $quantity);
            $batch->decrement('qty', $quantity);
            $signedQuantity = -$quantity;
        }

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $signedQuantity,
            'stock_type' => StockHistory::STOCK_TYPE_ADJUSTMENT,
        ]);
    }

    // Show details of a specific stock adjustment
    // public function show(StockAdjustment $stockAdjustment)
    // {
    //     $stockAdjustment->load('adjustmentProducts.product', 'location');
    //     return view('stock_adjustments.show', compact('stockAdjustment'));
    // }

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
