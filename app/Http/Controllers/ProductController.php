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
use App\Events\StockUpdated;
use App\Models\Discount;

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

    // public function getStockHistory($productId)
    // {
    //     // Fetch the product
    //     $product = Product::findOrFail($productId);

    //     // Fetch stock histories
    //     $stockHistories = StockHistory::whereHas('locationBatch.batch', function ($query) use ($productId) {
    //         $query->where('product_id', $productId);
    //     })->with(['locationBatch.batch.product', 'locationBatch.location'])->get();

    //     // Prepare data for the view
    //     $data = [
    //         'product' => $product,
    //         'stockHistories' => $stockHistories,
    //     ];

    //     return view('product.product_stock_history', $data);
    // }

    public function getStockHistory($productId)
    {
        $product = Product::with([
            'stockHistories.locationBatch.batch.purchaseProducts.purchase.supplier',
            'stockHistories.locationBatch.batch.salesProducts.sale.customer',
            'stockHistories.locationBatch.batch.purchaseReturns.purchaseReturn.supplier',
            'stockHistories.locationBatch.batch.saleReturns.salesReturn.customer',
            'stockHistories.locationBatch.batch.stockAdjustments.stockAdjustment',
            'stockHistories.locationBatch.batch.stockTransfers.stockTransfer',
        ])->findOrFail($productId);

        $stockHistories = $product->stockHistories;

        // Calculate quantities in and out
        $quantitiesIn = $stockHistories->whereIn('stock_type', [
            StockHistory::STOCK_TYPE_PURCHASE,
            StockHistory::STOCK_TYPE_OPENING,
            StockHistory::STOCK_TYPE_SALE_RETURN_WITH_BILL,
            StockHistory::STOCK_TYPE_SALE_RETURN_WITHOUT_BILL,
            StockHistory::STOCK_TYPE_TRANSFER_IN,
        ])->sum('quantity');

        $quantitiesOut = $stockHistories->whereIn('stock_type', [
            StockHistory::STOCK_TYPE_SALE,
            StockHistory::STOCK_TYPE_ADJUSTMENT,
            StockHistory::STOCK_TYPE_PURCHASE_RETURN,
            StockHistory::STOCK_TYPE_TRANSFER_OUT,
        ])->sum(function ($history) {
            return abs($history->quantity);
        });

        // Calculate sum for each stock type
        $stockTypeSums = $stockHistories->groupBy('stock_type')->map(function ($histories) {
            return $histories->sum('quantity');
        });

        $currentStock = $quantitiesIn - $quantitiesOut;

        $data = [
            'product' => $product,
            'stock_histories' => $stockHistories,
            'quantities_in' => $quantitiesIn,
            'quantities_out' => $quantitiesOut,
            'current_stock' => $currentStock,
            'stock_type_sums' => $stockTypeSums
        ];

        // return response()->json($data);

        if (request()->ajax()) {
            return response()->json($data);
        }

        return view('product.product_stock_history', $data);
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
        Product::with(['batches.locationBatches.location', 'locations',
       'discounts' => function($query) {
            $query->where('is_active', true)
                  ->where('start_date', '<=', now())
                  ->where(function($query) {
                      $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                  });
        }
        ])->chunk(500, function ($products) use ($userRole, $userLocationId, &$productStocks) {
            // Process each product in the chunk
            foreach ($products as $product) {
                // Filter the batches based on the user's location ID
                $filteredBatches = $product->batches->filter(function ($batch) use ($userLocationId) {
                    return $batch->locationBatches->contains('location_id', $userLocationId);
                });
    
                // Calculate total stock for the user's location (0 if no batches)
                $totalStock = $filteredBatches->isEmpty() ? 0 : $filteredBatches->sum(function ($batch) use ($userLocationId) {
                    return $batch->locationBatches->where('location_id', $userLocationId)->sum('qty');
                });
    
                // Prepare batches data (empty array if no batches)
                $batches = $filteredBatches->isEmpty() ? [] : $filteredBatches->map(function ($batch) use ($userLocationId) {
                    // Get location batches for the user's location
                    $locationBatches = $batch->locationBatches
                        ->where('location_id', $userLocationId)
                        ->map(function ($locationBatch) {
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

                    // Get active discounts with pivot data
                    $activeDiscounts = $product->discounts->map(function ($discount) {
                        return [
                            'id' => $discount->id,
                            'name' => $discount->name,
                            'type' => $discount->type,
                            'amount' => $discount->amount,
                            'start_date' => $discount->start_date,
                            'end_date' => $discount->end_date,
                            'pivot' => $discount->pivot // Include pivot data if needed
                        ];
                    });

                // Add the product to the response array regardless of whether it has batches
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
                    'has_batches' => !$filteredBatches->isEmpty(),
                    'discounts' => $activeDiscounts,
                    'discounted_price' => $this->calculateDiscountedPrice($product->retail_price, $activeDiscounts),
                ];
            }
        });
    
        // Return the response
        return response()->json(['status' => 200, 'data' => $productStocks]);
    }

        // Helper function to calculate discounted price
    private function calculateDiscountedPrice($retailPrice, $discounts)
    {
        $price = $retailPrice;
        
        foreach ($discounts as $discount) {
            if ($discount['type'] === 'percentage') {
                $price = $price * (1 - ($discount['amount'] / 100));
            } else {
                $price = $price - $discount['amount'];
            }
        }
        
        return max($price, 0); 
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

    public function exportBlankTemplate()
    {
        return Excel::download(new ExportProductTemplate, 'Import Product Blank Template.xlsx');
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
    

