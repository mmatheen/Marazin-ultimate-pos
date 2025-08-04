<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesRep;
use App\Models\VehicleLocation;
use App\Models\Route;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesRepController extends Controller
{
    /**
     * Display a listing of sales representatives.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $salesReps = SalesRep::with([
                'user:id,user_name,email',
                'vehicleLocation:id,vehicle_id,location_id',
                'vehicleLocation.vehicle:id,vehicle_number,vehicle_type',
                'vehicleLocation.location:id,name',
                'route:id,name',
                'route.cities:id,name,district,province'
            ])
                ->select('id', 'user_id', 'vehicle_location_id', 'route_id', 'assigned_date', 'status', 'created_at', 'updated_at')
                ->get()
                ->map(function ($salesRep) {
                    return [
                        'id' => $salesRep->id,
                        'user_id' => $salesRep->user_id,
                        'vehicle_location_id' => $salesRep->vehicle_location_id,
                        'route_id' => $salesRep->route_id,
                        'assigned_date' => $salesRep->assigned_date,
                        'status' => $salesRep->status,
                        'user' => $salesRep->user,
                        'vehicle_location' => $salesRep->vehicleLocation,
                        'vehicle' => $salesRep->vehicleLocation->vehicle ?? null,
                        'location' => $salesRep->vehicleLocation->location ?? null,
                        'route' => $salesRep->route ? [
                            'id' => $salesRep->route->id,
                            'name' => $salesRep->route->name,
                            'cities' => $salesRep->route->cities->map(function ($city) {
                                return [
                                    'id' => $city->id,
                                    'name' => $city->name,
                                    'district' => $city->district,
                                    'province' => $city->province,
                                ];
                            })
                        ] : null,
                        'created_at' => $salesRep->created_at,
                        'updated_at' => $salesRep->updated_at,
                    ];
                });

            if ($salesReps->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No sales representatives found.',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Sales representatives retrieved successfully.',
                'data' => $salesReps,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve sales representatives.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created sales representative.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'vehicle_location_id' => 'required|exists:vehicle_locations,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if user_id + route_id combination already exists
        $existingSalesRep = SalesRep::where('user_id', $request->user_id)
            ->where('route_id', $request->route_id)
            ->first();

        if ($existingSalesRep) {
            return response()->json([
                'status' => false,
                'message' => 'This user is already assigned to this route.',
                'errors' => ['combination' => ['User-Route combination already exists.']],
            ], 422);
        }

        // Check if vehicle_location is valid (has both vehicle and location)
        $vehicleLocation = VehicleLocation::with(['vehicle', 'location'])->find($request->vehicle_location_id);
        if (!$vehicleLocation || !$vehicleLocation->vehicle || !$vehicleLocation->location) {
            return response()->json([
                'status' => false,
                'message' => 'Selected vehicle location is invalid.',
                'errors' => ['vehicle_location_id' => ['Vehicle location must have valid vehicle and location.']],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $salesRep = SalesRep::create([
                'user_id' => $request->user_id,
                'vehicle_location_id' => $request->vehicle_location_id,
                'route_id' => $request->route_id,
                'assigned_date' => $request->assigned_date ?? now(),
                'status' => $request->status ?? 'active',
            ]);

            // Load relationships for response
            $salesRep->load([
                'user:id,user_name,email',
                'vehicleLocation:id,vehicle_id,location_id',
                'vehicleLocation.vehicle:id,vehicle_number',
                'vehicleLocation.location:id,name',
                'route:id,name'
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales representative created successfully.',
                'data' => [
                    'id' => $salesRep->id,
                    'user_id' => $salesRep->user_id,
                    'vehicle_location_id' => $salesRep->vehicle_location_id,
                    'route_id' => $salesRep->route_id,
                    'assigned_date' => $salesRep->assigned_date,
                    'status' => $salesRep->status,
                    'user' => $salesRep->user,
                    'vehicle' => $salesRep->vehicleLocation->vehicle,
                    'location' => $salesRep->vehicleLocation->location,
                    'route' => $salesRep->route,
                    'created_at' => $salesRep->created_at,
                    'updated_at' => $salesRep->updated_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create sales representative.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified sales representative.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $salesRep = SalesRep::with([
                'user:id,user_name,email',
                'vehicleLocation:id,vehicle_id,location_id',
                'vehicleLocation.vehicle:id,vehicle_number,vehicle_type',
                'vehicleLocation.location:id,name',
                'route:id,name',
                'route.cities:id,name,district,province',
                'targets'
            ])->find($id);

            if (!$salesRep) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sales representative not found.',
                    'data' => null,
                ], 404);
            }

            $data = [
                'id' => $salesRep->id,
                'user_id' => $salesRep->user_id,
                'vehicle_location_id' => $salesRep->vehicle_location_id,
                'route_id' => $salesRep->route_id,
                'assigned_date' => $salesRep->assigned_date,
                'status' => $salesRep->status,
                'user' => $salesRep->user,
                'vehicle' => $salesRep->vehicleLocation->vehicle ?? null,
                'location' => $salesRep->vehicleLocation->location ?? null,
                'route' => $salesRep->route ? [
                    'id' => $salesRep->route->id,
                    'name' => $salesRep->route->name,
                    'cities' => $salesRep->route->cities
                ] : null,
                'targets' => $salesRep->targets,
                'created_at' => $salesRep->created_at,
                'updated_at' => $salesRep->updated_at,
            ];

            return response()->json([
                'status' => true,
                'message' => 'Sales representative retrieved successfully.',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve sales representative.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified sales representative.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $salesRep = SalesRep::find($id);

        if (!$salesRep) {
            return response()->json([
                'status' => false,
                'message' => 'Sales representative not found.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'vehicle_location_id' => 'required|exists:vehicle_locations,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if user_id + route_id combination already exists (excluding current record)
        $existingSalesRep = SalesRep::where('user_id', $request->user_id)
            ->where('route_id', $request->route_id)
            ->where('id', '!=', $id)
            ->first();

        if ($existingSalesRep) {
            return response()->json([
                'status' => false,
                'message' => 'This user is already assigned to this route.',
                'errors' => ['combination' => ['User-Route combination already exists.']],
            ], 422);
        }

        // Check if vehicle_location is valid
        $vehicleLocation = VehicleLocation::with(['vehicle', 'location'])->find($request->vehicle_location_id);
        if (!$vehicleLocation || !$vehicleLocation->vehicle || !$vehicleLocation->location) {
            return response()->json([
                'status' => false,
                'message' => 'Selected vehicle location is invalid.',
                'errors' => ['vehicle_location_id' => ['Vehicle location must have valid vehicle and location.']],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $salesRep->update([
                'user_id' => $request->user_id,
                'vehicle_location_id' => $request->vehicle_location_id,
                'route_id' => $request->route_id,
                'assigned_date' => $request->assigned_date ?? $salesRep->assigned_date,
                'status' => $request->status ?? $salesRep->status,
            ]);

            // Load relationships for response
            $salesRep->load([
                'user:id,user_name,email',
                'vehicleLocation:id,vehicle_id,location_id',
                'vehicleLocation.vehicle:id,vehicle_number',
                'vehicleLocation.location:id,name',
                'route:id,name'
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales representative updated successfully.',
                'data' => [
                    'id' => $salesRep->id,
                    'user_id' => $salesRep->user_id,
                    'vehicle_location_id' => $salesRep->vehicle_location_id,
                    'route_id' => $salesRep->route_id,
                    'assigned_date' => $salesRep->assigned_date,
                    'status' => $salesRep->status,
                    'user' => $salesRep->user,
                    'vehicle' => $salesRep->vehicleLocation->vehicle,
                    'location' => $salesRep->vehicleLocation->location,
                    'route' => $salesRep->route,
                    'created_at' => $salesRep->created_at,
                    'updated_at' => $salesRep->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update sales representative.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified sales representative from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $salesRep = SalesRep::with(['user', 'targets'])->find($id);

            if (!$salesRep) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sales representative not found.',
                    'data' => null,
                ], 404);
            }

            $userName = $salesRep->user->user_name ?? 'Unknown';

            // Delete associated targets first
            $salesRep->targets()->delete();

            // Delete the sales rep
            $salesRep->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Sales representative '{$userName}' and all associated targets deleted successfully.",
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete sales representative.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available vehicle locations for assignment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableVehicleLocations()
    {
        try {
            $vehicleLocations = VehicleLocation::with([
                'vehicle:id,vehicle_number,vehicle_type',
                'location:id,name'
            ])->select('id', 'vehicle_id', 'location_id')->get();

            $data = $vehicleLocations->map(function ($vehicleLocation) {
                return [
                    'id' => $vehicleLocation->id,
                    'vehicle_id' => $vehicleLocation->vehicle_id,
                    'location_id' => $vehicleLocation->location_id,
                    'vehicle_number' => $vehicleLocation->vehicle->vehicle_number ?? 'N/A',
                    'vehicle_type' => $vehicleLocation->vehicle->vehicle_type ?? 'N/A',
                    'location_name' => $vehicleLocation->location->name ?? 'N/A',
                    'display_name' => ($vehicleLocation->vehicle->vehicle_number ?? 'N/A') . ' - ' . ($vehicleLocation->location->name ?? 'N/A'),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Available vehicle locations retrieved successfully.',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve available vehicle locations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available routes for assignment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableRoutes()
    {
        try {
            $routes = Route::with(['cities:id,name'])
                ->select('id', 'name')
                ->get()
                ->map(function ($route) {
                    return [
                        'id' => $route->id,
                        'name' => $route->name,
                        'cities_count' => $route->cities->count(),
                        'cities' => $route->cities->pluck('name')->take(3)->implode(', ') .
                            ($route->cities->count() > 3 ? ' +' . ($route->cities->count() - 3) . ' more' : ''),
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Available routes retrieved successfully.',
                'data' => $routes,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve available routes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get location details from vehicle_location_id.
     *
     * @param  int  $vehicleLocationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocationFromVehicleLocation($vehicleLocationId)
    {
        try {
            $vehicleLocation = VehicleLocation::with([
                'vehicle:id,vehicle_number,vehicle_type',
                'location:id,name'
            ])->find($vehicleLocationId);

            if (!$vehicleLocation) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vehicle location not found.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Location details retrieved successfully.',
                'data' => [
                    'vehicle_location_id' => $vehicleLocation->id,
                    'vehicle' => $vehicleLocation->vehicle,
                    'location' => $vehicleLocation->location,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve location details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
