<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\City;
use App\Models\RouteCity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RouteCityController extends Controller
{
   
    public function index()
    {
        try {
            $routes = Route::with(['cities:id,name,district,province'])
                ->has('cities') // Only routes with cities
                ->get(['id', 'name', 'created_at', 'updated_at'])
                ->map(function ($route) {
                    return [
                        'id' => $route->id,
                        'route_name' => $route->name,
                        'city_count' => $route->cities->count(),
                        'updated_at' => $route->updated_at,
                        'cities' => $route->cities, // Add this line to include cities in the response
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Routes with cities retrieved successfully.',
                'data' => $routes,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store new route-city assignments.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'route_id' => 'required|exists:routes,id',
            'city_ids' => 'required|array|min:1',
            'city_ids.*' => 'required|exists:cities,id',
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
            $route = Route::findOrFail($request->route_id);
            $cityIds = array_unique($request->city_ids);

            // Sync will add new and remove unselected cities
            $syncResult = $route->cities()->sync($cityIds);

            $added = count($syncResult['attached']);
            $removed = count($syncResult['detached']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "{$added} city(s) assigned, {$removed} city(s) removed from route successfully.",
                'data' => [
                    'route_id' => $route->id,
                    'route_name' => $route->name,
                    'new_assignments' => $added,
                    'removed_assignments' => $removed,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to assign cities.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get cities assigned to a specific route.
     */
    public function getRouteCities($routeId)
    {
        try {
            $route = Route::with('cities:id,name,district,province')
                ->findOrFail($routeId);

            return response()->json([
                'status' => true,
                'message' => 'Route cities retrieved successfully.',
                'data' => [
                    'route_name' => $route->name,
                    'cities' => $route->cities->map(function ($city) {
                        return [
                            'id' => $city->id,
                            'name' => $city->name,
                            'district' => $city->district,
                            'province' => $city->province,
                        ];
                    }),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve route cities.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a specific route with its assigned cities (grouped).
     */
    public function show($id)
    {
        try {
            $route = Route::with(['cities:id,name,district,province'])
                ->find($id);

            if (!$route) {
                return response()->json([
                    'status' => false,
                    'message' => 'Route not found.',
                ], 404);
            }

            $data = [
                'id' => $route->id,
                'route_name' => $route->name,
                'cities' => $route->cities->map(function ($city) {
                    return [
                        'id' => $city->id,
                        'name' => $city->name,
                        'district' => $city->district,
                        'province' => $city->province,
                    ];
                }),
                'city_count' => $route->cities->count(),
                'created_at' => $route->created_at,
                'updated_at' => $route->updated_at,
            ];

            return response()->json([
                'status' => true,
                'message' => 'Routes with cities retrieved successfully.',
                'data' => [$data],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a specific route-city assignment.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'route_id' => 'required|exists:routes,id',
            'city_id' => 'required|exists:cities,id',
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
            $routeCity = RouteCity::find($id);

            if (!$routeCity) {
                return response()->json([
                    'status' => false,
                    'message' => 'Route-city assignment not found.',
                ], 404);
            }

            // Check if the new combination already exists (excluding current record)
            $existing = RouteCity::where('route_id', $request->route_id)
                ->where('city_id', $request->city_id)
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return response()->json([
                    'status' => false,
                    'message' => 'This route-city assignment already exists.',
                ], 422);
            }

            $routeCity->update([
                'route_id' => $request->route_id,
                'city_id' => $request->city_id,
            ]);

            $routeCity->load(['route:id,name', 'city:id,name,district,province']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Route-city assignment updated successfully.',
                'data' => [
                    'id' => $routeCity->id,
                    'route_id' => $routeCity->route_id,
                    'city_id' => $routeCity->city_id,
                    'route_name' => $routeCity->route->name,
                    'city_name' => $routeCity->city->name,
                    'district' => $routeCity->city->district,
                    'province' => $routeCity->city->province,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update assignment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a specific route-city assignment.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $routeCity = RouteCity::with(['route:id,name', 'city:id,name'])
                ->find($id);

            if (!$routeCity) {
                return response()->json([
                    'status' => false,
                    'message' => 'Route-city assignment not found.',
                ], 404);
            }

            $routeName = $routeCity->route->name ?? 'Unknown Route';
            $cityName = $routeCity->city->name ?? 'Unknown City';

            $routeCity->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "City '{$cityName}' removed from route '{$routeName}' successfully.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete assignment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all cities, optionally filtered by province.
     */
    public function getAllCities(Request $request)
    {
        $query = City::query();

        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        $cities = $query->orderBy('name')->get(['id', 'name', 'district', 'province']);

        return response()->json([
            'status' => true,
            'data' => $cities,
        ]);
    }

    /**
     * Get all routes for dropdown.
     */
    public function getAllRoutes()
    {
        try {
            $routes = Route::orderBy('name')->get(['id', 'name']);

            return response()->json([
                'status' => true,
                'data' => $routes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve routes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
