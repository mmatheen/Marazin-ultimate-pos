<?php

namespace Database\Seeders;

use Database\Seeders\RolesAndPermissions\BuiltInRoleBootstrapper;
use Database\Seeders\RolesAndPermissions\DefaultRoleMatrixBuilder;
use Database\Seeders\RolesAndPermissions\LegacyPermissionMigrator;
use Database\Seeders\RolesAndPermissions\PermissionCatalogSynchronizer;
use Database\Seeders\RolesAndPermissions\PostCatalogPermissionGrants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;


class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Roles and Permissions Seeder...');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('Cleaning up existing permissions and roles...');
        $legacy = new LegacyPermissionMigrator();
        $legacy->removeDuplicatePermissionRows($this->command);
        $legacy->runLegacyRenamesAndMerges($this->command);

        $permissionGroups = require __DIR__ . '/Data/permission_groups.php';

        $this->command->info('Syncing permissions...');
        $catalogSync = new PermissionCatalogSynchronizer();
        $newCatalogPermissionNames = $catalogSync->upsertAndDiscoverNewNames($permissionGroups);

        $this->command->info('Cleaning up old permission group names...');
        $legacy->runLegacyRenamesAndMerges($this->command);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roleMatrix = (new DefaultRoleMatrixBuilder())->build();
        (new BuiltInRoleBootstrapper())->bootstrap($this->command, $roleMatrix);

        $grants = new PostCatalogPermissionGrants();
        $grants->grantNewCatalogPermissionsToAdmins($this->command, $newCatalogPermissionNames);
        $grants->grantInheritedPermissionsToNonAdminRoles($this->command);
        $grants->ensureMasterSuperAdminHasAllPermissions($this->command);

        $this->command->info('Roles and Permissions Seeder completed successfully!');
    }
}
