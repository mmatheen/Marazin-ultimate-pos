<?php

namespace Database\Seeders\RolesAndPermissions;

use Spatie\Permission\Models\Permission;

/**
 * Upserts permission rows from the catalog file and returns names that were not in DB before this run.
 */
final class PermissionCatalogSynchronizer
{
    /**
     * @param  array<string, list<string>>  $permissionGroups
     * @return list<string> New permission names (for admin grants)
     */
    public function upsertAndDiscoverNewNames(array $permissionGroups): array
    {
        $permissionNamesBefore = Permission::where('guard_name', 'web')->pluck('name')->all();

        foreach ($permissionGroups as $group => $perms) {
            foreach ($perms as $permission) {
                Permission::updateOrCreate(
                    ['name' => $permission, 'guard_name' => 'web'],
                    ['group_name' => $group]
                );
            }
        }

        $catalogPermissionNames = collect($permissionGroups)->flatten()->unique()->values()->all();

        return array_values(array_diff($catalogPermissionNames, $permissionNamesBefore));
    }
}
