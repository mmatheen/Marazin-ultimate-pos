<?php

namespace App\Traits;

use App\Services\User\UserAccessService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

trait RolePermissionHelper
{
    /**
     * Check if user is Master Super Admin
     */
    public function isMasterSuperAdmin()
    {
        return $this->getUserAccessService()->isMasterSuperAdmin($this);
    }

    /**
     * Check if user is any type of Super Admin
     */
    public function isSuperAdmin()
    {
        return $this->getUserAccessService()->isSuperAdmin($this);
    }

    /**
     * Check if user is regular Super Admin (not Master)
     */
    public function isRegularSuperAdmin()
    {
        return $this->isSuperAdmin() && !$this->isMasterSuperAdmin();
    }

    private function getUserAccessService(): UserAccessService
    {
        return app(UserAccessService::class);
    }

    /**
     * Get accessible permissions based on role hierarchy
     */
    public function getAccessiblePermissions()
    {
        if ($this->isMasterSuperAdmin()) {
            return Permission::all();
        }

        // Regular users get only their assigned permissions
        return $this->getAllPermissions();
    }

    /**
     * Get roles that current user can manage
     */
    public function getManageableRoles()
    {
        if ($this->isMasterSuperAdmin()) {
            return Role::all();
        }

        if ($this->isRegularSuperAdmin()) {
            // Super Admin can manage all roles except Master Super Admin
            return Role::where('name', '!=', 'Master Super Admin')
                      ->where('is_system_role', '!=', true)
                      ->get();
        }

        // Other roles cannot manage roles
        return collect();
    }

    /**
     * Check if user can access master admin features
     */
    public function canAccessMasterFeatures()
    {
        return $this->isMasterSuperAdmin();
    }

    /**
     * Check if user can bypass location scope
     */
    public function canBypassLocationScope()
    {
        if ($this->isMasterSuperAdmin()) {
            return true;
        }

        // Check if any role has bypass_location_scope flag
        foreach ($this->roles as $role) {
            if ($role->bypass_location_scope ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user's location-restricted permissions
     */
    public function getLocationBasedPermissions($locationId = null)
    {
        if ($this->isMasterSuperAdmin()) {
            return Permission::all();
        }

        if ($this->canBypassLocationScope()) {
            return $this->getAllPermissions();
        }

        // Get permissions based on user's location assignment
        // You can customize this based on your location-user relationship
        return $this->getAllPermissions();
    }

    /**
     * Check if user can manage specific role
     */
    public function canManageRole($role)
    {
        if ($this->isMasterSuperAdmin()) {
            return true;
        }

        if ($role instanceof Role) {
            $roleName = $role->name;
            $isSystemRole = $role->is_system_role ?? false;
        } else {
            $roleName = $role;
            $roleModel = Role::where('name', $roleName)->first();
            $isSystemRole = $roleModel ? ($roleModel->is_system_role ?? false) : false;
        }

        // Master Super Admin role can only be managed by Master Super Admin
        if ($roleName === $this->getUserAccessService()->masterSuperAdminRoleName()) {
            return false;
        }

        // System roles cannot be managed by regular users
        if ($isSystemRole && !$this->isMasterSuperAdmin()) {
            return false;
        }

        return $this->isRegularSuperAdmin();
    }

    /**
     * Check if user can assign specific permission
     */
    public function canAssignPermission($permission)
    {
        if ($this->isMasterSuperAdmin()) {
            return true;
        }

        // Master admin permissions can only be assigned by Master Super Admin
        $masterAdminPermissions = $this->getUserAccessService()->getMasterAdminAssignmentBlockedPermissions();

        if (is_string($permission) && in_array($permission, $masterAdminPermissions)) {
            return false;
        }

        if (is_object($permission) && in_array($permission->name, $masterAdminPermissions)) {
            return false;
        }

        return $this->isRegularSuperAdmin();
    }

    /**
     * Get roles that current user can see/manage based on hierarchy (returns collection)
     */
    public function getVisibleRoles()
    {
        return $this->getUserAccessService()->getVisibleRolesQuery($this)->get();
    }

    /**
     * Get roles query that current user can see/manage based on hierarchy (returns query builder)
     */
    public function getVisibleRolesQuery()
    {
        return $this->getUserAccessService()->getVisibleRolesQuery($this);
    }

    /**
     * Get roles that current user can assign to other users
     */
    public function getAssignableRoles()
    {
        return $this->getVisibleRoles();
    }

    /**
     * Check if current user can view specific role
     */
    public function canViewRole($roleName)
    {
        return $this->getUserAccessService()->canViewRoleName($this, (string) $roleName);
    }

    /**
     * Check if current user can assign specific role
     */
    public function canAssignRole($roleName)
    {
        return $this->canViewRole($roleName); // Same logic as viewing
    }

    /**
     * Filter users based on role hierarchy (returns query builder)
     */
    public function getVisibleUsers()
    {
        return $this->getUserAccessService()->getHierarchyVisibleUsersQuery($this);
    }

    /**
     * Get permissions that current user can see/assign based on their own permissions
     */
    public function getVisiblePermissions()
    {
        if ($this->isMasterSuperAdmin()) {
            // Master Super Admin can see all permissions
            return Permission::all();
        } elseif ($this->isSuperAdmin()) {
            // Super Admin can only see permissions they have been granted
            return $this->getAllPermissions();
        } else {
            // Other users can only see basic permissions they have
            return $this->getAllPermissions()->filter(function($permission) {
                // Exclude admin-level permissions
                $adminPermissions = [
                    'create user', 'edit user', 'delete user', 'view user',
                    'create role', 'edit role', 'delete role', 'view role',
                    'create location', 'edit location', 'delete location', 'view location',
                    'manage system settings', 'view reports'
                ];
                return !in_array($permission->name, $adminPermissions);
            });
        }
    }

    /**
     * Get permissions grouped by category that current user can see
     */
    public function getVisiblePermissionsGrouped()
    {
        return $this->getVisiblePermissions()->groupBy('group_name');
    }

    /**
     * Check if current user can assign specific permission to others (enhanced version)
     */
    public function canAssignPermissionEnhanced($permissionName)
    {
        if ($this->isMasterSuperAdmin()) {
            return true; // Master Super Admin can assign any permission
        }

        // Other users can only assign permissions they themselves have
        return $this->hasPermissionTo($permissionName);
    }

    /**
     * Get permissions that current user can assign to specific role
     */
    public function getAssignablePermissionsForRole($roleName)
    {
        $visiblePermissions = $this->getVisiblePermissions();

        // If assigning to Super Admin role, exclude Master Super Admin only permissions
        if ($roleName === $this->getUserAccessService()->superAdminRoleName() && !$this->isMasterSuperAdmin()) {
            $masterOnlyPermissions = $this->getUserAccessService()->getSuperAdminAssignmentExcludedPermissions();

            return $visiblePermissions->reject(function($permission) use ($masterOnlyPermissions) {
                return in_array($permission->name, $masterOnlyPermissions);
            });
        }

        return $visiblePermissions;
    }

    /**
     * Filter sidebar menu items based on user permissions
     */
    public function getVisibleMenuItems()
    {
        $menuItems = [];

        // Dashboard - always visible for authenticated users
        $menuItems['dashboard'] = true;

        // Location Management
        $menuItems['locations'] = $this->hasPermissionTo('view location') || $this->isMasterSuperAdmin();
        $menuItems['create_location'] = $this->hasPermissionTo('create location') || $this->isMasterSuperAdmin();

        // User Management
        $menuItems['users'] = $this->hasPermissionTo('view user');
        $menuItems['create_user'] = $this->hasPermissionTo('create user');

        // Role & Permission Management
        $menuItems['roles_permissions'] = $this->hasPermissionTo('view role') || $this->hasPermissionTo('view permission');

        // Sales Management
        $menuItems['sales'] = $this->hasPermissionTo('view sale');
        $menuItems['create_sale'] = $this->hasPermissionTo('create sale');

        // Inventory Management
        $menuItems['products'] = $this->hasPermissionTo('view product');
        $menuItems['inventory'] = $this->hasPermissionTo('view inventory');

        // Reports
        $menuItems['reports'] = $this->hasPermissionTo('view reports') || $this->isMasterSuperAdmin();

        // System Settings (Master Super Admin only)
        $menuItems['system_settings'] = $this->isMasterSuperAdmin();

        return $menuItems;
    }
}
