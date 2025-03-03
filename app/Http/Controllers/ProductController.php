<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Location;
use App\Models\MainCategory;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Unit;
use App\Models\LocationBatch;
use App\Models\StockHistory;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ProductController extends Controller
{
    public function product()
    {
        return view('product.product');
    }

    public function addProduct()
    {
        return view('product.add_product');
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

            $getValue = Product::whereHas('locations', function($query) use ($locationId) {
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
                'product' =>$product,
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

         if($products){

             return response()->json([
                 'status' => 200,
                 'message' => $products
             ]);
         }
         else{
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
        $rules = [
            'sku' => [
                'nullable', 'string', 'max:255', 'unique:products,sku,'.$id,
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^SKU\d{4}$/', $value)) {
                        $fail('The ' . $attribute . ' must be in the format SKU followed by 4 digits. eg: SKU0001');
                    }
                }
            ],
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

        // Auto-generate SKU
        $sku = $request->sku ?: 'SKU' . sprintf("%04d", Product::count() + 1);

        // Update product details
        $product->fill([
            'product_name' => $request->product_name,
            'sku' => $sku,
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


    // private function removeUnusedLocationBatchesAndStockHistory($product, $newLocations)
    // {
    //     $newLocationIds = array_column($newLocations, 'id');

    //     // Get the existing location ids for the product
    //     $existingLocationIds = $product->locations->pluck('id')->toArray();

    //     // Find locations that are no longer associated with the product
    //     $removedLocationIds = array_diff($existingLocationIds, $newLocationIds);

    //     // Remove stock history records and location batches for locations that are no longer associated
    //     foreach ($removedLocationIds as $locationId) {
    //         $locationBatches = LocationBatch::where('location_id', $locationId)
    //             ->whereHas('batch', function ($query) use ($product) {
    //                 $query->where('product_id', $product->id);
    //             })->get();

    //         foreach ($locationBatches as $locationBatch) {
    //             StockHistory::where('loc_batch_id', $locationBatch->id)
    //                 ->where('stock_type', StockHistory::STOCK_TYPE_OPENING)
    //                 ->delete();
    //             $locationBatch->delete();
    //         }
    //     }

    //     // Remove batches that are no longer associated with any location
    //     $batchIds = Batch::where('product_id', $product->id)->pluck('id')->toArray();
    //     $usedBatchIds = LocationBatch::whereIn('batch_id', $batchIds)->pluck('batch_id')->toArray();
    //     $unusedBatchIds = array_diff($batchIds, $usedBatchIds);
    //     Batch::whereIn('id', $unusedBatchIds)->delete();
    // }

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
            'locations.*.batch_no' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^BATCH[0-9]{3,}$/',
            ],
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
            DB::transaction(function () use ($filteredLocations, $product, &$message) {
                $isUpdate = false;
                $locationIds = array_column($filteredLocations, 'id');
    
                // Get the existing location batches for the product
                $existingLocationBatches = LocationBatch::whereHas('batch', function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                })->get();
    
                // Remove stock history records, location batches, and batches for locations that are no longer associated
                foreach ($existingLocationBatches as $locationBatch) {
                    if (!in_array($locationBatch->location_id, $locationIds)) {
                        StockHistory::where('loc_batch_id', $locationBatch->id)
                            ->where('stock_type', StockHistory::STOCK_TYPE_OPENING)
                            ->delete();
                        $locationBatch->delete();
                    }
                }
    
                // Remove batches that are no longer associated with any location
                $batchIds = Batch::where('product_id', $product->id)->pluck('id')->toArray();
                $usedBatchIds = LocationBatch::whereIn('batch_id', $batchIds)->pluck('batch_id')->toArray();
                $unusedBatchIds = array_diff($batchIds, $usedBatchIds);
                Batch::whereIn('id', $unusedBatchIds)->delete();
    
                foreach ($filteredLocations as $locationData) {
                    $formattedExpiryDate = $locationData['expiry_date']
                        ? \Carbon\Carbon::parse($locationData['expiry_date'])->format('Y-m-d')
                        : null;
    
                    $existingBatch = Batch::where('batch_no', $locationData['batch_no'] ?? '')
                        ->where('product_id', $product->id)
                        ->first();
    
                    if ($existingBatch) {
                        $isUpdate = true;
                    }
    
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
                }
    
                $message = $isUpdate ? 'Opening Stock updated successfully!' : 'Opening Stock saved successfully!';
            });
    
            return response()->json(['status' => 200, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
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



    public function getAllProductStocks()
    {
        $user = auth()->user();
        $userRole = $user->role_name;
        $userLocationId = $user->location_id;
    
        // Initialize an array to store product stock data
        $productStocks = [];
    
        // Retrieve products based on the user's role in chunks
        Product::with(['batches.locationBatches.location', 'locations'])->chunk(500, function ($products) use ($userRole, $userLocationId, &$productStocks) {
            // Process each product in the chunk
            foreach ($products as $product) {
                // Calculate total stock
                $totalStock = $product->batches->sum(function ($batch) {
                    return $batch->locationBatches->sum('qty');
                });
    
                // Map through batches
                $batches = $product->batches->map(function ($batch) {
                    // Map through location batches
                    $locationBatches = $batch->locationBatches->map(function ($locationBatch) {
                        return [
                            'batch_id' => $locationBatch->batch_id ?? 'N/A',
                            'location_id' => $locationBatch->location_id ?? 'N/A',
                            'location_name' => $locationBatch->location->name ?? 'N/A',
                            'quantity' => $locationBatch->qty,
                        ];
                    });
    
                    return [
                        'id' => $batch->id,
                        'batch_no' => $batch->batch_no,
                        'unit_cost' => $batch->unit_cost,
                        'wholesale_price' => $batch->wholesale_price,
                        'special_price' => $batch->special_price,
                        'retail_price' => $batch->retail_price,
                        'max_retail_price' => $batch->max_retail_price,
                        'expiry_date' => $batch->expiry_date,
                        'total_batch_quantity' => $batch->locationBatches->sum('qty'),
                        'location_batches' => $locationBatches,
                    ];
                });
    
                // Add the processed product stock to the response array
                $productStocks[] = [
                    'product' => [
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
                    ],
                    'total_stock' => $totalStock,
                    'batches' => $batches,
                    'locations' => $product->locations->map(function ($location) {
                        return [
                            'location_id' => $location->id,
                            'location_name' => $location->name,
                        ];
                    }),
                ];
            }
        });
    
        // Return the response
        return response()->json(['status' => 200, 'data' => $productStocks]);
    }


    public function getNotifications()
    {
        $products = Product::with('batches.locationBatches.location')->get();

        $notifications = $products->filter(function ($product) {
            $totalStock = $product->batches->sum(function ($batch) {
                return $batch->locationBatches->sum('qty');
            });

            return $totalStock <= $product->alert_quantity;
        })->map(function ($product) {
            $totalStock = $product->batches->sum(function ($batch) {
                return $batch->locationBatches->sum('qty');
            });

            return [
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
        });

        return response()->json(['status' => 200, 'data' => $notifications, 'count' => $notifications->count()]);
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



    // public function getStocks(Request $request)
    // {
    //     $searchTerm = $request->query('search', '');

    //     // Fetch data from the API (replace with your actual API call)
    //     $response = file_get_contents('http://127.0.0.1:8000/products/stocks');
    //     $data = json_decode($response, true);

    //     // Filter products based on search term
    //     if ($searchTerm) {
    //         $data['data'] = array_filter($data['data'], function ($product) use ($searchTerm) {
    //             return stripos($product['product']['product_name'], $searchTerm) !== false ||
    //                    stripos($product['product']['sku'], $searchTerm) !== false;
    //         });
    //     }

    //     return response()->json($data);
    // }


    public function showSubCategoryDetailsUsingByMainCategoryId(string $main_category_id)
    {
        $subcategoryDetails = SubCategory::where('main_category_id', $main_category_id)->select('id', 'subCategoryname', 'main_category_id','subCategoryCode','description')->orderBy('main_category_id', 'asc')->get();
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
        $getValue = Product::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Product Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Product Found!"
            ]);
        }
    }
}
