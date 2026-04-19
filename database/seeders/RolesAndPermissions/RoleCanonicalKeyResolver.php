<?php

namespace Database\Seeders\RolesAndPermissions;

/**
 * Maps human-readable role names to stable `roles.key` values.
 */
final class RoleCanonicalKeyResolver
{
    public function resolve(string $roleName): string
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $roleName)));

        $aliases = [
            'master super admin' => RolePermissionConstants::ROLE_KEY_MASTER_SUPER_ADMIN,
            'super admin' => RolePermissionConstants::ROLE_KEY_SUPER_ADMIN,
            'sales rep' => 'sales_rep',
            'sales representative' => 'sales_rep',
            'sales executive' => 'sales_rep',
            // Historic misspellings — match legacy role names already in DB
            'sales reperesntive' => 'sales_rep',
            'sales excutive' => 'sales_rep',
        ];

        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }

        $slug = preg_replace('/[^a-z0-9]+/', '_', $normalized);

        return trim($slug, '_');
    }
}
