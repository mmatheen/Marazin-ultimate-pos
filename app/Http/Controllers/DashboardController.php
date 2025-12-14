<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view dashboard', ['only' => ['index', 'getDashboardData']]);
        $this->middleware('permission:view sales-analytics', ['only' => ['getSalesAnalytics']]);
        $this->middleware('permission:view purchase-analytics', ['only' => ['getPurchaseAnalytics']]);
        $this->middleware('permission:view stock-analytics', ['only' => ['getStockAnalytics']]);
        $this->middleware('permission:view financial-overview', ['only' => ['getFinancialOverview']]);
    }


    public function index()
    {
        return view('includes.dashboards.dashboard');
    }

    public function getDashboardData(Request $request)
    {
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $selectedLocationId = $request->query('location_id');

        // Create unique cache key based on parameters
        $userId = auth()->id();
        $cacheKey = "dashboard_data_{$userId}_{$startDate}_{$endDate}_{$selectedLocationId}";

        // Cache for 5 minutes (300 seconds)
        return Cache::remember($cacheKey, 300, function() use ($request, $startDate, $endDate, $selectedLocationId) {
            return $this->fetchDashboardData($startDate, $endDate, $selectedLocationId);
        });
    }

    private function fetchDashboardData($startDate, $endDate, $selectedLocationId)
    {

        // Get user's accessible locations (optimized)
        $user = auth()->user();
        $userLocations = ($user->role === 'Super Admin' || !$user->locations || $user->locations->isEmpty())
            ? null
            : $user->locations->pluck('id')->toArray();

        // Determine which locations to filter by
        $locationFilter = null;
        if ($selectedLocationId) {
            if ($userLocations === null || in_array($selectedLocationId, $userLocations)) {
                $locationFilter = [$selectedLocationId];
            }
        } else {
            $locationFilter = $userLocations;
        }

        // Sales filter condition (reusable)
        $salesFilter = function($query) {
            $query->where('transaction_type', 'invoice')
                  ->orWhere(function($subQuery) {
                      $subQuery->whereNull('transaction_type')->where('status', 'final');
                  });
        };

        // Sales filter condition (reusable)
        $salesFilter = function($query) {
            $query->where('transaction_type', 'invoice')
                  ->orWhere(function($subQuery) {
                      $subQuery->whereNull('transaction_type')->where('status', 'final');
                  });
        };

        // Optimized: Get all metrics in fewer queries using aggregation
        $salesMetrics = DB::table('sales')
            ->selectRaw('
                SUM(final_total) as total_sales,
                SUM(total_due) as total_sales_due
            ')
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->where($salesFilter)
            ->when($locationFilter, fn($q) => $q->whereIn('location_id', $locationFilter))
            ->first();

        $purchaseMetrics = DB::table('purchases')
            ->selectRaw('
                SUM(final_total) as total_purchases,
                SUM(total_due) as total_purchases_due
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($locationFilter, fn($q) => $q->whereIn('location_id', $locationFilter))
            ->first();

        $returnMetrics = DB::table(DB::raw('(
            SELECT "purchase" as type, return_total, total_due, location_id, created_at FROM purchase_returns
            UNION ALL
            SELECT "sales" as type, return_total, total_due, location_id, created_at FROM sales_returns
        ) as returns'))
            ->selectRaw('
                SUM(CASE WHEN type = "purchase" THEN return_total ELSE 0 END) as total_purchase_return,
                SUM(CASE WHEN type = "purchase" THEN total_due ELSE 0 END) as total_purchase_return_due,
                SUM(CASE WHEN type = "sales" THEN return_total ELSE 0 END) as total_sales_return,
                SUM(CASE WHEN type = "sales" THEN total_due ELSE 0 END) as total_sales_return_due
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($locationFilter, fn($q) => $q->whereIn('location_id', $locationFilter))
            ->first();

        $stockTransfer = DB::table('stock_transfers')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($locationFilter, fn($q) => $q->whereIn('from_location_id', $locationFilter))
            ->sum('final_total');

        $totalProducts = DB::table('products')->count('id');

        $totalProducts = DB::table('products')->count('id');

        // Optimized: Get chart data in single query
        $chartData = DB::table('sales')
            ->selectRaw('DATE(sales_date) as date, SUM(final_total) as sales_amount')
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->where($salesFilter)
            ->when($locationFilter, fn($q) => $q->whereIn('location_id', $locationFilter))
            ->groupBy(DB::raw('DATE(sales_date)'))
            ->get();

        $salesDates = $chartData->pluck('date');
        $salesAmounts = $chartData->pluck('sales_amount');

        $purchaseChartData = DB::table('purchases')
            ->selectRaw('DATE(created_at) as date, SUM(final_total) as purchase_amount')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($locationFilter, fn($q) => $q->whereIn('location_id', $locationFilter))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();

        $purchaseDates = $purchaseChartData->pluck('date');
        $purchaseAmounts = $purchaseChartData->pluck('purchase_amount');

        $purchaseDates = $purchaseChartData->pluck('date');
        $purchaseAmounts = $purchaseChartData->pluck('purchase_amount');

        // Optimized: Top selling products with index hints
        $topProducts = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('products', 'sales_products.product_id', '=', 'products.id')
            ->selectRaw('
                products.id,
                products.product_name,
                products.sku,
                SUM(sales_products.quantity) as quantity_sold,
                SUM(sales_products.quantity * sales_products.price) as total_sales
            ')
            ->whereBetween('sales.sales_date', [$startDate, $endDate])
            ->where($salesFilter)
            ->when($locationFilter, fn($q) => $q->whereIn('sales.location_id', $locationFilter))
            ->groupBy('products.id', 'products.product_name', 'products.sku')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        $maxSales = $topProducts->max('total_sales') ?: 1;
        $topProducts->transform(function($product) use ($maxSales) {
            $product->sales_percentage = ($product->total_sales / $maxSales) * 100;
            return $product;
        });

        $topProducts->transform(function($product) use ($maxSales) {
            $product->sales_percentage = ($product->total_sales / $maxSales) * 100;
            return $product;
        });

        // Optimized: Low stock products
        $lowStockProducts = DB::table('products')
            ->leftJoin('batches', 'products.id', '=', 'batches.product_id')
            ->leftJoin('location_batches', 'batches.id', '=', 'location_batches.batch_id')
            ->selectRaw('
                products.id,
                products.product_name,
                products.sku,
                products.alert_quantity,
                COALESCE(SUM(location_batches.qty), 0) as current_stock
            ')
            ->whereNotNull('products.alert_quantity')
            ->when($locationFilter, fn($q) => $q->whereIn('location_batches.location_id', $locationFilter))
            ->groupBy('products.id', 'products.product_name', 'products.sku', 'products.alert_quantity')
            ->havingRaw('current_stock <= products.alert_quantity')
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        $lowStockProducts = DB::table('products')
            ->leftJoin('batches', 'products.id', '=', 'batches.product_id')
            ->leftJoin('location_batches', 'batches.id', '=', 'location_batches.batch_id')
            ->selectRaw('
                products.id,
                products.product_name,
                products.sku,
                products.alert_quantity,
                COALESCE(SUM(location_batches.qty), 0) as current_stock
            ')
            ->whereNotNull('products.alert_quantity')
            ->when($locationFilter, fn($q) => $q->whereIn('location_batches.location_id', $locationFilter))
            ->groupBy('products.id', 'products.product_name', 'products.sku', 'products.alert_quantity')
            ->havingRaw('current_stock <= products.alert_quantity')
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        // Optimized: Recent sales with subquery
        $recentSales = DB::table('sales')
            ->leftJoin(DB::raw('(
                SELECT sale_id, product_id,
                       ROW_NUMBER() OVER (PARTITION BY sale_id ORDER BY id) as rn
                FROM sales_products
            ) sp'), function($join) {
                $join->on('sales.id', '=', 'sp.sale_id')
                     ->where('sp.rn', '=', 1);
            })
            ->leftJoin('products', 'sp.product_id', '=', 'products.id')
            ->leftJoin('main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->selectRaw('
                sales.id,
                sales.invoice_no,
                sales.final_total,
                sales.status,
                sales.sales_date,
                sales.created_at,
                products.product_name,
                main_categories.mainCategoryName as category
            ')
            ->whereBetween('sales.sales_date', [$startDate, $endDate])
            ->where($salesFilter)
            ->when($locationFilter, fn($q) => $q->whereIn('sales.location_id', $locationFilter))
            ->orderByDesc('sales.created_at')
            ->limit(10)
            ->get();

        $recentSales = DB::table('sales')
            ->leftJoin(DB::raw('(
                SELECT sale_id, product_id,
                       ROW_NUMBER() OVER (PARTITION BY sale_id ORDER BY id) as rn
                FROM sales_products
            ) sp'), function($join) {
                $join->on('sales.id', '=', 'sp.sale_id')
                     ->where('sp.rn', '=', 1);
            })
            ->leftJoin('products', 'sp.product_id', '=', 'products.id')
            ->leftJoin('main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->selectRaw('
                sales.id,
                sales.invoice_no,
                sales.final_total,
                sales.status,
                sales.sales_date,
                sales.created_at,
                products.product_name,
                main_categories.mainCategoryName as category
            ')
            ->whereBetween('sales.sales_date', [$startDate, $endDate])
            ->where($salesFilter)
            ->when($locationFilter, fn($q) => $q->whereIn('sales.location_id', $locationFilter))
            ->orderByDesc('sales.created_at')
            ->limit(10)
            ->get();

        // Optimized: Products with stock but no sales
        $stockSubquery = DB::table('batches')
            ->join('location_batches', 'batches.id', '=', 'location_batches.batch_id')
            ->selectRaw('
                batches.product_id,
                SUM(location_batches.qty) as total_stock
            ')
            ->when($locationFilter, fn($q) => $q->whereIn('location_batches.location_id', $locationFilter))
            ->groupBy('batches.product_id')
            ->havingRaw('total_stock > 0');

        $noSalesProducts = DB::table('products')
            ->joinSub($stockSubquery, 'stock', 'products.id', '=', 'stock.product_id')
            ->leftJoin('sales_products', 'products.id', '=', 'sales_products.product_id')
            ->leftJoin('main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->selectRaw('
                products.id,
                products.product_name,
                products.sku,
                products.retail_price,
                products.original_price,
                products.created_at,
                main_categories.mainCategoryName as category,
                brands.name as brand,
                stock.total_stock as current_stock,
                (stock.total_stock * products.original_price) as stock_value,
                (SELECT COUNT(*) FROM purchase_products WHERE product_id = products.id) as purchase_count
            ')
            ->whereNull('sales_products.id')
            ->orderByDesc('stock.total_stock')
            ->limit(50)
            ->get();

        $noSalesProducts = DB::table('products')
            ->joinSub($stockSubquery, 'stock', 'products.id', '=', 'stock.product_id')
            ->leftJoin('sales_products', 'products.id', '=', 'sales_products.product_id')
            ->leftJoin('main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->selectRaw('
                products.id,
                products.product_name,
                products.sku,
                products.retail_price,
                products.original_price,
                products.created_at,
                main_categories.mainCategoryName as category,
                brands.name as brand,
                stock.total_stock as current_stock,
                (stock.total_stock * products.original_price) as stock_value,
                (SELECT COUNT(*) FROM purchase_products WHERE product_id = products.id) as purchase_count
            ')
            ->whereNull('sales_products.id')
            ->orderByDesc('stock.total_stock')
            ->limit(50)
            ->get();

        return response()->json([
            'totalSales' => $salesMetrics->total_sales ?? 0,
            'totalPurchases' => $purchaseMetrics->total_purchases ?? 0,
            'totalSalesReturn' => $returnMetrics->total_sales_return ?? 0,
            'totalPurchaseReturn' => $returnMetrics->total_purchase_return ?? 0,
            'totalSalesDue' => $salesMetrics->total_sales_due ?? 0,
            'totalPurchasesDue' => $purchaseMetrics->total_purchases_due ?? 0,
            'totalPurchaseReturnDue' => $returnMetrics->total_purchase_return_due ?? 0,
            'totalSalesReturnDue' => $returnMetrics->total_sales_return_due ?? 0,
            'stockTransfer' => $stockTransfer ?? 0,
            'totalProducts' => $totalProducts,
            'salesDates' => $salesDates,
            'salesAmounts' => $salesAmounts,
            'purchaseDates' => $purchaseDates,
            'purchaseAmounts' => $purchaseAmounts,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts,
            'recentSales' => $recentSales,
            'noSalesProducts' => $noSalesProducts,
        ]);
    }

    // Clear cache when sales, purchases, or products are updated
    public function clearDashboardCache()
    {
        $userId = auth()->id();
        $pattern = "dashboard_data_{$userId}_*";

        // Clear all dashboard cache for this user
        Cache::flush(); // Or use more specific cache clearing based on your cache driver

        return response()->json(['message' => 'Dashboard cache cleared successfully']);
    }
}
