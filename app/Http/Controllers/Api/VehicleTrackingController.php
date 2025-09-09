<?php

namespace App\Http\Controllers\Api;

use App\Models\VehicleLocationLog;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class VehicleTrackingController extends Controller
{
    /**
     * Receive GPS data from sales rep's device
     */

    public function simulateMovement(Request $request)
    {
        // Only allow in local environment
        if (!app()->isLocal()) {
            abort(403, 'This feature is only available in local environment.');
        }

        $vehicleId = $request->vehicle_id ?? 4; // default test vehicle
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            return response()->json(['status' => 404, 'message' => 'Vehicle not found.'], 404);
        }

        // Define route: from Oluvil to Kalmunai (example)
        $start = ['lat' => 7.412908, 'lng' => 81.827132];  // Oluvil
        $end = ['lat' => 7.5586, 'lng' => 81.8600];       // Kalmunai

        // Number of steps
        $steps = 50;
        $currentStep = $request->step ?? 0;

        if ($currentStep >= $steps) {
            return response()->json(['status' => 200, 'message' => 'Journey complete!', 'completed' => true]);
        }

        // Calculate current position
        $lat = $start['lat'] + ($end['lat'] - $start['lat']) * ($currentStep / $steps);
        $lng = $start['lng'] + ($end['lng'] - $start['lng']) * ($currentStep / $steps);

        // Optional: add small random drift
        $lat += (rand(-100, 100) / 100000);
        $lng += (rand(-100, 100) / 100000);

        // Simulate speed: 40 km/h
        $speed = 40;

        // Create fake log
        $log = VehicleLocationLog::create([
            'vehicle_id' => $vehicle->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'speed' => $speed,
            'accuracy' => 5,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Simulated location updated',
            'data' => [
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number,
                'latitude' => $lat,
                'longitude' => $lng,
                'speed' => $speed,
                'step' => $currentStep + 1,
                'total_steps' => $steps,
                'completed' => false,
            ],
        ]);
    }
    public function updateLocation(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized.'], 401);
        }

        if (!$user->isSalesRep()) {
            return response()->json(['status' => 403, 'message' => 'Access denied.'], 403);
        }

        $salesRep = $user->salesRep;

        if (!$salesRep) {
            return response()->json(['status' => 404, 'message' => 'Sales rep profile not found.'], 404);
        }

        if ($salesRep->status !== 'active') {
            return response()->json(['status' => 403, 'message' => 'Sales rep not active.'], 403);
        }

        $vehicle = $salesRep->vehicle;

        if (!$vehicle) {
            return response()->json(['status' => 400, 'message' => 'No vehicle assigned.'], 400);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
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

    public function getLiveVehicles()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized.'], 401);
        }

        // Allow Super Admin and other authorized roles
        $allowedRoles = ['Super Admin', 'Admin', 'Manager'];
        if (!$user->hasAnyRole($allowedRoles)) {
            return response()->json(['status' => 403, 'message' => 'Access denied.'], 403);
        }

        $vehicles = Vehicle::with(['salesReps.user', 'salesReps.route'])
            ->whereHas('vehicleLocationLogs') // only vehicles with logs
            ->get()
            ->map(function ($vehicle) {
                $latest = $vehicle->vehicleLocationLogs()->latest('recorded_at')->first();
                if (!$latest) return null;

                $salesRep = $vehicle->salesReps->first();

                return [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_number' => $vehicle->vehicle_number,
                    'vehicle_type' => $vehicle->vehicle_type,
                    'driver_name' => $salesRep?->user?->full_name ?? 'Unknown',
                    'route' => $salesRep?->route?->name ?? 'N/A',
                    'latitude' => (float) $latest->latitude,
                    'longitude' => (float) $latest->longitude,
                    'accuracy' => $latest->accuracy,
                    'speed' => $latest->speed ? round($latest->speed, 1) : null,
                    'recorded_at' => $latest->recorded_at,
                    'updated_at' => $latest->updated_at,
                ];
            })->filter();

        return response()->json([
            'status' => 200,
            'count' => $vehicles->count(),
            'data' => $vehicles->values()
        ]);
    }
}
