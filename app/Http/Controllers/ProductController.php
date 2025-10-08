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
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportProductTemplate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Get cached dropdown data for performance optimization
     */
    private function getCachedDropdownData()
    {
        $user = auth()->user();
        $userId = $user ? $user->id : 0;
        
        // Cache key should include user ID to prevent location data leakage between users
        $cacheKey = "product_dropdown_data_user_{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) { // Cache for 5 minutes per user
            // Get locations with proper user access filtering
            $locations = $this->getUserAccessibleLocations($user);
            
            // Add selection flags for frontend
            $locationsWithSelection = $locations->map(function($location) use ($locations) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'selected' => $locations->count() === 1 // Auto-select if only one location
                ];
            });
            
            return [
                'mainCategories' => MainCategory::select('id', 'mainCategoryName')->get(),
                'subCategories' => SubCategory::select('id', 'subCategoryname', 'main_category_id')->get(),
                'brands' => Brand::select('id', 'name')->get(),
                'units' => Unit::select('id', 'name', 'allow_decimal')->get(),
                'locations' => $locationsWithSelection,
                'auto_select_single_location' => $locations->count() === 1,
            ];
        });
    }
    
    /**
     * Get locations accessible to the current user
     */
    private function getUserAccessibleLocations($user)
    {
        if (!$user) {
            Log::warning('getUserAccessibleLocations called with null user');
            return collect([]); // Return empty collection if no user
        }
        
        // Load user roles if not already loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }
        
        // Check if user is Master Super Admin or has bypass permission
        $isMasterSuperAdmin = $user->roles->pluck('name')->contains('Master Super Admin') || 
                              $user->roles->pluck('key')->contains('master_super_admin');
        
        $hasBypassPermission = false;
        foreach ($user->roles as $role) {
            if ($role->bypass_location_scope ?? false) {
                $hasBypassPermission = true;
                break;
            }
        }
        
        if (!$hasBypassPermission) {
            try {
                $hasBypassPermission = $user->hasPermissionTo('override location scope');
            } catch (\Exception $e) {
                // Permission doesn't exist, continue without bypass
                $hasBypassPermission = false;
            }
        }
        
        Log::info('Location access check', [
            'user_id' => $user->id,
            'is_master_super_admin' => $isMasterSuperAdmin,
            'has_bypass_permission' => $hasBypassPermission,
            'user_roles' => $user->roles->pluck('name')->toArray()
        ]);
        
        if ($isMasterSuperAdmin || $hasBypassPermission) {
            // Master Super Admin or users with bypass permission see all locations
            $locations = Location::select('id', 'name')->get();
            Log::info('User has admin/bypass access, returning all locations', ['count' => $locations->count()]);
            return $locations;
        } else {
            // Regular users see only their assigned locations
            $locations = Location::select('locations.id', 'locations.name')
                ->join('location_user', 'locations.id', '=', 'location_user.location_id')
                ->where('location_user.user_id', $user->id)
                ->get();
            Log::info('Regular user, returning assigned locations', [
                'user_id' => $user->id, 
                'count' => $locations->count(),
                'locations' => $locations->toArray()
            ]);
            return $locations;
        }
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

        try {
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

            // Log for debugging
            Log::info('Stock History Debug', [
                'product_id' => $productId,
                'location_id' => $locationId,
                'location_batches_count' => $product->locationBatches->count(),
                'stock_histories_count' => $stockHistories->count(),
                'request_params' => request()->all()
            ]);

            // Additional debug: Log location batch details when filtering
            if ($locationId) {
                Log::info('Location Filtering Debug', [
                    'filtered_location_id' => $locationId,
                    'location_batches' => $product->locationBatches->map(function($lb) {
                        return [
                            'id' => $lb->id,
                            'location_id' => $lb->location_id,
                            'location_name' => $lb->location->name ?? 'Unknown',
                            'stock_histories_count' => $lb->stockHistories->count()
                        ];
                    })->toArray()
                ]);
            }

            if ($stockHistories->isEmpty()) {
                if (request()->ajax()) {
                    return response()->json([
                        'error' => 'No stock history found for this product' . ($locationId ? ' in the selected location' : ''),
                        'product' => $product,
                        'stock_histories' => [],
                        'stock_type_sums' => [],
                        'current_stock' => 0,
                    ], 200); // Return 200 instead of 404 for better UX
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
                StockHistory::STOCK_TYPE_SALE_REVERSAL,
                StockHistory::STOCK_TYPE_TRANSFER_IN,
            ];
            $outTypes = [
                StockHistory::STOCK_TYPE_SALE,
                StockHistory::STOCK_TYPE_PURCHASE_RETURN,
                StockHistory::STOCK_TYPE_PURCHASE_RETURN_REVERSAL,
                StockHistory::STOCK_TYPE_TRANSFER_OUT,
            ];

            // Calculate totals - handle adjustments separately based on their sign
            $quantitiesIn = $stockTypeSums->filter(fn($val, $key) => in_array($key, $inTypes))->sum();
            $quantitiesOut = $stockTypeSums->filter(fn($val, $key) => in_array($key, $outTypes))->sum(fn($val) => abs($val));
            
            // Handle adjustments: positive adjustments are IN, negative adjustments are OUT
            $adjustmentTotal = $stockTypeSums[StockHistory::STOCK_TYPE_ADJUSTMENT] ?? 0;
            if ($adjustmentTotal > 0) {
                $quantitiesIn += $adjustmentTotal;
            } else {
                $quantitiesOut += abs($adjustmentTotal);
            }
            
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
            $locations = $this->getUserAccessibleLocations(auth()->user());

            return view('product.product_stock_history', compact('products', 'locations'))->with($responseData);
            
        } catch (\Exception $e) {
            Log::error('Error in getStockHistory: ' . $e->getMessage(), [
                'product_id' => $productId,
                'location_id' => $locationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            if (request()->ajax()) {
                return response()->json([
                    'error' => 'Error loading stock history: ' . $e->getMessage(),
                    'product' => null,
                    'stock_histories' => [],
                    'stock_type_sums' => [],
                    'current_stock' => 0,
                ], 500);
            }
            
            return redirect()->back()->withErrors('Error loading stock history. Please try again.');
        }
    }

 
    public function initialProductDetails()
    {
        try {
            // Use cached dropdown data for better performance (now includes user-specific location filtering)
            $dropdownData = $this->getCachedDropdownData();
            
            // Get subcategories with main category relationship for the frontend
            $subCategories = Cache::remember('product_subcategories_with_main', 300, function () {
                return SubCategory::with(['mainCategory:id,mainCategoryName'])
                    ->select('id', 'subCategoryname', 'main_category_id')
                    ->get();
            });

            // Log location access for debugging
            $user = auth()->user();
            if ($user) {
                Log::info('Initial product details - User: ' . $user->id . ', Locations: ' . count($dropdownData['locations']) . ', Auto-select: ' . ($dropdownData['auto_select_single_location'] ? 'true' : 'false'));
            }

            // Check if we have any data to return
            if ($dropdownData['mainCategories']->count() > 0 || $subCategories->count() > 0 || 
                $dropdownData['brands']->count() > 0 || $dropdownData['units']->count() > 0 || 
                count($dropdownData['locations']) > 0) {
                return response()->json([
                    'status' => 200,
                    'message' => [
                        'brands' => $dropdownData['brands'],
                        'subCategories' => $subCategories,
                        'mainCategories' => $dropdownData['mainCategories'],
                        'units' => $dropdownData['units'],
                        'locations' => $dropdownData['locations'],
                        'auto_select_single_location' => $dropdownData['auto_select_single_location'] ?? false,
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Records Found!"
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in initialProductDetails: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 500,
                'message' => 'Error loading product details'
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
        // Fetch the product with only necessary relationships
        $product = Product::with(['locations:id,name', 'mainCategory:id,mainCategoryName', 'brand:id,name', 'unit:id,name'])
            ->select('id', 'product_name', 'sku', 'description', 'pax', 'original_price', 'retail_price', 
                    'whole_sale_price', 'special_price', 'max_retail_price', 'alert_quantity', 'product_type', 
                    'is_imei_or_serial_no', 'is_for_selling', 'product_image', 'main_category_id', 
                    'sub_category_id', 'brand_id', 'unit_id')
            ->find($id);

        // Check if the product exists
        if (!$product) {
            if (request()->ajax() || request()->is('api/*')) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not found'
                ], 404);
            }
            abort(404, 'Product not found');
        }

        // Get cached dropdown data for better performance
        $dropdownData = $this->getCachedDropdownData();
        
        // Extract data from cache
        $mainCategories = $dropdownData['mainCategories'];
        $subCategories = $dropdownData['subCategories'];
        $brands = $dropdownData['brands'];
        $units = $dropdownData['units'];
        $locations = $dropdownData['locations'];

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
        $product = Product::with(['locations', 'unit:id,name,short_name,allow_decimal'])->findOrFail($productId);
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
        $product = Product::with(['locations', 'unit:id,name,short_name,allow_decimal'])->findOrFail($productId);
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

        // Determine if decimals are allowed for this product
        $allowDecimal = $product->unit && $product->unit->allow_decimal;

        $openingStock = [
            'product_id' => $product->id,
            'batches' => $batches->flatMap(function ($batch) use ($allowDecimal) {
                return $batch->locationBatches->map(function ($locationBatch) use ($batch, $allowDecimal) {
                    $location = Location::find($locationBatch->location_id);
                    
                    // Format quantity based on unit's allow_decimal property
                    $formattedQuantity = $allowDecimal 
                        ? number_format((float)$locationBatch->qty, 2, '.', '')
                        : (int)$locationBatch->qty;
                    
                    return [
                        'batch_id' => $locationBatch->batch_id,
                        'location_id' => $locationBatch->location_id,
                        'location_name' => $location->name,
                        'quantity' => $formattedQuantity,
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

        // Clean up empty expiry dates to null
        foreach ($filteredLocations as &$location) {
            if (isset($location['expiry_date']) && ($location['expiry_date'] === '' || $location['expiry_date'] === 'null')) {
                $location['expiry_date'] = null;
            }
        }

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
            $locationId = $request->input('location_id'); // Add location filtering

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
                'max_retail_price',
                'is_active'
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
                    'batches.locationBatches' => function ($query) use ($locationId) {
                        $query->select(['id', 'batch_id', 'location_id', 'qty'])
                            ->with('location:id,name');
                        // Filter by location if provided
                        if ($locationId) {
                            $query->where('location_id', $locationId);
                        }
                    }
                ])
                // Only filter by is_active for POS (when show_all parameter is not set)
                ->when(!$request->has('show_all'), function ($query) {
                    return $query->where('is_active', true);
                })
                // Filter by location if provided (only show products that exist in that location)
                ->when($locationId, function ($query) use ($locationId) {
                    return $query->whereHas('batches.locationBatches', function ($q) use ($locationId) {
                        $q->where('location_id', $locationId);
                        // Only show products that actually exist in the selected location
                    });
                });

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
                        'is_active' => $product->is_active,
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
                    'locations' => $locationId ? 
                        // If location filter is applied, show the filtered location data
                        $filteredBatches->flatMap(function($batch) {
                            return $batch->locationBatches->map(function($lb) {
                                return [
                                    'location_id' => $lb->location_id,
                                    'location_name' => optional($lb->location)->name ?? 'N/A',
                                    'quantity' => $lb->qty
                                ];
                            });
                        })->unique('location_id')->values()->toArray() :
                        // If no location filter, show all locations where this product exists  
                        $filteredBatches->flatMap(function($batch) {
                            return $batch->locationBatches->map(function($lb) {
                                return [
                                    'location_id' => $lb->location_id,
                                    'location_name' => optional($lb->location)->name ?? 'N/A',
                                    'quantity' => $lb->qty
                                ];
                            });
                        })->groupBy('location_id')->map(function($locBatches, $locId) {
                            $firstLoc = $locBatches->first();
                            return [
                                'location_id' => $locId,
                                'location_name' => $firstLoc['location_name'],
                                'quantity' => $locBatches->sum('quantity')
                            ];
                        })->values()->toArray(),
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
        ])->where('is_active', true) // Only show active products for POS
        // Filter by location if provided (only show products with stock in that location)
        ->when($locationId, function ($query) use ($locationId) {
            return $query->whereHas('batches.locationBatches', function ($q) use ($locationId) {
                $q->where('location_id', $locationId)
                  ->where('qty', '>', 0);
            });
        });

        if ($search) {
            // Use ORDER BY with CASE statements to prioritize exact matches
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })->orderByRaw("
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
                $search,                    // Exact SKU match (priority 1)
                $search,                    // Exact product name match (priority 2)
                $search . '%',              // SKU starts with search term (priority 3)
                $search . '%',              // Product name starts with search term (priority 4)
                '%' . $search . '%'         // Description contains search term (priority 5)
            ]);
        } else {
            $query->orderBy('product_name', 'ASC');
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

    public function toggleStatus(int $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 404,
                    'message' => "Product not found!"
                ]);
            }

            // Toggle the is_active status
            $product->is_active = !$product->is_active;
            $product->save();

            $statusText = $product->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'status' => 200,
                'message' => "Product has been {$statusText} successfully!",
                'is_active' => $product->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update product status: ' . $e->getMessage()
            ]);
        }
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
        // Validate the request file and location
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
            'import_location' => 'required|integer|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        // Verify that the selected location is assigned to the current user
        $user = auth()->user();
        $selectedLocationId = $request->input('import_location');
        
        // Check user access to the selected location
        $userLocationIds = $user->locations->pluck('id')->toArray();
        if (!in_array($selectedLocationId, $userLocationIds)) {
            return response()->json([
                'status' => 403,
                'message' => 'You do not have access to the selected location.'
            ]);
        }

        // Store the selected location in session for the import process
        session(['selected_location' => $selectedLocationId]);

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

                    // Log for debugging
                    Log::info("Import Results - Success Count: {$successCount}, Error Count: {$errorCount}");
                    Log::info("Validation Errors: " . json_encode($validationErrors));
                    
                    // If there are validation errors, return them in the response
                    if (!empty($validationErrors)) {
                        return response()->json([
                            'status' => 401, // Changed to match frontend expectation
                            'message' => "Import failed due to validation errors. Please fix the following issues and try again.",
                            'validation_errors' => $validationErrors,
                            'success_count' => $successCount,
                            'error_count' => $errorCount,
                            'has_errors' => true
                        ]);
                    }

                    // If no validation errors and we reach here, assume success
                    // The products are being created even if $records is empty due to how the import works
                    if ($successCount === 0) {
                        // Count actual products created in the last few seconds as a fallback
                        $recentProductCount = \App\Models\Product::where('created_at', '>=', now()->subMinutes(1))->count();
                        
                        if ($recentProductCount > 0) {
                            // Products were actually created, so it's a success
                            return response()->json([
                                'status' => 200,
                                'data' => [],
                                'message' => "Import successful! {$recentProductCount} products imported successfully.",
                                'success_count' => $recentProductCount,
                                'error_count' => 0,
                                'has_errors' => false
                            ]);
                        } else {
                            return response()->json([
                                'status' => 401,
                                'message' => "No products were imported. Please check your Excel file format and ensure it contains valid data.",
                                'validation_errors' => ['No valid rows found in the Excel file. Please check that your file has data and follows the correct format.'],
                                'success_count' => 0,
                                'error_count' => 1,
                                'has_errors' => true
                            ]);
                        }
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
