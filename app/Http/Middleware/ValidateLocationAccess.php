<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateLocationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Skip validation for Master Super Admin
        if ($this->isMasterSuperAdmin($user)) {
            return $next($request);
        }

        // Skip validation for users with bypass permission
        if ($this->hasLocationBypassPermission($user)) {
            return $next($request);
        }

        // Validate location access from various request parameters
        $this->validateLocationParameters($request, $user);

        return $next($request);
    }

    /**
     * Validate location parameters in the request
     */
    private function validateLocationParameters(Request $request, $user)
    {
        $locationFields = ['location_id', 'selected_location'];
        $userLocationIds = $this->getUserLocationIds($user);

        foreach ($locationFields as $field) {
            $locationId = $request->input($field) ?? $request->header('X-Selected-Location');
            
            if ($locationId && !empty($userLocationIds)) {
                if (!in_array($locationId, $userLocationIds)) {
                    Log::warning("LocationAccessMiddleware: User {$user->id} attempted to access unauthorized location {$locationId}");
                    
                    // For web requests, redirect with error
                    if ($request->expectsJson()) {
                        abort(403, 'You do not have access to this location.');
                    } else {
                        return redirect()->back()->with('error', 'You do not have access to the requested location.');
                    }
                }
            }
        }

        // Validate location selection in session
        $selectedLocation = session('selected_location');
        if ($selectedLocation && !empty($userLocationIds) && !in_array($selectedLocation, $userLocationIds)) {
            Log::warning("LocationAccessMiddleware: User {$user->id} has unauthorized location in session {$selectedLocation}");
            session()->forget('selected_location');
        }
    }

    /**
     * Check if user is Master Super Admin
     */
    private function isMasterSuperAdmin($user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->pluck('name')->contains('Master Super Admin') || 
               $user->roles->pluck('key')->contains('master_super_admin');
    }

    /**
     * Check if user has location bypass permission
     */
    private function hasLocationBypassPermission($user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Check if any role has bypass_location_scope flag
        foreach ($user->roles as $role) {
            if ($role->bypass_location_scope ?? false) {
                return true;
            }
        }

        // Check for specific permissions
        return $user->hasPermissionTo('override location scope');
    }

    /**
     * Get location IDs assigned to the user
     */
    private function getUserLocationIds($user): array
    {
        try {
            if (!$user->relationLoaded('locations')) {
                $user->load('locations');
            }

            return $user->locations->pluck('id')->toArray();
        } catch (\Exception $e) {
            Log::warning("LocationAccessMiddleware: Failed to load user locations: " . $e->getMessage());
            return [];
        }
    }
}