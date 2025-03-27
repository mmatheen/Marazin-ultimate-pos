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

        // Define Permissions with Groups
        $permissions = [
            // user management
            'user-management' => [
                'create user', 'edit user', 'view user', 'delete user'
            ],

            'role-management' => [
                'create role', 'edit role', 'view role', 'delete role'
            ],

            'role & permission-management' => [
                'create role & permission', 'edit role & permission', 'view role & permission', 'delete role & permission'
            ],
            'sales-commission-agent-management' => [
                'create sales-commission-agent', 'edit sales-commission-agent', 'view sales-commission-agent', 'delete sales-commission-agent'
            ],

            // contact management
            'supplier-management' => [
                'create supplier', 'edit supplier', 'view supplier', 'delete supplier'
            ],
            'customer-management' => [
                'create customer', 'edit customer', 'view customer', 'delete customer'
            ],
            'customer-group-management' => [
                'create customer-group', 'edit customer-group', 'view customer-group', 'delete customer-group'
            ],

            // product management
            'unit-management' => [
                'create unit', 'edit unit', 'view unit', 'delete unit'
            ],
            'brand-management' => [
                'create brand', 'edit brand', 'view brand', 'delete brand'
            ],

            'main-category-management' => [
                'create main-category', 'edit main-category', 'view main-category', 'delete main-category'
            ],

            'sub-catagory-management' => [
                'create sub-category', 'edit sub-catagory', 'view sub-catagory', 'delete sub-catagory'
            ],

            'warranty-management' => [
                'create warranty', 'edit warranty', 'view warranty', 'delete warranty'
            ],
            'import-product-management' => [
                'view import-product', 'create import-product'
            ],

            // purchase management
            'product-purchase-management' => [
                'view purchase', 'add purchase', 'create purchase', 'edit purchase'
            ],
            // purchase-return management
            'product-purchase-return-management' => [
                'view purchase-return', 'add purchase-return', 'create purchase-return', 'edit purchase-return'
            ],

            // expenses management
            'parent-expenses-management' => [
                'create parent-expense', 'edit parent-expense', 'view parent-expense', 'delete parent-expense'
            ],
            'child-expenses-management' => [
                'create child-expense', 'edit child-expense', 'view child-expense', 'delete child-expense'
            ],
            // stock-transfer management
            'stock-transfer-management' => [
                'view stock-transfer', 'add stock-transfer', 'create stock-transfer', 'edit stock-transfer', 'delete stock-transfer'
            ],
            // stock-transfer management
            'stock-adjustment-management' => [
                'view stock-adjustment', 'add stock-adjustment', 'create stock-adjustment', 'edit stock-adjustment', 'delete stock-adjustment'
            ],

              // setting management
              'location-management' => [
                'create location', 'edit location', 'view location', 'delete location'
            ],

              // daily-report management
              'daily-report-management' => [
                'view daily-report'
            ],

        ];

        // Create Each Permission & Assign Group
        foreach ($permissions as $group => $perms) {
            foreach ($perms as $permission) {
                Permission::create(['name' => $permission, 'group_name' => $group]);
            }
        }

        // Define Roles & Assign Permissions

        // Super Admin - All Permissions
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - Role & Warranty Management
        $admin = Role::create(['name' => 'Admin']);
        $admin->givePermissionTo(['create role', 'edit role', 'view role', 'edit warranty', 'view warranty']);

        // Manager - Read-Only Access
        $manager = Role::create(['name' => 'Manager']);
        $manager->givePermissionTo(['view role', 'view warranty']);

        // Cashier - Read-Only Access
        $cashier = Role::create(['name' => 'Cashier']);
        $cashier->givePermissionTo(['view role', 'view warranty']);
    }
}
