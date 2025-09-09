<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class VehicleLocationController extends Controller
{
    /**
     * Display a listing of vehicle locations.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $records = VehicleLocation::with(['vehicle', 'location'])
            ->select('id', 'vehicle_id', 'location_id', 'created_at', 'updated_at')
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No vehicle locations found.',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Vehicle locations retrieved successfully.',
            'data' => $records,
        ], 200);
    }

    /**
     * Store a newly created vehicle location.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'location_id' => 'required|exists:locations,id',
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
            $record = VehicleLocation::create($validator->validated());

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle location assigned successfully.',
                'data' => $record,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to assign vehicle location.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified vehicle location.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $record = VehicleLocation::with(['vehicle', 'location'])
            ->select('id', 'vehicle_id', 'location_id', 'created_at', 'updated_at')
            ->find($id);

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Vehicle location not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Vehicle location retrieved successfully.',
            'data' => $record,
        ], 200);
    }

    /**
     * Update the specified vehicle location.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $record = VehicleLocation::find($id);

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Vehicle location not found.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'location_id' => 'required|exists:locations,id',
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
            $record->update($validator->validated());

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle location updated successfully.',
                'data' => $record->refresh(),
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to update vehicle location.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified vehicle location.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $record = VehicleLocation::find($id);

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Vehicle location not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $record->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle location removed successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to remove vehicle location.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}