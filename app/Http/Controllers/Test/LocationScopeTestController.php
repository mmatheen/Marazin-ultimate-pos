<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Location;

class LocationScopeTestController extends Controller
{
    public function index()
    {
        return view('test.location_scope_test');
    }

    public function setLocation(Request $request)
    {
        $locationId = $request->input('location_id');
        
        if ($locationId) {
            Session::put('selected_location', $locationId);
            return redirect()->route('test.location.scope')->with('success', "Location set to ID: {$locationId}");
        } else {
            Session::forget('selected_location');
            return redirect()->route('test.location.scope')->with('success', 'Location cleared');
        }
    }

    public function clearLocation()
    {
        Session::forget('selected_location');
        return redirect()->route('test.location.scope')->with('success', 'Location selection cleared');
    }

    public function debugUserLocations()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated']);
        }

        // Load user relationships - cast to User model
        /** @var User $user */
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }
        if (!$user->relationLoaded('locations')) {
            $user->load('locations');
        }
        
        // Test the ProductController method directly
        $productController = new \App\Http\Controllers\ProductController();
        $reflection = new \ReflectionClass($productController);
        $method = $reflection->getMethod('getUserAccessibleLocations');
        $method->setAccessible(true);
        $accessibleLocations = $method->invoke($productController, $user);

        $debugInfo = [
            'user_id' => $user->id,
            'user_name' => $user->full_name ?? 'Unknown',
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'user_role_keys' => $user->roles->pluck('key')->toArray(),
            'bypass_flags' => $user->roles->pluck('bypass_location_scope')->toArray(),
            'assigned_locations_count' => $user->locations->count(),
            'assigned_locations' => $user->locations->map(function($loc) {
                return [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'pivot' => $loc->pivot ? $loc->pivot->toArray() : null
                ];
            })->toArray(),
            'accessible_locations_from_method' => $accessibleLocations->toArray(),
            'all_locations_query_result' => \App\Models\Location::select('id', 'name')->get()->toArray(),
        ];

        // Test permissions
        try {
            $debugInfo['has_override_permission'] = $user->hasPermissionTo('override location scope');
        } catch (\Exception $e) {
            $debugInfo['has_override_permission'] = false;
            $debugInfo['permission_error'] = $e->getMessage();
        }

        return response()->json($debugInfo, 200, [], JSON_PRETTY_PRINT);
    }
}