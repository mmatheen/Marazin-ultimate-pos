<?php

namespace Database\Seeders\RolesAndPermissions;

/**
 * Single source for role keys and permission lists used across seeding / grants.
 */
final class RolePermissionConstants
{
    public const ROLE_KEY_MASTER_SUPER_ADMIN = 'master_super_admin';

    public const ROLE_KEY_SUPER_ADMIN = 'super_admin';

    /** Excluded from Super Admin’s default set (Master Super Admin only). */
    public const MASTER_ONLY_PERMISSION_NAMES = [
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
        'override location scope',
        'manage system roles',
        'access production database',
        'manage system maintenance',
    ];
}
