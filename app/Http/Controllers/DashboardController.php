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
    
        // Apply location filter for sales
        $salesQuery = DB::table('sales')
            ->whereBetween('sales_date', [$startDate, $endDate]);
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
    
        // Fetch sales dates and amounts with location filter
        $salesDataQuery = DB::table('sales')
            ->select(DB::raw('DATE(sales_date) as date'), DB::raw('SUM(final_total) as amount'))
            ->whereBetween('sales_date', [$startDate, $endDate]);
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
        ]);
    }
}