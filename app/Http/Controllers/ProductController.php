<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Location;
use App\Models\MainCategory;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Unit;
use App\Models\OpeningStock;
use App\Models\LocationBatch;
use App\Models\StockHistory;
use App\Models\Batch;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $product = Product::with(['locations', 'category', 'brand']) // Assuming relationships are set up in the model
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
            // Calculate derived fields like line_total if needed
            $line_total = $product->retail_price * ($product->pax ?? 1);
            $net_cost = $product->retail_price * (1 + ($product->product_tax ?? 0) / 100);
            $profit_margin = $product->retail_price - $product->original_price;

            return response()->json([
                'status' => 200,
                'product' => [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'purchase_quantity' => $product->pax,
                    // 'original_price' => $product->original_price,
                    'discount_percent' => $product->discount_percent,
                    'retail_price' => $product->retail_price,
                    'sub_total' => $line_total,
                    'product_tax' => $product->product_tax,
                    'net_cost' => $net_cost,
                    'line_total' => $line_total,
                    'profit_margin' => $profit_margin,
                    'whole_sale_price' => $product->whole_sale_price,
                ],
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
                'error' => $e->getMessage()
            ]);
         }

    }


    public function EditProduct($id)
    {
        // Fetch the product and related data
        $product = Product::with(['locations', 'category', 'brand'])->find($id);

        $mainCategories = MainCategory::all();
        $subCategories = SubCategory::all();
        $brands = Brand::all();
        $units = Unit::all();
        $locations = Location::all();

        // Check if the request is AJAX
        if (request()->ajax()) {
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
        return view('product.edit_product', compact('id', 'product', 'mainCategories', 'subCategories', 'brands', 'units', 'locations'));
    }



    // public function store(Request $request)
    // {
    //     $validator = Validator::make(
    //         $request->all(),
    //         [
    //             'sku' => [
    //                 'nullable', 'string', 'max:255', 'unique:products',
    //                 function ($attribute, $value, $fail) {
    //                     if (!preg_match('/^SKU\d{4}$/', $value)) {
    //                         $fail('The ' . $attribute . ' must be in the format SKU followed by 4 digits. eg: SKU0001');
    //                     }
    //                 }
    //             ],
    //             'product_name' => 'required|string|max:255',
    //             'unit_id' => 'required|integer|exists:units,id',
    //             'brand_id' => 'required|integer|exists:brands,id',
    //             'main_category_id' => 'required|integer|exists:main_categories,id',
    //             'sub_category_id' => 'required|integer|exists:sub_categories,id',
    //             'location_id' => 'required|array',
    //             'location_id.*' => 'integer|exists:locations,id',
    //             'stock_alert' => 'nullable|boolean',
    //             'alert_quantity' => 'nullable|numeric|min:0',
    //             'product_image' => 'nullable|mimes:jpeg,png,jpg,gif|max:5120',
    //             'description' => 'nullable|string',
    //             'is_imei_or_serial_no' => 'nullable|boolean',
    //             'is_for_selling' => 'required|boolean',
    //             'product_type' => 'required|string',
    //             'pax' => 'nullable|integer',
    //             'retail_price' => 'required|numeric|min:0',
    //             'whole_sale_price' => 'required|numeric|min:0',
    //             'special_price' => 'required|numeric|min:0',
    //             'original_price' => 'required|numeric|min:0',
    //             'max_retail_price' => 'required|numeric|min:0'
    //         ]
    //     );

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'errors' => $validator->messages()]);
    //     }

    //     // Auto-generate SKU
    //     $sku = $request->sku ?: 'PRO' . sprintf("%04d", Product::count() + 1);

    //     // File upload
    //     $fileName = null;
    //     if ($request->hasFile('product_image')) {
    //         $file = $request->file('product_image');
    //         $fileName = time() . '.' . $file->getClientOriginalExtension();
    //         $file->move(public_path('/assets/images'), $fileName);
    //     }

    //     // Create product
    //     $product = Product::create([
    //         'product_name' => $request->product_name,
    //         'sku' => $sku,
    //         'unit_id' => $request->unit_id,
    //         'brand_id' => $request->brand_id,
    //         'main_category_id' => $request->main_category_id,
    //         'sub_category_id' => $request->sub_category_id,
    //         'stock_alert' => $request->stock_alert,
    //         'alert_quantity' => $request->alert_quantity,
    //         'product_image' => $fileName,
    //         'description' => $request->description,
    //         'is_imei_or_serial_no' => $request->is_imei_or_serial_no,
    //         'is_for_selling' => $request->is_for_selling,
    //         'product_type' => $request->product_type,
    //         'pax' => $request->pax,
    //         'retail_price' => $request->retail_price,
    //         'whole_sale_price' => $request->whole_sale_price,
    //         'special_price' => $request->special_price,
    //         'original_price' => $request->original_price,
    //     ]);

    //     // Attach locations to the product
    //     $product->locations()->attach($request->location_id);

    //     return response()->json(['status' => 200, 'message' => "New Product Details Created Successfully!", 'product_id' => $product->id]);
    // }


    // public function openingStockStore(Request $request, $productId)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'locations' => 'required|array',
    //         'locations.*.id' => 'required|integer|exists:locations,id',
    //         'locations.*.quantity' => 'required|numeric|min:1',
    //         'locations.*.unit_cost' => 'required|numeric|min:0',
    //         'locations.*.batch_id' => 'nullable|string|max:255',
    //         'locations.*.expiry_date' => 'required|date',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'errors' => $validator->messages()]);
    //     }

    //     $product = Product::find($productId);
    //     if (!$product) {
    //         return response()->json(['status' => 404, 'message' => 'Product not found']);
    //     }

    //     try {
    //         foreach ($request->locations as $locationData) {
    //             // Parse and format expiry date
    //             $formattedExpiryDate = \Carbon\Carbon::parse($locationData['expiry_date'])->format('Y-m-d');

    //             // // Determine the batch ID
    //             $batchId = $locationData['batch_id'] ?? null;

    //              // Check if batch exists
    //         $batch = null;
    //         if ($batchId) {
    //             $batch = Batch::where('batch_id', $batchId)
    //                           ->where('product_id', $productId)
    //                           ->first();

    //             if ($batch) {
    //                 // Update existing batch
    //                 $batch->quantity += $locationData['quantity'];
    //                 $batch->price = $locationData['unit_cost'];
    //                 $batch->expiry_date = $formattedExpiryDate;
    //                 $batch->save();
    //             } else {
    //                 // Create new batch
    //                 $batch = Batch::create([
    //                     'batch_id' => $batchId,
    //                     'product_id' => $productId,
    //                     'price' => $locationData['unit_cost'],
    //                     'quantity' => $locationData['quantity'],
    //                     'expiry_date' => $formattedExpiryDate,
    //                 ]);
    //             }
    //         }
    //             // Create Opening Stock
    //             OpeningStock::create([
    //                 'product_id' => $productId,
    //                 'location_id' => $locationData['id'],
    //                 'batch_id' => $batch ? $batchId : null,
    //                 'quantity' => $locationData['quantity'],
    //                 'unit_cost' => $locationData['unit_cost'],
    //                 'expiry_date' => $formattedExpiryDate,
    //             ]);

    //             // Update or Create Stock
    //             $stock = Stock::firstOrNew([
    //                 'product_id' => $productId,
    //                 'location_id' => $locationData['id'],
    //                 'batch_id' => $batch ? $batchId : null,
    //                 'stock_type' => "Opening Stock"
    //             ]);

    //             $stock->quantity += $locationData['quantity'];
    //             $stock->save();
    //         }

    //         return response()->json(['status' => 200, 'message' => 'Opening Stock added successfully!']);
    //     } catch (\Exception $e) {

    //         return response()->json(['status' => 500, 'message' => 'An error occurred while processing the request.']);
    //     }
    // }




    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'sku' => [
                    'nullable', 'string', 'max:255', 'unique:products',
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
                'sub_category_id' => 'required|integer|exists:sub_categories,id',
                'locations' => 'required|array',
                'locations.*' => 'required|integer|exists:locations,id',
                'stock_alert' => 'nullable|boolean',
                'alert_quantity' => 'nullable|numeric|min:0',
                'product_image' => 'nullable|mimes:jpeg,png,jpg,gif|max:5120',
                'description' => 'nullable|string',
                'is_imei_or_serial_no' => 'nullable|boolean',
                'is_for_selling' => 'required|boolean',
                'product_type' => 'required|string',
                'pax' => 'nullable|integer',
                'retail_price' => 'required|numeric|min:0',
                'whole_sale_price' => 'required|numeric|min:0',
                'special_price' => 'required|numeric|min:0',
                'original_price' => 'required|numeric|min:0',
                'max_retail_price' => 'required|numeric|min:0',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        // Auto-generate SKU
        $sku = $request->sku ?: 'SKU' . sprintf("%04d", Product::count() + 1);

        // File upload
        $fileName = null;
        if ($request->hasFile('product_image')) {
            $file = $request->file('product_image');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('/assets/images'), $fileName);
        }

        // Create product
        $product = Product::create([
            'product_name' => $request->product_name,
            'sku' => $sku,
            'unit_id' => $request->unit_id,
            'brand_id' => $request->brand_id,
            'main_category_id' => $request->main_category_id,
            'sub_category_id' => $request->sub_category_id,
            'stock_alert' => $request->stock_alert,
            'alert_quantity' => $request->alert_quantity,
            'product_image' => $fileName,
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
        ]);

        // Attach locations to the product
        foreach ($request->locations as $locationId) {
            $product->locations()->attach($locationId, ['qty' => 0]);
        }

        return response()->json(['status' => 200, 'message' => "New Product Details Created Successfully!", 'product_id' => $product->id]);
    }



    public function openingStockStore(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'locations' => 'required|array',
            'locations.*.id' => 'required|integer|exists:locations,id',
            'locations.*.qty' => 'required|numeric|min:1',
            'locations.*.unit_cost' => 'required|numeric|min:0',
            'locations.*.batch_no' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^BATCH[0-9]{3,}$/', // Ensure batch_no starts with 'BATCH' followed by at least 3 digits
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
            DB::transaction(function () use ($request, $product) {
                foreach ($request->locations as $locationData) {
                    // Validate batch_no format
                    if (!empty($locationData['batch_no']) && !preg_match('/^BATCH[0-9]{3,}$/', $locationData['batch_no'])) {
                        throw new \Exception("Invalid batch number: " . $locationData['batch_no']);
                    }

                    // Parse and format expiry date
                    $formattedExpiryDate = $locationData['expiry_date']
                        ? \Carbon\Carbon::parse($locationData['expiry_date'])->format('Y-m-d')
                        : null;

                    // Determine the batch ID
                    $batch = Batch::firstOrCreate(
                        [
                            'batch_no' => $locationData['batch_no'] ?? Batch::generateNextBatchNo(),
                            'product_id' => $product->id,
                        ],
                        [
                            'qty' => $locationData['qty'],
                            'unit_cost' => $locationData['unit_cost'],
                            'wholesale_price' => $product->whole_sale_price,
                            'special_price' => $product->special_price,
                            'retail_price' => $product->retail_price,
                            'max_retail_price' => $product->max_retail_price,
                            'expiry_date' => $formattedExpiryDate,
                        ]
                    );

                    // Create location batch
                    $locationBatch = LocationBatch::create([
                        'batch_id' => $batch->id,
                        'location_id' => $locationData['id'],
                        'qty' => $locationData['qty'],
                    ]);

                    // Update location_product table
                    $product->locations()->updateExistingPivot($locationData['id'], ['qty' => $locationData['qty']]);

                    // Record stock history
                    StockHistory::create([
                        'loc_batch_id' => $locationBatch->id,
                        'quantity' => $locationData['qty'],
                        'stock_type' => 'opening_stock',
                    ]);
                }
            });

            return response()->json(['status' => 200, 'message' => 'Opening Stock added successfully!']);
        } catch (\Exception $e) {

            $error =$e->getMessage();


            // Return detailed error message
            return response()->json(['status' => 500, 'message' => 'An error occurred while processing the request. ',$error]);
        }
    }



    // public function getAllStockDetails()
    // {
    //     $user = auth()->user();

    //     // Get all products
    //     $allProducts = Product::all();

    //     // Prepare the stock query
    //     $stocksQuery = Stock::with(['product', 'batch', 'location'])
    //         ->select('product_id', 'location_id', 'batch_id', \DB::raw('SUM(quantity) as batch_quantity'))
    //         ->groupBy('product_id', 'location_id', 'batch_id');

    //     // If the user is not a superadmin, filter by their location_id
    //     if ($user->role_name !== 'Super Admin') {
    //         $stocksQuery->where('location_id', $user->location_id);
    //     }

    //     $stocks = $stocksQuery->get();

    //     // Group stocks by product ID
    //     $groupedStocks = $stocks->groupBy('product_id');

    //     // Map the products data to the desired structure
    //     $stockDetails = $allProducts->map(function ($product) use ($groupedStocks) {
    //         $productStocks = $groupedStocks->get($product->id, collect());

    //         return [
    //             'product' => $product,
    //             'total_quantity' => $productStocks->sum('batch_quantity'), // Correct total quantity for the product
    //             'locations' => $productStocks->groupBy('location_id')->map(function ($locationStocks) {
    //                 $location = $locationStocks->first()->location;
    //                 return [
    //                     'location_id' => $location->id,
    //                     'location_name' => $location->name,
    //                     'total_quantity' => $locationStocks->sum('batch_quantity'), // Total quantity for the location
    //                     'batches' => $locationStocks->map(function ($stock) {
    //                         return [
    //                             'batch_id' => $stock->batch_id,
    //                             'batch_quantity' => $stock->batch_quantity,
    //                             'expiry_date' => optional($stock->batch)->expiry_date,
    //                             'batch_price' => optional($stock->batch)->price,
    //                         ];
    //                     })->values(),
    //                 ];
    //             })->values(),
    //             // 'purchase_message' => $productStocks->isEmpty() ? 'Please purchase this product' : null,
    //         ];
    //     });

    //     if ($stockDetails->isNotEmpty()) {
    //         return response()->json([
    //             'status' => 200,
    //             'message' => 'Stock details fetched successfully',
    //             'stocks' => $stockDetails,
    //         ]);
    //     } else {
    //         return response()->json([
    //             'status' => 200,
    //             'message' => 'No Records Found',
    //         ]);
    //     }
    // }

    public function getAllProductStocks()
    {
        // Retrieve all products with related stock details
        $products = Product::with(['batches.locationBatches.location'])->get();

        // Prepare structured data
        $productStocks = $products->map(function($product) {
            // Calculate the total stock by summing up the quantities from all batches
            $totalStock = $product->batches->sum(function($batch) {
                return $batch->locationBatches->sum('qty');
            });

            // Prepare batch-wise data
            $batches = $product->batches->map(function($batch) {
                $totalBatchQty = $batch->locationBatches->sum('qty');
                return [
                    'batch_no' => $batch->batch_no,
                    'unit_cost' => $batch->unit_cost,
                    'wholesale_price' => $batch->wholesale_price,
                    'special_price' => $batch->special_price,
                    'retail_price' => $batch->retail_price,
                    'max_retail_price' => $batch->max_retail_price,
                    'expiry_date' => $batch->expiry_date,
                    'total_quantity' => $totalBatchQty,
                ];
            });

            return [
                'product' => $product,
                'total_stock' => $totalStock,
                'batches' => $batches,
            ];
        });

        return response()->json(['status' => 200, 'data' => $productStocks]);
    }



    public function showOpeningStock($productId)
        {
            $product = Product::with('locations')->findOrFail($productId);
            $locations = $product->locations;

            return view('product.opening_stock', compact('product', 'locations'));
        }




    public function update(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make(
            $request->all(),
            [
                'product_name' => 'required|string|max:255',
                'unit_id' => 'required|integer|exists:units,id',
                'brand_id' => 'required|integer|exists:brands,id',
                'main_category_id' => 'required|integer|exists:main_categories,id',
                'sub_category_id' => 'required|integer|exists:sub_categories,id',
                'location_id' => 'required|array',
                'location_id.*' => 'integer|exists:locations,id',
                'stock_alert' => 'nullable|boolean',
                'alert_quantity' => 'nullable|numeric|min:0',
                'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'description' => 'nullable|string',
                'is_imei_or_serial_no' => 'nullable|boolean',
                'is_for_selling' => 'required|boolean',
                'product_type' => 'required|string',
                'pax' => 'nullable|integer',
                'retail_price' => 'required|numeric|min:0',
                'whole_sale_price' => 'required|numeric|min:0',
                'special_price' => 'required|numeric|min:0',
                'original_price' => 'required|numeric|min:0'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        // Retrieve the product by ID
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['status' => 404, 'message' => 'Product not found!']);
        }

        // File upload logic
        if ($request->hasFile('product_image')) {
            // Delete the old image if exists
            if ($product->product_image) {
                $oldImagePath = public_path('assets/images/' . $product->product_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $fileName = time() . '.' . $request->file('product_image')->extension();
            $request->file('product_image')->move(public_path('/assets/images'), $fileName);
            $product->product_image = $fileName;
        }

        // Update product details
        $product->update([
            'product_name' => $request->product_name,
            'unit_id' => $request->unit_id,
            'brand_id' => $request->brand_id,
            'main_category_id' => $request->main_category_id,
            'sub_category_id' => $request->sub_category_id,
            'stock_alert' => $request->stock_alert,
            'alert_quantity' => $request->alert_quantity,
            'description' => $request->description,
            'is_imei_or_serial_no' => $request->is_imei_or_serial_no,
            'is_for_selling' => $request->is_for_selling,
            'product_type' => $request->product_type,
            'pax' => $request->pax,
            'retail_price' => $request->retail_price,
            'whole_sale_price' => $request->whole_sale_price,
            'special_price' => $request->special_price,
            'original_price' => $request->original_price,
        ]);

        // Attach locations to the product (sync to avoid duplicates)
        $product->locations()->sync($request->location_id);

        return response()->json(['status' => 200, 'message' => "Product Details Updated Successfully!"]);
    }


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
