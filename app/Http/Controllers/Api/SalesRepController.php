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

class SalesRepController extends Controller
{
    public function index() {
        return response()->json(SalesRep::with(['user', 'vehicle', 'location'])->get());
    }

    public function store(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'assigned_location_id' => 'required|exists:locations,id'
        ]);
        return response()->json(SalesRep::create($request->all()), 201);
    }

    public function show($id) {
        return response()->json(SalesRep::findOrFail($id));
    }

    public function update(Request $request, $id) {
        $rep = SalesRep::findOrFail($id);
        $rep->update($request->all());
        return response()->json($rep);
    }

    public function destroy($id) {
        SalesRep::destroy($id);
        return response()->json(null, 204);
    }
}

