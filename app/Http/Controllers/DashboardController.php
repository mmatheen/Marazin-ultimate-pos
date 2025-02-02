<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDashboardData()
    {
        $totalSales = DB::table('sales')->sum('amount');
        $totalPurchases = DB::table('purchase')->sum('amount');
        $totalSalesReturn = DB::table('sale_return')->sum('amount');
        $totalPurchaseReturn = DB::table('purchase_return')->sum('amount');
        $stockTransfer = DB::table('stock_transfers')->sum('quantity');
        $stockAdjustment = DB::table('stock_adjustments')->sum('quantity');
        $totalProducts = DB::table('products')->count();
        $expenses = DB::table('expenses')->sum('amount');

        return response()->json([
            'totalSales' => $totalSales,
            'totalPurchases' => $totalPurchases,
            'totalSalesReturn' => $totalSalesReturn,
            'totalPurchaseReturn' => $totalPurchaseReturn,
            'stockTransfer' => $stockTransfer,
            'stockAdjustment' => $stockAdjustment,
            'totalProducts' => $totalProducts,
            'expenses' => $expenses
        ]);
    }
}
