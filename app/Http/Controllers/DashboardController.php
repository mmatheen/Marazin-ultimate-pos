<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller


{

   
    public function index()
    {
        return view('discounts.index');
    }

    public function getDashboardData(Request $request)
    {
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        
        // Get user's accessible locations
        $user = auth()->user();
        $userLocations = ($user->role === 'Super Admin' || !$user->locations || $user->locations->isEmpty()) ? null : $user->locations->pluck('id');
    
        // Apply location filter for sales
        $salesQuery = DB::table('sales')
            ->whereBetween('sales_date', [$startDate, $endDate]);
        if ($userLocations) {
            $salesQuery->whereIn('location_id', $userLocations);
        }
        
        $totalSales = $salesQuery->sum('final_total');
        $totalSalesDue = $salesQuery->sum('total_due');
        
        // Apply location filter for purchases
        $purchasesQuery = DB::table('purchases')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($userLocations) {
            $purchasesQuery->whereIn('location_id', $userLocations);
        }
        
        $totalPurchases = $purchasesQuery->sum('final_total');
        $totalPurchasesDue = $purchasesQuery->sum('total_due');
        
        // Apply location filter for purchase returns
        $purchaseReturnQuery = DB::table('purchase_returns')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($userLocations) {
            $purchaseReturnQuery->whereIn('location_id', $userLocations);
        }
        
        $totalPurchaseReturn = $purchaseReturnQuery->sum('return_total');
        $totalPurchaseReturnDue = $purchaseReturnQuery->sum('total_due');
        
        // Apply location filter for sales returns
        $salesReturnQuery = DB::table('sales_returns')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($userLocations) {
            $salesReturnQuery->whereIn('location_id', $userLocations);
        }
        
        $totalSalesReturn = $salesReturnQuery->sum('return_total');
        $totalSalesReturnDue = $salesReturnQuery->sum('total_due');
        
        // Apply location filter for stock transfers
        $stockTransferQuery = DB::table('stock_transfers')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($userLocations) {
            $stockTransferQuery->whereIn('from_location_id', $userLocations);
        }
        
        $stockTransfer = $stockTransferQuery->sum('final_total');
        
        // Apply location filter for products count
        $productsQuery = DB::table('products');
        
        $totalProducts = $productsQuery->count();
    
        // Fetch sales dates and amounts with location filter
        $salesDataQuery = DB::table('sales')
            ->select(DB::raw('DATE(sales_date) as date'), DB::raw('SUM(final_total) as amount'))
            ->whereBetween('sales_date', [$startDate, $endDate]);
        if ($userLocations) {
            $salesDataQuery->whereIn('location_id', $userLocations);
        }
        
        $salesData = $salesDataQuery->groupBy(DB::raw('DATE(sales_date)'))->get();
        $salesDates = $salesData->pluck('date');
        $salesAmounts = $salesData->pluck('amount');
    
        // Fetch purchase dates and amounts with location filter
        $purchaseDataQuery = DB::table('purchases')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(final_total) as amount'))
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($userLocations) {
            $purchaseDataQuery->whereIn('location_id', $userLocations);
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