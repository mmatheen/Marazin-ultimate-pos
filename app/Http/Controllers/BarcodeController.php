<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Batch;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Illuminate\Support\Facades\Validator;

class BarcodeController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view product', ['only' => ['index']]);
        $this->middleware('permission:print barcodes', ['only' => ['generateBarcodes']]);
    }

    /**
     * Display the barcode generation page
     */
    public function index()
    {
        return view('barcode.barcode');
    }

    /**
     * Search products by name or SKU for barcode generation
     */
    public function searchProducts(Request $request)
    {
        try {
            $search = $request->input('search', '');

            if (empty($search)) {
                return response()->json([
                    'status' => 200,
                    'products' => []
                ]);
            }

            // Search products with their batches
            $products = Product::where(function($query) use ($search) {
                    $query->where('product_name', 'LIKE', "%{$search}%")
                          ->orWhere('sku', 'LIKE', "%{$search}%");
                })
                ->with(['unit:id,name,short_name', 'batches.locationBatches'])
                ->select('id', 'product_name', 'sku', 'unit_id')
                ->take(10)
                ->get()
                ->map(function($product) {
                    // Get batches with stock
                    $batches = $product->batches->map(function($batch) use ($product) {
                        $totalStock = $batch->locationBatches->sum('qty');

                        return [
                            'id' => $batch->id,
                            'batch_no' => $batch->batch_no,
                            'sku' => $product->sku,
                            'cost_price' => number_format($batch->unit_cost, 2),
                            'wholesale_price' => number_format($batch->wholesale_price, 2),
                            'special_price' => number_format($batch->special_price, 2),
                            'retail_price' => number_format($batch->retail_price, 2),
                            'max_retail_price' => number_format($batch->max_retail_price, 2),
                            'quantity' => $totalStock,
                            'expiry_date' => $batch->expiry_date
                        ];
                    })->filter(function($batch) {
                        return $batch['quantity'] > 0; // Only show batches with stock
                    })->values();

                    return [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'sku' => $product->sku,
                        'unit' => $product->unit?->short_name ?? 'Unit',
                        'batches' => $batches
                    ];
                })
                ->filter(function($product) {
                    return $product['batches']->count() > 0; // Only show products with stock
                });

            return response()->json([
                'status' => 200,
                'products' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error searching products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate barcodes based on batch and quantity
     */
    public function generateBarcodes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'batch_id' => 'required|exists:batches,id',
                'quantity' => 'required|integer|min:1|max:100',
                'price_type' => 'required|in:cost_price,wholesale_price,special_price,retail_price,max_retail_price'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $batch = Batch::with('product')->findOrFail($request->batch_id);
            $quantity = (int) $request->quantity;
            $priceType = $request->price_type;

            // Get the selected price
            $priceMap = [
                'cost_price' => $batch->unit_cost,
                'wholesale_price' => $batch->wholesale_price,
                'special_price' => $batch->special_price,
                'retail_price' => $batch->retail_price,
                'max_retail_price' => $batch->max_retail_price
            ];

            $selectedPrice = $priceMap[$priceType];

            // Generate barcode using HTML format for web display
            $generatorHTML = new BarcodeGeneratorHTML();

            // Generate barcodes array
            $barcodes = [];
            for ($i = 0; $i < $quantity; $i++) {
                $barcodes[] = [
                    'product_name' => $batch->product->product_name,
                    'batch_no' => $batch->batch_no,
                    'sku' => $batch->product->sku,
                    'barcode_html' => $generatorHTML->getBarcode($batch->product->sku, $generatorHTML::TYPE_CODE_128, 2, 50),
                    'price' => 'Rs. ' . number_format($selectedPrice, 2),
                    'price_type' => ucfirst(str_replace('_', ' ', $priceType))
                ];
            }

            return response()->json([
                'status' => 200,
                'barcodes' => $barcodes,
                'batch' => [
                    'product_name' => $batch->product->product_name,
                    'batch_no' => $batch->batch_no,
                    'sku' => $batch->product->sku,
                    'price' => number_format($selectedPrice, 2)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error generating barcodes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate barcode image (PNG format) for download/print
     */
    public function generateBarcodeImage(Request $request)
    {
        try {
            $sku = $request->input('sku');

            if (empty($sku)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'SKU is required'
                ], 400);
            }

            $generatorPNG = new BarcodeGeneratorPNG();
            $barcode = $generatorPNG->getBarcode($sku, $generatorPNG::TYPE_CODE_128, 3, 60);

            return response($barcode)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'inline; filename="barcode-' . $sku . '.png"');

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error generating barcode image: ' . $e->getMessage()
            ], 500);
        }
    }
}
