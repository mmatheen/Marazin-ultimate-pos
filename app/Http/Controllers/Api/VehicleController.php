<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $vehicles = Vehicle::select('id', 'vehicle_number', 'vehicle_type', 'description', 'created_at', 'updated_at')->get();

        if ($vehicles->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No vehicles found.',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Vehicles retrieved successfully.',
            'data' => $vehicles,
        ], 200);
    }

  
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_number' => 'required|string|max:50|unique:vehicles,vehicle_number',
            'vehicle_type' => 'required|in:bike,van,other',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $vehicle = Vehicle::create($validator->validated());

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle created successfully.',
                'data' => $vehicle,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to create vehicle.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($id)
    {
        $vehicle = Vehicle::select('id', 'vehicle_number', 'vehicle_type', 'description', 'created_at', 'updated_at')
            ->find($id);

        if (!$vehicle) {
            return response()->json([
                'status' => false,
                'message' => 'Vehicle not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Vehicle retrieved successfully.',
            'data' => $vehicle,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'status' => false,
                'message' => 'Vehicle not found.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_number' => 'required|string|max:50|unique:vehicles,vehicle_number,' . $id,
            'vehicle_type' => 'required|in:bike,van,other',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $vehicle->update($validator->validated());

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle updated successfully.',
                'data' => $vehicle->refresh(),
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to update vehicle.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified vehicle from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'status' => false,
                'message' => 'Vehicle not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Optional: detach relationships before delete
            $vehicle->salesReps()->detach();
            $vehicle->routes()->detach();

            $vehicle->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete vehicle.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
