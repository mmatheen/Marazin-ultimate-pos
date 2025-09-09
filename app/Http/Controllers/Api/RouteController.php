<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    /**
     * Display a listing of routes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $routes = Route::all();

            if ($routes->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No routes found.',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Routes retrieved successfully.',
                'data' => $routes,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve routes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:routes,name',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:active,inactive',
            'city_ids' => 'nullable|array',
            'city_ids.*' => 'exists:cities,id',
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
            $route = Route::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 'active',
            ]);

            // Attach cities if provided
            if ($request->has('city_ids') && is_array($request->city_ids)) {
                $route->cities()->attach($request->city_ids);
            }

            DB::commit();

            $route->load(['cities:id,name,district,province']);

            return response()->json([
                'status' => true,
                'message' => 'Route created successfully.',
                'data' => [
                    'id' => $route->id,
                    'name' => $route->name,
                    'description' => $route->description,
                    'status' => $route->status,
                    'cities' => $route->cities,
                    'created_at' => $route->created_at,
                    'updated_at' => $route->updated_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create route.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $route = Route::with([
                'cities:id,name,district,province',
                'salesReps:id,user_id,route_id,status,assigned_date',
                'salesReps.user:id,user_name,email',
                'salesReps.vehicleLocation:id,vehicle_id,location_id',
                'salesReps.vehicleLocation.vehicle:id,vehicle_number',
                'salesReps.vehicleLocation.location:id,name'
            ])->find($id);

            if (!$route) {
                return response()->json([
                    'status' => false,
                    'message' => 'Route not found.',
                    'data' => null,
                ], 404);
            }

            $data = [
                'id' => $route->id,
                'name' => $route->name,
                'description' => $route->description,
                'status' => $route->status,
                'cities' => $route->cities,
                'sales_reps' => $route->salesReps->map(function ($salesRep) {
                    return [
                        'id' => $salesRep->id,
                        'user_id' => $salesRep->user_id,
                        'user' => $salesRep->user,
                        'vehicle' => $salesRep->vehicleLocation->vehicle ?? null,
                        'location' => $salesRep->vehicleLocation->location ?? null,
                        'assigned_date' => $salesRep->assigned_date,
                        'status' => $salesRep->status,
                    ];
                }),
                'created_at' => $route->created_at,
                'updated_at' => $route->updated_at,
            ];

            return response()->json([
                'status' => true,
                'message' => 'Route retrieved successfully.',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve route.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json([
                'status' => false,
                'message' => 'Route not found.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:routes,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:active,inactive',
            'city_ids' => 'nullable|array',
            'city_ids.*' => 'exists:cities,id',
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
            $route->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? $route->status,
            ]);

            // Update cities if provided
            if ($request->has('city_ids')) {
                if (is_array($request->city_ids)) {
                    $route->cities()->sync($request->city_ids);
                } else {
                    $route->cities()->detach();
                }
            }

            $route->load(['cities:id,name,district,province']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Route updated successfully.',
                'data' => [
                    'id' => $route->id,
                    'name' => $route->name,
                    'description' => $route->description,
                    'status' => $route->status,
                    'cities' => $route->cities,
                    'cities_count' => $route->cities->count(),
                    'created_at' => $route->created_at,
                    'updated_at' => $route->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update route.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified route from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $route = Route::with(['salesReps', 'cities'])->find($id);

            if (!$route) {
                return response()->json([
                    'status' => false,
                    'message' => 'Route not found.',
                    'data' => null,
                ], 404);
            }

            if ($route->salesReps->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete route. It has assigned sales representatives.',
                    'errors' => ['constraint' => ['Route has active sales rep assignments.']],
                ], 422);
            }

            $routeName = $route->name;

            $route->cities()->detach();
            $route->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Route '{$routeName}' deleted successfully.",
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete route.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all available cities for route assignment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableCities()
    {
        try {
            $cities = City::select('id', 'name', 'district', 'province')
                ->orderBy('name')
                ->get()
                ->map(function ($city) {
                    return [
                        'id' => $city->id,
                        'name' => $city->name,
                        'district' => $city->district,
                        'province' => $city->province,
                        'display_name' => $city->name . ' (' . $city->district . ', ' . $city->province . ')',
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Available cities retrieved successfully.',
                'data' => $cities,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve available cities.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cities assigned to a specific route.
     *
     * @param  int  $routeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRouteCities($routeId)
    {
        try {
            $route = Route::with(['cities:id,name,district,province'])->find($routeId);

            if (!$route) {
                return response()->json([
                    'status' => false,
                    'message' => 'Route not found.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Route cities retrieved successfully.',
                'data' => [
                    'route_id' => $route->id,
                    'route_name' => $route->name,
                    'cities' => $route->cities,
                    'cities_count' => $route->cities->count(),
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
     * Add cities to a route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $routeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCities(Request $request, $routeId)
    {
        $route = Route::find($routeId);

        if (!$route) {
            return response()->json([
                'status' => false,
                'message' => 'Route not found.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'city_ids' => 'required|array|min:1',
            'city_ids.*' => 'exists:cities,id',
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
            $route->cities()->syncWithoutDetaching($request->city_ids);
            $route->load(['cities:id,name,district,province']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Cities added to route successfully.',
                'data' => [
                    'route_id' => $route->id,
                    'route_name' => $route->name,
                    'cities' => $route->cities,
                    'cities_count' => $route->cities->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to add cities to route.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove cities from a route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $routeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCities(Request $request, $routeId)
    {
        $route = Route::find($routeId);

        if (!$route) {
            return response()->json([
                'status' => false,
                'message' => 'Route not found.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'city_ids' => 'required|array|min:1',
            'city_ids.*' => 'exists:cities,id',
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
            $route->cities()->detach($request->city_ids);
            $route->load(['cities:id,name,district,province']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Cities removed from route successfully.',
                'data' => [
                    'route_id' => $route->id,
                    'route_name' => $route->name,
                    'cities' => $route->cities,
                    'cities_count' => $route->cities->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to remove cities from route.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //change route status
    public function changeStatus(Request $request, $id)
    {
        $route = Route::find($id);
        if (!$route) {
            return response()->json([                              

                'status' => false,
                'message' => 'Route not found.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $route->status = $request->status;
        $route->save();
        return response()->json([
            'status' => true,
            'message' => 'Route status updated successfully.',
            'data' => $route,
        ], 200);
    }

}
