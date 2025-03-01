<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDashboardData()
    {
        $totalSales = DB::table('sales')->sum('final_total');
        $totalSalesDue = DB::table('sales')->sum('total_due');
        $totalPurchases = DB::table('purchases')->sum('final_total');
        $totalPurchasesDue = DB::table('purchases')->sum('total_due');
        $totalPurchaseReturn = DB::table('purchase_returns')->sum('return_total');
        $totalPurchaseReturnDue = DB::table('purchase_returns')->sum('total_due');
        $totalSalesReturn = DB::table('sales_returns')->sum('return_total');
        $totalSalesReturnDue= DB::table('sales_returns')->sum('total_due');
        $stockTransfer = DB::table('stock_transfers')->sum('final_total');
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
        ]);
    }
}
