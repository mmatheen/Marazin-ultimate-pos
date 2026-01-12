<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeController extends Controller
{
    private $barcodeHTML;
    private $barcodePNG;

    function __construct()
    {
        $this->middleware('permission:view product', ['only' => ['index']]);
        $this->middleware('permission:print barcodes', ['only' => ['generateBarcodes']]);

        // Initialize barcode generators once
        $this->barcodeHTML = new BarcodeGeneratorHTML();
        $this->barcodePNG = new BarcodeGeneratorPNG();
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
     * Optimized for high performance with caching and minimal database queries
     */
    public function searchProducts(Request $request)
    {
        try {
            $search = trim($request->input('search', ''));

            if (empty($search)) {
                return response()->json([
                    'status' => 200,
                    'products' => []
                ]);
            }

            // Cache search results for 5 minutes to reduce database load
            $cacheKey = 'barcode_search_' . md5(strtolower($search));

            $products = \Cache::remember($cacheKey, 300, function() use ($search) {
                // Use raw query for maximum performance - single optimized query
                $results = \DB::select("
                    SELECT
                        p.id,
                        p.product_name,
                        p.sku,
                        u.short_name as unit,
                        b.id as batch_id,
                        b.batch_no,
                        b.unit_cost,
                        b.wholesale_price,
                        b.special_price,
                        b.retail_price,
                        b.max_retail_price,
                        b.expiry_date,
                        COALESCE(SUM(lb.qty), 0) as total_qty
                    FROM products p
                    LEFT JOIN units u ON p.unit_id = u.id
                    LEFT JOIN batches b ON p.id = b.product_id
                    LEFT JOIN location_batches lb ON b.id = lb.batch_id
                    WHERE (p.product_name LIKE ? OR p.sku LIKE ?)
                    GROUP BY p.id, p.product_name, p.sku, u.short_name, b.id, b.batch_no,
                             b.unit_cost, b.wholesale_price, b.special_price, b.retail_price,
                             b.max_retail_price, b.expiry_date
                    HAVING total_qty > 0
                    ORDER BY p.product_name ASC
                    LIMIT 50
                ", ["%{$search}%", "%{$search}%"]);

                // Group results by product efficiently
                $groupedProducts = [];
                foreach ($results as $row) {
                    $productId = $row->id;

                    if (!isset($groupedProducts[$productId])) {
                        $groupedProducts[$productId] = [
                            'id' => $row->id,
                            'product_name' => $row->product_name,
                            'sku' => $row->sku,
                            'unit' => $row->unit ?? 'Unit',
                            'batches' => []
                        ];
                    }

                    if ($row->batch_id) {
                        $groupedProducts[$productId]['batches'][] = [
                            'id' => $row->batch_id,
                            'batch_no' => $row->batch_no,
                            'sku' => $row->sku,
                            'cost_price' => number_format((float)$row->unit_cost, 2),
                            'wholesale_price' => number_format((float)$row->wholesale_price, 2),
                            'special_price' => number_format((float)$row->special_price, 2),
                            'retail_price' => number_format((float)$row->retail_price, 2),
                            'max_retail_price' => number_format((float)$row->max_retail_price, 2),
                            'quantity' => (int)$row->total_qty,
                            'expiry_date' => $row->expiry_date
                        ];
                    }
                }

                // Convert to indexed array and limit to 10 products
                return array_slice(array_values($groupedProducts), 0, 10);
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
            // Parameters: (code, type, widthFactor, height)
            // widthFactor: 2 = good for printing, height: 25 = compact barcode height
            $generatorSVG = new BarcodeGeneratorSVG();

            // Fetch location name for the product
            $locationName = null;
            $productLocation = \DB::table('location_product')
                ->join('locations', 'location_product.location_id', '=', 'locations.id')
                ->where('location_product.product_id', $batch->product_id)
                ->select('locations.name')
                ->first();

            if ($productLocation) {
                $locationName = $productLocation->name;
            }

            // Generate barcodes array
            $barcodes = [];
            for ($i = 0; $i < $quantity; $i++) {
                $barcodes[] = [
                    'product_name' => $batch->product->product_name,
                    'batch_no' => $batch->batch_no,
                    'sku' => $batch->product->sku,
                    'barcode_html' => $generatorSVG->getBarcode($batch->product->sku, $generatorSVG::TYPE_CODE_128, 2, 25),
                    'price' => 'Rs. ' . number_format($selectedPrice, 0),
                    'price_type' => ucfirst(str_replace('_', ' ', $priceType)),
                    'location_name' => $locationName
                ];
            }

            return response()->json([
                'status' => 200,
                'barcodes' => $barcodes,
                'batch' => [
                    'product_name' => $batch->product->product_name,
                    'batch_no' => $batch->batch_no,
                    'sku' => $batch->product->sku,
                    'price' => number_format($selectedPrice, 0)
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
