<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Unit;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\SubCategory;
use App\Models\MainCategory;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use App\Models\LocationBatch;
use App\Imports\importProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportProductTemplate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Events\StockUpdated;
use App\Models\Discount;
use App\Models\ImeiNumber;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view product', ['only' => ['index', 'show', 'product', 'getStockHistory']]);
        $this->middleware('permission:create product', ['only' => ['store', 'addProduct']]);
        $this->middleware('permission:edit product', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete product', ['only' => ['destroy']]);
        $this->middleware('permission:import product', ['only' => ['importProduct', 'downloadProductImportTemplate']]);
        $this->middleware('permission:export product', ['only' => ['exportProduct']]);
        $this->middleware('permission:duplicate product', ['only' => ['duplicateProduct']]);
    }

    public function product()
    {
        return view('product.product');
    }

    public function addProduct()
    {
        return view('product.add_product');
    }


    public function getStockHistory($productId)
    {
        $locationId = request()->input('location_id');
        $searchTerm = request()->input('term'); // For select2 search

        // Handle AJAX search requests (for select2)
        if (request()->ajax() && $searchTerm) {
            return Product::where('product_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('sku', 'like', '%' . $searchTerm . '%')
                ->select('id', 'product_name', 'sku')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'text' => $product->product_name . ' - ' . $product->sku
                    ];
                });
        }

        // Fetch product with all necessary relationships
        $productQuery = Product::with([
            'locationBatches' => function ($query) use ($locationId) {
                if ($locationId) {
                    $query->where('location_id', $locationId);
                }
                $query->with('batch');
            },
            'locationBatches.stockHistories' => function ($query) {
                $query->with([
                    'locationBatch.batch.purchaseProducts.purchase.supplier',
                    'locationBatch.batch.salesProducts.sale.customer',
                    'locationBatch.batch.purchaseReturns.purchaseReturn.supplier',
                    'locationBatch.batch.saleReturns.salesReturn.customer',
                    'locationBatch.batch.stockAdjustments.stockAdjustment',
                    'locationBatch.batch.stockTransfers.stockTransfer',
                ]);
            }
        ]);

        $product = $productQuery->findOrFail($productId);

        // Flatten all stock histories across location batches
        $stockHistories = $product->locationBatches->flatMap(function ($locBatch) {
            return $locBatch->stockHistories;
        });

        if ($stockHistories->isEmpty()) {
            if (request()->ajax()) {
                return response()->json(['error' => 'No stock history found for this product'], 404);
            }
            return redirect()->back()->withErrors('No stock history found for this product.');
        }

        // Group by stock_type and sum quantities
        $stockTypeSums = $stockHistories->groupBy('stock_type')->map(function ($group) {
            return $group->sum('quantity');
        });

        // Define types for In and Out
        $inTypes = [
            StockHistory::STOCK_TYPE_OPENING,
            StockHistory::STOCK_TYPE_PURCHASE,
            StockHistory::STOCK_TYPE_SALE_RETURN_WITH_BILL,
            StockHistory::STOCK_TYPE_SALE_RETURN_WITHOUT_BILL,
            StockHistory::STOCK_TYPE_TRANSFER_IN,
        ];

        $outTypes = [
            StockHistory::STOCK_TYPE_SALE,
            StockHistory::STOCK_TYPE_ADJUSTMENT,
            StockHistory::STOCK_TYPE_PURCHASE_RETURN,
            StockHistory::STOCK_TYPE_TRANSFER_OUT,
        ];

        // Calculate totals
        $quantitiesIn = $stockTypeSums->filter(fn($val, $key) => in_array($key, $inTypes))->sum();
        $quantitiesOut = $stockTypeSums->filter(fn($val, $key) => in_array($key, $outTypes))->sum(fn($val) => abs($val));
        $currentStock = $quantitiesIn - $quantitiesOut;

        $responseData = [
            'product' => $product,
            'stock_histories' => $stockHistories,
            'stock_type_sums' => $stockTypeSums,
            'current_stock' => round($currentStock, 2),
        ];

        if (request()->ajax()) {
            return response()->json($responseData);
        }

        // For initial page load (non-AJAX)
        $products = Product::where('id', $productId)->get(); // Only load the current product initially
        $locations = Location::all();

        return view('product.product_stock_history', compact('products', 'locations'))->with($responseData);
    }

 
    public function initialProductDetails()
    {
        $mainCategories = MainCategory::all();
        $subCategories = SubCategory::with('mainCategory')->get();
        $brands = Brand::all();
        $units = Unit::all();
        $locations = Location::all();

        // Check if all collections have records
        if ($mainCategories->count() > 0 || $subCategories->count() > 0 || $brands->count() > 0 || $units->count() > 0 || $locations->count() > 0) {
            return response()->json([
                'status' => 200,
                'message' => [
                    'brands' => $brands,
                    'subCategories' => $subCategories,
                    'mainCategories' => $mainCategories,
                    'units' => $units,
                    'locations' => $locations,
                ]
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Records Found!"
            ]);
        }
    }

    public function index()
    {

        $user = Auth::user();

        // Check if user has a specific location associated
        if ($user->location_id !== null) {
            // Filter products by the user's location
            $locationId = $user->location_id;

            $getValue = Product::whereHas('locations', function ($query) use ($locationId) {
                $query->where('locations.id', $locationId);
            })->with('locations')->get();
        } else {
            $getValue = Product::with('locations')->get();
        }


        // Check if any records were found
        if ($getValue->count() > 0) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Records Found!"
            ]);
        }
    }


    public function getProductDetails($id)
    {
        // Fetch the product details by ID
        $product = Product::with(['locations', 'mainCategory', 'brand']) // Using the correct relationship names
            ->find($id);

        // Check if the product exists
        if (!$product) {
            return response()->json(['status' => 404, 'message' => 'Product not found']);
        }

        // Return product details as JSON
        return response()->json(['status' => 200, 'message' => $product]);
    }


    public function getLastProduct()
    {
        // Fetch the latest product along with relationships
        $product = Product::with(['unit', 'brand', 'locations'])->latest('created_at')->first();

        if ($product) {

            return response()->json([
                'status' => 200,
                'product' => $product,
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No product found.',
            ]);
        }
    }


    public function getProductsByCategory($categoryId)
    {

        // Fetch products with the specified category ID
        $products = Product::where('main_category_id', $categoryId)->get();

        if ($products) {

            return response()->json([
                'status' => 200,
                'message' => $products
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching products',
            ]);
        }
    }


    public function editProduct($id)
    {
        // Fetch the product and related data
        $product = Product::with(['locations', 'mainCategory', 'brand', 'unit'])->find($id);

        // Check if the product exists
        if (!$product) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);
        }

        $mainCategories = MainCategory::all();
        $subCategories = SubCategory::all();
        $brands = Brand::all();
        $units = Unit::all();
        $locations = Location::all();

        // Check if the request is AJAX
        if (request()->ajax() || request()->is('api/*')) {
            return response()->json([
                'status' => 200,
                'message' => [
                    'product' => $product,
                    'mainCategories' => $mainCategories,
                    'subCategories' => $subCategories,
                    'brands' => $brands,
                    'units' => $units,
                    'locations' => $locations,
                ]
            ]);
        }

        // Render the edit product view for non-AJAX requests
        return view('product.add_product', compact('product', 'mainCategories', 'subCategories', 'brands', 'units', 'locations'));
    }


    public function storeOrUpdate(Request $request, $id = null)
    {
        // Validation rules
        $rules = [
            'product_name' => 'required|string|max:255',
            'unit_id' => 'required|integer|exists:units,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'main_category_id' => 'required|integer|exists:main_categories,id',
            'sub_category_id' => 'nullable|integer|exists:sub_categories,id',
            'locations' => 'required|array',
            'locations.*' => 'required|integer|exists:locations,id',
            'stock_alert' => 'nullable|boolean',
            'alert_quantity' => 'nullable|numeric|min:0',
            'product_image' => 'nullable|mimes:jpeg,png,jpg,gif|max:5120',
            'description' => 'nullable|string',
            'is_imei_or_serial_no' => 'nullable|boolean',
            'is_for_selling' => 'required|boolean',
            'product_type' => 'nullable|string',
            'pax' => 'nullable|integer',
            'retail_price' => 'required|numeric|min:0',
            'whole_sale_price' => 'required|numeric|min:0',
            'special_price' => 'nullable|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'max_retail_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:products,sku,' . $id,
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        // File upload logic
        $fileName = null;
        if ($request->hasFile('product_image')) {
            $file = $request->file('product_image');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('/assets/images'), $fileName);
        }

        // Retrieve or create the product
        $product = $id ? Product::find($id) : new Product;

        if (!$product) {
            return response()->json(['status' => 404, 'message' => 'Product not found!']);
        }

        // Auto-increment SKU logic
        if (!$request->has('sku') || empty($request->sku)) {
            $lastProduct = Product::orderBy('id', 'desc')->first();
            $lastId = $lastProduct ? $lastProduct->id : 0;
            $autoIncrementSku = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            $product->sku = $autoIncrementSku;
        } else {
            $product->sku = $request->sku;
        }

        // Update product details
        $product->fill([
            'product_name' => $request->product_name,
            'unit_id' => $request->unit_id,
            'brand_id' => $request->brand_id,
            'main_category_id' => $request->main_category_id,
            'sub_category_id' => $request->sub_category_id,
            'stock_alert' => $request->stock_alert,
            'alert_quantity' => $request->alert_quantity,
            'product_image' => $fileName ? $fileName : $product->product_image,
            'description' => $request->description,
            'is_imei_or_serial_no' => $request->is_imei_or_serial_no,
            'is_for_selling' => $request->is_for_selling,
            'product_type' => $request->product_type,
            'pax' => $request->pax,
            'retail_price' => $request->retail_price,
            'whole_sale_price' => $request->whole_sale_price,
            'special_price' => $request->special_price,
            'original_price' => $request->original_price,
            'max_retail_price' => $request->max_retail_price,
        ])->save();

        // Sync locations
        $product->locations()->sync($request->locations);

        $message = $id ? 'Product Details Updated Successfully!' : 'New Product Details Created Successfully!';
        return response()->json(['status' => 200, 'message' => $message, 'product_id' => $product->id]);
    }

    public function showOpeningStock($productId)
    {
        $product = Product::with('locations')->findOrFail($productId);
        $locations = $product->locations;

        $openingStock = [
            'batches' => [],
        ];

        if (request()->ajax() || request()->is('api/*')) {
            return response()->json([
                'status' => 200,
                'product' => $product,
                'locations' => $locations,
                'openingStock' => $openingStock
            ]);
        }

        return view('product.opening_stock', [
            'product' => $product,
            'locations' => $locations,
            'openingStock' => $openingStock,
            'editing' => false
        ]);
    }

    public function editOpeningStock($productId)
    {
        $product = Product::with('locations')->findOrFail($productId);
        $locations = $product->locations;

        $batches = Batch::where('product_id', $productId)
            ->whereHas('locationBatches.stockHistories', function ($query) {
                $query->where('stock_type', StockHistory::STOCK_TYPE_OPENING);
            })
            ->with(['locationBatches.stockHistories' => function ($query) {
                $query->where('stock_type', StockHistory::STOCK_TYPE_OPENING);
            }])
            ->get();

        // Fetch existing IMEIs
        $imeis = ImeiNumber::where('product_id', $productId)
            ->orderBy('id')
            ->pluck('imei_number', 'id');


        $openingStock = [
            'product_id' => $product->id,
            'batches' => $batches->flatMap(function ($batch) {
                return $batch->locationBatches->map(function ($locationBatch) use ($batch) {
                    $location = Location::find($locationBatch->location_id);
                    return [
                        'batch_id' => $locationBatch->batch_id,
                        'location_id' => $locationBatch->location_id,
                        'location_name' => $location->name,
                        'quantity' => $locationBatch->qty,
                        'batch_no' => $batch->batch_no,
                        'expiry_date' => $batch->expiry_date,
                        'stock_histories' => $locationBatch->stockHistories->map(function ($stockHistory) {
                            return [
                                'stock_history_id' => $stockHistory->id,
                                'quantity' => $stockHistory->quantity,
                                'stock_type' => $stockHistory->stock_type,
                            ];
                        })->values(),
                    ];
                });
            })->values(),
            'imeis' => $imeis,
        ];

        if (request()->ajax() || request()->is('api/*')) {
            return response()->json(['status' => 200, 'product' => $product, 'locations' => $locations, 'openingStock' => $openingStock], 200);
        }

        return view('product.opening_stock', [
            'product' => $product,
            'locations' => $locations,
            'openingStock' => $openingStock,
            'editing' => true
        ]);
    }

    public function storeOrUpdateOpeningStock(Request $request, $productId)
    {
        $filteredLocations = array_filter($request->locations, function ($location) {
            return !empty($location['qty']);
        });

        $validator = Validator::make(['locations' => $filteredLocations], [
            'locations' => 'required|array',
            'locations.*.id' => 'required|integer|exists:locations,id',
            'locations.*.qty' => 'required|numeric|min:1',
            'locations.*.unit_cost' => 'required|numeric|min:0',
            'locations.*.batch_no' => ['nullable', 'string', 'max:255'],
            'locations.*.expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['status' => 404, 'message' => 'Product not found']);
        }

        try {
            $batchIds = [];

            DB::transaction(function () use ($filteredLocations, $product, &$batchIds, &$message) {
                $isUpdate = false;
                $locationIds = array_column($filteredLocations, 'id');

                // Remove obsolete data
                $existingLocationBatches = LocationBatch::whereHas('batch', function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                })->get();

                foreach ($existingLocationBatches as $locationBatch) {
                    if (!in_array($locationBatch->location_id, $locationIds)) {
                        StockHistory::where('loc_batch_id', $locationBatch->id)
                            ->where('stock_type', StockHistory::STOCK_TYPE_OPENING)
                            ->delete();
                        $locationBatch->delete();
                    }
                }

                $batchIdsInUse = LocationBatch::whereIn('location_id', $locationIds)
                    ->whereHas('batch.product', fn($q) => $q->where('id', $product->id))
                    ->pluck('batch_id')->toArray();

                Batch::where('product_id', $product->id)
                    ->whereNotIn('id', $batchIdsInUse)
                    ->delete();

                foreach ($filteredLocations as $locationData) {
                    $formattedExpiryDate = $locationData['expiry_date']
                        ? \Carbon\Carbon::parse($locationData['expiry_date'])->format('Y-m-d')
                        : null;

                    $batch = Batch::updateOrCreate(
                        [
                            'batch_no' => $locationData['batch_no'] ?? Batch::generateNextBatchNo(),
                            'product_id' => $product->id,
                        ],
                        [
                            'qty' => $locationData['qty'],
                            'unit_cost' => $locationData['unit_cost'],
                            'wholesale_price' => $product->whole_sale_price,
                            'special_price' => $product->special_price ?? 0,
                            'retail_price' => $product->retail_price,
                            'max_retail_price' => $product->max_retail_price ?? 0,
                            'expiry_date' => $formattedExpiryDate,
                        ]
                    );

                    $locationBatch = LocationBatch::updateOrCreate(
                        [
                            'batch_id' => $batch->id,
                            'location_id' => $locationData['id'],
                        ],
                        [
                            'qty' => $locationData['qty'],
                        ]
                    );

                    $product->locations()->updateExistingPivot($locationData['id'], ['qty' => $locationData['qty']]);

                    StockHistory::updateOrCreate(
                        [
                            'loc_batch_id' => $locationBatch->id,
                            'stock_type' => StockHistory::STOCK_TYPE_OPENING,
                        ],
                        [
                            'quantity' => $locationData['qty'],
                        ]
                    );

                    $batchIds[] = [
                        'batch_id' => $batch->id,
                        'location_id' => $locationData['id'],
                        'qty' => $locationData['qty'],
                    ];
                }

                $message = count($batchIds) > 0 ? 'Opening Stock updated successfully!' : 'Opening Stock saved successfully!';
            });

            return response()->json([
                'status' => 200,
                'message' => $message,
                'product' => $product,
                'batches' => $batchIds,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }


    public function saveOrUpdateImei(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'batches' => 'required|array',
            'batches.*.batch_id' => 'required|exists:batches,id',
            'batches.*.location_id' => 'required|exists:locations,id',
            'imeis' => 'nullable|array',
            'imeis.*' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $operation = 'save'; // Default to save
            DB::transaction(function () use ($request, &$operation) {
                $validImeis = collect($request->imeis)
                    ->filter(fn($imei) => $imei !== null && trim($imei) !== '')
                    ->values();

                foreach ($request->batches as $batchInfo) {
                    $batchQty = (int)($batchInfo['qty'] ?? 0);

                    if ($batchQty === 0 || $validImeis->isEmpty()) continue;

                    $assignedImeis = $validImeis->take($batchQty);

                    // Remove assigned IMEIs from the list
                    $validImeis = $validImeis->slice($assignedImeis->count());

                    foreach ($assignedImeis as $imei) {
                        // Check for duplicate IMEI, regardless of location/status
                        if (ImeiNumber::isDuplicate($imei)) {
                            throw new \Exception("IMEI number $imei is already associated with another product or sold.");
                        }
                        $imeiModel = ImeiNumber::where([
                            'product_id' => $request->product_id,
                            'batch_id' => $batchInfo['batch_id'],
                            'location_id' => $batchInfo['location_id'],
                            'imei_number' => $imei
                        ])->first();

                        if ($imeiModel) {
                            $operation = 'update';
                            $imeiModel->touch();
                        } else {
                            $operation = 'save';
                            ImeiNumber::create([
                                'product_id' => $request->product_id,
                                'batch_id' => $batchInfo['batch_id'],
                                'location_id' => $batchInfo['location_id'],
                                'imei_number' => $imei,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            });

            $msg = $operation === 'update'
                ? 'IMEI numbers updated successfully.'
                : 'IMEI numbers saved successfully.';

            return response()->json([
                'status' => 200,
                'message' => $msg
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to save or update IMEIs: ' . $e->getMessage()
            ]);
        }
    }
    public function updateSingleImei(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:imei_numbers,id', // Pass the primary key 'id'
            'new_imei' => 'required|string|max:255|unique:imei_numbers,imei_number,' . $request->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            // Check for duplicate IMEI before updating
            if (ImeiNumber::where('imei_number', $request->new_imei)->where('id', '!=', $request->id)->exists()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Duplicate IMEI: This IMEI is already associated with another product or sold.'
                ]);
            }

            $imeiNumber = ImeiNumber::findOrFail($request->id);
            $imeiNumber->imei_number = $request->new_imei;
            $imeiNumber->save();

            return response()->json([
                'status' => 200,
                'message' => 'IMEI updated successfully.',
                'updated_imei' => $request->new_imei
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update IMEI: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteImei(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:imei_numbers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $imei = ImeiNumber::findOrFail($request->id);
            $imei->delete();

            return response()->json([
                'status' => 200,
                'message' => 'IMEI deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete IMEI: ' . $e->getMessage()
            ]);
        }
    }

    public function getImeis($productId)
    {
        $imeis = ImeiNumber::where('product_id', $productId)->get(['id', 'imei_number']);

        return response()->json([
            'status' => 200,
            'imeis' => $imeis
        ]);
    }

    public function OpeningStockGetAll()
    {
        // Fetch all products with their locations
        $products = Product::with('locations')->get();

        // Prepare the opening stock response
        $openingStock = [];

        foreach ($products as $product) {
            // Fetch all batches related to the product that have stock type 'opening_stock'
            $batches = Batch::where('product_id', $product->id)
                ->whereHas('locationBatches.stockHistories', function ($query) {
                    $query->where('stock_type', StockHistory::STOCK_TYPE_OPENING);
                })
                ->with(['locationBatches.stockHistories' => function ($query) {
                    $query->where('stock_type', StockHistory::STOCK_TYPE_OPENING);
                }])
                ->get();

            // Prepare data structure
            $openingStock[] = [
                'products' => $products,
                'batches' => $batches->flatMap(function ($batch) {
                    return $batch->locationBatches->map(function ($locationBatch) use ($batch) {
                        return [
                            'batch_id' => $locationBatch->batch_id,
                            'location_id' => $locationBatch->location_id,
                            'quantity' => $locationBatch->qty,
                            'batch_no' => $batch->batch_no,
                            'expiry_date' => $batch->expiry_date,
                            'stock_histories' => $locationBatch->stockHistories->map(function ($stockHistory) {
                                return [
                                    'stock_history_id' => $stockHistory->id,
                                    'quantity' => $stockHistory->quantity,
                                    'stock_type' => $stockHistory->stock_type,
                                ];
                            })->values(),
                        ];
                    });
                })->values(),
            ];
        }

        return response()->json(['status' => 200, 'openingStock' => $openingStock], 200);
    }


    public function getAllProductStocks(Request $request)
    {
        try {
            $startTime = microtime(true);
            $now = now();

            // DataTable params
            $perPage = $request->input('length', 100); // DataTable uses 'length'
            $start = $request->input('start', 0);
            $page = intval($start / $perPage) + 1;

            // DataTable search and ordering
            $search = $request->input('search.value'); // DataTables sends global search as 'search.value'
            $orderColumnIndex = $request->input('order.0.column');
            $orderColumn = $request->input("columns.$orderColumnIndex.data", 'id');
            $orderDir = $request->input('order.0.dir', 'asc');

            // Custom filters (from filter dropdowns)
            $filterProductName = $request->input('product_name');
            $filterCategory = $request->input('main_category_id');
            $filterBrand = $request->input('brand_id');

            // Build product query
            $query = Product::select([
                'id',
                'product_name',
                'sku',
                'unit_id',
                'brand_id',
                'main_category_id',
                'sub_category_id',
                'stock_alert',
                'alert_quantity',
                'product_image',
                'description',
                'is_imei_or_serial_no',
                'is_for_selling',
                'product_type',
                'pax',
                'original_price',
                'retail_price',
                'whole_sale_price',
                'special_price',
                'max_retail_price'
            ])
                ->with([
                    'locations:id,name',
                    'unit:id,name,short_name,allow_decimal', // Eager load unit
                    'discounts' => function ($query) use ($now) {
                        $query->where('is_active', true)
                            ->where('start_date', '<=', $now);
                    },
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
                    'batches.locationBatches' => function ($query) {
                        $query->select(['id', 'batch_id', 'location_id', 'qty'])
                            ->with('location:id,name');
                    }
                ]);

            // Apply DataTable global search
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply custom filters (from dropdowns)
            if (!empty($filterProductName)) {
                $query->where('product_name', $filterProductName);
            }
            if (!empty($filterCategory)) {
                $query->where('main_category_id', $filterCategory);
            }
            if (!empty($filterBrand)) {
                $query->where('brand_id', $filterBrand);
            }

            // Set ordering (if valid column)
            $validOrderCols = [
                'id',
                'product_name',
                'sku',
                'retail_price',
                'total_stock',
                'main_category_id',
                'brand_id'
            ];
            if (in_array($orderColumn, $validOrderCols)) {
                $query->orderBy($orderColumn, $orderDir);
            } else {
                $query->orderBy('id', 'asc');
            }

            // Get total count before filtering
            $totalCount = Product::count();

            // Get filtered paginated products
            $products = $query->paginate($perPage, ['*'], 'page', $page);

            // Get filtered count for pagination
            $filteredCount = $products->total();

            // Get product IDs for batch and IMEI filtering
            $productIds = $products->pluck('id');

            // Load IMEIs grouped by product ID
            $imeis = ImeiNumber::whereIn('product_id', $productIds)
                ->with(['location:id,name'])
                ->get()
                ->groupBy('product_id');

            // Prepare response data
            $productStocks = [];

            foreach ($products as $product) {
                $productBatches = $product->batches;

                // Filter batches with locationBatches
                $filteredBatches = $productBatches->filter(function ($batch) {
                    return $batch->locationBatches->isNotEmpty();
                });

                // Determine if allow_decimal is true for this product's unit
                $allowDecimal = $product->unit && $product->unit->allow_decimal;

                // Calculate total stock (decimal or integer based on allow_decimal)
                $totalStock = $filteredBatches->sum(
                    fn($batch) =>
                        $batch->locationBatches->sum(function ($lb) use ($allowDecimal) {
                            return $allowDecimal ? (float)$lb->qty : (int)$lb->qty;
                        })
                );
                if ($allowDecimal) {
                    $totalStock = round($totalStock, 2);
                } else {
                    $totalStock = (int)$totalStock;
                }

                // Map active discounts
                $activeDiscounts = $product->discounts->map(function ($discount) use ($now) {
                    return [
                        'id' => $discount->id,
                        'name' => $discount->name,
                        'description' => $discount->description,
                        'type' => $discount->type,
                        'amount' => $discount->amount,
                        'start_date' => $discount->start_date ? $discount->start_date->format('Y-m-d H:i:s') : null,
                        'end_date' => $discount->end_date ? $discount->end_date->format('Y-m-d H:i:s') : null,
                        'is_active' => (bool)$discount->is_active,
                        'apply_to_all' => (bool)$discount->apply_to_all,
                        'is_expired' => $discount->end_date && $discount->end_date < $now,
                    ];
                });

                // Map IMEIs
                $productImeis = $imeis->get($product->id, collect())->map(function ($imei) use ($productBatches) {
                    $batch = $productBatches->firstWhere('id', $imei->batch_id);
                    return [
                        'id' => $imei->id,
                        'imei_number' => $imei->imei_number,
                        'location_id' => $imei->location_id,
                        'location_name' => optional($imei->location)->name ?? 'N/A',
                        'batch_id' => $imei->batch_id,
                        'batch_no' => optional($batch)->batch_no ?? 'N/A',
                        'status' => $imei->status ?? 'available'
                    ];
                });

                // Build final product stock array
                $productStocks[] = [
                    'product' => [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'sku' => $product->sku,
                        'unit_id' => $product->unit_id,
                        'unit' => $product->unit ? [
                            'id' => $product->unit->id,
                            'name' => $product->unit->name,
                            'short_name' => $product->unit->short_name,
                            'allow_decimal' => (bool) ($product->unit->allow_decimal ?? 0),
                        ] : null,
                        'brand_id' => $product->brand_id,
                        'main_category_id' => $product->main_category_id,
                        'sub_category_id' => $product->sub_category_id,
                        'stock_alert' => $product->stock_alert,
                        'alert_quantity' => $product->alert_quantity,
                        'product_image' => $product->product_image,
                        'description' => $product->description,
                        'is_imei_or_serial_no' => $product->is_imei_or_serial_no,
                        'is_for_selling' => $product->is_for_selling,
                        'product_type' => $product->product_type,
                        'pax' => $product->pax,
                        'original_price' => $product->original_price,
                        'retail_price' => $product->retail_price,
                        'whole_sale_price' => $product->whole_sale_price,
                        'special_price' => $product->special_price,
                        'max_retail_price' => $product->max_retail_price,
                    ],
                    'total_stock' => $totalStock,
                    'batches' => $filteredBatches->map(function ($batch) use ($allowDecimal) {
                        return [
                            'id' => $batch->id,
                            'batch_no' => $batch->batch_no,
                            'unit_cost' => $batch->unit_cost,
                            'wholesale_price' => $batch->wholesale_price,
                            'special_price' => $batch->special_price,
                            'retail_price' => $batch->retail_price,
                            'max_retail_price' => $batch->max_retail_price,
                            'expiry_date' => $batch->expiry_date,
                            'total_batch_quantity' => $allowDecimal
                                ? round($batch->locationBatches->sum(fn($lb) => (float)$lb->qty), 2)
                                : (int)$batch->locationBatches->sum(fn($lb) => (int)$lb->qty),
                            'location_batches' => $batch->locationBatches->map(function ($lb) use ($allowDecimal) {
                                return [
                                    'batch_id' => $lb->batch_id,
                                    'location_id' => $lb->location_id,
                                    'location_name' => optional($lb->location)->name ?? 'N/A',
                                    'quantity' => $allowDecimal ? round((float)$lb->qty, 2) : (int)$lb->qty
                                ];
                            })
                        ];
                    }),
                    'locations' => $product->locations->map(fn($loc) => [
                        'location_id' => $loc->id,
                        'location_name' => $loc->name
                    ]),
                    'has_batches' => $filteredBatches->isNotEmpty(),
                    'discounts' => $activeDiscounts,
                    'imei_numbers' => $productImeis
                ];
            }

            // Log execution time
            $executionTime = round(microtime(true) - $startTime, 3);
            Log::info("Product stocks fetched in {$executionTime}s", [
                'page' => $page,
                'per_page' => $perPage,
                'total_products' => count($productStocks),
            ]);

            // DataTables expects these keys: draw, recordsTotal, recordsFiltered, data
            return response()->json([
                'draw' => intval($request->input('draw')), // DataTables draw count
                'recordsTotal' => $totalCount,
                'recordsFiltered' => $filteredCount,
                'data' => $productStocks,
                'status' => 200,
                'pagination' => [
                    'total' => $filteredCount,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product stocks:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while fetching product stocks.'
            ], 500);
        }
    }

    public function autocompleteStock(Request $request)
    {
        $locationId = $request->input('location_id');
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $query = Product::with([
            'locations:id,name',
            'unit:id,name,short_name,allow_decimal',
            'discounts' => function ($query) {
                $query->where('is_active', true);
            },
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
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
                $q->select(['id', 'batch_id', 'location_id', 'qty'])
                    ->with('location:id,name');
            }
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->take($perPage)->get();

        // Get product IDs for IMEI filtering
        $productIds = $products->pluck('id');
        $imeis = ImeiNumber::whereIn('product_id', $productIds)
            ->with(['location:id,name'])
            ->get()
            ->groupBy('product_id');

        $results = $products->map(function ($product) use ($locationId, $imeis) {
            $productBatches = $product->batches;

            // Filter batches with locationBatches
            $filteredBatches = $productBatches->filter(function ($batch) {
                return $batch->locationBatches->isNotEmpty();
            });

            // Determine if allow_decimal is true for this product's unit
            $allowDecimal = $product->unit && $product->unit->allow_decimal;

            // Calculate total stock (for the location if provided)
            $totalStock = $filteredBatches->sum(function ($batch) use ($locationId, $allowDecimal) {
                return $batch->locationBatches->filter(function ($lb) use ($locationId) {
                    return !$locationId || $lb->location_id == $locationId;
                })->sum(function ($lb) use ($allowDecimal) {
                    return $allowDecimal ? (float)$lb->qty : (int)$lb->qty;
                });
            });
            if ($allowDecimal) {
                $totalStock = round($totalStock, 2);
            } else {
                $totalStock = (int)$totalStock;
            }

            // Map active discounts
            $activeDiscounts = $product->discounts->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'description' => $discount->description,
                    'type' => $discount->type,
                    'amount' => $discount->amount,
                    'start_date' => $discount->start_date ? $discount->start_date->format('Y-m-d H:i:s') : null,
                    'end_date' => $discount->end_date ? $discount->end_date->format('Y-m-d H:i:s') : null,
                    'is_active' => (bool)$discount->is_active,
                    'apply_to_all' => (bool)$discount->apply_to_all,
                    'is_expired' => $discount->end_date && $discount->end_date < now(),
                ];
            });

            // Map IMEIs
            $productImeis = $imeis->get($product->id, collect())->map(function ($imei) use ($productBatches) {
                $batch = $productBatches->firstWhere('id', $imei->batch_id);
                return [
                    'id' => $imei->id,
                    'imei_number' => $imei->imei_number,
                    'location_id' => $imei->location_id,
                    'location_name' => optional($imei->location)->name ?? 'N/A',
                    'batch_id' => $imei->batch_id,
                    'batch_no' => optional($batch)->batch_no ?? 'N/A',
                    'status' => $imei->status ?? 'available'
                ];
            });

            return [
                'product' => [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'unit_id' => $product->unit_id,
                    'unit' => $product->unit ? [
                        'id' => $product->unit->id,
                        'name' => $product->unit->name,
                        'short_name' => $product->unit->short_name,
                        'allow_decimal' => (bool) $product->unit->allow_decimal,
                    ] : null,
                    'brand_id' => $product->brand_id,
                    'main_category_id' => $product->main_category_id,
                    'sub_category_id' => $product->sub_category_id,
                    'stock_alert' => $product->stock_alert,
                    'alert_quantity' => $product->alert_quantity,
                    'product_image' => $product->product_image,
                    'description' => $product->description,
                    'is_imei_or_serial_no' => $product->is_imei_or_serial_no,
                    'is_for_selling' => $product->is_for_selling,
                    'product_type' => $product->product_type,
                    'pax' => $product->pax,
                    'original_price' => $product->original_price,
                    'retail_price' => $product->retail_price,
                    'whole_sale_price' => $product->whole_sale_price,
                    'special_price' => $product->special_price,
                    'max_retail_price' => $product->max_retail_price,
                ],
                'total_stock' => $product->stock_alert == 0 ? 'Unlimited' : $totalStock,
                'batches' => $filteredBatches->map(function ($batch) use ($allowDecimal) {
                    return [
                        'id' => $batch->id,
                        'batch_no' => $batch->batch_no,
                        'unit_cost' => $batch->unit_cost,
                        'wholesale_price' => $batch->wholesale_price,
                        'special_price' => $batch->special_price,
                        'retail_price' => $batch->retail_price,
                        'max_retail_price' => $batch->max_retail_price,
                        'expiry_date' => $batch->expiry_date,
                        'total_batch_quantity' => $allowDecimal
                            ? round($batch->locationBatches->sum(fn($lb) => (float)$lb->qty), 2)
                            : (int)$batch->locationBatches->sum(fn($lb) => (int)$lb->qty),
                        'location_batches' => $batch->locationBatches->map(function ($lb) use ($allowDecimal) {
                            return [
                                'batch_id' => $lb->batch_id,
                                'location_id' => $lb->location_id,
                                'location_name' => optional($lb->location)->name ?? 'N/A',
                                'quantity' => $allowDecimal ? round((float)$lb->qty, 2) : (int)$lb->qty
                            ];
                        })
                    ];
                }),
                'locations' => $product->locations->map(fn($loc) => [
                    'location_id' => $loc->id,
                    'location_name' => $loc->name
                ]),
                'has_batches' => $filteredBatches->isNotEmpty(),
                'discounts' => $activeDiscounts,
                'imei_numbers' => $productImeis
            ];
        });

        return response()->json([
            'status' => 200,
            'data' => $results,
        ]);
    }



    public function getNotifications()
    {
        // Only select needed columns for performance
        $products = Product::select([
            'id',
            'product_name',
            'sku',
            'unit_id',
            'brand_id',
            'main_category_id',
            'sub_category_id',
            'stock_alert',
            'alert_quantity',
            'product_image',
            'description',
            'is_imei_or_serial_no',
            'is_for_selling',
            'product_type',
            'pax',
            'original_price',
            'retail_price',
            'whole_sale_price',
            'special_price',
            'max_retail_price',
            'created_at',
            'updated_at'
        ])
            ->with([
                'batches:id,product_id',
                'batches.locationBatches:id,batch_id,qty'
            ])
            ->whereNotNull('alert_quantity')
            ->where('alert_quantity', '>', 0)
            ->get();

        $notifications = [];

        foreach ($products as $product) {
            // Sum all batch quantities for this product
            $totalStock = 0;
            foreach ($product->batches as $batch) {
                foreach ($batch->locationBatches as $lb) {
                    $totalStock += $lb->qty;
                }
            }

            if ($totalStock <= $product->alert_quantity) {
                $notifications[] = [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'unit_id' => $product->unit_id,
                    'brand_id' => $product->brand_id,
                    'main_category_id' => $product->main_category_id,
                    'sub_category_id' => $product->sub_category_id,
                    'stock_alert' => $product->stock_alert,
                    'alert_quantity' => $product->alert_quantity,
                    'product_image' => $product->product_image,
                    'description' => $product->description,
                    'is_imei_or_serial_no' => $product->is_imei_or_serial_no,
                    'is_for_selling' => $product->is_for_selling,
                    'product_type' => $product->product_type,
                    'pax' => $product->pax,
                    'original_price' => $product->original_price,
                    'retail_price' => $product->retail_price,
                    'whole_sale_price' => $product->whole_sale_price,
                    'special_price' => $product->special_price,
                    'max_retail_price' => $product->max_retail_price,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'total_stock' => $totalStock,
                ];
            }
        }

        return response()->json([
            'status' => 200,
            'data' => $notifications,
            'count' => count($notifications)
        ]);
    }


    public function markNotificationsAsSeen()
    {
        $products = Product::with('batches.locationBatches.location')->get();
        $seenNotifications = Session::get('seen_notifications', []);

        $notifications = $products->filter(function ($product) {
            $totalStock = $product->batches->sum(function ($batch) {
                return $batch->locationBatches->sum('qty');
            });

            return $totalStock <= $product->alert_quantity;
        });

        foreach ($notifications as $notification) {
            if (!in_array($notification->id, $seenNotifications)) {
                $seenNotifications[] = $notification->id;
            }
        }

        Session::put('seen_notifications', $seenNotifications);

        return response()->json(['status' => 200, 'message' => 'Notifications marked as seen.']);
    }



    public function showSubCategoryDetailsUsingByMainCategoryId(string $main_category_id)
    {
        $subcategoryDetails = SubCategory::where('main_category_id', $main_category_id)->select('id', 'subCategoryname', 'main_category_id', 'subCategoryCode', 'description')->orderBy('main_category_id', 'asc')->get();
        if ($subcategoryDetails) {
            return response()->json([
                'status' => 200,
                'message' => $subcategoryDetails
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such sub category Details record Found!"
            ]);
        }
    }

    public function updatePrice()
    {
        return view('product.update_price');
    }

    public function importProduct()
    {
        return view('product.import_product');
    }


    public function destroy(int $id)
    {
        try {
            $result = DB::transaction(function () use ($id) {
                $product = Product::with('batches')->find($id);

                if (!$product) {
                    return [
                        'status' => 404,
                        'message' => "No Such Product Found!"
                    ];
                }

                // Delete all related batches and their location batches
                if ($product->batches->isNotEmpty()) {
                    $batchIds = $product->batches->pluck('id')->toArray();

                    // Delete location batches first
                    LocationBatch::whereIn('batch_id', $batchIds)->delete();

                    // Then delete the batches
                    Batch::whereIn('id', $batchIds)->delete();
                }

                // Delete the product
                $product->delete();

                return [
                    'status' => 200,
                    'message' => "Product and all associated batches deleted successfully!"
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "Error deleting product: " . $e->getMessage()
            ]);
        }
    }

    public function exportBlankTemplate()
    {
        return Excel::download(new ExportProductTemplate(true), 'Import_Product_Template.xlsx');
    }

    public function exportProducts()
    {
        return Excel::download(new ExportProductTemplate(), 'Products_Export_' . date('Y-m-d') . '.xlsx');
    }

    public function importProductStore(Request $request)
    {
        // Validate the request file
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        // Check if the file is present in the request
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Check if file upload was successful
            if ($file->isValid()) {
                try {
                    // Create an instance of the import class
                    $import = new importProduct();

                    // Process the Excel file
                    Excel::import($import, $file);

                    // Get validation errors and data from the import process
                    $validationErrors = $import->getValidationErrors();
                    $records = $import->getData();
                    $successCount = count($records);
                    $errorCount = count($validationErrors);

                    // If there are validation errors, return them in the response
                    if (!empty($validationErrors)) {
                        return response()->json([
                            'status' => 422, // Unprocessable Entity
                            'message' => "Import completed with errors. {$successCount} products imported successfully, {$errorCount} rows had errors.",
                            'validation_errors' => $validationErrors,
                            'success_count' => $successCount,
                            'error_count' => $errorCount,
                            'has_errors' => true
                        ]);
                    }

                    return response()->json([
                        'status' => 200,
                        'data' => $records,
                        'message' => "Import successful! {$successCount} products imported successfully.",
                        'success_count' => $successCount,
                        'error_count' => 0,
                        'has_errors' => false
                    ]);

                } catch (\Exception $e) {
                    Log::error('Product import failed: ' . $e->getMessage());
                    return response()->json([
                        'status' => 500,
                        'message' => "Import failed due to an unexpected error: " . $e->getMessage()
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => "File upload failed. Please try again."
                ]);
            }
        }

        return response()->json([
            'status' => 400,
            'message' => "No file uploaded or file is invalid."
        ]);
    }


    public function saveChanges(Request $request)
    {
        $productIds = $request->input('product_ids', []);
        $locationIds = $request->input('location_ids', []);
        $now = now();

        if (empty($productIds) || empty($locationIds)) {
            return response()->json(['status' => 'error', 'message' => 'Please select at least one product and one location.'], 400);
        }

        DB::beginTransaction();

        try {
            foreach ($productIds as $productId) {
                // 1. Existing locations for the product
                $existingLocations = DB::table('location_product')
                    ->where('product_id', $productId)
                    ->pluck('location_id')
                    ->toArray();

                // 2. Remove unselected locations from location_product
                DB::table('location_product')
                    ->where('product_id', $productId)
                    ->whereNotIn('location_id', $locationIds)
                    ->delete();

                // 3. Insert or update selected locations in location_product
                foreach ($locationIds as $locationId) {
                    DB::table('location_product')->updateOrInsert(
                        [
                            'product_id' => $productId,
                            'location_id' => $locationId,
                        ],
                        [
                            'qty' => 0,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }

                // 4. Get all batch IDs related to the product
                $batchIds = DB::table('batches')
                    ->where('product_id', $productId)
                    ->pluck('id')
                    ->toArray();

                foreach ($batchIds as $batchId) {
                    // 5. Fetch existing location_batches records for the batch
                    $existingBatchLocations = DB::table('location_batches')
                        ->where('batch_id', $batchId)
                        ->pluck('location_id', 'id')
                        ->toArray();

                    // 6. Update only the location_id in location_batches (Qty should not change)
                    foreach ($existingBatchLocations as $locationBatchId => $existingLocationId) {
                        if (!in_array($existingLocationId, $locationIds)) {
                            // Change location_id only if it's not in the newly selected locations
                            DB::table('location_batches')
                                ->where('id', $locationBatchId)
                                ->update([
                                    'location_id' => reset($locationIds), // Assign the first selected location
                                    'updated_at' => $now,
                                ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Changes saved successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function applyDiscount(Request $request)
    {
        $request->merge([
            'product_ids' => array_unique($request->product_ids)
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'required|boolean',  // Changed to required|boolean
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        try {
            // Create the discount
            $discount = Discount::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_active' => (bool)$validated['is_active'],  // Explicit cast to boolean
                'apply_to_all' => false
            ]);

            // Attach products to the discount
            $discount->products()->attach($validated['product_ids']);

            return response()->json([
                'status' => 'success',
                'message' => 'Discount applied successfully to selected products'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to apply discount: ' . $e->getMessage()
            ], 500);
        }
    }
}
