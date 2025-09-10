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

        // Define Permissions with Groups - Complete System Permissions
        $permissions = [
            // 1. Authentication & User Management
            '1. user-management' => [
                'create user',
                'edit user',
                'view user',
                'delete user',
                'manage user profile',
                'change user password'
            ],

            '2. role-management' => [
                'create role',
                'edit role',
                'view role',
                'delete role'
            ],

            '3. role-permission-management' => [
                'create role-permission',
                'edit role-permission',
                'view role-permission',
                'delete role-permission',
                'assign permissions'
            ],

            '4. sales-commission-agent-management' => [
                'create sales-commission-agent',
                'edit sales-commission-agent',
                'view sales-commission-agent',
                'delete sales-commission-agent'
            ],

            // 2. Contact Management
            '5. supplier-management' => [
                'create supplier',
                'edit supplier',
                'view supplier',
                'delete supplier',
                'import supplier',
                'export supplier'
            ],

            '6. customer-management' => [
                'create customer',
                'edit customer',
                'view customer',
                'delete customer',
                'import customer',
                'export customer'
            ],

            '7. customer-group-management' => [
                'create customer-group',
                'edit customer-group',
                'view customer-group',
                'delete customer-group'
            ],

            // 3. Product Management
            '8. product-management' => [
                'create product',
                'add product',
                'edit product',
                'view product',
                'delete product',
                'import product',
                'export product',
                'view product history',
                'manage opening stock',
                'view product details',
                'manage product variations',
                'duplicate product'
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

            '12. sub-category-management' => [
                'create sub-category',
                'edit sub-category',
                'view sub-category',
                'delete sub-category'
            ],

            '13. warranty-management' => [
                'create warranty',
                'edit warranty',
                'view warranty',
                'delete warranty'
            ],

            '14. variation-management' => [
                'create variation',
                'edit variation',
                'view variation',
                'delete variation',
                'create variation-title',
                'edit variation-title',
                'view variation-title',
                'delete variation-title'
            ],

            // 4. Sales Management
            '15. sale-management' => [
                'view all sales',
                'view own sales',
                'create sale',
                'edit sale',
                'delete sale',
                'view sale details',
                'access pos',
                'print sale invoice',
                'email sale invoice'
            ],

            '16. sale-return-management' => [
                'view sale-return',
                'create sale-return',
                'edit sale-return',
                'delete sale-return',
                'print return invoice'
            ],

            '17. pos-management' => [
                'access pos',
                'create job-ticket',
                'create quotation',
                'save draft',
                'suspend sale',
                'credit sale',
                'card payment',
                'cheque payment',
                'multiple payment methods',
                'cash payment',
                'discount application'
            ],

            // 5. Purchase Management
            '18. purchase-management' => [
                'view purchase',
                'create purchase',
                'edit purchase',
                'delete purchase',
                'print purchase order',
                'email purchase order'
            ],

            '19. purchase-return-management' => [
                'view purchase-return',
                'create purchase-return',
                'edit purchase-return',
                'delete purchase-return'
            ],

            // 6. Payment Management
            '20. payment-management' => [
                'view payments',
                'create payment',
                'edit payment',
                'delete payment',
                'bulk sale payment',
                'bulk purchase payment',
                'view payment history'
            ],

            // 7. Expense Management
            '21. parent-expense-management' => [
                'create parent-expense',
                'edit parent-expense',
                'view parent-expense',
                'delete parent-expense'
            ],

            '22. child-expense-management' => [
                'create child-expense',
                'edit child-expense',
                'view child-expense',
                'delete child-expense'
            ],

            // 8. Stock Management
            '23. stock-transfer-management' => [
                'view stock-transfer',
                'create stock-transfer',
                'edit stock-transfer',
                'delete stock-transfer',
                'approve stock-transfer'
            ],

            '24. stock-adjustment-management' => [
                'view stock-adjustment',
                'create stock-adjustment',
                'edit stock-adjustment',
                'delete stock-adjustment'
            ],

            '25. opening-stock-management' => [
                'view opening-stock',
                'create opening-stock',
                'edit opening-stock',
                'import opening-stock',
                'export opening-stock'
            ],

            // 9. Inventory Management
            '26. inventory-management' => [
                'view inventory',
                'adjust inventory',
                'view stock levels',
                'low stock alerts',
                'batch management',
                'imei management'
            ],

            // 10. Location Management
            '27. location-management' => [
                'create location',
                'edit location',
                'view location',
                'delete location',
                'manage location settings'
            ],

            // 11. Discount Management
            '28. discount-management' => [
                'view discount',
                'create discount',
                'edit discount',
                'delete discount'
            ],

            // 12. Sales Rep Management
            '29. sales-rep-management' => [
                'view sales-rep',
                'create sales-rep',
                'edit sales-rep',
                'delete sales-rep',
                'assign routes',
                'view assigned routes',
                'manage sales targets',
                'view sales rep performance'
            ],

            // 13. Route Management
            '30. route-management' => [
                'view routes',
                'create route',
                'edit route',
                'delete route',
                'assign cities to route'
            ],

            // 14. Vehicle Management
            '31. vehicle-management' => [
                'view vehicles',
                'create vehicle',
                'edit vehicle',
                'delete vehicle',
                'track vehicle',
                'assign vehicle to location'
            ],

            // 15. Reports Management
            '32. report-management' => [
                'view daily-report',
                'view sales-report',
                'view purchase-report',
                'view stock-report',
                'view profit-loss-report',
                'view payment-report',
                'view customer-report',
                'view supplier-report',
                'view expense-report',
                'export reports'
            ],

            // 16. Settings Management
            '33. settings-management' => [
                'view settings',
                'edit business-settings',
                'edit tax-settings',
                'edit email-settings',
                'edit sms-settings',
                'backup database',
                'restore database',
                'manage currencies',
                'manage selling-price-groups'
            ],

            // 17. Print & Label Management
            '34. print-label-management' => [
                'print product-labels',
                'print barcodes',
                'design labels',
                'batch print labels'
            ],

            // 18. Dashboard Management
            '35. dashboard-management' => [
                'view dashboard',
                'view sales-analytics',
                'view purchase-analytics',
                'view stock-analytics',
                'view financial-overview'
            ],

            // 19. Import/Export Management
            '36. import-export-management' => [
                'import products',
                'export products',
                'import customers',
                'export customers',
                'import suppliers',
                'export suppliers',
                'import opening-stock',
                'export opening-stock',
                'download templates'
            ],

            // 20. Profile Management
            '37. profile-management' => [
                'view own-profile',
                'edit own-profile',
                'change own-password'
            ],

            // 21. Master Admin Management (Only for Master Super Admin)
            '38. master-admin-management' => [
                'access master admin panel',
                'manage all locations',
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
                'manage system maintenance'
            ]
        ];

        // Insert or update permissions first
        foreach ($permissions as $group => $perms) {
            foreach ($perms as $permission) {
                Permission::updateOrCreate(
                    ['name' => $permission, 'guard_name' => 'web'],
                    ['group_name' => $group]
                );
            }
        }



        // Clear cache again after permissions inserted
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Roles & give permissions
        $roles = [
            // MASTER SUPER ADMIN - Has ALL permissions and cannot be restricted
            'Master Super Admin' => Permission::all()->pluck('name')->toArray(),
            
            // REGULAR SUPER ADMIN - Can be customized per shop/location (excludes master admin permissions)
            'Super Admin' => array_filter(Permission::all()->pluck('name')->toArray(), function($permission) {
                $masterAdminPermissions = [
                    'access master admin panel',
                    'manage all locations', 
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
                    'manage system maintenance'
                ];
                return !in_array($permission, $masterAdminPermissions);
            }),
            
            'Admin' => [
                // User Management
                'create user', 'edit user', 'view user', 'delete user',
                'create role', 'edit role', 'view role', 'delete role',
                'create role-permission', 'edit role-permission', 'view role-permission', 'delete role-permission',
                
                // Product Management
                'create product', 'edit product', 'view product', 'delete product', 'import product', 'export product',
                'create unit', 'edit unit', 'view unit', 'delete unit',
                'create brand', 'edit brand', 'view brand', 'delete brand',
                'create main-category', 'edit main-category', 'view main-category', 'delete main-category',
                'create sub-category', 'edit sub-category', 'view sub-category', 'delete sub-category',
                'create warranty', 'edit warranty', 'view warranty', 'delete warranty',
                
                // Contact Management
                'create customer', 'edit customer', 'view customer', 'delete customer',
                'create supplier', 'edit supplier', 'view supplier', 'delete supplier',
                'create customer-group', 'edit customer-group', 'view customer-group', 'delete customer-group',
                
                // Sales & Purchase
                'view all sales', 'create sale', 'edit sale', 'view sale details', 'access pos',
                'view purchase', 'create purchase', 'edit purchase',
                
                // Location & Settings
                'create location', 'edit location', 'view location', 'delete location',
                'view settings', 'edit business-settings',
                
                // Reports
                'view daily-report', 'view sales-report', 'view purchase-report', 'view dashboard'
            ],
            
            'Manager' => [
                // View permissions for most modules
                'view user', 'view role', 'view product', 'view customer', 'view supplier',
                'view all sales', 'view purchase', 'view sale-return', 'view purchase-return',
                'view stock-transfer', 'view stock-adjustment', 'view inventory',
                
                // Limited create/edit permissions
                'create sale', 'edit sale', 'access pos',
                'create customer', 'edit customer',
                'create purchase', 'edit purchase',
                
                // Reports access
                'view daily-report', 'view sales-report', 'view purchase-report', 
                'view stock-report', 'view dashboard',
                
                // POS permissions
                'access pos', 'cash payment', 'card payment', 'credit sale',
                'create job-ticket', 'create quotation', 'save draft'
            ],
            
            'Cashier' => [
                // POS focused permissions
                'access pos', 'create sale', 'view own sales',
                'create sale-return', 'view sale-return',
                'view product', 'view customer', 'create customer',
                
                // Payment methods
                'cash payment', 'card payment', 'cheque payment', 'credit sale',
                'multiple payment methods',
                
                // POS features
                'create job-ticket', 'create quotation', 'save draft', 'suspend sale',
                'discount application',
                
                // Limited inventory
                'view inventory', 'view stock levels',
                
                // Profile
                'view own-profile', 'edit own-profile', 'change own-password'
            ],
            
            'Sales Rep' => [
                // Customer focused
                'view customer', 'create customer', 'edit customer',
                'view assigned routes',
                
                // Sales
                'view own sales', 'create sale', 'access pos',
                'cash payment', 'card payment', 'credit sale',
                
                // Products
                'view product', 'view product details',
                
                // Limited POS
                'access pos', 'save draft', 'suspend sale',
                
                // Profile
                'view own-profile', 'edit own-profile', 'change own-password'
            ],
            
            'Inventory Manager' => [
                // Product management
                'view product', 'create product', 'edit product',
                'manage opening stock', 'import product', 'export product',
                
                // Stock management
                'view stock-transfer', 'create stock-transfer', 'edit stock-transfer',
                'view stock-adjustment', 'create stock-adjustment', 'edit stock-adjustment',
                'view inventory', 'adjust inventory', 'view stock levels', 'batch management',
                
                // Purchase related
                'view purchase', 'create purchase', 'edit purchase',
                'view purchase-return', 'create purchase-return',
                
                // Suppliers
                'view supplier', 'create supplier', 'edit supplier',
                
                // Reports
                'view stock-report', 'view purchase-report', 'view dashboard'
            ],
            
            'Accountant' => [
                // Financial permissions
                'view payments', 'create payment', 'edit payment',
                'bulk sale payment', 'bulk purchase payment',
                
                // Expenses
                'view parent-expense', 'create parent-expense', 'edit parent-expense',
                'view child-expense', 'create child-expense', 'edit child-expense',
                
                // Sales & Purchase (view only)
                'view all sales', 'view purchase', 'view sale-return', 'view purchase-return',
                
                // Reports
                'view daily-report', 'view sales-report', 'view purchase-report',
                'view profit-loss-report', 'view payment-report', 'export reports',
                
                // Customers & Suppliers (limited)
                'view customer', 'view supplier',
                
                // Dashboard
                'view dashboard', 'view financial-overview'
            ]
        ];


        // Now assign roles after all permissions exist
        foreach ($roles as $roleName => $rolePermissions) {
            // Generate role key from name
            $roleKey = strtolower(str_replace(' ', '_', $roleName));
            
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['key' => $roleKey]
            );
            
            // Update key if it doesn't exist
            if (!$role->key) {
                $role->update(['key' => $roleKey]);
            }
            
            // Set special flags for Master Super Admin
            if ($roleName === 'Master Super Admin') {
                $role->update([
                    'key' => $roleKey,
                    'is_system_role' => true,
                    'is_master_role' => true,
                    'bypass_location_scope' => true
                ]);
            }
            
            // Set flags for regular Super Admin (can be restricted per location)
            if ($roleName === 'Super Admin') {
                $role->update([
                    'key' => $roleKey,
                    'is_system_role' => false,
                    'is_master_role' => false,
                    'bypass_location_scope' => false // Can be changed per shop
                ]);
            }
            
            $role->syncPermissions($rolePermissions);
        }
    }
}
