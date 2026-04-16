<?php

namespace App\Http\Controllers;

use App\Models\SalesRep;
use App\Models\User;
use App\Services\User\UserAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private UserAccessService $userAccessService;

    public function __construct(UserAccessService $userAccessService)
    {
        $this->userAccessService = $userAccessService;
        $this->middleware('permission:view dashboard', ['only' => ['index', 'getDashboardData']]);
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

        // Convert empty string or 'null' string to actual null for proper handling
        if (empty($selectedLocationId) || $selectedLocationId === 'null' || $selectedLocationId === 'undefined') {
            $selectedLocationId = null;
        }

        // Temporarily disable cache to debug the issue
        return $this->fetchDashboardData($startDate, $endDate, $selectedLocationId);
    }

    private function fetchDashboardData($startDate, $endDate, $selectedLocationId)
    {
        /** @var User $user */
        $user = auth()->user();
        $user->loadMissing(['roles', 'locations']);

        $permittedLocationIds = $this->resolvePermittedLocationIds($user);
        $seesAllLocations = $permittedLocationIds === null;
        $restrictSalesToRepUserId = $user->isSalesRep() ? $user->id : null;

        // Determine which locations to filter by (aligned with LocationController::index)
        $locationFilter = null;

        if ($selectedLocationId) {
            if ($seesAllLocations) {
                $locationFilter = [$selectedLocationId];
            } else {
                if (in_array((int) $selectedLocationId, array_map('intval', $permittedLocationIds), true)) {
                    $locationFilter = [$selectedLocationId];
                } else {
                    $locationFilter = [];
                }
            }
        } else {
            if ($seesAllLocations) {
                $locationFilter = null;
            } else {
                $locationFilter = $permittedLocationIds;
            }
        }

        // Sales filter condition - only include finalized invoices, exclude draft/quotation/sale_order
        $salesFilter = function($query) {
            $query->where(function($q) {
                $q->where('transaction_type', 'invoice')
                  ->orWhere(function($subQuery) {
                      $subQuery->whereNull('transaction_type')->where('status', 'final');
                  });
            })
            // Explicitly exclude draft, quotation, and sale_order transactions
            ->where('status', 'final')
            ->whereNotIn('status', ['draft', 'quotation', 'suspend', 'jobticket']);
        };

        // Optimized: Get sales metrics with proper filtering and indexes
        $salesQuery = DB::table('sales')
            ->selectRaw('COALESCE(SUM(final_total), 0) as total_sales, COALESCE(SUM(total_due), 0) as total_sales_due')
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->where($salesFilter);

        if ($locationFilter !== null) {
            if (empty($locationFilter)) {
                // No accessible locations - return zero
                $salesQuery->whereRaw('1 = 0');
            } else {
                $salesQuery->whereIn('location_id', $locationFilter);
            }
        }

        if ($restrictSalesToRepUserId !== null) {
            $salesQuery->where('user_id', $restrictSalesToRepUserId);
        }

        $salesMetrics = $salesQuery->first();

        $purchaseQuery = DB::table('purchases')
            ->selectRaw('COALESCE(SUM(final_total), 0) as total_purchases, COALESCE(SUM(total_due), 0) as total_purchases_due')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($locationFilter !== null) {
            if (empty($locationFilter)) {
                $purchaseQuery->whereRaw('1 = 0');
            } else {
                $purchaseQuery->whereIn('location_id', $locationFilter);
            }
        }

        $purchaseMetrics = $purchaseQuery->first();

        // Optimize return metrics with separate queries for better performance
        $purchaseReturnQuery = DB::table('purchase_returns')
            ->selectRaw('COALESCE(SUM(return_total), 0) as total, COALESCE(SUM(total_due), 0) as due')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $salesReturnQuery = DB::table('sales_returns')
            ->selectRaw('COALESCE(SUM(return_total), 0) as total, COALESCE(SUM(total_due), 0) as due')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($locationFilter !== null) {
            if (empty($locationFilter)) {
                $purchaseReturnQuery->whereRaw('1 = 0');
                $salesReturnQuery->whereRaw('1 = 0');
            } else {
                $purchaseReturnQuery->whereIn('location_id', $locationFilter);
                $salesReturnQuery->whereIn('location_id', $locationFilter);
            }
        }

        if ($restrictSalesToRepUserId !== null) {
            $repId = $restrictSalesToRepUserId;
            $salesReturnQuery->where(function ($q) use ($repId) {
                $q->whereIn('sale_id', function ($sub) use ($repId) {
                    $sub->select('id')->from('sales')->where('user_id', $repId);
                })->orWhere('sales_returns.user_id', $repId);
            });
        }

        $purchaseReturnData = $purchaseReturnQuery->first();
        $salesReturnData = $salesReturnQuery->first();

        $returnMetrics = (object) [
            'total_purchase_return' => $purchaseReturnData->total ?? 0,
            'total_purchase_return_due' => $purchaseReturnData->due ?? 0,
            'total_sales_return' => $salesReturnData->total ?? 0,
            'total_sales_return_due' => $salesReturnData->due ?? 0
        ];

        $stockTransferQuery = DB::table('stock_transfers')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($locationFilter !== null) {
            if (empty($locationFilter)) {
                $stockTransferQuery->whereRaw('1 = 0');
            } else {
                $stockTransferQuery->whereIn('from_location_id', $locationFilter);
            }
        }

        $stockTransfer = $stockTransferQuery->sum('final_total') ?? 0;

        $totalProducts = DB::table('products')->count('id');

        // Optimized: Get chart data
        $salesChartQuery = DB::table('sales')
            ->selectRaw('DATE(sales_date) as date, SUM(final_total) as sales_amount')
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->where($salesFilter);

        if ($locationFilter !== null) {
            if (!empty($locationFilter)) {
                $salesChartQuery->whereIn('location_id', $locationFilter);
            } else {
                $salesChartQuery->whereRaw('1 = 0');
            }
        }

        if ($restrictSalesToRepUserId !== null) {
            $salesChartQuery->where('user_id', $restrictSalesToRepUserId);
        }

        $chartData = $salesChartQuery->groupBy(DB::raw('DATE(sales_date)'))->get();
        $salesDates = $chartData->pluck('date');
        $salesAmounts = $chartData->pluck('sales_amount');

        $purchaseChartQuery = DB::table('purchases')
            ->selectRaw('DATE(created_at) as date, SUM(final_total) as purchase_amount')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($locationFilter !== null) {
            if (!empty($locationFilter)) {
                $purchaseChartQuery->whereIn('location_id', $locationFilter);
            } else {
                $purchaseChartQuery->whereRaw('1 = 0');
            }
        }

        $purchaseChartData = $purchaseChartQuery->groupBy(DB::raw('DATE(created_at)'))->get();
        $purchaseDates = $purchaseChartData->pluck('date');
        $purchaseAmounts = $purchaseChartData->pluck('purchase_amount');

        // Optimized: Top selling products - reduced to 5 for speed
        $topProductsQuery = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('products', 'sales_products.product_id', '=', 'products.id')
            ->selectRaw('products.id, products.product_name, products.sku, SUM(sales_products.quantity) as quantity_sold, ROUND(SUM(sales_products.quantity * sales_products.price), 2) as total_sales')
            ->whereBetween('sales.sales_date', [$startDate, $endDate])
            ->where($salesFilter);

        if ($locationFilter !== null) {
            if (!empty($locationFilter)) {
                $topProductsQuery->whereIn('sales.location_id', $locationFilter);
            } else {
                $topProductsQuery->whereRaw('1 = 0');
            }
        }

        $topProducts = $topProductsQuery
            ->groupBy('products.id', 'products.product_name', 'products.sku')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        $maxSales = $topProducts->max('total_sales') ?: 1;
        $topProducts->transform(function($product) use ($maxSales) {
            $product->sales_percentage = ($product->total_sales / $maxSales) * 100;
            return $product;
        });

        // Optimized: Low stock products - reduced to 5 for speed
        $lowStockQuery = DB::table('products')
            ->leftJoin('batches', 'products.id', '=', 'batches.product_id')
            ->leftJoin('location_batches', 'batches.id', '=', 'location_batches.batch_id')
            ->selectRaw('products.id, products.product_name, products.sku, products.alert_quantity, COALESCE(SUM(location_batches.qty), 0) as current_stock')
            ->whereNotNull('products.alert_quantity');

        if ($locationFilter !== null && !empty($locationFilter)) {
            $lowStockQuery->whereIn('location_batches.location_id', $locationFilter);
        }

        $lowStockProducts = $lowStockQuery
            ->groupBy('products.id', 'products.product_name', 'products.sku', 'products.alert_quantity')
            ->havingRaw('current_stock <= products.alert_quantity')
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        // Simplified: Recent sales without complex window functions - reduced to 5
        $recentSalesQuery = DB::table('sales')
            ->selectRaw('sales.id, sales.invoice_no, sales.final_total, sales.status, sales.sales_date, sales.created_at')
            ->whereBetween('sales.sales_date', [$startDate, $endDate])
            ->where($salesFilter);

        if ($locationFilter !== null) {
            if (!empty($locationFilter)) {
                $recentSalesQuery->whereIn('sales.location_id', $locationFilter);
            } else {
                $recentSalesQuery->whereRaw('1 = 0');
            }
        }

        if ($restrictSalesToRepUserId !== null) {
            $recentSalesQuery->where('sales.user_id', $restrictSalesToRepUserId);
        }

        $recentSales = $recentSalesQuery
            ->orderByDesc('sales.created_at')
            ->limit(5)
            ->get();

        // Simplified: Products with stock but no sales - optimized and reduced to 10
        $stockSubqueryBuilder = DB::table('batches')
            ->join('location_batches', 'batches.id', '=', 'location_batches.batch_id')
            ->selectRaw('batches.product_id, SUM(location_batches.qty) as total_stock');

        if ($locationFilter !== null && !empty($locationFilter)) {
            $stockSubqueryBuilder->whereIn('location_batches.location_id', $locationFilter);
        }

        $stockSubquery = $stockSubqueryBuilder->groupBy('batches.product_id')->havingRaw('total_stock > 0');

        $noSalesProducts = DB::table('products')
            ->joinSub($stockSubquery, 'stock', 'products.id', '=', 'stock.product_id')
            ->leftJoin('sales_products', 'products.id', '=', 'sales_products.product_id')
            ->leftJoin('main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->selectRaw('products.id, products.product_name, products.sku, products.retail_price, products.original_price, products.created_at, main_categories.mainCategoryName as category, brands.name as brand, stock.total_stock as current_stock, ROUND(stock.total_stock * products.original_price, 2) as stock_value')
            ->whereNull('sales_products.id')
            ->orderByDesc('stock.total_stock')
            ->limit(10)
            ->get();

        $totalSaleOrdersValue = 0;
        $totalSaleOrdersCount = 0;

        if ($user->isSalesRep()) {
            $startDateOnly = substr((string) $startDate, 0, 10);
            $endDateOnly = substr((string) $endDate, 0, 10);

            $saleOrdersQuery = DB::table('sales')
                ->selectRaw('COALESCE(SUM(final_total), 0) as total_value, COUNT(*) as order_count')
                ->where('transaction_type', 'sale_order')
                ->where(function ($q) {
                    $q->whereNull('order_status')->orWhere('order_status', '!=', 'cancelled');
                })
                ->where(function ($q) use ($startDate, $endDate, $startDateOnly, $endDateOnly) {
                    $q->whereBetween('order_date', [$startDateOnly, $endDateOnly])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->whereNull('order_date')->whereBetween('sales_date', [$startDate, $endDate]);
                        });
                })
                ->where('user_id', $user->id);

            if ($locationFilter !== null) {
                if (empty($locationFilter)) {
                    $saleOrdersQuery->whereRaw('1 = 0');
                } else {
                    $saleOrdersQuery->whereIn('location_id', $locationFilter);
                }
            }

            $saleOrdersRow = $saleOrdersQuery->first();
            $totalSaleOrdersValue = $saleOrdersRow->total_value ?? 0;
            $totalSaleOrdersCount = (int) ($saleOrdersRow->order_count ?? 0);
        }

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
            'totalSaleOrdersValue' => $totalSaleOrdersValue,
            'totalSaleOrdersCount' => $totalSaleOrdersCount,
        ]);
    }

    /**
     * null = all locations; [] = none; non-empty array = restrict to these IDs.
     */
    private function resolvePermittedLocationIds(User $user): ?array
    {
        if ($this->isDashboardMasterSuperAdmin($user)) {
            return null;
        }
        if ($this->hasDashboardLocationBypass($user)) {
            return null;
        }
        if ($user->isSalesRep()) {
            return $this->getSalesRepPermittedLocationIds($user);
        }

        return $user->locations->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    private function isDashboardMasterSuperAdmin(User $user): bool
    {
        return $this->userAccessService->isMasterSuperAdmin($user);
    }

    private function hasDashboardLocationBypass(User $user): bool
    {
        return $this->userAccessService->hasLocationBypassPermission($user);
    }

    private function getSalesRepPermittedLocationIds(User $user): array
    {
        $assignments = SalesRep::where('user_id', $user->id)
            ->where('status', SalesRep::STATUS_ACTIVE)
            ->with(['subLocation'])
            ->get();

        if ($assignments->isEmpty()) {
            return [];
        }

        $ids = [];
        foreach ($assignments as $assignment) {
            $sub = $assignment->subLocation;
            if ($sub) {
                $ids[] = (int) $sub->id;
                if ($sub->parent_id) {
                    $ids[] = (int) $sub->parent_id;
                }
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    // Clear cache when sales, purchases, or products are updated
    public function clearDashboardCache()
    {
        // Clear all dashboard cache for this user
        Cache::flush(); // Or use more specific cache clearing based on your cache driver

        return response()->json(['message' => 'Dashboard cache cleared successfully']);
    }
}
