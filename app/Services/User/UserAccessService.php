<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class UserAccessService
{
    private const ROLE_MASTER_SUPER_ADMIN = 'Master Super Admin';
    private const ROLE_SUPER_ADMIN = 'Super Admin';
    private const ROLE_ADMIN = 'Admin';
    private const ROLE_SALES_REP = 'Sales Rep';
    private const ROLE_KEY_MASTER_SUPER_ADMIN = 'master_super_admin';
    private const ROLE_KEY_SUPER_ADMIN = 'super_admin';
    private const ROLE_KEY_ADMIN = 'admin';
    private const ROLE_KEY_SALES_REP = 'sales_rep';

    public function masterSuperAdminRoleName(): string
    {
        return self::ROLE_MASTER_SUPER_ADMIN;
    }

    public function superAdminRoleName(): string
    {
        return self::ROLE_SUPER_ADMIN;
    }

    public function isMasterSuperAdminRoleName(?string $roleName): bool
    {
        return $roleName === self::ROLE_MASTER_SUPER_ADMIN;
    }

    public function isSuperAdminRoleName(?string $roleName): bool
    {
        return $roleName === self::ROLE_SUPER_ADMIN;
    }

    public function isAdminRoleName(?string $roleName): bool
    {
        return $roleName === self::ROLE_ADMIN;
    }

    public function isProtectedSystemRoleName(?string $roleName): bool
    {
        return $this->isMasterSuperAdminRoleName($roleName) || $this->isSuperAdminRoleName($roleName);
    }

    public function canAssignRole(User $actor, string $roleName): bool
    {
        if ($this->isMasterSuperAdmin($actor)) {
            return true;
        }

        // Protected system roles are reserved for Master Super Admin only.
        if ($this->isProtectedSystemRoleName($roleName)) {
            return false;
        }

        return $this->getVisibleRolesQuery($actor)
            ->where('name', $roleName)
            ->exists();
    }

    public function canViewRoleName(User $user, string $roleName): bool
    {
        if ($this->isMasterSuperAdmin($user)) {
            return true;
        }

        if ($this->isSuperAdmin($user)) {
            return $roleName !== self::ROLE_MASTER_SUPER_ADMIN;
        }

        return !in_array($roleName, [self::ROLE_MASTER_SUPER_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    /**
     * Permissions that only Master Super Admin should be able to assign generally.
     */
    public function getMasterAdminAssignmentBlockedPermissions(): array
    {
        return [
            'access master admin panel',
            'manage all locations',
            'manage all roles',
            'manage role hierarchy',
            'create super admin',
            'edit super admin',
            'delete super admin',
            'view all shops data',
            'system wide reports',
            'global settings',
            'manage system backups',
            'view system logs',
            'manage master permissions',
        ];
    }

    /**
     * Extra permissions to exclude while assigning permissions for Super Admin role.
     */
    public function getSuperAdminAssignmentExcludedPermissions(): array
    {
        return [
            'access master admin panel',
            'manage all locations',
            'manage all roles',
            'manage role hierarchy',
            'create super admin',
            'edit super admin',
            'delete super admin',
            'override location scope',
            'manage master permissions',
            'manage system roles',
            'access production database',
            'manage system maintenance',
        ];
    }

    public function countMasterSuperAdminUsers(): int
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', self::ROLE_MASTER_SUPER_ADMIN)
                ->orWhere('key', self::ROLE_KEY_MASTER_SUPER_ADMIN);
        })->count();
    }

    public function userHasPermission(User $user, string $permission): bool
    {
        $userPermissions = $user->permissions->pluck('name')->toArray();

        $rolePermissions = $user->roles->flatMap(function ($role) {
            return $role->permissions;
        })->pluck('name')->toArray();

        $allPermissions = array_merge($userPermissions, $rolePermissions);

        return in_array($permission, $allPermissions, true);
    }

    public function isMasterSuperAdmin(User $user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->pluck('name')->contains(self::ROLE_MASTER_SUPER_ADMIN) ||
            $user->roles->pluck('key')->contains(self::ROLE_KEY_MASTER_SUPER_ADMIN);
    }

    public function isSuperAdmin(User $user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->pluck('name')->contains(self::ROLE_SUPER_ADMIN) ||
            $user->roles->pluck('key')->contains(self::ROLE_KEY_SUPER_ADMIN) ||
            $this->isMasterSuperAdmin($user);
    }

    public function isAdmin(User $user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->pluck('name')->contains(self::ROLE_ADMIN) ||
            $user->roles->pluck('key')->contains(self::ROLE_KEY_ADMIN);
    }

    public function hasLocationBypassPermission(User $user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        foreach ($user->roles as $role) {
            if ($role->bypass_location_scope ?? false) {
                return true;
            }
        }

        return $user->hasPermissionTo('override location scope');
    }

    public function getUserLocationIds(User $user): array
    {
        if (!$user->relationLoaded('locations')) {
            $user->load('locations');
        }

        return $user->locations->pluck('id')->toArray();
    }

    public function hasSharedLocationAccess(User $currentUser, User $targetUser): bool
    {
        $currentUserLocationIds = $this->getUserLocationIds($currentUser);
        $targetUserLocationIds = $this->getUserLocationIds($targetUser);

        return !empty(array_intersect($currentUserLocationIds, $targetUserLocationIds));
    }

    public function validateAndSyncUserLocations(User $actor, User $targetUser, array $locationIds): void
    {
        if ($this->isMasterSuperAdmin($actor) || $this->hasLocationBypassPermission($actor)) {
            $targetUser->locations()->sync($locationIds);
            return;
        }

        $actorLocationIds = $this->getUserLocationIds($actor);
        $validLocationIds = array_intersect($locationIds, $actorLocationIds);

        if (count($validLocationIds) !== count($locationIds)) {
            $invalidIds = array_diff($locationIds, $actorLocationIds);
            throw new \Exception("You cannot assign the following locations as you don't have access to them: " . implode(', ', $invalidIds));
        }

        $targetUser->locations()->sync($validLocationIds);
    }

    /**
     * Build the roles query visible to the current user.
     */
    public function getVisibleRolesQuery(User $currentUser): Builder
    {
        $query = Role::query();

        if (method_exists($currentUser, 'canBypassLocationScope') && $currentUser->canBypassLocationScope()) {
            return $query;
        }

        if ($currentUser->can('manage all roles')) {
            return $query;
        }

        if ($currentUser->can('manage role hierarchy')) {
            $userRoleLevel = $this->getUserRoleLevel($currentUser);
            return $query->where('level', '>=', $userRoleLevel);
        }

        if ($currentUser->can('view role')) {
            if ($this->isMasterSuperAdmin($currentUser)) {
                return $query;
            }

            if ($this->isSuperAdmin($currentUser)) {
                return $query->where('name', '!=', self::ROLE_MASTER_SUPER_ADMIN);
            }

            if ($this->isAdmin($currentUser)) {
                return $query->where(function (Builder $roleQuery) {
                    $roleQuery->whereIn('name', [self::ROLE_ADMIN, self::ROLE_SALES_REP])
                        ->orWhereIn('key', [self::ROLE_KEY_ADMIN, self::ROLE_KEY_SALES_REP]);
                });
            }
        }

        $userRoleIds = $currentUser->roles->pluck('id')->toArray();

        return $query->whereIn('id', $userRoleIds);
    }

    private function getUserRoleLevel(User $user): int
    {
        if (Schema::hasColumn('roles', 'level')) {
            return $user->roles->min('level') ?? 999;
        }

        if ($this->isMasterSuperAdmin($user)) {
            return 1;
        }

        if ($this->isSuperAdmin($user)) {
            return 2;
        }

        if ($this->isAdmin($user)) {
            return 3;
        }

        return 4;
    }

    /**
     * Build the users query visible to the current user.
     */
    public function getVisibleUsersQuery(User $currentUser): Builder
    {
        $isMasterSuperAdmin = $this->isMasterSuperAdmin($currentUser);
        $isSuperAdmin = $this->isSuperAdmin($currentUser);
        $canBypassLocationScope = $this->hasLocationBypassPermission($currentUser);

        $query = User::with(['roles', 'locations']);

        if (!$isMasterSuperAdmin) {
            $query->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('name', self::ROLE_MASTER_SUPER_ADMIN)
                    ->orWhere('key', 'master_super_admin');
            });
        }

        if (!$isMasterSuperAdmin && !$isSuperAdmin) {
            $canEditUsers = $this->userHasPermission($currentUser, 'edit user');
            $canDeleteUsers = $this->userHasPermission($currentUser, 'delete user');
            $canCreateUsers = $this->userHasPermission($currentUser, 'create user');
            $hasFullUserManagement = $canEditUsers || $canDeleteUsers || $canCreateUsers;

            if (!$canBypassLocationScope) {
                $this->applyUserVisibilityScope($query, $currentUser);
            }

            if (!$hasFullUserManagement && !$canBypassLocationScope) {
                return $query;
            }

            return $query;
        }

        if ($isSuperAdmin && !$canBypassLocationScope) {
            $this->applyUserVisibilityScope($query, $currentUser);
        }

        return $query;
    }

    /**
     * Build user visibility query using only role hierarchy rules.
     */
    public function getHierarchyVisibleUsersQuery(User $currentUser): Builder
    {
        if ($this->isMasterSuperAdmin($currentUser)) {
            return User::query();
        }

        if ($this->isSuperAdmin($currentUser)) {
            return User::whereDoesntHave('roles', function ($query) {
                $query->where('name', self::ROLE_MASTER_SUPER_ADMIN);
            });
        }

        return User::whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', [self::ROLE_MASTER_SUPER_ADMIN, self::ROLE_SUPER_ADMIN]);
        });
    }

    /**
     * Apply same-location-or-self filter to user query.
     */
    private function applyUserVisibilityScope(Builder $query, User $currentUser): void
    {
        $userLocationIds = $this->getUserLocationIds($currentUser);

        if (!empty($userLocationIds)) {
            $query->where(function ($subQuery) use ($userLocationIds, $currentUser) {
                $subQuery->whereHas('locations', function ($locationQuery) use ($userLocationIds) {
                    $locationQuery->whereIn('locations.id', $userLocationIds);
                })
                ->orWhere('id', $currentUser->id);
            });

            return;
        }

        $query->where('id', $currentUser->id);
    }
}
