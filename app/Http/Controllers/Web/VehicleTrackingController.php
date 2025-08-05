<?php

namespace App\Http\Controllers\Web;

use App\Models\VehicleLocationLog;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class VehicleTrackingController extends Controller
{
    /**
     * Show tracking page
     */
    public function index()
    {
        return view('sales_rep_module.vehicle_tracking.tracking');
    }

    /**
     * Update vehicle location from Sales Rep device
     */


    /**
     * Simulate vehicle moving along a realistic Oluvil → Kalmunai route
     */
    public function simulateMovement(Request $request)
    {
        if (!app()->isLocal()) {
            abort(403, 'Simulation only available in local environment.');
        }

        $vehicleId = $request->input('vehicle_id', 4);
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            return response()->json(['status' => 404, 'message' => 'Vehicle not found.']);
        }

        // Realistic GPS waypoints: Oluvil to Kalmunai via A4 Highway
        $routeWaypoints = [
            ['lat' => 7.412908, 'lng' => 81.827132], // Oluvil Start
            ['lat' => 7.415120, 'lng' => 81.830047],
            ['lat' => 7.418000, 'lng' => 81.835000],
            ['lat' => 7.422000, 'lng' => 81.841000],
            ['lat' => 7.426000, 'lng' => 81.847000],
            ['lat' => 7.430000, 'lng' => 81.853000],
            ['lat' => 7.434000, 'lng' => 81.858000],
            ['lat' => 7.438000, 'lng' => 81.863000],
            ['lat' => 7.442000, 'lng' => 81.868000],
            ['lat' => 7.446000, 'lng' => 81.873000],
            ['lat' => 7.450000, 'lng' => 81.877000],
            ['lat' => 7.454000, 'lng' => 81.881000],
            ['lat' => 7.458000, 'lng' => 81.885000],
            ['lat' => 7.462000, 'lng' => 81.889000],
            ['lat' => 7.466000, 'lng' => 81.893000],
            ['lat' => 7.470000, 'lng' => 81.897000],
            ['lat' => 7.474000, 'lng' => 81.901000],
            ['lat' => 7.478000, 'lng' => 81.905000],
            ['lat' => 7.482000, 'lng' => 81.909000],
            ['lat' => 7.486000, 'lng' => 81.913000],
            ['lat' => 7.490000, 'lng' => 81.917000],
            ['lat' => 7.494000, 'lng' => 81.921000],
            ['lat' => 7.498000, 'lng' => 81.925000],
            ['lat' => 7.502000, 'lng' => 81.929000],
            ['lat' => 7.506000, 'lng' => 81.933000],
            ['lat' => 7.510000, 'lng' => 81.937000],
            ['lat' => 7.514000, 'lng' => 81.941000],
            ['lat' => 7.518000, 'lng' => 81.945000],
            ['lat' => 7.522000, 'lng' => 81.949000],
            ['lat' => 7.526000, 'lng' => 81.953000],
            ['lat' => 7.530000, 'lng' => 81.957000],
            ['lat' => 7.534000, 'lng' => 81.961000],
            ['lat' => 7.538000, 'lng' => 81.965000],
            ['lat' => 7.542000, 'lng' => 81.969000],
            ['lat' => 7.546000, 'lng' => 81.973000],
            ['lat' => 7.550000, 'lng' => 81.977000],
            ['lat' => 7.554000, 'lng' => 81.981000],
            ['lat' => 7.558600, 'lng' => 81.985000], // Near Kalmunai
            ['lat' => 7.558600, 'lng' => 81.977000], // Kalmunai Center (final)
        ];

        $totalSteps = count($routeWaypoints);
        $step = $request->input('step', 0);

        if ($step >= $totalSteps) {
            return response()->json([
                'status' => 200,
                'completed' => true,
                'message' => 'Journey from Oluvil to Kalmunai completed!'
            ]);
        }

        $point = $routeWaypoints[$step];

        // Add slight randomness for realism
        $lat = $point['lat'] + (rand(-30, 30) / 100000);
        $lng = $point['lng'] + (rand(-30, 30) / 100000);

        // Simulate speed: 40–50 km/h
        $speed = rand(4000, 5000) / 100;

        VehicleLocationLog::create([
            'vehicle_id' => $vehicle->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'speed' => $speed,
            'accuracy' => 5,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'status' => 200,
            'completed' => false,
            'step' => $step + 1,
            'total_steps' => $totalSteps,
            'location' => ['lat' => $lat, 'lng' => $lng],
            'speed' => $speed,
        ]);
    }
    public function updateLocation(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized.'], 401);
        }

        if ($user->isSalesRep()) {
            $salesRep = $user->salesRep;
            if (!$salesRep) {
                return response()->json(['status' => 404, 'message' => 'Sales rep profile not found.'], 404);
            }
            if ($salesRep->status !== 'active') {
                return response()->json(['status' => 403, 'message' => 'Sales rep not active.'], 403);
            }
            $vehicleLocation = $salesRep->vehicleLocation;
            if (!$vehicleLocation) {
                return response()->json(['status' => 400, 'message' => 'No vehicle location assigned.'], 400);
            }
            $vehicle = $vehicleLocation->vehicle;
            if (!$vehicle) {
                return response()->json(['status' => 400, 'message' => 'No vehicle assigned to this location.'], 400);
            }
        } else {
            // Optional: allow admin to test with a vehicle
            $vehicle = Vehicle::first();
            if (!$vehicle) {
                return response()->json(['status' => 400, 'message' => 'No vehicle available.'], 400);
            }
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
        ]);

        $log = VehicleLocationLog::create([
            'vehicle_id' => $vehicle->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'speed' => $request->speed,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Location updated successfully',
            'data' => $log
        ]);
    }

    /**
     * Get all live vehicles - Admin, Manager, Super Admin only
     */
    public function getLiveVehicles()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized.'], 401);
        }

        $allowedRoles = ['Super Admin', 'Admin', 'Manager'];
        if (!$user->hasAnyRole($allowedRoles)) {
            return response()->json(['status' => 403, 'message' => 'Access denied.'], 403);
        }

        $vehicles = Vehicle::with(['salesReps.user', 'salesReps.route'])
            ->has('locationLogs')
            ->get()
            ->map(function ($vehicle) {
                $latest = $vehicle->latestLocation()->first();
                if (!$latest) return null;

                $salesRep = $vehicle->salesReps->first();
                return [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_number' => $vehicle->vehicle_number,
                    'vehicle_type' => $vehicle->vehicle_type,
                    'driver_name' => $salesRep?->user?->full_name ?? 'Unknown',
                    'route' => $salesRep?->route?->name ?? 'N/A',
                    'latitude' => (float)$latest->latitude,
                    'longitude' => (float)$latest->longitude,
                    'accuracy' => $latest->accuracy,
                    'speed' => $latest->speed ? round($latest->speed, 1) : null,
                    'recorded_at' => $latest->recorded_at,
                    'updated_at' => $latest->updated_at,
                ];
            })->filter();

        return response()->json([
            'status' => 200,
            'count' => $vehicles->count(),
            'data' => $vehicles->values(),
        ]);
    }

    /**
     * Get live vehicle for current Sales Rep only
     */
    public function getMyLiveVehicle()
    {
        $user = Auth::user();
        if (!$user || !$user->isSalesRep()) {
            return response()->json(['status' => 403, 'message' => 'Access denied. Only Sales Reps allowed.'], 403);
        }

        $salesRep = $user->salesRep;
        if (!$salesRep) {
            return response()->json(['status' => 404, 'message' => 'Sales rep profile not found.'], 404);
        }

        $vehicle = $salesRep->vehicleLocation?->vehicle;
        if (!$vehicle) {
            return response()->json(['status' => 404, 'message' => 'No vehicle assigned to you.'], 404);
        }

        $latest = $vehicle->latestLocation()->first();
        if (!$latest) {
            return response()->json(['status' => 404, 'message' => 'No location data found for your vehicle.'], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => [
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number,
                'latitude' => (float)$latest->latitude,
                'longitude' => (float)$latest->longitude,
                'speed' => $latest->speed ? round($latest->speed, 1) : null,
                'accuracy' => $latest->accuracy,
                'updated_at' => $latest->updated_at,
            ]
        ]);
    }
}
