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
    
        $totalSales = DB::table('sales')
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->sum('final_total');
        $totalSalesDue = DB::table('sales')
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->sum('total_due');
        $totalPurchases = DB::table('purchases')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('final_total');
        $totalPurchasesDue = DB::table('purchases')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_due');
        $totalPurchaseReturn = DB::table('purchase_returns')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('return_total');
        $totalPurchaseReturnDue = DB::table('purchase_returns')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_due');
        $totalSalesReturn = DB::table('sales_returns')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('return_total');
        $totalSalesReturnDue = DB::table('sales_returns')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_due');
        $stockTransfer = DB::table('stock_transfers')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('final_total');
        $totalProducts = DB::table('products')->count();
    
        // Fetch sales dates and amounts
        $salesData = DB::table('sales')
            ->select(DB::raw('DATE(sales_date) as date'), DB::raw('SUM(final_total) as amount'))
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(sales_date)'))
            ->get();
    
        $salesDates = $salesData->pluck('date');
        $salesAmounts = $salesData->pluck('amount');
    
        // Fetch purchase dates and amounts
        $purchaseData = DB::table('purchases')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(final_total) as amount'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();
    
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