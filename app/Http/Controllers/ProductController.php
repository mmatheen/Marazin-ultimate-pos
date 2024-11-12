<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Location;
use App\Models\MainCategory;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Unit;
use App\Models\OpeningStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function product()
    {
        return view('product.product');
    }

    public function addProduct()
    {
        $MainCategories = MainCategory::all(); // this course come from modal
        $SubCategories = SubCategory::with('mainCategory')->get(); // this course come from modal
        $brands = Brand::all();
        $units = Unit::all();
        $locations = Location::all();
        $products = Product::all();
        return view('product.add_product', compact('brands', 'SubCategories', 'MainCategories','units','locations','products'));
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
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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
    
        // Auto-generate SKU
        $sku = $request->sku ?: 'PRO' . sprintf("%04d", Product::count() + 1);
    
        // File upload
        $fileName = $request->hasFile('product_image') ? time() . '.' . $request->file('product_image')->extension() : null;
        if ($fileName) {
            $request->file('product_image')->move(public_path('/assets/images'), $fileName);
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
            'description' => $request->description ?? '',
            'is_imei_or_serial_no' => $request->is_imei_or_serial_no,
            'is_for_selling' => $request->is_for_selling,
            'product_type' => $request->product_type,
            'pax' => $request->pax,
            'retail_price' => $request->retail_price,
            'whole_sale_price' => $request->whole_sale_price,
            'special_price' => $request->special_price,
            'original_price' => $request->original_price,
        ]);
    
        // Attach locations to the product
        $product->locations()->attach($request->location_id);
    
        return response()->json(['status' => 200, 'message' => "New Product Details Created Successfully!"]);
    }
    

    public function openingStockStore(Request $request)
    {

        // dd($request->all());
        $validator = Validator::make(
            $request->all(),
            [
                'sku' => [
                    'nullable',
                    'string',
                    'max:255',
                    'unique:opening_stocks',
                    function($attribute, $value, $fail) {
                        // Custom rule for location_id format
                        if (!preg_match('/^SKU\d{4}$/', $value)) {
                            $fail('The ' . $attribute . ' must be in the format SKU followed by 4 digits. eg:  SKU0001');
                        }
                    }
                ],
                'location_id' => 'required|string|max:255',
                'product_id' => 'required|string|max:255',
                'quantity' => 'required|string|max:255',
                'unit_cost' => 'required|string|max:255',
                'lot_no' => 'required|string|max:255',
                'expiry_date' => 'required|string|max:255',
            ],
            [
                'sku.unique' => 'The location_id has already been taken.',
            ]
        );


      // Custom logic for generating location_id auto-increment code start

      // Generate location_id only if not provided
      $sku = $request->sku;
      if (!$sku) {
        // Custom logic for generating location_id auto-increment code
        $prefix = 'SKU'; // The prefix for location_id
        $latestSKU = OpeningStock::where('sku', 'like', $prefix . '%')->orderBy('sku', 'desc')->first();

        // Extract the numeric part of the latest location_id and increment it
        if ($latestSKU) {
            // Extract numeric part after the prefix 'LOC'
            $latestID = intval(substr($latestSKU->sku, strlen($prefix)));
        } else {
            $latestID = 1; // If no record found, start from 1
        }

        $nextID = $latestID + 1;
        $sku = $prefix . sprintf("%04d", $nextID); // Format as LOC0001, LOC0002, etc.

        // Check for uniqueness of the generated location_id and regenerate if necessary
        while (OpeningStock::where('sku', $sku)->exists()) {
            $nextID++;
            $sku = $prefix . sprintf("%04d", $nextID);
        }
    }
        // Custom logic for generating location_id auto-increment code end



        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = OpeningStock::create([


                'sku' => $sku, // Use unique generated sku ID
                'location_id' => $request->location_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_cost' => $request->unit_cost,
                'lot_no' => $request->lot_no,
                'expiry_date' => $request->expiry_date,

            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Opening Stock Details Created Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => "Something went wrong!"
                ]);
            }
        }
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
}