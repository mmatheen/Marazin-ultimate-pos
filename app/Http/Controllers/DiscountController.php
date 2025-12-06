<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Product;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DiscountsExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DiscountController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view discount', ['only' => ['index','getDiscountsData', 'show']]);
        $this->middleware('permission:create discount', ['only' => ['store']]);
        $this->middleware('permission:edit discount', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete discount', ['only' => ['destroy']]);
    }


    public function index()
    {
        return view('discounts.index');
    }

    public function getDiscountsData(Request $request)
    {
        $query = Discount::query()->withCount('products');

        // Date range filter - modified to handle cases where only one date is provided
        if ($request->filled('from')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $query->where('start_date', '>=', $from);
        }

        if ($request->filled('to')) {
            $to = Carbon::parse($request->to)->endOfDay();
            $query->where(function($q) use ($to) {
                $q->where('end_date', '<=', $to)
                  ->orWhereNull('end_date');
            });
        }

        // Status filter - only apply if status is explicitly provided (0 or 1)
        if ($request->has('status') && $request->status !== '' && $request->status !== null) {
            $query->where('is_active', $request->status);
        }
        // When status is null/empty, don't apply any status filter (show all)

        return response()->json($query->get());
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'required|boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        // Validate discount amount logic
        if ($validated['type'] === 'percentage' && $validated['amount'] > 100) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['amount' => ['Percentage discount cannot exceed 100%']]
            ], 422);
        }

        // If specific products selected, validate against their batch prices
        if ($request->has('product_ids') && !empty($request->product_ids)) {
            $products = Product::whereIn('id', $request->product_ids)
                ->with(['batches' => function($query) {
                    $query->select('batches.id', 'batches.product_id', 'batches.batch_no', 'batches.retail_price', 'batches.max_retail_price', 'batches.wholesale_price')
                          ->join('location_batches', 'batches.id', '=', 'location_batches.batch_id')
                          ->selectRaw('SUM(location_batches.qty) as total_stock')
                          ->groupBy('batches.id', 'batches.product_id', 'batches.batch_no', 'batches.retail_price', 'batches.max_retail_price', 'batches.wholesale_price')
                          ->havingRaw('SUM(location_batches.qty) > 0'); // Only batches with stock
                }])
                ->get();

            foreach ($products as $product) {
                if ($product->batches->count() === 0) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => ['product_ids' => ["Product '{$product->product_name}' has no active batches with stock in any location"]]
                    ], 422);
                }

                foreach ($product->batches as $batch) {
                    $maxPrice = max(
                        $batch->retail_price ?? 0,
                        $batch->max_retail_price ?? 0,
                        $batch->wholesale_price ?? 0
                    );

                    if ($validated['type'] === 'fixed' && $validated['amount'] > $maxPrice) {
                        return response()->json([
                            'message' => 'Validation failed',
                            'errors' => ['amount' => ["Fixed discount (Rs. {$validated['amount']}) cannot exceed the batch price (Rs. {$maxPrice}) of product: {$product->product_name} - Batch: {$batch->batch_no} (Stock: {$batch->total_stock})"]]
                        ], 422);
                    }
                }
            }
        }

        $discount = Discount::create($validated);

        if ($request->has('product_ids')) {
            $discount->products()->sync($request->product_ids);
        }

        return response()->json([
            'success' => 'Discount created successfully',
            'discount' => $discount
        ]);
    }

    public function edit(Discount $discount)
    {
        $discount->load('products:id,product_name,sku');
        return response()->json($discount);
    }

    public function update(Request $request, Discount $discount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'required|boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        // Validate discount amount logic
        if ($validated['type'] === 'percentage' && $validated['amount'] > 100) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['amount' => ['Percentage discount cannot exceed 100%']]
            ], 422);
        }

        // If specific products selected, validate against their batch prices
        if ($request->has('product_ids') && !empty($request->product_ids)) {
            $products = Product::whereIn('id', $request->product_ids)
                ->with(['batches' => function($query) {
                    $query->select('batches.id', 'batches.product_id', 'batches.batch_no', 'batches.retail_price', 'batches.max_retail_price', 'batches.wholesale_price')
                          ->join('location_batches', 'batches.id', '=', 'location_batches.batch_id')
                          ->selectRaw('SUM(location_batches.qty) as total_stock')
                          ->groupBy('batches.id', 'batches.product_id', 'batches.batch_no', 'batches.retail_price', 'batches.max_retail_price', 'batches.wholesale_price')
                          ->havingRaw('SUM(location_batches.qty) > 0'); // Only batches with stock
                }])
                ->get();

            foreach ($products as $product) {
                if ($product->batches->count() === 0) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => ['product_ids' => ["Product '{$product->product_name}' has no active batches with stock in any location"]]
                    ], 422);
                }

                foreach ($product->batches as $batch) {
                    $maxPrice = max(
                        $batch->retail_price ?? 0,
                        $batch->max_retail_price ?? 0,
                        $batch->wholesale_price ?? 0
                    );

                    if ($validated['type'] === 'fixed' && $validated['amount'] > $maxPrice) {
                        return response()->json([
                            'message' => 'Validation failed',
                            'errors' => ['amount' => ["Fixed discount (Rs. {$validated['amount']}) cannot exceed the batch price (Rs. {$maxPrice}) of product: {$product->product_name} - Batch: {$batch->batch_no} (Stock: {$batch->total_stock})"]]
                        ], 422);
                    }
                }
            }
        }

        $discount->update($validated);

        $productIds = $request->has('product_ids') ? $request->product_ids : [];
        $discount->products()->sync($productIds);

        return response()->json([
            'success' => 'Discount updated successfully',
            'discount' => $discount
        ]);
    }

    public function destroy(Discount $discount)
    {
        $discount->products()->detach();
        $discount->delete();
        return response()->json(['success' => 'Discount deleted successfully']);
    }

    public function getProducts(Discount $discount)
    {
        // Use explicit table name for the id column to avoid ambiguity
        $products = $discount->products()
            ->select('products.id as product_id', 'products.product_name', 'products.sku')
            ->get();

        return response()->json($products);
    }

    public function toggleStatus(Discount $discount)
    {
        $discount->update(['is_active' => !$discount->is_active]);
        return response()->json([
            'success' => 'Status updated successfully',
            'is_active' => $discount->fresh()->is_active
        ]);
    }

    public function export(Request $request)
    {
        try {
            $type = $request->get('type', 'xlsx');
            $from = $request->get('from');
            $to = $request->get('to');
            $status = $request->get('status');

            $export = new DiscountsExport($from, $to, $status);
            $filename = 'discounts_' . date('Y-m-d') . '.' . $type;

            // Direct download without additional headers that might cause notifications
            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            Log::error('Export failed', ['error' => $e->getMessage()]);
            // Return a simple error page instead of JSON to avoid notifications
            return response()->view('errors.export-error', ['message' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    public function validateProductPrices(Request $request)
    {
        $productIds = $request->input('product_ids', []);

        if (empty($productIds)) {
            return response()->json([
                'valid' => true,
                'message' => 'No products selected - discount will apply to all products'
            ]);
        }

        $products = Product::whereIn('id', $productIds)
            ->with(['batches' => function($query) {
                $query->select('batches.id', 'batches.product_id', 'batches.batch_no', 'batches.retail_price', 'batches.max_retail_price', 'batches.wholesale_price', 'batches.unit_cost')
                      ->join('location_batches', 'batches.id', '=', 'location_batches.batch_id')
                      ->selectRaw('SUM(location_batches.qty) as total_stock')
                      ->groupBy('batches.id', 'batches.product_id', 'batches.batch_no', 'batches.retail_price', 'batches.max_retail_price', 'batches.wholesale_price', 'batches.unit_cost')
                      ->havingRaw('SUM(location_batches.qty) > 0'); // Only batches with stock in any location
            }])
            ->select('id', 'product_name', 'sku')
            ->get()
            ->map(function($product) {
                $batchPrices = [];
                $maxPrice = 0;
                $minPrice = PHP_INT_MAX;
                $totalStock = 0;

                if ($product->batches->count() > 0) {
                    // Get all batch prices for this product
                    foreach ($product->batches as $batch) {
                        $batchMaxPrice = max(
                            $batch->retail_price ?? 0,
                            $batch->max_retail_price ?? 0,
                            $batch->wholesale_price ?? 0
                        );

                        if ($batchMaxPrice > $maxPrice) {
                            $maxPrice = $batchMaxPrice;
                        }
                        if ($batchMaxPrice < $minPrice && $batchMaxPrice > 0) {
                            $minPrice = $batchMaxPrice;
                        }

                        $totalStock += $batch->total_stock ?? 0;

                        $batchPrices[] = [
                            'batch_no' => $batch->batch_no,
                            'retail_price' => $batch->retail_price ?? 0,
                            'max_retail_price' => $batch->max_retail_price ?? 0,
                            'wholesale_price' => $batch->wholesale_price ?? 0,
                            'stock' => $batch->total_stock ?? 0,
                            'max_price' => $batchMaxPrice
                        ];
                    }
                } else {
                    // No batches found, return 0 prices
                    $minPrice = 0;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'sku' => $product->sku,
                    'batch_count' => $product->batches->count(),
                    'total_stock' => $totalStock,
                    'batches' => $batchPrices,
                    'min_price' => $minPrice === PHP_INT_MAX ? 0 : $minPrice,
                    'max_price' => $maxPrice,
                    'display_price_range' => $minPrice === $maxPrice
                        ? 'Rs. ' . number_format($maxPrice, 2)
                        : 'Rs. ' . number_format($minPrice, 2) . ' - Rs. ' . number_format($maxPrice, 2)
                ];
            });

        return response()->json([
            'valid' => true,
            'products' => $products
        ]);
    }
}
