<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Ensure User model is imported

class LocationScope implements Scope
{
    // Cache properties to avoid repeated queries/calculations within the same request
    private static $authenticatedUser;
    private static $userRoles = [];
    private static $userLocationIds = [];
    private static $selectedLocation;
    private static $locationBypassPermissions = [];
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        // Skip scope if model requests bypass
        if (method_exists($model, 'shouldBypassLocationScope') && $model->shouldBypassLocationScope()) {
            Log::info("LocationScope: Bypassed for model " . get_class($model));
            return;
        }

        $user = $this->getAuthenticatedUser();

        // No filter if no user is logged in - restrict to null location only
        if (!$user) {
            Log::warning("LocationScope: No authenticated user - restricting to null location only");
            $builder->whereNull($this->getLocationColumn($model));
            return;
        }

        Log::info("LocationScope: Applying scope for user " . $user->id . " on model " . get_class($model));

        // Check for bypass permissions (Master Super Admin or location bypass)
        if ($this->canBypassLocationScope($user)) {
            Log::info("LocationScope: User " . $user->id . " has bypass permission");
            return;
        }

        // Apply location filter for all users (including sales reps)
        Log::info("LocationScope: Applying location filter for user " . $user->id);
        $this->applyLocationFilter($builder, $user);
        
        // Apply additional sales rep filtering for Customer model
        if ($model instanceof \App\Models\Customer && $this->isSalesRep($user)) {
            Log::info("LocationScope: Applying sales rep customer filtering for user " . $user->id);
            $this->applySalesRepCustomerFilter($builder, $user);
        }
    }

    /**
     * Check if user can bypass location scope (Master Super Admin or has bypass permission)
     */
    private function canBypassLocationScope($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        $userId = $user->id;
        
        // Return cached result if available
        if (isset(self::$locationBypassPermissions[$userId])) {
            return self::$locationBypassPermissions[$userId];
        }

        $userRoles = $this->getUserRoles($user);
        
        // Check for Master Super Admin or Super Admin roles
        $adminRoles = ['master_super_admin', 'super_admin', 'Master Super Admin', 'Super Admin'];
        $hasAdminRole = !empty(array_intersect($userRoles, $adminRoles));
        
        // Check for bypass permission in roles
        $hasBypassRole = false;
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }
        
        foreach ($user->roles as $role) {
            if ($role->bypass_location_scope ?? false) {
                $hasBypassRole = true;
                break;
            }
        }

        // Check specific permission
        $hasPermission = method_exists($user, 'hasPermissionTo') && 
                        $user->hasPermissionTo('override location scope');

        $canBypass = $hasAdminRole || $hasBypassRole || $hasPermission;
        
        // Cache the result
        self::$locationBypassPermissions[$userId] = $canBypass;
        
        return $canBypass;
    }

    /**
     * Get user roles (cached)
     */
    private function getUserRoles($user): array
    {
        if (!$user instanceof User) {
            return [];
        }

        $userId = $user->id;
        
        if (!isset(self::$userRoles[$userId])) {
            // Load roles if not already loaded
            if (!$user->relationLoaded('roles')) {
                $user->load('roles');
            }
            
            self::$userRoles[$userId] = array_merge(
                $user->roles->pluck('name')->toArray(),
                $user->roles->pluck('key')->toArray()
            );
        }

        return self::$userRoles[$userId];
    }



    /**
     * Apply location filter logic - optimized and strict
     */
    private function applyLocationFilter(Builder $builder, $user)
    {
        $selectedLocation = $this->getSelectedLocation();
        $locationIds = $this->getUserLocationIds($user);

        Log::info("LocationScope: Applying filter - Selected: {$selectedLocation}, User Locations: " . json_encode($locationIds));

        $builder->where(function ($query) use ($selectedLocation, $locationIds, $user) {
            $filterLocationIds = [];

            // Priority 1: Use selected location if user has access
            if ($selectedLocation && (empty($locationIds) || in_array($selectedLocation, $locationIds))) {
                $filterLocationIds = [$selectedLocation];
                Log::info("LocationScope: Applied selected location filter: {$selectedLocation}");
            }
            // Priority 2: Use all assigned locations
            elseif (!empty($locationIds)) {
                $filterLocationIds = $locationIds;
                Log::info("LocationScope: Applied user locations filter: " . implode(',', $locationIds));
            }

            // Apply strict location filter - no null records for regular users
            if (!empty($filterLocationIds)) {
                $query->whereIn('location_id', $filterLocationIds);
                Log::info("LocationScope: Applied strict location filter: " . implode(',', $filterLocationIds));
            } else {
                // If user has no location access, restrict to impossible condition
                $query->where('location_id', 0); // Assuming 0 is never a valid location ID
                Log::info("LocationScope: No location access - blocked all records");
            }
            
            // Log warning if selected location access denied
            if ($selectedLocation && !empty($locationIds) && !in_array($selectedLocation, $locationIds)) {
                Log::warning("LocationScope: User {$user->id} doesn't have access to selected location {$selectedLocation}");
            }
        });
    }

    /**
     * Apply sales rep customer filter - restrict customers to assigned route cities
     */
    private function applySalesRepCustomerFilter(Builder $builder, $user)
    {
        try {
            // Load sales rep relationship if not already loaded
            if (!$user->relationLoaded('salesRep')) {
                $user->load('salesRep.route.cities');
            }

            $salesRep = $user->salesRep;
            
            if (!$salesRep || !$salesRep->route) {
                Log::warning("LocationScope: Sales rep {$user->id} has no route assigned - blocking all customer access");
                // Block all customers if no route assigned
                $builder->where('city_id', 0); // Impossible condition
                return;
            }

            $routeCityIds = $salesRep->route->cities->pluck('id')->toArray();
            
            if (empty($routeCityIds)) {
                Log::warning("LocationScope: Sales rep {$user->id} route has no cities - blocking all customer access");
                // Block all customers if route has no cities
                $builder->where('city_id', 0); // Impossible condition
                return;
            }

            Log::info("LocationScope: Sales rep {$user->id} can access customers from cities: " . implode(',', $routeCityIds));
            
            // Only show customers from assigned route cities
            $builder->where(function ($query) use ($routeCityIds) {
                $query->whereIn('city_id', $routeCityIds)
                      ->orWhereNull('city_id'); // Allow customers without city assignment
            });
            
        } catch (\Exception $e) {
            Log::error("LocationScope: Failed to apply sales rep customer filter: " . $e->getMessage());
            // On error, block all customers for safety
            $builder->where('city_id', 0);
        }
    }

    /**
     * Check if the user is a Sales Rep - optimized
     */
    private function isSalesRep($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // Use the method if available (most efficient)
        if (method_exists($user, 'isSalesRep')) {
            return $user->isSalesRep();
        }

        // Use cached roles
        $userRoles = $this->getUserRoles($user);
        return in_array('sales_rep', $userRoles);
    }

    /**
     * Get authenticated user from Sanctum, web guard, or default (cached)
     */
    private function getAuthenticatedUser()
    {
        // Return cached user if available
        if (self::$authenticatedUser !== null) {
            return self::$authenticatedUser;
        }

        try {
            $user = null;
            
            // Check guards in order of priority
            foreach (['sanctum', 'web'] as $guard) {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    break;
                }
            }
            
            // Fallback to default guard
            if (!$user) {
                $user = Auth::user();
            }
            
            // Cache the result
            self::$authenticatedUser = $user;
            
            return $user;
        } catch (\Exception $e) {
            Log::warning("LocationScope: Failed to get authenticated user: " . $e->getMessage());
            self::$authenticatedUser = null;
            return null;
        }
    }

    /**
     * Get selected location from session (web) or header (API) - cached
     */
    private function getSelectedLocation()
    {
        // Return cached result if available
        if (self::$selectedLocation !== null) {
            return self::$selectedLocation;
        }

        try {
            $location = null;
            
            // Web: from session
            if (!app()->runningInConsole() && Session::has('selected_location')) {
                $location = Session::get('selected_location');
            }
            // API: from header
            elseif (($request = request()) && $request->hasHeader('X-Selected-Location')) {
                $location = $request->header('X-Selected-Location');
            }

            // Cache the result
            self::$selectedLocation = $location;
            
            return $location;
        } catch (\Exception $e) {
            Log::warning("LocationScope: Failed to get selected location: " . $e->getMessage());
            self::$selectedLocation = null;
            return null;
        }
    }

    /**
     * Get location IDs assigned to the user - cached
     */
    private function getUserLocationIds($user): array
    {
        if (!$user instanceof User) {
            return [];
        }

        $userId = $user->id;
        
        // Return cached result if available
        if (isset(self::$userLocationIds[$userId])) {
            return self::$userLocationIds[$userId];
        }

        try {
            if (!$user->relationLoaded('locations')) {
                $user->load('locations');
            }

            $locationIds = $user->locations->pluck('id')->toArray();
            
            // Cache the result
            self::$userLocationIds[$userId] = $locationIds;
            
            return $locationIds;
        } catch (\Exception $e) {
            Log::warning("LocationScope: Failed to load user locations: " . $e->getMessage());
            self::$userLocationIds[$userId] = [];
            return [];
        }
    }

    /**
     * Get the location column name for the model
     */
    private function getLocationColumn(Model $model): string
    {
        // Check if model has a custom location column
        if (method_exists($model, 'getLocationColumn')) {
            return $model->getLocationColumn();
        }

        // Default to location_id
        return 'location_id';
    }

    /**
     * Reset static caches - useful for testing or long-running processes
     */
    public static function clearCache(): void
    {
        self::$authenticatedUser = null;
        self::$userRoles = [];
        self::$userLocationIds = [];
        self::$selectedLocation = null;
        self::$locationBypassPermissions = [];
    }
}