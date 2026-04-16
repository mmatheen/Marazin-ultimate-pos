<?php

namespace App\Http\Middleware;

use App\Services\User\UserAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateLocationAccess
{
    public function __construct(private readonly UserAccessService $userAccessService)
    {
    }

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
        $validationResponse = $this->validateLocationParameters($request, $user);
        if ($validationResponse instanceof Response) {
            return $validationResponse;
        }

        return $next($request);
    }

    /**
     * Validate location parameters in the request
     */
    private function validateLocationParameters(Request $request, $user): ?Response
    {
        $locationFields = ['location_id', 'selected_location', 'selectedLocation'];
        $userLocationIds = array_map('intval', $this->getUserLocationIds($user));

        foreach ($locationFields as $field) {
            $rawLocationValue = $request->input($field);
            if ($rawLocationValue === null && $field === 'selected_location') {
                $rawLocationValue = $request->header('X-Selected-Location');
            }

            $locationValues = is_array($rawLocationValue) ? $rawLocationValue : [$rawLocationValue];

            foreach ($locationValues as $locationValue) {
                if ($locationValue === null || $locationValue === '') {
                    continue;
                }

                // Some forms use a non-numeric `location_id` code (e.g., LOC0001).
                // Only validate numeric values that represent real location IDs.
                if (!is_numeric($locationValue)) {
                    continue;
                }

                $locationId = (int) $locationValue;

                if (!empty($userLocationIds) && !in_array($locationId, $userLocationIds, true)) {
                    Log::warning("LocationAccessMiddleware: User {$user->id} attempted to access unauthorized location {$locationId}");

                    if ($request->expectsJson()) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'You do not have access to this location.'
                        ], 403);
                    }

                    return redirect()->back()->with('error', 'You do not have access to the requested location.');
                }
            }
        }

        // Validate location selection in session
        $selectedLocation = session('selected_location');
        if ($selectedLocation && is_numeric($selectedLocation) && !empty($userLocationIds)) {
            $selectedLocationId = (int) $selectedLocation;
            if (!in_array($selectedLocationId, $userLocationIds, true)) {
                Log::warning("LocationAccessMiddleware: User {$user->id} has unauthorized location in session {$selectedLocationId}");
                session()->forget('selected_location');
                session()->forget('selectedLocation');
            }
        }

        return null;
    }

    /**
     * Check if user is Master Super Admin
     */
    private function isMasterSuperAdmin($user): bool
    {
        return $this->userAccessService->isMasterSuperAdmin($user);
    }

    /**
     * Check if user has location bypass permission
     */
    private function hasLocationBypassPermission($user): bool
    {
        return $this->userAccessService->hasLocationBypassPermission($user);
    }

    /**
     * Get location IDs assigned to the user
     */
    private function getUserLocationIds($user): array
    {
        try {
            return $this->userAccessService->getUserLocationIds($user);
        } catch (\Exception $e) {
            Log::warning("LocationAccessMiddleware: Failed to load user locations: " . $e->getMessage());
            return [];
        }
    }
}
