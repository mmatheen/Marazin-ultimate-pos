<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\SalesRep;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\Route;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesPosController extends Controller
{
    // public function stockForSalesRep($userId)
    // {
    //     $rep = SalesRep::where('user_id', $userId)->firstOrFail();
    //     return DB::table('location_batches')
    //         ->join('batches', 'batches.id', '=', 'location_batches.batch_id')
    //         ->join('products', 'products.id', '=', 'batches.product_id')
    //         ->select('products.product_name', 'batches.id as batch_id', 'location_batches.qty', 'batches.unit_cost', 'batches.retail_price')
    //         ->where('location_batches.location_id', $rep->assigned_location_id)
    //         ->get();
    // }

    // public function customersForSalesRep($userId)
    // {
    //     $rep = SalesRep::where('user_id', $userId)->firstOrFail();
    //     $routeIds = Route::where('sales_rep_id', $rep->id)->pluck('id');
    //     return Customer::whereIn('route_id', $routeIds)->get();
    // }

    // public function submitSale(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $sale = Sale::create([...]); // Add sale creation logic
    //         foreach ($request->products as $item) {
    //             SaleProduct::create([...]);
    //             DB::table('location_batches')
    //                 ->where('location_id', $request->location_id)
    //                 ->where('batch_id', $item['batch_id'])
    //                 ->decrement('qty', $item['quantity']);
    //         }
    //         if ($request->total_due > 0 && $request->customer_id != 1) {
    //             Customer::where('id', $request->customer_id)->increment('current_balance', $request->total_due);
    //         }
    //         DB::commit();
    //         return response()->json(['status' => 'success']);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }
}
