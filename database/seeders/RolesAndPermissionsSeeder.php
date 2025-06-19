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
            '1. user-management' => [
                'create user',
                'edit user',
                'view user',
                'delete user'
            ],

            '2. role-management' => [
                'create role',
                'edit role',
                'view role',
                'delete role'
            ],

            '3. role & permission-management' => [
                'create role & permission',
                'edit role & permission',
                'view role & permission',
                'delete role & permission'
            ],
            '4. sales-commission-agent-management' => [
                'create sales-commission-agent',
                'edit sales-commission-agent',
                'view sales-commission-agent',
                'delete sales-commission-agent'
            ],

            // contact management
            '5. supplier-management' => [
                'create supplier',
                'edit supplier',
                'view supplier',
                'delete supplier'
            ],
            '6. customer-management' => [
                'create customer',
                'edit customer',
                'view customer',
                'delete customer'
            ],
            '7. customer-group-management' => [
                'create customer-group',
                'edit customer-group',
                'view customer-group',
                'delete customer-group'
            ],

            // product management
            '8. product-management' => [
                'create product',
                'add product',
                'edit product',
                'view product',
                'delete product',
                'Add & Edit Opening Stock product',
                'product Full History',
                'show one product details'
            ],


            '9. unit-management' => [
                'create unit',
                'edit unit',
                'view unit',
                'delete unit'
            ],
            '10. brand-management' => [
                'create brand',
                'edit brand',
                'view brand',
                'delete brand'
            ],

            '11. main-category-management' => [
                'create main-category',
                'edit main-category',
                'view main-category',
                'delete main-category'
            ],

            '12. sub-catagory-management' => [
                'create sub-category',
                'edit sub-catagory',
                'view sub-category',
                'delete sub-category'
            ],

            '13. warranty-management' => [
                'create warranty',
                'edit warranty',
                'view warranty',
                'delete warranty'
            ],
            '14. import-product-management' => [
                'view import-product',
                'create import-product'
            ],

            // sale management
            '15. sale-management' => [
                'view sale',
                'add sale',
                'edit sale',
                'pos page'
            ],
            // sale-return management
            '16. sale-return-management' => [
                'view return-sale',
                'add return-sale'
            ],
            // bulk-payment-management
            '17. bulk-payment-management' => [
                'add bulk sale payment',
                'add bulk purchase payment'
            ],
            // purchase management
            '18. product-purchase-management' => [
                'view purchase',
                'add purchase',
                'create purchase',
                'edit purchase'
            ],
            // purchase-return management
            '19. product-purchase-return-management' => [
                'view purchase-return',
                'add purchase-return',
                'create purchase-return',
                'edit purchase-return'
            ],
            // expenses management
            '20. parent-expenses-management' => [
                'create parent-expense',
                'edit parent-expense',
                'view parent-expense',
                'delete parent-expense'
            ],
            '21. child-expenses-management' => [
                'create child-expense',
                'edit child-expense',
                'view child-expense',
                'delete child-expense'
            ],
            // stock-transfer management
            '22. stock-transfer-management' => [
                'view stock-transfer',
                'add stock-transfer',
                'create stock-transfer',
                'edit stock-transfer',
                'delete stock-transfer'
            ],
            // stock-transfer management
            '23. stock-adjustment-management' => [
                'view stock-adjustment',
                'add stock-adjustment',
                'create stock-adjustment',
                'edit stock-adjustment',
                'delete stock-adjustment'
            ],
            // setting management
            '24. location-management' => [
                'create location',
                'edit location',
                'view location',
                'delete location'
            ],
            // daily-report management
            '25. daily-report-management' => [
                'view daily-report'
            ],
            // daily-report management
            '26. product-discount-management' => [
                'view product-discount',
                'create product-discount',
                'edit product-discount',
                'delete product-discount'
            ],
        
            // pos button management
            '27. pos-button-management' => [
                'job ticket',
                'quotation',
                'draft',
                'suspend',
                'credit sale',
                'card',  
                'cheque',
                'multiple pay',
                'cash',
              
            ],

        ];

        // Create Each Permission & Assign Group using firstOrCre cb ate
        foreach ($permissions as $group => $perms) {
            foreach ($perms as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['group_name' => $group]
            );
            }
        }

          // Roles & give permissions
        $roles = [
            'Super Admin' => Permission::all()->pluck('name')->toArray(),
            'Manager' => [
                'view user',
                'view role',
                'view product',
                'view sale',
                'add sale',
                'view purchase',
                'view daily-report'
            ],
            'Cashier' => [
                'pos page',
                'add sale',
                'view sale',
                'add return-sale',
                'view return-sale',
                'job ticket',
                'quotation',
                'draft',
                'suspend',
                'credit sale',
                'card',
                'cheque',
                'multiple pay',
                'cash'
            ],
            'Admin' => [
                'create user',
                'edit user',
                'view user',
                'delete user',
                'create role',
                'edit role',
                'view role',
                'delete role',
                'create product',
                'edit product',
                'view product',
                'delete product',
                'job ticket'
            ]
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
