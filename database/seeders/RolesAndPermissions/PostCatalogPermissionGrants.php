<?php

namespace Database\Seeders\RolesAndPermissions;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * After catalog + role bootstrap: grant new perms to admins, inheritance for others, Master safety net.
 */
final class PostCatalogPermissionGrants
{
    public function grantNewCatalogPermissionsToAdmins(Command $command, array $newPermissionNames): void
    {
        if ($newPermissionNames === []) {
            return;
        }

        $masterDeny = array_flip(RolePermissionConstants::MASTER_ONLY_PERMISSION_NAMES);

        $master = Role::where('guard_name', 'web')
            ->where('key', RolePermissionConstants::ROLE_KEY_MASTER_SUPER_ADMIN)
            ->first();

        $super = Role::where('guard_name', 'web')
            ->where('key', RolePermissionConstants::ROLE_KEY_SUPER_ADMIN)
            ->first();

        foreach ($newPermissionNames as $name) {
            $perm = Permission::where('name', $name)->where('guard_name', 'web')->first();
            if (! $perm) {
                continue;
            }

            if ($master && ! $master->hasPermissionTo($perm)) {
                $master->givePermissionTo($perm);
                $command->info("New permission '{$name}' → Master Super Admin");
            }

            if ($super && ! isset($masterDeny[$name]) && ! $super->hasPermissionTo($perm)) {
                $super->givePermissionTo($perm);
                $command->info("New permission '{$name}' → Super Admin");
            }
        }
    }

    public function grantInheritedPermissionsToNonAdminRoles(Command $command): void
    {
        $command->info('Assigning inherited new permissions to non-admin roles (by related permission)...');

        /** @var array<string, list<string>> $inheritance */
        $inheritance = require dirname(__DIR__) . '/Data/new_permission_inheritance.php';

        $allRoles = Role::with('permissions')->get();

        foreach ($allRoles as $role) {
            $roleKey = $role->key;
            if (in_array($roleKey, [
                RolePermissionConstants::ROLE_KEY_MASTER_SUPER_ADMIN,
                RolePermissionConstants::ROLE_KEY_SUPER_ADMIN,
            ], true)) {
                continue;
            }

            if (in_array($role->name, ['Master Super Admin', 'Super Admin'], true)) {
                continue;
            }

            $currentPermissions = $role->permissions->pluck('name')->toArray();
            $permissionsToAdd = [];

            foreach ($inheritance as $newPermission => $relatedPermissions) {
                if (in_array($newPermission, $currentPermissions, true)) {
                    continue;
                }

                foreach ($relatedPermissions as $relatedPermission) {
                    if (in_array($relatedPermission, $currentPermissions, true)) {
                        $permissionsToAdd[] = $newPermission;
                        $command->info("Adding '{$newPermission}' to role '{$role->name}' (has '{$relatedPermission}')");
                        break;
                    }
                }
            }

            $permissionsToAdd = array_values(array_unique($permissionsToAdd));

            foreach ($permissionsToAdd as $permissionName) {
                $permission = Permission::where('name', $permissionName)->where('guard_name', 'web')->first();
                if ($permission && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        $command->info('Inheritance-based permission grants finished.');
    }

    public function ensureMasterSuperAdminHasAllPermissions(Command $command): void
    {
        $command->info('Verifying Master Super Admin has ALL permissions...');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $masterRole = Role::where('guard_name', 'web')
            ->where('key', RolePermissionConstants::ROLE_KEY_MASTER_SUPER_ADMIN)
            ->first();

        if (! $masterRole) {
            $masterRole = Role::where('name', 'Master Super Admin')->first();
        }

        if (! $masterRole) {
            $command->warn('Master Super Admin role not found. Skipping safety-net check.');

            return;
        }

        $allPermissions = Permission::all();
        $currentPermIds = $masterRole->permissions->pluck('id')->toArray();
        $missingPermissions = $allPermissions->filter(fn ($p) => ! in_array($p->id, $currentPermIds));

        if ($missingPermissions->isEmpty()) {
            $command->info('Master Super Admin already has all permissions. ✓');

            return;
        }

        foreach ($missingPermissions as $permission) {
            $masterRole->givePermissionTo($permission);
            $command->warn("Added missing permission to Master Super Admin: '{$permission->name}'");
        }

        $command->info("Safety net added {$missingPermissions->count()} missing permission(s) to Master Super Admin.");
    }
}
