<?php

namespace Database\Seeders\RolesAndPermissions;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Creates built-in roles and applies first-run sync rules (Master always full sync).
 */
final class BuiltInRoleBootstrapper
{
    public function __construct(
        private readonly RoleCanonicalKeyResolver $keyResolver = new RoleCanonicalKeyResolver(),
    ) {
    }

    /**
     * @param  array<string, list<string>>  $roles  name => permission names
     */
    public function bootstrap(Command $command, array $roles): void
    {
        foreach ($roles as $roleName => $rolePermissions) {
            $roleKey = $this->keyResolver->resolve($roleName);

            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['key' => $roleKey]
            );

            $isNewRole = $role->wasRecentlyCreated;

            if (! $role->key) {
                $role->update(['key' => $roleKey]);
            }

            if ($roleKey === RolePermissionConstants::ROLE_KEY_MASTER_SUPER_ADMIN) {
                $role->update([
                    'key' => $roleKey,
                    'is_system_role' => true,
                    'is_master_role' => true,
                    'bypass_location_scope' => true,
                ]);
                $role->syncPermissions($rolePermissions);

                continue;
            }

            if ($roleKey === RolePermissionConstants::ROLE_KEY_SUPER_ADMIN) {
                $role->update([
                    'key' => $roleKey,
                    'is_system_role' => false,
                    'is_master_role' => false,
                    'bypass_location_scope' => false,
                ]);
            }

            if ($isNewRole) {
                $role->syncPermissions($rolePermissions);
                $command->info("Role '{$roleName}' created with default permissions.");
            } else {
                $command->info("Role '{$roleName}' already exists - preserving custom permissions.");
            }
        }
    }
}
