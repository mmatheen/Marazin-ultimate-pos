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

        // Master Super Admin bypasses ALL scopes and sees everything
        if ($this->isMasterSuperAdmin($user)) {
            Log::info("LocationScope: Master Super Admin bypass for user " . $user->id);
            return;
        }

        // Check if user has general bypass permission through role
        if ($this->hasLocationBypassPermission($user)) {
            Log::info("LocationScope: User " . $user->id . " has bypass permission");
            return;
        }

        // Sales Rep: Apply location filter based on their assigned routes/cities
        if ($this->isSalesRep($user)) {
            Log::info("LocationScope: Applying sales rep location filter for user " . $user->id);
            $this->applySalesRepLocationFilter($builder, $user);
            return;
        }

        // For all other users, apply standard location filter
        Log::info("LocationScope: Applying standard location filter for user " . $user->id);
        $this->applyLocationFilter($builder, $user);
    }

    /**
     * Check if the user is a Master Super Admin
     */
    private function isMasterSuperAdmin($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // Load roles if not already loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->pluck('name')->contains('Master Super Admin') || 
               $user->roles->pluck('key')->contains('master_super_admin');
    }

    /**
     * Check if the user is a Super Admin
     */
    private function isSuperAdmin($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // Load roles if not already loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->pluck('key')->contains('super_admin') ||
               $user->roles->pluck('name')->contains('Super Admin');
    }



    /**
     * Apply location filter logic
     */
    private function applyLocationFilter(Builder $builder, $user)
    {
        $selectedLocation = $this->getSelectedLocation();
        $locationIds = $this->getUserLocationIds($user);

        Log::info("LocationScope: Applying filter - Selected: {$selectedLocation}, User Locations: " . json_encode($locationIds));

        $builder->where(function ($query) use ($selectedLocation, $locationIds, $user) {
            $hasLocationFilter = false;

            // Priority 1: Use selected location (from session or header)
            if ($selectedLocation) {
                // Validate that user has access to this location
                if (empty($locationIds) || in_array($selectedLocation, $locationIds)) {
                    $query->where('location_id', $selectedLocation);
                    $hasLocationFilter = true;
                    Log::info("LocationScope: Applied selected location filter: {$selectedLocation}");
                } else {
                    Log::warning("LocationScope: User {$user->id} doesn't have access to selected location {$selectedLocation}");
                }
            }
            
            // Priority 2: Use assigned locations
            if (!$hasLocationFilter && !empty($locationIds)) {
                $query->whereIn('location_id', $locationIds);
                $hasLocationFilter = true;
                Log::info("LocationScope: Applied user locations filter: " . implode(',', $locationIds));
            }

            // If user has no location assignments, restrict to null location only
            if (!$hasLocationFilter) {
                $query->whereNull('location_id');
                Log::info("LocationScope: No location access - restricted to null location only");
            } else {
                // Also allow null location records (walk-in customers, etc.)
                $query->orWhereNull('location_id');
            }
        });
    }

    /**
     * Check if the user is a Sales Rep
     */
    private function isSalesRep($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // Use the method if available
        if (method_exists($user, 'isSalesRep')) {
            return $user->isSalesRep();
        }

        // Fallback: check role key directly
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->pluck('key')->contains('sales_rep');
    }

    /**
     * Get authenticated user from Sanctum, web guard, or default
     */
    private function getAuthenticatedUser()
    {
        try {
            if (Auth::guard('sanctum')->check()) {
                return Auth::guard('sanctum')->user();
            }

            if (Auth::guard('web')->check()) {
                return Auth::guard('web')->user();
            }

            return Auth::user(); // fallback
        } catch (\Exception $e) {
            Log::warning("LocationScope: Failed to get authenticated user: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get selected location from session (web) or header (API)
     */
    private function getSelectedLocation()
    {
        try {
            // Web: from session
            if (!app()->runningInConsole() && Session::has('selected_location')) {
                return Session::get('selected_location');
            }

            // API: from header
            $request = request();
            if ($request && $request->hasHeader('X-Selected-Location')) {
                return $request->header('X-Selected-Location');
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("LocationScope: Failed to get selected location: " . $e->getMessage());
            return null;
        }
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
            Log::warning("LocationScope: Failed to load user locations: " . $e->getMessage());
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
     * Check if user has location bypass permission
     */
    private function hasLocationBypassPermission($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // Load roles if not already loaded
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
     * Apply location filter for sales reps based on their routes/cities
     */
    private function applySalesRepLocationFilter(Builder $builder, $user)
    {
        // For now, apply the same location filter as other users
        // This can be customized later for sales rep specific logic
        $this->applyLocationFilter($builder, $user);
    }

    /**
     * Apply user_id filter if the column exists on the model's table
     */
    private function applyUserIdFilter(Builder $builder, Model $model, $user)
    {
        $table = $model->getTable();

        try {
            $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($table);

            if (in_array('user_id', $columns)) {
                $builder->where('user_id', $user->id);
            }
        } catch (\Exception $e) {
            Log::warning("LocationScope: Could not fetch columns for table {$table}: " . $e->getMessage());
        }
    }
}