<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $totalSales = DB::table('sales')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('final_total');
        $totalSalesDue = DB::table('sales')
            ->whereBetween('created_at', [$startDate, $endDate])
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
            'salesDates' => [], // Add logic to fetch sales dates
            'salesAmounts' => [], // Add logic to fetch sales amounts
            'purchaseDates' => [], // Add logic to fetch purchase dates
            'purchaseAmounts' => [], // Add logic to fetch purchase amounts
        ]);
    }
}