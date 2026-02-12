<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class LocationScope implements Scope
{
    // ── Per-request static caches ──────────────────────────────────────
    private static $authenticatedUser;
    private static $authChecked = false;
    private static $userRoles = [];
    private static $userLocationIds = [];
    private static $selectedLocation;
    private static $selectedLocationChecked = false;
    private static $locationBypassPermissions = [];

    /** @var bool|null Resolved once per request */
    private static $debugLogging;

    /**
     * Only emit debug/info logs in local or testing environments.
     */
    private static function shouldLog(): bool
    {
        if (self::$debugLogging === null) {
            self::$debugLogging = in_array(app()->environment(), ['local', 'testing']);
        }
        return self::$debugLogging;
    }

    // ── Main entry point ───────────────────────────────────────────────

    public function apply(Builder $builder, Model $model)
    {
        // Skip scope if model requests bypass
        if (method_exists($model, 'shouldBypassLocationScope') && $model->shouldBypassLocationScope()) {
            return;
        }

        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $builder->whereNull($this->getLocationColumn($model));
            return;
        }

        if ($this->canBypassLocationScope($user)) {
            return;
        }

        $this->applyLocationFilter($builder, $user, $model);

        if ($model instanceof \App\Models\Customer && $this->isSalesRep($user)) {
            $this->applySalesRepCustomerFilter($builder, $user);
        }
    }

    // ── Bypass check ───────────────────────────────────────────────────

    private function canBypassLocationScope($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        $userId = $user->id;

        if (isset(self::$locationBypassPermissions[$userId])) {
            return self::$locationBypassPermissions[$userId];
        }

        $userRoles = $this->getUserRoles($user);

        $adminRoles = ['master_super_admin', 'super_admin', 'Master Super Admin', 'Super Admin'];
        $hasAdminRole = !empty(array_intersect($userRoles, $adminRoles));

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

        $hasPermission = false;
        try {
            $hasPermission = method_exists($user, 'hasPermissionTo')
                && $user->hasPermissionTo('override location scope');
        } catch (\Exception $e) {
            // Permission row may not exist — safe to ignore
        }

        $canBypass = $hasAdminRole || $hasBypassRole || $hasPermission;

        self::$locationBypassPermissions[$userId] = $canBypass;

        return $canBypass;
    }

    // ── Role helper ────────────────────────────────────────────────────

    private function getUserRoles($user): array
    {
        if (!$user instanceof User) {
            return [];
        }

        $userId = $user->id;

        if (!isset(self::$userRoles[$userId])) {
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

    // ── Location filter ────────────────────────────────────────────────

    private function applyLocationFilter(Builder $builder, $user, Model $model)
    {
        $selectedLocation = $this->getSelectedLocation();
        $locationIds      = $this->getUserLocationIds($user);
        $locationColumn   = $this->getLocationColumn($model);

        $filterLocationIds = [];

        if ($selectedLocation && (empty($locationIds) || in_array($selectedLocation, $locationIds))) {
            $filterLocationIds = [$selectedLocation];
        } elseif (!empty($locationIds)) {
            $filterLocationIds = $locationIds;
        }

        if (!empty($filterLocationIds)) {
            $builder->whereIn($locationColumn, $filterLocationIds);
        } else {
            $builder->where($locationColumn, 0);

            if (self::shouldLog()) {
                Log::debug('LocationScope: User ' . $user->id . ' has no location access on ' . get_class($model));
            }
        }

        // Only log actionable warnings (denied selected location)
        if ($selectedLocation && !empty($locationIds) && !in_array($selectedLocation, $locationIds)) {
            Log::warning("LocationScope: User {$user->id} denied access to selected location {$selectedLocation}");
        }
    }

    // ── Sales-rep customer filter ──────────────────────────────────────

    private function applySalesRepCustomerFilter(Builder $builder, $user)
    {
        try {
            if (!$user->relationLoaded('salesRep')) {
                $user->load('salesRep.route.cities');
            }

            $salesRep = $user->salesRep;

            if (!$salesRep || !$salesRep->route) {
                Log::warning("LocationScope: Sales rep {$user->id} has no route assigned");
                $builder->where('city_id', 0);
                return;
            }

            $routeCityIds = $salesRep->route->cities->pluck('id')->toArray();

            if (empty($routeCityIds)) {
                Log::warning("LocationScope: Sales rep {$user->id} route has no cities");
                $builder->where('city_id', 0);
                return;
            }

            $builder->where(function ($query) use ($routeCityIds) {
                $query->whereIn('city_id', $routeCityIds)
                    ->orWhereNull('city_id');
            });
        } catch (\Exception $e) {
            Log::error("LocationScope: Sales rep filter failed: " . $e->getMessage());
            $builder->where('city_id', 0);
        }
    }

    // ── Helpers (sales rep check) ──────────────────────────────────────

    private function isSalesRep($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (method_exists($user, 'isSalesRep')) {
            return $user->isSalesRep();
        }

        return in_array('sales_rep', $this->getUserRoles($user));
    }

    // ── Auth resolution (cached) ───────────────────────────────────────

    private function getAuthenticatedUser()
    {
        if (self::$authChecked) {
            return self::$authenticatedUser;
        }

        try {
            $user = null;

            foreach (['sanctum', 'web'] as $guard) {
                try {
                    if (Auth::guard($guard)->check()) {
                        $user = Auth::guard($guard)->user();
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!$user) {
                $user = Auth::user();
            }

            self::$authenticatedUser = $user;
            self::$authChecked = true;

            return $user;
        } catch (\Exception $e) {
            Log::warning('LocationScope: Auth check failed: ' . $e->getMessage());
            self::$authenticatedUser = null;
            self::$authChecked = true;
            return null;
        }
    }

    // ── Selected location (cached, supports both session keys) ─────────

    private function getSelectedLocation()
    {
        if (self::$selectedLocationChecked) {
            return self::$selectedLocation;
        }

        try {
            $location = null;

            if (!app()->runningInConsole()) {
                // Support both session key conventions
                if (Session::has('selected_location')) {
                    $location = Session::get('selected_location');
                } elseif (Session::has('selectedLocation')) {
                    $location = Session::get('selectedLocation');
                }
            }

            if (!$location) {
                $request = request();
                if ($request && $request->hasHeader('X-Selected-Location')) {
                    $location = $request->header('X-Selected-Location');
                }
            }

            self::$selectedLocation = $location;
            self::$selectedLocationChecked = true;

            return $location;
        } catch (\Exception $e) {
            self::$selectedLocation = null;
            self::$selectedLocationChecked = true;
            return null;
        }
    }

    // ── User location IDs (cached) ─────────────────────────────────────

    private function getUserLocationIds($user): array
    {
        if (!$user instanceof User) {
            return [];
        }

        $userId = $user->id;

        if (isset(self::$userLocationIds[$userId])) {
            return self::$userLocationIds[$userId];
        }

        try {
            if (!$user->relationLoaded('locations')) {
                $user->load('locations');
            }

            $locationIds = $user->locations->pluck('id')->toArray();

            self::$userLocationIds[$userId] = $locationIds;

            return $locationIds;
        } catch (\Exception $e) {
            Log::warning("LocationScope: Failed to load locations for user {$userId}");
            self::$userLocationIds[$userId] = [];
            return [];
        }
    }

    // ── Location column ────────────────────────────────────────────────

    private function getLocationColumn(Model $model): string
    {
        if (method_exists($model, 'getLocationColumn')) {
            return $model->getLocationColumn();
        }

        return 'location_id';
    }

    // ── Cache reset ────────────────────────────────────────────────────

    public static function clearCache(): void
    {
        self::$authenticatedUser = null;
        self::$authChecked = false;
        self::$userRoles = [];
        self::$userLocationIds = [];
        self::$selectedLocation = null;
        self::$selectedLocationChecked = false;
        self::$locationBypassPermissions = [];
        self::$debugLogging = null;
    }
}