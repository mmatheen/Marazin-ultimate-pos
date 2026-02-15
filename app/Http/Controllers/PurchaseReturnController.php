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
use App\Services\UnifiedLedgerService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseReturnController extends Controller
{
    protected $unifiedLedgerService;

    function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        $this->middleware('permission:view purchase-return', ['only' => ['purchaseReturn', 'index', 'show']]);
        $this->middleware('permission:create purchase-return', ['only' => ['addPurchaseReturn', 'store', 'storeOrUpdate']]);
        $this->middleware('permission:edit purchase-return', ['only' => ['edit', 'update', 'storeOrUpdate']]);
        $this->middleware('permission:delete purchase-return', ['only' => ['destroy']]);
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
            'products.*.free_quantity' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value !== null && $value > 0) {
                        // Extract the index from the attribute
                        if (preg_match('/products\.(\d+)\.free_quantity/', $attribute, $matches)) {
                            $index = $matches[1];
                            $productData = $request->input("products.$index");
                            if ($productData && isset($productData['product_id'])) {
                                $product = \App\Models\Product::find($productData['product_id']);
                                // Validate unit type (integer vs decimal)
                                if ($product && $product->unit && !$product->unit->allow_decimal && floor($value) != $value) {
                                    $fail("The free quantity must be an integer for this unit.");
                                }
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
                $isUpdate = !is_null($purchaseReturnId);

                // Store old values for ledger reversal if updating
                $oldReturnTotal = 0;
                $oldReferenceNo = null;
                if ($isUpdate) {
                    $existingReturn = PurchaseReturn::find($purchaseReturnId);
                    if ($existingReturn) {
                        $oldReturnTotal = $existingReturn->return_total;
                        $oldReferenceNo = $existingReturn->reference_no;
                    }
                }

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
                        // Removed total_due - it's auto-calculated by the database
                        'payment_status' => 'Due',
                    ]
                );

                // If updating, reverse stock adjustments for existing products
                if ($isUpdate) {
                    // Note: UnifiedLedgerService will handle ledger cleanup automatically

                    $existingProducts = $purchaseReturn->purchaseReturnProducts;
                    foreach ($existingProducts as $existingProduct) {
                        $quantity = $existingProduct->quantity;
                        $batchId = $existingProduct->batch_no;

                        // Restore batch or FIFO stock
                        $this->restoreStock($existingProduct->product_id, $purchaseReturn->location_id, $quantity, $batchId);

                        // Delete existing purchase return products
                        $existingProduct->delete();
                    }
                }

                // Process each product in the request
                foreach ($request->products as $productData) {
                    // Validate that product exists and has stock in the location
                    $product = \App\Models\Product::find($productData['product_id']);
                    if (!$product) {
                        throw new \Exception("Product ID {$productData['product_id']} not found.");
                    }

                    // Check if there's sufficient stock in the location for the return
                    $availableStock = $this->getProductStockInLocation($productData['product_id'], $request->location_id, $productData['batch_id'] ?? null);
                    if ($availableStock < $productData['quantity']) {
                        throw new \Exception("Insufficient stock for product '{$product->product_name}'. Available: {$availableStock}, Requested: {$productData['quantity']}");
                    }

                    $this->processProductReturn($productData, $purchaseReturn->id, $request->location_id);
                }

                // Record or update purchase return in ledger
                if ($isUpdate) {
                    $this->unifiedLedgerService->updatePurchaseReturn($purchaseReturn, $oldReferenceNo);
                } else {
                    $this->unifiedLedgerService->recordPurchaseReturn($purchaseReturn);
                }

                // Clear cache to ensure POS gets updated stock quantities
                \Illuminate\Support\Facades\Cache::flush();

                // Note: UnifiedLedgerService automatically handles balance calculations
            });

            $message = $purchaseReturnId ? 'Purchase return updated successfully!' : 'Purchase return recorded successfully!';
            return response()->json(['status' => 200, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'message' => $e->getMessage()]);
        }
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
        $paidQtyToReturn = floatval($productData['quantity'] ?? 0);
        $freeQtyToReturn = floatval($productData['free_quantity'] ?? 0);
        $totalQuantityToReturn = $paidQtyToReturn + $freeQtyToReturn;
        $batchId = $productData['batch_id'];

        if (empty($batchId)) {
            // Handle FIFO return (no specific batch) - reduce both paid + free
            $this->reduceStockFIFO($productData['product_id'], $locationId, $totalQuantityToReturn);
        } else {
            // Handle batch-specific return - reduce both paid + free
            $this->reduceBatchStock($batchId, $locationId, $totalQuantityToReturn);
        }

        // Create purchase return product record
        PurchaseReturnProduct::create([
            'purchase_return_id' => $purchaseReturnId,
            'product_id' => $productData['product_id'],
            'quantity' => $paidQtyToReturn,
            'free_quantity' => $freeQtyToReturn,
            'unit_price' => $productData['unit_price'],
            'subtotal' => $productData['subtotal'],
            'batch_no' => $batchId,
        ]);
    }

    private function reduceBatchStock($batchId, $locationId, $quantity)
    {
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
        $locationBatch = LocationBatch::where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->firstOrFail();

        $locationBatch->increment('qty', $quantity);

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


    private function getProductStockInLocation($productId, $locationId, $batchId = null)
    {
        if ($batchId) {
            // Get stock for specific batch
            $locationBatch = LocationBatch::where('batch_id', $batchId)
                ->where('location_id', $locationId)
                ->first();
            return $locationBatch ? $locationBatch->qty : 0;
        } else {
            // Get total stock for product across all batches in location
            return LocationBatch::whereHas('batch', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('location_id', $locationId)
            ->sum('qty');
        }
    }

    /**
     * Get all products with stock in a specific location for purchase returns
     * Supports search functionality similar to POS autocomplete
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllProductsWithStock(Request $request)
    {
        try {
            $locationId = $request->get('location_id');
            $search = $request->get('search'); // Add search parameter

            if (!$locationId) {
                return response()->json(['message' => 'Location ID is required.'], 400);
            }

            // Build query for products with stock in the specified location
            $productsQuery = Product::with([
                'unit:id,name,short_name,allow_decimal',
                'batches' => function ($query) {
                    $query->select([
                        'id',
                        'batch_no',
                        'product_id',
                        'unit_cost',
                        'wholesale_price',
                        'special_price',
                        'retail_price',
                        'max_retail_price',
                        'expiry_date'
                    ]);
                },
                'batches.locationBatches' => function ($q) use ($locationId) {
                    $q->where('location_id', $locationId)
                      ->where('qty', '>', 0)
                      ->select(['id', 'batch_id', 'location_id', 'qty']);
                }
            ])->where('is_active', true)
              ->whereHas('batches.locationBatches', function ($q) use ($locationId) {
                  $q->where('location_id', $locationId)
                    ->where('qty', '>', 0);
              });

            // Add search functionality if search term is provided
            if ($search) {
                $productsQuery->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });

                // Order by relevance: exact matches first, then partial matches
                $productsQuery->orderByRaw("
                    CASE
                        WHEN sku = ? THEN 1
                        WHEN LOWER(product_name) = LOWER(?) THEN 2
                        WHEN sku LIKE ? THEN 3
                        WHEN LOWER(product_name) LIKE LOWER(?) THEN 4
                        WHEN description LIKE ? THEN 5
                        ELSE 6
                    END,
                    CHAR_LENGTH(sku) ASC,
                    product_name ASC
                ", [
                    $search,                    // Exact SKU match
                    $search,                    // Exact product name match
                    $search . '%',              // SKU starts with search term
                    $search . '%',              // Product name starts with search term
                    '%' . $search . '%'         // Description contains search term
                ]);
            }

            $productsWithStock = $productsQuery->get();
            $products = [];

            foreach ($productsWithStock as $product) {
                $batches = [];
                foreach ($product->batches as $batch) {
                    foreach ($batch->locationBatches as $locationBatch) {
                        if ($locationBatch->qty > 0) {
                            $batches[] = [
                                'batch_id' => $batch->id,
                                'batch_no' => $batch->batch_no,
                                'quantity' => $locationBatch->qty,
                                'unit_cost' => $batch->unit_cost,
                                'wholesale_price' => $batch->wholesale_price,
                                'special_price' => $batch->special_price,
                                'retail_price' => $batch->retail_price,
                                'max_retail_price' => $batch->max_retail_price,
                                'expiry_date' => $batch->expiry_date,
                            ];
                        }
                    }
                }

                if (!empty($batches)) {
                    $products[] = [
                        'product' => $product,
                        'unit' => $product->unit ?? null,
                        'batches' => $batches,
                        'total_stock' => array_sum(array_column($batches, 'quantity'))
                    ];
                }
            }

            return response()->json(['products' => $products], 200);
        } catch (\Exception $e) {
            Log::error('Error in getAllProductsWithStock: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching products with stock.'], 500);
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
        $purchaseReturn = PurchaseReturn::with([
            'supplier',
            'location',
            'purchaseReturnProducts' => function($query) {
                $query->with([
                    'product',
                    'batch' => function($batchQuery) {
                        $batchQuery->with('locationBatches');
                    }
                ]);
            }
        ])->findOrFail($id);

        // Process the data to ensure batch information is available
        foreach ($purchaseReturn->purchaseReturnProducts as $returnProduct) {
            // If batch_no is null (FIFO), we need to get the latest batch for display
            if (!$returnProduct->batch_no) {
                $latestBatch = \App\Models\Batch::where('product_id', $returnProduct->product_id)
                    ->whereHas('locationBatches', function($query) use ($purchaseReturn) {
                        $query->where('location_id', $purchaseReturn->location_id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($latestBatch) {
                    // Create a mock batch relationship for consistency
                    $returnProduct->setRelation('batch', $latestBatch);
                }
            }
        }

        if (request()->ajax() || request()->is('api/*')) {
            return response()->json(['purchase_return' => $purchaseReturn], 200);
        }

        return view('purchase.add_purchase_return', compact('purchaseReturn'));
    }





}
