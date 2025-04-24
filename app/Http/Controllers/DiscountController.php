<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Product;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DiscountsExport;
use Carbon\Carbon;

class DiscountController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view product-discount', ['only' => ['index','getDiscountsData']]);
        $this->middleware('permission:create product-discount', ['only' => ['store']]);
        $this->middleware('permission:edit product-discount', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete product-discount', ['only' => ['destroy']]);
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
        $type = $request->get('type', 'xlsx');
        $from = $request->get('from');
        $to = $request->get('to');

        // return Excel::download(new DiscountsExport($from, $to), 'discounts.'.$type);
    }
}
