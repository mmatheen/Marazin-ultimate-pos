<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\SalesRep;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function index() {
        return response()->json(Route::with('salesRep')->get());
    }

    public function store(Request $request) {
        $request->validate([
            'city' => 'required',
            'district' => 'required',
            'province' => 'required',
            'sales_rep_id' => 'required|exists:sales_reps,id'
        ]);
        return response()->json(Route::create($request->all()), 201);
    }

    public function show($id) {
        return response()->json(Route::findOrFail($id));
    }

    public function update(Request $request, $id) {
        $route = Route::findOrFail($id);
        $route->update($request->all());
        return response()->json($route);
    }

    public function destroy($id) {
        Route::destroy($id);
        return response()->json(null, 204);
    }
}
