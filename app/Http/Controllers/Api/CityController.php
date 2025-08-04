<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CityController extends Controller
{
    /**
     * Display a listing of cities.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $cities = City::select('id', 'name', 'district', 'province', 'created_at', 'updated_at')->get();

        if ($cities->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No cities found.',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Cities retrieved successfully.',
            'data' => $cities,
        ], 200);
    }

    /**
     * Store a newly created city.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:cities,name',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
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
            $city = City::create($validator->validated());

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'City created successfully.',
                'data' => $city,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to create city.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified city.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $city = City::with(['routes:id,name'])
            ->select('id', 'name', 'district', 'province', 'created_at', 'updated_at')
            ->find($id);

        if (!$city) {
            return response()->json([
                'status' => false,
                'message' => 'City not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'City retrieved successfully.',
            'data' => $city,
        ], 200);
    }

    /**
     * Update the specified city.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $city = City::find($id);

        if (!$city) {
            return response()->json([
                'status' => false,
                'message' => 'City not found.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:cities,name,' . $id,
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
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
            $city->update($validator->validated());

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'City updated successfully.',
                'data' => $city->refresh(),
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to update city.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified city from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $city = City::find($id);

        if (!$city) {
            return response()->json([
                'status' => false,
                'message' => 'City not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Clean up relationships
            $city->routes()->detach();
            $city->routeCities()->delete();

            $city->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'City deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete city.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
