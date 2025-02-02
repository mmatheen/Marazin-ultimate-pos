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
                    // // Validate batch_no format
                    // if (!empty($locationData['batch_no']) && !preg_match('/^BATCH[0-4]{3,}$/', $locationData['batch_no'])) {
                    //     throw new \Exception("Invalid batch number: " . $locationData['batch_no']);
                    // }

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

    // public function getAllProductStocks()
    // {
    //     // Retrieve all products
    //     $products = Product::with('batches.locationBatches.location')->get();

    //     // Prepare the response
    //     $productStocks = $products->map(function ($product) {
    //         // Calculate total stock
    //         $totalStock = $product->batches->sum(function ($batch) {
    //             return $batch->locationBatches->sum('qty');
    //         });

    //         // Map through batches
    //         $batches = $product->batches->map(function ($batch) {
    //             // Map through location batches
    //             $locationBatches = $batch->locationBatches->map(function ($locationBatch) {
    //                 return [
    //                     'batch_id' => $locationBatch->batch_id ?? 'N/A',
    //                     'location_id' => $locationBatch->location_id ?? 'N/A',
    //                     'location_name' => $locationBatch->location->name ?? 'N/A',
    //                     'quantity' => $locationBatch->qty,
    //                 ];
    //             });

    //             return [
    //                 'id'=>$batch->id,
    //                 'batch_no' => $batch->batch_no,
    //                 'unit_cost' => $batch->unit_cost,
    //                 'wholesale_price' => $batch->wholesale_price,
    //                 'special_price' => $batch->special_price,
    //                 'retail_price' => $batch->retail_price,
    //                 'max_retail_price' => $batch->max_retail_price,
    //                 'expiry_date' => $batch->expiry_date,
    //                 'total_batch_quantity' => $batch->locationBatches->sum('qty'),
    //                 'location_batches' => $locationBatches,
    //             ];
    //         });

    //         // Return the product structure
    //         return [
    //             'product' => [
    //                 'id' => $product->id,
    //                 'product_name' => $product->product_name,
    //                 'sku' => $product->sku,
    //                 'unit_id' => $product->unit_id,
    //                 'brand_id' => $product->brand_id,
    //                 'main_category_id' => $product->main_category_id,
    //                 'sub_category_id' => $product->sub_category_id,
    //                 'stock_alert' => $product->stock_alert,
    //                 'alert_quantity' => $product->alert_quantity,
    //                 'product_image' => $product->product_image,
    //                 'description' => $product->description,
    //                 'is_imei_or_serial_no' => $product->is_imei_or_serial_no,
    //                 'is_for_selling' => $product->is_for_selling,
    //                 'product_type' => $product->product_type,
    //                 'pax' => $product->pax,
    //                 'original_price' => $product->original_price,
    //                 'retail_price' => $product->retail_price,
    //                 'whole_sale_price' => $product->whole_sale_price,
    //                 'special_price' => $product->special_price,
    //                 'max_retail_price' => $product->max_retail_price,
    //             ],
    //             'total_stock' => $totalStock,
    //             'batches' => $batches,
    //         ];
    //     });

    //     // Return the response
    //     return response()->json(['status' => 200, 'data' => $productStocks]);
    // }

    public function getAllProductStocks()
        {
            $user = auth()->user();
            $userRole = $user->role_name;
            $userLocationId = $user->location_id;

            // Retrieve products based on the user's role
            if ($userRole === 'Super Admin') {
                $products = Product::with('batches.locationBatches.location')->get();
            } else {
                $products = Product::with(['batches.locationBatches' => function ($query) use ($userLocationId) {
                    $query->where('location_id', $userLocationId);
                }])->get();
            }

            // Prepare the response
            $productStocks = $products->map(function ($product) use ($userRole) {
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

                // Return the product structure
                return [
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
                ];
            });

            // Return the response
            return response()->json(['status' => 200, 'data' => $productStocks]);
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
