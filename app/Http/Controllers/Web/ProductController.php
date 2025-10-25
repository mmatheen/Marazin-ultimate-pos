<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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
        $this->middleware('permission:view product', ['only' => ['product', 'index', 'getProductDetails', 'getLastProduct', 'getProductsByCategory', 'initialProductDetails', 'getStockHistory', 'getAllProductStocks', 'autocompleteStock', 'getNotifications', 'OpeningStockGetAll', 'getImeis', 'showSubCategoryDetailsUsingByMainCategoryId', 'updatePrice']]);
        $this->middleware('permission:create product', ['only' => ['addProduct', 'storeOrUpdate']]);
        $this->middleware('permission:edit product', ['only' => ['editProduct']]);
        $this->middleware('permission:delete product', ['only' => ['deleteImei']]);
        $this->middleware('permission:import product', ['only' => ['importProduct', 'importProductStore']]);
        $this->middleware('permission:export product', ['only' => ['exportBlankTemplate', 'exportProducts']]);
        $this->middleware('permission:edit batch prices', ['only' => ['getProductBatches', 'updateBatchPrices']]);
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

            if ($stockHistories->isEmpty()) {
                if (request()->ajax()) {
                    return response()->json([
                        'error' => 'No stock history found for this product' . ($locationId ? ' in the selected location' : ''),
                        'product' => $product,
                        'stock_histories' => [],
                        'stock_type_sums' => [],
                        'current_stock' => 0,
                    ]);
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
                StockHistory::STOCK_TYPE_ADJUSTMENT,
                StockHistory::STOCK_TYPE_PURCHASE_RETURN,
                StockHistory::STOCK_TYPE_PURCHASE_REVERSAL,
                StockHistory::STOCK_TYPE_PURCHASE_RETURN_REVERSAL,
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
            $locations = $this->getUserAccessibleLocations(auth()->user());

            return view('product.product_stock_history', compact('products', 'locations'))->with($responseData);
            
        } catch (\Exception $e) {
            Log::error('Error in Web getStockHistory: ' . $e->getMessage());
            
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
            $mainCategories = MainCategory::all();
            $subCategories = SubCategory::with('mainCategory')->get();
            $brands = Brand::all();
            $units = Unit::all();
            
            // Use proper location filtering instead of Location::all()
            $locations = $this->getUserAccessibleLocations(auth()->user());
            
            // Add selection flags for frontend
            $locationsWithSelection = $locations->map(function($location) use ($locations) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'selected' => $locations->count() === 1 // Auto-select if only one location
                ];
            });
            
            // Check if all collections have records
            if ($mainCategories->count() > 0 || $subCategories->count() > 0 || $brands->count() > 0 || $units->count() > 0 || $locations->count() > 0) {
                return response()->json([
                    'status' => 200,
                    'message' => [
                        'brands' => $brands,
                        'subCategories' => $subCategories,
                        'mainCategories' => $mainCategories,
                        'units' => $units,
                        'locations' => $locationsWithSelection,
                        'auto_select_single_location' => $locations->count() === 1,
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Records Found!"
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in Web initialProductDetails: ' . $e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => 'Error loading product details'
            ]);
        }
    }
    
    /**
     * Get locations accessible to the current user
     */
    private function getUserAccessibleLocations($user)
    {
        if (!$user) {
            return collect([]);
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
        
        if ($isMasterSuperAdmin || $hasBypassPermission) {
            // Master Super Admin or users with bypass permission see all locations
            return Location::select('id', 'name')->get();
        } else {
            // Regular users see only their assigned locations
            $locations = Location::select('locations.id', 'locations.name')
                ->join('location_user', 'locations.id', '=', 'location_user.location_id')
                ->where('location_user.user_id', $user->id)
                ->get();
            return $locations;
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
        ];

        // Add SKU validation rule conditionally
        if ($id) {
            // When updating, allow current product's SKU
            $rules['sku'] = 'nullable|string|unique:products,sku,' . $id;
        } else {
            // When creating, SKU must be unique
            $rules['sku'] = 'nullable|string|unique:products,sku';
        }

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

        // Check for duplicate product creation (prevent race condition)
        // Only check when creating new product (no $id), and if a SKU is provided
        if (!$id && $request->has('sku') && !empty($request->sku)) {
            $existingProduct = Product::where('sku', $request->sku)->first();
            if ($existingProduct) {
                Log::warning('Attempted duplicate product creation', [
                    'sku' => $request->sku,
                    'product_name' => $request->product_name,
                    'existing_product_id' => $existingProduct->id
                ]);
                return response()->json([
                    'status' => 400,
                    'message' => 'A product with this SKU (' . $request->sku . ') already exists!',
                    'errors' => ['sku' => 'SKU already exists']
                ]);
            }
        }

        // Prepare product data (exclude sku when auto-generating)
        $data = [
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
        ];

        try {
            if ($request->has('sku') && !empty($request->sku)) {
                // Use provided SKU (we already checked duplicates earlier)
                $product->fill(array_merge($data, ['sku' => (string) $request->sku]));
                $product->save();
            } else {
                // Find first available SKU by checking for gaps in sequence
                // Get all numeric SKUs sorted ascending
                $existingSkus = Product::whereRaw('sku REGEXP "^[0-9]+$"')
                    ->orderByRaw('CAST(sku AS UNSIGNED) ASC')
                    ->pluck('sku')
                    ->map(function($sku) {
                        return (int)$sku;
                    })
                    ->toArray();
                
                // Find the first gap in the sequence
                $nextSkuNumber = 1;
                foreach ($existingSkus as $existingSku) {
                    if ($existingSku == $nextSkuNumber) {
                        $nextSkuNumber++;
                    } else if ($existingSku > $nextSkuNumber) {
                        // Found a gap, use this number
                        break;
                    }
                }
                
                $generatedSku = str_pad($nextSkuNumber, 4, '0', STR_PAD_LEFT);
                
                Log::info('Auto-generated SKU (gap-filling)', [
                    'existing_skus' => $existingSkus,
                    'next_number' => $nextSkuNumber,
                    'generated_sku' => $generatedSku
                ]);

                // Insert with generated SKU directly
                $product->fill(array_merge($data, ['sku' => $generatedSku]));
                $product->save();
            }
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            Log::warning('Unique constraint violation while saving product', ['sku' => $request->sku ?? null, 'exception' => $e->getMessage()]);
            return response()->json([
                'status' => 400,
                'message' => 'A product with this SKU already exists. Please choose a different SKU or try again.',
                'errors' => ['sku' => 'SKU already exists']
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving product', ['exception' => $e->getMessage()]);
            return response()->json(['status' => 500, 'message' => 'Failed to save product. Please try again.']);
        }

        // Sync locations
        $product->locations()->sync($request->locations);

        $message = $id ? 'Product Details Updated Successfully!' : 'New Product Details Created Successfully!';
        return response()->json(['status' => 200, 'message' => $message, 'product_id' => $product->id]);
    }

    /**
     * Check if SKU is unique (for real-time validation)
     */
    public function checkSkuUniqueness(Request $request)
    {
        $sku = $request->input('sku');
        $productId = $request->input('product_id'); // Product ID if editing (to exclude from check)

        if (!$sku) {
            return response()->json(['exists' => false]);
        }

        // Query for existing SKU
        $query = Product::where('sku', $sku);

        // Exclude current product if editing
        if ($productId) {
            $query->where('id', '!=', $productId);
        }

        $exists = $query->exists();

        return response()->json(['exists' => $exists]);
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
        // Debug log to see what we're receiving
        Log::info('API saveOrUpdateImei called with data:', $request->all());

        // Determine which format to use based on the request data
        $hasLocationId = $request->has('location_id') && $request->filled('location_id');
        $hasBatches = $request->has('batches');
        
        Log::info("API Detection: hasLocationId={$hasLocationId}, hasBatches={$hasBatches}");

        // Priority logic:
        // 1. If request has location_id, use new format (intelligent batch selection)
        // 2. If request has batches array with valid structure, use old format
        // 3. Otherwise, use new format as default
        
        if ($hasLocationId) {
            Log::info('API Using new format (intelligent batch selection) - location_id present');
            return $this->saveOrUpdateImeiNewFormat($request);
        } elseif ($hasBatches && is_array($request->batches) && !empty($request->batches)) {
            // Validate that batches have proper structure
            $hasValidBatchStructure = isset($request->batches[0]['batch_id']) && 
                                    isset($request->batches[0]['location_id']);
            
            if ($hasValidBatchStructure) {
                Log::info('API Using old format (manual batch assignment) - valid batches structure');
                return $this->saveOrUpdateImeiOldFormat($request);
            } else {
                Log::warning('API Invalid batch structure, falling back to new format');
                return $this->saveOrUpdateImeiNewFormat($request);
            }
        } else {
            Log::info('API Using new format (intelligent batch selection) - default fallback');
            return $this->saveOrUpdateImeiNewFormat($request);
        }
    }

    /**
     * Handle the new format with intelligent batch selection (API version)
     */
    private function saveOrUpdateImeiNewFormat(Request $request)
    {
        // New format with intelligent batch selection
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:locations,id', // Single location for intelligent batch selection
            'imeis' => 'required|array|min:1',
            'imeis.*' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            Log::error('API Validation failed for new format:', $validator->messages()->toArray());
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $operation = 'save'; // Default to save
            $savedCount = 0;
            
            DB::transaction(function () use ($request, &$operation, &$savedCount) {
                $validImeis = collect($request->imeis)
                    ->filter(fn($imei) => $imei !== null && trim($imei) !== '')
                    ->values();

                Log::info("API Processing IMEIs for new format", [
                    'product_id' => $request->product_id,
                    'location_id' => $request->location_id,
                    'imeis' => $validImeis->toArray()
                ]);

                if ($validImeis->isEmpty()) {
                    throw new \Exception("No valid IMEI numbers provided.");
                }

                // Get exact batch assignments for the IMEIs (strict capacity enforcement)
                $batchAssignments = $this->getIntelligentBatchAssignments(
                    $request->product_id,
                    $request->location_id,
                    $validImeis->toArray()
                );

                Log::info("API Batch assignments calculated", ['assignments' => $batchAssignments]);

                if (empty($batchAssignments)) {
                    throw new \Exception("No available batches found for this product at the specified location.");
                }

                foreach ($batchAssignments as $assignment) {
                    $imei = $assignment['imei'];
                    $batchId = $assignment['batch_id'];

                    Log::info("API Processing IMEI assignment", [
                        'imei' => $imei,
                        'batch_id' => $batchId,
                        'batch_no' => $assignment['batch_no']
                    ]);

                    // Check for duplicate IMEI, regardless of location/status
                    if (ImeiNumber::isDuplicate($imei)) {
                        Log::warning("API Duplicate IMEI detected", ['imei' => $imei]);
                        throw new \Exception("IMEI number $imei is already associated with another product or sold.");
                    }

                    $imeiModel = ImeiNumber::where([
                        'product_id' => $request->product_id,
                        'batch_id' => $batchId,
                        'location_id' => $request->location_id,
                        'imei_number' => $imei
                    ])->first();

                    if ($imeiModel) {
                        $operation = 'update';
                        $imeiModel->touch();
                        Log::info("API Updated existing IMEI", ['imei' => $imei, 'id' => $imeiModel->id]);
                    } else {
                        $operation = 'save';
                        $newImei = ImeiNumber::create([
                            'product_id' => $request->product_id,
                            'batch_id' => $batchId,
                            'location_id' => $request->location_id,
                            'imei_number' => $imei,
                            'status' => 'available',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $savedCount++;
                        Log::info("API Created new IMEI", [
                            'imei' => $imei,
                            'id' => $newImei->id,
                            'batch_id' => $batchId,
                            'product_id' => $request->product_id,
                            'location_id' => $request->location_id
                        ]);
                    }
                }
                
                Log::info("API Transaction completed", [
                    'total_processed' => count($batchAssignments),
                    'saved_count' => $savedCount,
                    'operation' => $operation
                ]);
            });

            $msg = $operation === 'update'
                ? 'IMEI numbers updated successfully'
                : 'IMEI numbers saved successfully';

            return response()->json([
                'status' => 200,
                'message' => $msg
            ]);
        } catch (\Exception $e) {
            Log::error('API IMEI save failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Failed to save or update IMEIs: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the old format with batches array for backward compatibility (API version)
     */
    private function saveOrUpdateImeiOldFormat(Request $request)
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
                                'status' => 'available',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            });

            $msg = $operation === 'update'
                ? 'IMEI numbers updated successfully (legacy format).'
                : 'IMEI numbers saved successfully (legacy format).';

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

    /**
     * Intelligently assign IMEIs to batches based on available capacity (API version)
     * Fills batches sequentially (FIFO - First In, First Out)
     */
    private function getIntelligentBatchAssignments($productId, $locationId, $imeis)
    {
        Log::info("API Starting EXACT batch quantity based IMEI assignment", [
            'product_id' => $productId,
            'location_id' => $locationId,
            'imei_count' => count($imeis),
            'imeis' => $imeis
        ]);

        // Get all batches for this product at the specified location, ordered by batch ID (FIFO)
        // STRICT: Only use batches with available quantity > 0
        $batches = DB::table('location_batches')
            ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
            ->where('batches.product_id', $productId)
            ->where('location_batches.location_id', $locationId)
            ->where('location_batches.qty', '>', 0) // STRICT: Only batches with available stock
            ->select(
                'batches.id as batch_id',
                'batches.batch_no',
                'location_batches.qty as available_qty',
                'batches.created_at'
            )
            ->orderBy('batches.id') // FIFO - older batches first
            ->get();

        Log::info("API Found batches with available quantity", [
            'batch_count' => $batches->count(),
            'batches' => $batches->toArray()
        ]);

        if ($batches->isEmpty()) {
            Log::error("API No batches with available quantity found", [
                'product_id' => $productId,
                'location_id' => $locationId
            ]);
            throw new \Exception("No batches with available quantity found for this product at the specified location. Please check stock levels.");
        }

        $assignments = [];
        $remainingImeis = $imeis;

        foreach ($batches as $batch) {
            if (empty($remainingImeis)) {
                break; // All IMEIs have been assigned
            }

            // Calculate EXACT available capacity for this batch
            // Only count IMEIs with status 'available' - sold IMEIs don't consume batch capacity
            $existingAvailableImeiCount = ImeiNumber::where('product_id', $productId)
                ->where('batch_id', $batch->batch_id)
                ->where('location_id', $locationId)
                ->where('status', 'available') // Only count available IMEIs
                ->count();

            // STRICT calculation: exact available capacity
            $exactAvailableCapacity = $batch->available_qty - $existingAvailableImeiCount;

            Log::info("API Batch capacity analysis", [
                'batch_id' => $batch->batch_id,
                'batch_no' => $batch->batch_no,
                'total_batch_qty' => $batch->available_qty,
                'existing_available_imei_count' => $existingAvailableImeiCount,
                'exact_available_capacity' => $exactAvailableCapacity
            ]);

            if ($exactAvailableCapacity <= 0) {
                Log::info("API Batch is full, moving to next batch", [
                    'batch_id' => $batch->batch_id,
                    'available_capacity' => $exactAvailableCapacity
                ]);
                continue; // This batch is full, try next batch
            }

            // Assign IMEIs to this batch ONLY up to exact available capacity
            $imeisToAssignCount = min($exactAvailableCapacity, count($remainingImeis));
            $imeisToAssign = array_slice($remainingImeis, 0, $imeisToAssignCount);
            
            Log::info("API EXACT assignment to batch", [
                'batch_id' => $batch->batch_id,
                'batch_no' => $batch->batch_no,
                'exact_capacity' => $exactAvailableCapacity,
                'imeis_to_assign_count' => $imeisToAssignCount,
                'imeis_to_assign' => $imeisToAssign
            ]);
            
            foreach ($imeisToAssign as $imei) {
                $assignments[] = [
                    'imei' => $imei,
                    'batch_id' => $batch->batch_id,
                    'batch_no' => $batch->batch_no
                ];
            }

            // Remove assigned IMEIs from remaining list
            $remainingImeis = array_slice($remainingImeis, $imeisToAssignCount);
            
            Log::info("API Batch assignment completed", [
                'batch_id' => $batch->batch_id,
                'assigned_count' => $imeisToAssignCount,
                'remaining_imeis_count' => count($remainingImeis)
            ]);
        }

        // If there are still unassigned IMEIs, it means insufficient total capacity across all batches
        if (!empty($remainingImeis)) {
            $unassignedCount = count($remainingImeis);
            $totalAvailableCapacity = $batches->sum(function ($batch) use ($productId, $locationId) {
                $existingAvailableCount = ImeiNumber::where('product_id', $productId)
                    ->where('batch_id', $batch->batch_id)
                    ->where('location_id', $locationId)
                    ->where('status', 'available') // Only count available IMEIs
                    ->count();
                return max(0, $batch->available_qty - $existingAvailableCount);
            });
            
            Log::error("API Insufficient total batch capacity", [
                'unassigned_count' => $unassignedCount,
                'total_available_capacity' => $totalAvailableCapacity,
                'unassigned_imeis' => $remainingImeis,
                'product_id' => $productId,
                'location_id' => $locationId
            ]);
            
            throw new \Exception("Insufficient batch capacity. Cannot assign {$unassignedCount} IMEI(s). Total available capacity: {$totalAvailableCapacity}. Please add more stock or use a different location.");
        }

        Log::info("API EXACT batch assignment completed successfully", [
            'total_assignments' => count($assignments),
            'assignments' => $assignments
        ]);

        return $assignments;
    }

    /**
     * Emergency batch assignment method - only used when intelligent fails
     * Still respects batch quantities but more lenient
     */
    private function getSimpleBatchAssignments($productId, $locationId, $imeis)
    {
        Log::info("API Using emergency batch assignment (still quantity-based)", [
            'product_id' => $productId,
            'location_id' => $locationId,
            'imei_count' => count($imeis)
        ]);

        // Get batches with available quantity, fallback to any batch if needed
        $batchesWithQty = DB::table('location_batches')
            ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
            ->where('batches.product_id', $productId)
            ->where('location_batches.location_id', $locationId)
            ->where('location_batches.qty', '>', 0)
            ->select(
                'batches.id as batch_id',
                'batches.batch_no',
                'location_batches.qty as available_qty'
            )
            ->orderBy('batches.id')
            ->get();

        // If no batches with quantity, get any batch as last resort
        if ($batchesWithQty->isEmpty()) {
            Log::warning("API No batches with quantity found, using any available batch");
            
            $anyBatch = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productId)
                ->where('location_batches.location_id', $locationId)
                ->select('batches.id as batch_id', 'batches.batch_no')
                ->first();

            if (!$anyBatch) {
                throw new \Exception("No batches found for this product at the specified location.");
            }

            // Assign all IMEIs to this batch (emergency mode)
            $assignments = [];
            foreach ($imeis as $imei) {
                $assignments[] = [
                    'imei' => $imei,
                    'batch_id' => $anyBatch->batch_id,
                    'batch_no' => $anyBatch->batch_no
                ];
            }
            return $assignments;
        }

        // Use quantity-based assignment even in simple mode
        $assignments = [];
        $remainingImeis = $imeis;

        foreach ($batchesWithQty as $batch) {
            if (empty($remainingImeis)) {
                break;
            }

            $existingAvailableImeiCount = ImeiNumber::where('product_id', $productId)
                ->where('batch_id', $batch->batch_id)
                ->where('location_id', $locationId)
                ->where('status', 'available') // Only count available IMEIs
                ->count();

            $availableCapacity = max(0, $batch->available_qty - $existingAvailableImeiCount);
            
            if ($availableCapacity > 0) {
                $imeisToAssignCount = min($availableCapacity, count($remainingImeis));
                $imeisToAssign = array_slice($remainingImeis, 0, $imeisToAssignCount);
                
                foreach ($imeisToAssign as $imei) {
                    $assignments[] = [
                        'imei' => $imei,
                        'batch_id' => $batch->batch_id,
                        'batch_no' => $batch->batch_no
                    ];
                }
                
                $remainingImeis = array_slice($remainingImeis, $imeisToAssignCount);
            }
        }

        // If still unassigned, this is the final error
        if (!empty($remainingImeis)) {
            throw new \Exception("Cannot assign " . count($remainingImeis) . " IMEI(s) - insufficient batch capacity across all available batches.");
        }

        return $assignments;
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


    public function getImeis($productId, Request $request)
    {
        $locationId = $request->input('location_id');
        
        // Get the product info
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);
        }
        
        $query = ImeiNumber::where('product_id', $productId)
            ->with(['location:id,name', 'batch:id,batch_no']);
            
        // Filter by location if provided
        if ($locationId && $locationId !== '') {
            $query->where('location_id', $locationId);
        }
        
        $imeis = $query->get()->map(function ($imei) {
            return [
                'id' => $imei->id,
                'imei_number' => $imei->imei_number,
                'status' => $imei->status ?? 'available',
                'location_id' => $imei->location_id,
                'batch_id' => $imei->batch_id,
                'location_name' => $imei->location ? $imei->location->name : 'N/A',
                'batch_no' => $imei->batch ? $imei->batch->batch_no : 'N/A',
                'editable' => true // Allow editing since we're fetching for management purposes
            ];
        });

        return response()->json([
            'status' => 200,
            'data' => $imeis,
            'product_name' => $product->product_name
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
            // Clear any output buffer to prevent non-JSON content
            if (ob_get_level()) {
                ob_clean();
            }
            
            // Set headers to ensure JSON response
            header('Content-Type: application/json');
            
            Log::info('=== getAllProductStocks method called ===');
            Log::info('Request method: ' . $request->method());
            Log::info('Request URL: ' . $request->fullUrl());
            Log::info('Request parameters: ', $request->all());
            Log::info('Memory usage at start: ' . memory_get_usage(true) / 1024 / 1024 . 'MB');
            
            $startTime = microtime(true);
            $now = now();

            // DataTable params with validation
            $perPage = min((int)$request->input('length', 50), 100); // Limit max per page for hosting
            $start = max(0, (int)$request->input('start', 0));
            $page = intval($start / $perPage) + 1;

            Log::info('Pagination params:', ['page' => $page, 'perPage' => $perPage, 'start' => $start]);

            // DataTable search and ordering
            $search = $request->input('search.value'); // DataTables sends global search as 'search.value'
            $orderColumnIndex = $request->input('order.0.column');
            $orderColumn = $request->input("columns.$orderColumnIndex.data", 'id');
            $orderDir = $request->input('order.0.dir', 'asc');

            // Custom filters (from filter dropdowns)
            $filterProductName = $request->input('product_name');
            $filterCategory = $request->input('main_category_id');
            $filterBrand = $request->input('brand_id');
            $locationId = $request->input('location_id'); // Add location filter

            Log::info('Filters applied:', [
                'search' => $search,
                'filterProductName' => $filterProductName,
                'filterCategory' => $filterCategory,
                'filterBrand' => $filterBrand,
                'locationId' => $locationId
            ]);

            // Apply user location scope
            $user = auth()->user();
            $userAccessibleLocations = $this->getUserAccessibleLocations($user);
            $userLocationIds = $userAccessibleLocations->pluck('id')->toArray();
            
            // If a specific location is selected in filter, ensure user has access to it
            if ($locationId && !empty($userLocationIds) && !in_array($locationId, $userLocationIds)) {
                Log::warning('User attempted to access unauthorized location', [
                    'user_id' => $user->id,
                    'requested_location' => $locationId,
                    'accessible_locations' => $userLocationIds
                ]);
                $locationId = null; // Reset to prevent unauthorized access
            }

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
                    'locations' => function ($query) use ($userLocationIds) {
                        $query->select('locations.id', 'locations.name');
                        // Only load locations user has access to
                        if (!empty($userLocationIds)) {
                            $query->whereIn('locations.id', $userLocationIds);
                        }
                    },
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
                    'batches.locationBatches' => function ($query) use ($locationId, $userLocationIds) {
                        // Always enforce user's accessible locations
                        if (!empty($userLocationIds)) {
                            $query->whereIn('location_id', $userLocationIds);
                        }
                        
                        // Additionally filter by specific location if selected
                        if ($locationId) {
                            $query->where('location_id', $locationId);
                        }
                        
                        $query->select(['id', 'batch_id', 'location_id', 'qty'])
                            ->with('location:id,name');
                    }
                ])
                // Only filter by is_active for POS (when show_all parameter is not set)
                ->when(!$request->has('show_all'), function ($query) {
                    return $query->where('is_active', true);
                })
                // Filter by location if provided (show ALL products assigned to that location, including 0 qty)
                ->when($locationId, function ($query) use ($locationId) {
                    return $query->whereHas('locations', function ($q) use ($locationId) {
                        $q->where('locations.id', $locationId);
                        // Show all products assigned to the selected location (even with 0 stock)
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
            Log::info('Memory before total count: ' . memory_get_usage(true) / 1024 / 1024 . 'MB');
            $totalCount = Product::count();
            Log::info('Total products count: ' . $totalCount);

            // Get filtered paginated products with error handling
            Log::info('Memory before pagination: ' . memory_get_usage(true) / 1024 / 1024 . 'MB');
            try {
                $products = $query->paginate($perPage, ['*'], 'page', $page);
            } catch (\Exception $e) {
                Log::error('Error during pagination: ' . $e->getMessage());
                throw new \Exception('Database query failed: ' . $e->getMessage());
            }
            Log::info('Memory after pagination: ' . memory_get_usage(true) / 1024 / 1024 . 'MB');

            // Get filtered count for pagination
            $filteredCount = $products->total();
            Log::info('Filtered count: ' . $filteredCount);

            // Get product IDs for batch and IMEI filtering
            $productIds = $products->pluck('id');

            // Load IMEIs grouped by product ID (filter by location if specified)
            $imeisQuery = ImeiNumber::whereIn('product_id', $productIds)
                ->with(['location:id,name']);
            
            if ($locationId) {
                $imeisQuery->where('location_id', $locationId);
            }
            
            $imeis = $imeisQuery->get()->groupBy('product_id');

            // Prepare response data
            $productStocks = [];

            foreach ($products as $product) {
                $productBatches = $product->batches;

                // When location filter is applied, we still want to show the product even if no batches exist
                // So we don't filter batches aggressively - just filter the location data within batches
                $filteredBatches = $productBatches;

                // Determine if allow_decimal is true for this product's unit
                $allowDecimal = $product->unit && $product->unit->allow_decimal;

                // Calculate total stock based on location filter
                if ($locationId) {
                    // Calculate stock only for the specified location
                    $totalStock = $filteredBatches->sum(
                        fn($batch) =>
                        $batch->locationBatches->where('location_id', $locationId)->sum(function ($lb) use ($allowDecimal) {
                            return $allowDecimal ? (float)$lb->qty : (int)$lb->qty;
                        })
                    );
                } else {
                    // Calculate total stock across all locations
                    $totalStock = $filteredBatches->sum(
                        fn($batch) =>
                        $batch->locationBatches->sum(function ($lb) use ($allowDecimal) {
                            return $allowDecimal ? (float)$lb->qty : (int)$lb->qty;
                        })
                    );
                }
                
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

                // Build locations array based on filter
                $productLocations = $product->locations;
                if ($locationId) {
                    $productLocations = $productLocations->where('id', $locationId);
                }
                
                $locationData = $productLocations->map(fn($loc) => [
                    'location_id' => $loc->id,
                    'location_name' => $loc->name
                ])->values();

                // Ensure locationData is always an array
                if ($locationData->isEmpty()) {
                    $locationData = collect([]);
                }

                // Build final product stock array
                $productStocks[] = [
                    'product' => [
                        'id' => $product->id,
                        'product_name' => $product->product_name ?? '',
                        'sku' => $product->sku ?? '',
                        'unit_id' => $product->unit_id,
                        'unit' => $product->unit ? [
                            'id' => $product->unit->id,
                            'name' => $product->unit->name ?? '',
                            'short_name' => $product->unit->short_name ?? '',
                            'allow_decimal' => (bool) ($product->unit->allow_decimal ?? 0),
                        ] : null,
                        'brand_id' => $product->brand_id,
                        'main_category_id' => $product->main_category_id,
                        'sub_category_id' => $product->sub_category_id,
                        'stock_alert' => $product->stock_alert,
                        'alert_quantity' => $product->alert_quantity ?? 0,
                        'product_image' => $product->product_image ?? '',
                        'description' => $product->description ?? '',
                        'is_imei_or_serial_no' => $product->is_imei_or_serial_no ?? 0,
                        'is_for_selling' => $product->is_for_selling ?? 1,
                        'product_type' => $product->product_type ?? '',
                        'pax' => $product->pax ?? 0,
                        'original_price' => $product->original_price ?? 0,
                        'retail_price' => $product->retail_price ?? 0,
                        'whole_sale_price' => $product->whole_sale_price ?? 0,
                        'special_price' => $product->special_price ?? 0,
                        'max_retail_price' => $product->max_retail_price ?? 0,
                        'is_active' => $product->is_active ?? 0,
                    ],
                    'total_stock' => $totalStock,
                    'batches' => $filteredBatches->map(function ($batch) use ($allowDecimal, $locationId) {
                        // Filter location batches based on location filter
                        $locationBatches = $locationId 
                            ? $batch->locationBatches->where('location_id', $locationId)
                            : $batch->locationBatches;

                        return [
                            'id' => $batch->id,
                            'batch_no' => $batch->batch_no ?? '',
                            'unit_cost' => $batch->unit_cost ?? 0,
                            'wholesale_price' => $batch->wholesale_price ?? 0,
                            'special_price' => $batch->special_price ?? 0,
                            'retail_price' => $batch->retail_price ?? 0,
                            'max_retail_price' => $batch->max_retail_price ?? 0,
                            'expiry_date' => $batch->expiry_date,
                            'total_batch_quantity' => $allowDecimal
                                ? round($locationBatches->sum(fn($lb) => (float)($lb->qty ?? 0)), 2)
                                : (int)$locationBatches->sum(fn($lb) => (int)($lb->qty ?? 0)),
                            'location_batches' => $locationBatches->map(function ($lb) use ($allowDecimal) {
                                return [
                                    'batch_id' => $lb->batch_id,
                                    'location_id' => $lb->location_id,
                                    'location_name' => optional($lb->location)->name ?? 'N/A',
                                    'quantity' => $allowDecimal ? round((float)($lb->qty ?? 0), 2) : (int)($lb->qty ?? 0)
                                ];
                            })->values()
                        ];
                    })->values(),
                    'locations' => $locationData,
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
                'draw' => intval($request->input('draw', 0)), // DataTables draw count
                'recordsTotal' => $totalCount,
                'recordsFiltered' => $filteredCount,
                'data' => array_values($productStocks), // Ensure indexed array
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
            // Clear any output buffer that might contain PHP errors
            if (ob_get_level()) {
                ob_clean();
            }
            
            Log::error('Error fetching product stocks:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all(),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB',
                'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024 . 'MB',
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ]);
            
            // Set JSON header explicitly
            header('Content-Type: application/json');
            
            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'status' => 500,
                'message' => 'An error occurred while fetching product stocks.',
                'error' => $e->getMessage(),
                'debug' => [
                    'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB',
                    'php_version' => PHP_VERSION
                ]
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
        ])
        // Only show active products in POS/autocomplete
        ->where('is_active', true);

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
        $imeisQuery = ImeiNumber::whereIn('product_id', $productIds)
            ->with(['location:id,name']);
            
        if ($locationId) {
            $imeisQuery->where('location_id', $locationId);
        }
        
        $imeis = $imeisQuery->get()->groupBy('product_id');

        $results = $products->map(function ($product) use ($locationId, $imeis) {
            $productBatches = $product->batches;

            // Filter batches with locationBatches based on location filter
            $filteredBatches = $productBatches->filter(function ($batch) use ($locationId) {
                if ($locationId) {
                    // If location is specified, only include batches that have stock in that location
                    return $batch->locationBatches->isNotEmpty() && 
                           $batch->locationBatches->where('location_id', $locationId)->isNotEmpty();
                } else {
                    // If no location specified, include all batches with any location stock
                    return $batch->locationBatches->isNotEmpty();
                }
            });

            // Determine if allow_decimal is true for this product's unit
            $allowDecimal = $product->unit && $product->unit->allow_decimal;

            // Calculate total stock (for the location if provided)
            if ($locationId) {
                // Calculate stock only for the specified location
                $totalStock = $filteredBatches->sum(function ($batch) use ($locationId, $allowDecimal) {
                    return $batch->locationBatches->where('location_id', $locationId)->sum(function ($lb) use ($allowDecimal) {
                        return $allowDecimal ? (float)$lb->qty : (int)$lb->qty;
                    });
                });
            } else {
                // Calculate total stock across all locations
                $totalStock = $filteredBatches->sum(function ($batch) use ($allowDecimal) {
                    return $batch->locationBatches->sum(function ($lb) use ($allowDecimal) {
                        return $allowDecimal ? (float)$lb->qty : (int)$lb->qty;
                    });
                });
            }
            
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
                'batches' => $filteredBatches->map(function ($batch) use ($allowDecimal, $locationId) {
                    // Filter location batches based on location filter
                    $locationBatches = $locationId 
                        ? $batch->locationBatches->where('location_id', $locationId)
                        : $batch->locationBatches;

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
                            ? round($locationBatches->sum(fn($lb) => (float)$lb->qty), 2)
                            : (int)$locationBatches->sum(fn($lb) => (int)$lb->qty),
                        'location_batches' => $locationBatches->map(function ($lb) use ($allowDecimal) {
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
                $product = Product::with([
                    'batches.salesProducts',
                    'batches.purchaseProducts', 
                    'batches.purchaseReturns',
                    'batches.saleReturns',
                    'batches.stockAdjustments',
                    'batches.stockTransfers'
                ])->find($id);

                if (!$product) {
                    return [
                        'status' => 404,
                        'message' => "No Such Product Found!"
                    ];
                }

                // Check if product is used in any important tables
                $hasTransactions = false;
                $usedInTables = [];

                if ($product->batches->isNotEmpty()) {
                    foreach ($product->batches as $batch) {
                        if ($batch->salesProducts->isNotEmpty()) {
                            $hasTransactions = true;
                            $usedInTables[] = 'Sales';
                        }
                        if ($batch->purchaseProducts->isNotEmpty()) {
                            $hasTransactions = true;
                            $usedInTables[] = 'Purchases';
                        }
                        if ($batch->purchaseReturns->isNotEmpty()) {
                            $hasTransactions = true;
                            $usedInTables[] = 'Purchase Returns';
                        }
                        if ($batch->saleReturns->isNotEmpty()) {
                            $hasTransactions = true;
                            $usedInTables[] = 'Sale Returns';
                        }
                        if ($batch->stockAdjustments->isNotEmpty()) {
                            $hasTransactions = true;
                            $usedInTables[] = 'Stock Adjustments';
                        }
                        if ($batch->stockTransfers->isNotEmpty()) {
                            $hasTransactions = true;
                            $usedInTables[] = 'Stock Transfers';
                        }
                    }
                }

                // If product is used in any transaction, prevent deletion
                if ($hasTransactions) {
                    $usedInTables = array_unique($usedInTables);
                    return [
                        'status' => 403,
                        'can_delete' => false,
                        'message' => "Cannot delete this product! It is being used in: " . implode(', ', $usedInTables) . ". Please deactivate the product instead.",
                        'used_in' => $usedInTables,
                        'product_status' => $product->is_active ? 'active' : 'inactive'
                    ];
                }

                // Product is safe to delete - only exists in product_locations
                // Delete location_product pivot records
                DB::table('location_product')->where('product_id', $id)->delete();

                // Delete all related batches and their location batches
                if ($product->batches->isNotEmpty()) {
                    $batchIds = $product->batches->pluck('id')->toArray();

                    // Delete location batches first
                    LocationBatch::whereIn('batch_id', $batchIds)->delete();

                    // Then delete the batches
                    Batch::whereIn('id', $batchIds)->delete();
                }

                // Delete IMEI numbers if any
                DB::table('imei_numbers')->where('product_id', $id)->delete();

                // Delete discount associations
                DB::table('discount_product')->where('product_id', $id)->delete();

                // Finally delete the product
                $product->delete();

                return [
                    'status' => 200,
                    'can_delete' => true,
                    'message' => "Product deleted successfully!"
                ];
            });

            return response()->json($result, $result['status']);
        } catch (\Exception $e) {
            Log::error('Product deletion error: ' . $e->getMessage(), [
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 500,
                'message' => "Error deleting product: " . $e->getMessage()
            ], 500);
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
                // Create an instance of the import class
                $import = new importProduct();

                // Process the Excel file
                Excel::import($import, $file);

                // Get validation errors from the import process
                $validationErrors = $import->getValidationErrors();
                $records = $import->getData();

                // If there are validation errors, return them in the response
                if (!empty($validationErrors)) {
                    return response()->json([
                        'status' => 401,
                        'validation_errors' => $validationErrors, // Return specific error messages

                    ]);
                }

                return response()->json([
                    'status' => 200,
                    'data' => $records,
                    'message' => "Import Products Excel file uploaded successfully!"
                ]);
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


    public function getProductLocations(Request $request)
    {
        $productIds = $request->input('product_ids', []);

        if (empty($productIds)) {
            return response()->json(['status' => 'error', 'message' => 'No products selected.'], 400);
        }

        try {
            $products = Product::with(['locations' => function($query) {
                $query->select('locations.id', 'locations.name');
            }])
            ->whereIn('id', $productIds)
            ->get(['id', 'product_name'])
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'locations' => $product->locations->map(function($location) {
                        return [
                            'id' => $location->id,
                            'name' => $location->name
                        ];
                    })
                ];
            });

            return response()->json(['status' => 'success', 'data' => $products]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
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
                // 1. Get existing locations with their current stock
                $existingLocationData = DB::table('location_product')
                    ->where('product_id', $productId)
                    ->get()
                    ->keyBy('location_id');

                // 2. Only remove locations that have zero stock and are not in the new selection
                $locationsToRemove = [];
                foreach ($existingLocationData as $locationId => $locationData) {
                    if (!in_array($locationId, $locationIds) && $locationData->qty == 0) {
                        $locationsToRemove[] = $locationId;
                    }
                }

                // Remove only zero-stock locations that are not selected
                if (!empty($locationsToRemove)) {
                    DB::table('location_product')
                        ->where('product_id', $productId)
                        ->whereIn('location_id', $locationsToRemove)
                        ->delete();
                }

                // 3. Add new locations (only if they don't already exist)
                foreach ($locationIds as $locationId) {
                    if (!isset($existingLocationData[$locationId])) {
                        // This is a new location for this product
                        DB::table('location_product')->insert([
                            'product_id' => $productId,
                            'location_id' => $locationId,
                            'qty' => 0,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]);
                    }
                    // If location already exists, keep it unchanged (preserve stock)
                }

                // 4. Handle batches for new locations only
                $batchIds = DB::table('batches')
                    ->where('product_id', $productId)
                    ->pluck('id')
                    ->toArray();

                foreach ($batchIds as $batchId) {
                    // Get existing batch locations
                    $existingBatchLocations = DB::table('location_batches')
                        ->where('batch_id', $batchId)
                        ->pluck('location_id')
                        ->toArray();

                    // Add batch entries for new locations only
                    foreach ($locationIds as $locationId) {
                        if (!in_array($locationId, $existingBatchLocations) && !isset($existingLocationData[$locationId])) {
                            // This is a completely new location for this product
                            DB::table('location_batches')->insert([
                                'batch_id' => $batchId,
                                'location_id' => $locationId,
                                'qty' => 0,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Locations added successfully. Existing location stock preserved.']);
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

    /**
     * Toggle product active/inactive status
     */
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

    /**
     * Get all batches for a specific product with their prices
     */
    public function getProductBatches($productId)
    {
        try {
            $product = Product::with(['unit:id,name,short_name,allow_decimal'])->findOrFail($productId);
            
            $batches = Batch::where('product_id', $productId)
                ->with(['locationBatches.location:id,name'])
                ->select([
                    'id',
                    'batch_no',
                    'product_id',
                    'unit_cost as original_price',
                    'wholesale_price',
                    'special_price', 
                    'retail_price',
                    'max_retail_price',
                    'expiry_date',
                    'qty',
                    'created_at'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 200,
                'product' => [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'unit' => $product->unit
                ],
                'batches' => $batches->map(function($batch) use ($product) {
                    // Check if the product unit allows decimals
                    $allowDecimal = $product->unit && $product->unit->allow_decimal;
                    
                    return [
                        'id' => $batch->id,
                        'batch_no' => $batch->batch_no,
                        'original_price' => $batch->original_price,
                        'wholesale_price' => $batch->wholesale_price,
                        'special_price' => $batch->special_price,
                        'retail_price' => $batch->retail_price,
                        'max_retail_price' => $batch->max_retail_price,
                        'expiry_date' => $batch->expiry_date,
                        'qty' => $allowDecimal ? round((float)$batch->qty, 2) : (int)$batch->qty,
                        'locations' => $batch->locationBatches->map(function($lb) use ($allowDecimal) {
                            return [
                                'id' => $lb->location->id,
                                'name' => $lb->location->name,
                                'qty' => $allowDecimal ? round((float)$lb->qty, 2) : (int)$lb->qty
                            ];
                        })
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product batches: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching product batches'
            ], 500);
        }
    }

    /**
     * Update batch prices (excluding original_price/cost price)
     */
    public function updateBatchPrices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batches' => 'required|array|min:1',
            'batches.*.id' => 'required|exists:batches,id',
            'batches.*.wholesale_price' => 'required|numeric|min:0',
            'batches.*.special_price' => 'required|numeric|min:0', 
            'batches.*.retail_price' => 'required|numeric|min:0',
            'batches.*.max_retail_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->batches as $batchData) {
                    $batch = Batch::findOrFail($batchData['id']);
                    
                    // Update only the editable prices (not original_price/unit_cost)
                    $batch->update([
                        'wholesale_price' => $batchData['wholesale_price'],
                        'special_price' => $batchData['special_price'],
                        'retail_price' => $batchData['retail_price'],
                        'max_retail_price' => $batchData['max_retail_price'],
                    ]);
                }
            });

            return response()->json([
                'status' => 200,
                'message' => 'Batch prices updated successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating batch prices: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error updating batch prices'
            ], 500);
        }
    }
}