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
    public function product()
    {
        return view('product.product');
    }

    public function addProduct()
    {
        return view('product.add_product');
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
  
    public function updatePrice()
    {
        return view('product.update_price');
    }

    public function importProduct()
    {
        return view('product.import_product');
    }

   
}
