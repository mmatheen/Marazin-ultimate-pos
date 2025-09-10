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
            return;
        }

        $user = $this->getAuthenticatedUser();

        // No filter if no user is logged in
        if (!$user) {
            return;
        }

        // Master Super Admin bypasses ALL scopes and sees everything
        if ($this->isMasterSuperAdmin($user)) {
            return;
        }

        // Super Admin sees everything (but can be restricted per location if needed)
        if ($this->isSuperAdmin($user)) {
            // Check if this Super Admin should be restricted to specific locations
            if (!$this->shouldSuperAdminBypassLocationScope($user)) {
                // Apply location filter for restricted Super Admin
                $this->applyLocationFilter($builder, $user);
            }
            return;
        }

        // Sales Rep: Skip location filter — they are filtered by route/city in controller
        if ($this->isSalesRep($user)) {
            return;
        }

        // For all other users (admin, manager, cashier, etc.), apply location filter
        $this->applyLocationFilter($builder, $user);

        // Optionally filter by user_id if the table has that column
        $this->applyUserIdFilter($builder, $model, $user);
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
     * Check if Super Admin should bypass location scope
     */
    private function shouldSuperAdminBypassLocationScope($user): bool
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

        return false;
    }

    /**
     * Apply location filter logic
     */
    private function applyLocationFilter(Builder $builder, $user)
    {
        $selectedLocation = $this->getSelectedLocation();
        $locationIds = $this->getUserLocationIds($user);

        $builder->where(function ($query) use ($selectedLocation, $locationIds) {
            // Priority 1: Use selected location (from session or header)
            if ($selectedLocation) {
                $query->where('location_id', $selectedLocation);
            }
            // Priority 2: Use assigned locations
            elseif (!empty($locationIds)) {
                $query->whereIn('location_id', $locationIds);
            }

            // Always allow walk-in/null location customers
            $query->orWhereNull('location_id');
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