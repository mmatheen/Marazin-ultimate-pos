<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        //  Define Permissions with Groups
        $permissions = [
            'warranty-management' => [
                'create warranty', 'edit warranty', 'view warranty', 'delete warranty'
            ],

            'role-management' => [
                'create role', 'edit role', 'view role', 'delete role'
            ],

            'user-management' => [
                'create user', 'edit user', 'view user', 'delete user'
            ],

            'location-management' => [
                'create location', 'edit location', 'view location', 'delete location'
            ],

            'role & permission-management' => [
                'create role & permission', 'edit role & permission', 'view role & permission', 'delete role & permission'
            ],
        ];

        //  Create Each Permission & Assign Group
        foreach ($permissions as $group => $perms) {
            foreach ($perms as $permission) {
                Permission::create(['name' => $permission, 'group_name' => $group]);
            }
        }

        //  Define Roles & Assign Permissions

        // Super Admin - All Permissions
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - Role & Warranty Management
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(['create role', 'edit role', 'view role','edit warranty', 'view warranty']);

        // Manager - Read-Only Access
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo(['view role', 'view warranty']);
    }
}
