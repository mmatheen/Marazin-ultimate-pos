<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesRep;
use App\Models\Vehicle;
use App\Models\Route;
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
                'vehicle:id,vehicle_number,vehicle_type,location_id',
                'vehicle.location:id,name',
                'vehicle.location.parent:id,name',
                'route:id,name,',
                'route.cities:id,name,district,province'
            ])
                ->select('id', 'user_id', 'vehicle_id', 'route_id', 'assigned_date', 'end_date', 'status', 'created_at', 'updated_at')
                ->get();

            // Group by user_id and vehicle_id
            $grouped = $salesReps->groupBy(function ($rep) {
                return $rep->user_id . '-' . $rep->vehicle_id;
            })->map(function ($group) {
                $first = $group->first();
                return [
                    'user_id' => $first->user_id,
                    'vehicle_id' => $first->vehicle_id,
                    'user' => $first->user ? [
                        'id' => $first->user->id,
                        'user_name' => $first->user->user_name,
                        'email' => $first->user->email,
                    ] : null,
                    'vehicle' => $first->vehicle ? [
                        'id' => $first->vehicle->id,
                        'vehicle_number' => $first->vehicle->vehicle_number,
                        'vehicle_type' => $first->vehicle->vehicle_type,
                    ] : null,
                    'location' => $first->vehicle?->location ? [
                        'id' => $first->vehicle->location->id,
                        'name' => $first->vehicle->location->name,
                        'parent_name' => $first->vehicle->location->parent?->name,
                        'full_name' => ($first->vehicle->location->parent?->name ? $first->vehicle->location->parent->name . ' → ' : ' → ') . $first->vehicle->location->name,
                    ] : null,
                    'routes' => $group->map(function ($rep) {
                        return [
                            'id' => $rep->route?->id,
                            'name' => $rep->route?->name,
                            'assigned_date' => $rep->assigned_date,
                            'end_date' => $rep->end_date,
                            'status' => $rep->status,
                            'cities' => $rep->route ? $rep->route->cities->map(fn($city) => [
                                'id' => $city->id,
                                'name' => $city->name,
                                'district' => $city->district,
                                'province' => $city->province,
                            ]) : [],
                            'created_at' => $rep->created_at,
                            'updated_at' => $rep->updated_at,
                        ];
                    })->values(),
                ];
            })->values();

            if ($grouped->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No sales representatives found.',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Sales representatives retrieved successfully.',
                'data' => $grouped,
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
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prevent duplicate: same user + route
        $existing = SalesRep::where('user_id', $request->user_id)
            ->where('route_id', $request->route_id)
            ->when($request->id, fn($q) => $q->where('id', '!=', $request->id))
            ->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'This user is already assigned to this route.',
                'errors' => ['combination' => ['User-route assignment must be unique.']],
            ], 422);
        }

        // Validate vehicle has a location
        $vehicle = Vehicle::find($request->vehicle_id);
        if (!$vehicle || !$vehicle->location_id) {
            return response()->json([
                'status' => false,
                'message' => 'Selected vehicle is not assigned to any location.',
                'errors' => ['vehicle_id' => ['Vehicle must have a valid location assigned.']],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $salesRep = SalesRep::create([
                'user_id' => $request->user_id,
                'vehicle_id' => $request->vehicle_id,
                'route_id' => $request->route_id,
                'assigned_date' => $request->assigned_date ?? now(),
                'end_date' => $request->end_date,
                'status' => $request->status ?? 'active',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales representative created successfully.',
                'data' => $this->formatSalesRep($salesRep),
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
     */
    public function show($id)
    {
        try {
            $salesRep = SalesRep::with([
                'user:id,user_name,email',
                'vehicle:id,vehicle_number,vehicle_type,location_id',
                'vehicle.location:id,name,parent_id',
                'vehicle.location.parent:id,name',
                'route:id,name',
                'route.cities:id,name,district,province',
                'targets'
            ])->find($id);

            if (!$salesRep) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sales representative not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Sales representative retrieved successfully.',
                'data' => $this->formatSalesRep($salesRep),
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
     */
    public function update(Request $request, $id)
    {
        $salesRep = SalesRep::find($id);
        if (!$salesRep) {
            return response()->json([
                'status' => false,
                'message' => 'Sales representative not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Unique constraint: user + route
        $existing = SalesRep::where('user_id', $request->user_id)
            ->where('route_id', $request->route_id)
            ->where('id', '!=', $id)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'This user is already assigned to this route.',
                'errors' => ['combination' => ['User-route combination must be unique.']],
            ], 422);
        }

        // Validate vehicle has location
        $vehicle = Vehicle::find($request->vehicle_id);
        if (!$vehicle || !$vehicle->location_id) {
            return response()->json([
                'status' => false,
                'message' => 'Selected vehicle is not assigned to any location.',
                'errors' => ['vehicle_id' => ['Vehicle must have a location assigned.']],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $salesRep->update([
                'user_id' => $request->user_id,
                'vehicle_id' => $request->vehicle_id,
                'route_id' => $request->route_id,
                'assigned_date' => $request->assigned_date ?? $salesRep->assigned_date,
                'end_date' => $request->end_date,
                'status' => $request->status ?? $salesRep->status,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales representative updated successfully.',
                'data' => $this->formatSalesRep($salesRep->refresh()),
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
     * Remove the specified sales representative.
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
                ], 404);
            }

            $userName = $salesRep->user?->user_name ?? 'Unknown';

            $salesRep->targets()->delete();
            $salesRep->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Sales representative '{$userName}' and associated targets deleted successfully.",
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
     * Get available vehicles (must have location)
     */
    public function getAvailableVehicles()
    {
        $vehicles = Vehicle::with([
            'location:id,name',
            'location.parent:id,name'
        ])
            ->whereNotNull('location_id')
            ->get(['id', 'vehicle_number', 'vehicle_type', 'location_id'])
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'vehicle_number' => $v->vehicle_number,
                    'vehicle_type' => $v->vehicle_type,
                    'location_name' => $v->location?->name,
                    'main_location' => $v->location?->parent?->name,
                    'display_name' => $v->vehicle_number . ' (' . ($v->location?->parent?->name ?? 'Unknown') . ' → ' . ($v->location?->name ?? 'Unknown') . ')',
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Available vehicles retrieved successfully.',
            'data' => $vehicles,
        ], 200);
    }

    /**
     * Get available routes
     */
    public function getAvailableRoutes()
    {
        $routes = Route::withCount('cities')
            ->get(['id', 'name'])
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'cities_count' => $r->cities_count,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Available routes retrieved successfully.',
            'data' => $routes,
        ], 200);
    }

     private function formatSalesRep($rep)
    {
        return [
            'id' => $rep->id,
            'user_id' => $rep->user_id,
            'vehicle_id' => $rep->vehicle_id,
            'route_id' => $rep->route_id,
            'assigned_date' => $rep->assigned_date,
            'end_date' => $rep->end_date,
            'status' => $rep->status,
            'user' => $rep->user ? [
                'id' => $rep->user->id,
                'user_name' => $rep->user->user_name,
                'email' => $rep->user->email,
            ] : null,
            'vehicle' => $rep->vehicle ? [
                'id' => $rep->vehicle->id,
                'vehicle_number' => $rep->vehicle->vehicle_number,
            ] : null,
            'location' => $rep->vehicle?->location ? [
                'full_name' => $rep->vehicle->location->parent?->name
                    ? $rep->vehicle->location->parent->name . ' → ' . $rep->vehicle->location->name
                    : $rep->vehicle->location->name,
            ] : null,
            'route' => $rep->route ? [
                'id' => $rep->route->id,
                'name' => $rep->route->name,
                'cities' => $rep->route->cities,
            ] : null,
            'created_at' => $rep->created_at,
            'updated_at' => $rep->updated_at,
        ];
    }
}