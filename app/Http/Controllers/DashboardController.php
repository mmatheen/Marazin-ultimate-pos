<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
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
        $selectedLocationId = $request->query('location_id'); // Get selected location from frontend

        // Get user's accessible locations
        $user = auth()->user();
        $userLocations = ($user->role === 'Super Admin' || !$user->locations || $user->locations->isEmpty()) ? null : $user->locations->pluck('id');

        // Determine which locations to filter by
        $locationFilter = null;
        if ($selectedLocationId) {
            // If a specific location is selected, use only that location (if user has access)
            if ($userLocations === null || $userLocations->contains($selectedLocationId)) {
                $locationFilter = [$selectedLocationId];
            }
        } else {
            // If "All Location" is selected, use user's accessible locations
            $locationFilter = $userLocations ? $userLocations->toArray() : null;
        }

        // Apply location filter for sales - EXCLUDE SALE ORDERS, only count actual final sales
        $salesQuery = DB::table('sales')
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('transaction_type', 'invoice') // Only invoices
                      ->orWhere(function($subQuery) {
                          $subQuery->whereNull('transaction_type') // Legacy sales without transaction_type
                                   ->where('status', 'final'); // Only final status
                      });
            });
        if ($locationFilter) {
            $salesQuery->whereIn('location_id', $locationFilter);
        }

        $totalSales = $salesQuery->sum('final_total');
        $totalSalesDue = $salesQuery->sum('total_due');

        // Apply location filter for purchases
        $purchasesQuery = DB::table('purchases')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($locationFilter) {
            $purchasesQuery->whereIn('location_id', $locationFilter);
        }

        $totalPurchases = $purchasesQuery->sum('final_total');
        $totalPurchasesDue = $purchasesQuery->sum('total_due');

        // Apply location filter for purchase returns
        $purchaseReturnQuery = DB::table('purchase_returns')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($locationFilter) {
            $purchaseReturnQuery->whereIn('location_id', $locationFilter);
        }

        $totalPurchaseReturn = $purchaseReturnQuery->sum('return_total');
        $totalPurchaseReturnDue = $purchaseReturnQuery->sum('total_due');

        // Apply location filter for sales returns
        $salesReturnQuery = DB::table('sales_returns')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($locationFilter) {
            $salesReturnQuery->whereIn('location_id', $locationFilter);
        }

        $totalSalesReturn = $salesReturnQuery->sum('return_total');
        $totalSalesReturnDue = $salesReturnQuery->sum('total_due');

        // Apply location filter for stock transfers
        $stockTransferQuery = DB::table('stock_transfers')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($locationFilter) {
            $stockTransferQuery->whereIn('from_location_id', $locationFilter);
        }

        $stockTransfer = $stockTransferQuery->sum('final_total');

        // Apply location filter for products count
        $productsQuery = DB::table('products');

        $totalProducts = $productsQuery->count();

        // Fetch sales dates and amounts with location filter - EXCLUDE SALE ORDERS
        $salesDataQuery = DB::table('sales')
            ->select(DB::raw('DATE(sales_date) as date'), DB::raw('SUM(final_total) as amount'))
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('transaction_type', 'invoice') // Only invoices
                      ->orWhere(function($subQuery) {
                          $subQuery->whereNull('transaction_type') // Legacy sales without transaction_type
                                   ->where('status', 'final'); // Only final status
                      });
            });
        if ($locationFilter) {
            $salesDataQuery->whereIn('location_id', $locationFilter);
        }

        $salesData = $salesDataQuery->groupBy(DB::raw('DATE(sales_date)'))->get();
        $salesDates = $salesData->pluck('date');
        $salesAmounts = $salesData->pluck('amount');

        // Fetch purchase dates and amounts with location filter
        $purchaseDataQuery = DB::table('purchases')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(final_total) as amount'))
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($locationFilter) {
            $purchaseDataQuery->whereIn('location_id', $locationFilter);
        }

        $purchaseData = $purchaseDataQuery->groupBy(DB::raw('DATE(created_at)'))->get();
        $purchaseDates = $purchaseData->pluck('date');
        $purchaseAmounts = $purchaseData->pluck('amount');

        // Get top selling products
        $topProductsQuery = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('products', 'sales_products.product_id', '=', 'products.id')
            ->select(
                'products.product_name',
                'products.sku',
                DB::raw('SUM(sales_products.quantity) as quantity_sold'),
                DB::raw('SUM(sales_products.quantity * sales_products.price) as total_sales')
            )
            ->whereBetween('sales.sales_date', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('sales.transaction_type', 'invoice')
                      ->orWhere(function($subQuery) {
                          $subQuery->whereNull('sales.transaction_type')
                                   ->where('sales.status', 'final');
                      });
            });

        if ($locationFilter) {
            $topProductsQuery->whereIn('sales.location_id', $locationFilter);
        }

        $topProducts = $topProductsQuery
            ->groupBy('products.id', 'products.product_name', 'products.sku')
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();

        // Calculate sales percentage for each product
        $maxSales = $topProducts->max('total_sales');
        $topProducts = $topProducts->map(function($product) use ($maxSales) {
            $product->sales_percentage = $maxSales > 0 ? ($product->total_sales / $maxSales) * 100 : 0;
            return $product;
        });

        // Get low stock products
        $lowStockQuery = DB::table('products')
            ->leftJoin('batches', 'products.id', '=', 'batches.product_id')
            ->leftJoin('location_batches', 'batches.id', '=', 'location_batches.batch_id')
            ->select(
                'products.id',
                'products.product_name',
                'products.sku',
                'products.alert_quantity',
                DB::raw('COALESCE(SUM(location_batches.qty), 0) as current_stock')
            )
            ->whereNotNull('products.alert_quantity')
            ->groupBy('products.id', 'products.product_name', 'products.sku', 'products.alert_quantity');

        if ($locationFilter) {
            $lowStockQuery->whereIn('location_batches.location_id', $locationFilter);
        }

        $lowStockProducts = $lowStockQuery
            ->havingRaw('COALESCE(SUM(location_batches.qty), 0) <= products.alert_quantity')
            ->orderBy('current_stock', 'asc')
            ->limit(10)
            ->get();

        // Get recent sales
        $recentSalesQuery = DB::table('sales')
            ->leftJoin('sales_products', function($join) {
                $join->on('sales.id', '=', 'sales_products.sale_id')
                     ->whereRaw('sales_products.id = (SELECT id FROM sales_products WHERE sale_id = sales.id LIMIT 1)');
            })
            ->leftJoin('products', 'sales_products.product_id', '=', 'products.id')
            ->leftJoin('main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->select(
                'sales.id',
                'sales.invoice_no',
                'sales.final_total',
                'sales.status',
                'sales.sales_date',
                'sales.created_at',
                'products.product_name',
                'main_categories.mainCategoryName as category'
            )
            ->whereBetween('sales.sales_date', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('sales.transaction_type', 'invoice')
                      ->orWhere(function($subQuery) {
                          $subQuery->whereNull('sales.transaction_type')
                                   ->where('sales.status', 'final');
                      });
            });

        if ($locationFilter) {
            $recentSalesQuery->whereIn('sales.location_id', $locationFilter);
        }

        $recentSales = $recentSalesQuery
            ->orderBy('sales.created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'totalSales' => $totalSales,
            'totalPurchases' => $totalPurchases,
            'totalSalesReturn' => $totalSalesReturn,
            'totalPurchaseReturn' => $totalPurchaseReturn,
            'totalSalesDue' => $totalSalesDue,
            'totalPurchasesDue' => $totalPurchasesDue,
            'totalPurchaseReturnDue' => $totalPurchaseReturnDue,
            'totalSalesReturnDue' => $totalSalesReturnDue,
            'stockTransfer' => $stockTransfer,
            'totalProducts' => $totalProducts,
            'salesDates' => $salesDates,
            'salesAmounts' => $salesAmounts,
            'purchaseDates' => $purchaseDates,
            'purchaseAmounts' => $purchaseAmounts,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts,
            'recentSales' => $recentSales,
        ]);
    }
}
