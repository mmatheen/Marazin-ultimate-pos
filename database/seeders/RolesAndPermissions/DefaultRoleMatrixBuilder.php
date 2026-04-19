<?php

namespace Database\Seeders\RolesAndPermissions;

use Spatie\Permission\Models\Permission;

/**
 * Builds the permission-name matrix used when creating/syncing built-in roles.
 */
final class DefaultRoleMatrixBuilder
{
    /**
     * @return array<string, list<string>>
     */
    public function build(): array
    {
        $allNames = Permission::all()->pluck('name')->toArray();

        $superAdminNames = array_values(array_filter(
            $allNames,
            fn ($name) => ! in_array((string) $name, RolePermissionConstants::MASTER_ONLY_PERMISSION_NAMES, true)
        ));

        $salesRep = require dirname(__DIR__) . '/Data/default_sales_rep_permissions.php';

        return [
            'Master Super Admin' => $allNames,
            'Super Admin' => $superAdminNames,
            'Sales Rep' => $salesRep,
        ];
    }
}
