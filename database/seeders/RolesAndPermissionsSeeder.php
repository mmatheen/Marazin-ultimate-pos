<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Roles and Permissions Seeder...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clean existing data first
        $this->command->info('Cleaning up existing permissions and roles...');
        $this->cleanupExistingData();

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
                'duplicate product',
                'edit batch prices'
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
                'create sale-order',
                'view sale-order',
                'edit sale-order',
                'delete sale-order',
                'convert sale-order to invoice',
                'suspend sale',
                'credit sale',
                'card payment',
                'cheque payment',
                'multiple payment methods',
                'cash payment',
                'discount application',
                'select retail price',
                'select wholesale price',
                'select special price',
                'select max retail price',
                'edit unit price in pos',
                'edit discount in pos',
                'use free quantity'
            ],

            // 5. Purchase Management
            '18. purchase-management' => [
                'view purchase',
                'create purchase',
                'edit purchase',
                'delete purchase',
                'print purchase order',
                'email purchase order',
                // Supplier Free Claim permissions
                'view supplier claims',
                'create supplier claims',
                'receive supplier claims',
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
                'view payment history',
                'manage cheque',
                'view cheque',
                'approve cheque',
                'reject cheque',
                'view cheque-management'
            ],

            // 7. Expense Management
            '21. expense-management' => [
                'create expense',
                'edit expense',
                'view expense',
                'delete expense',
                'approve expense',
                'export expense'
            ],

            '22. parent-expense-management' => [
                'create parent-expense',
                'edit parent-expense',
                'view parent-expense',
                'delete parent-expense'
            ],

            '23. child-expense-management' => [
                'create child-expense',
                'edit child-expense',
                'view child-expense',
                'delete child-expense'
            ],

            // 8. Stock Management
            '24. stock-transfer-management' => [
                'view stock-transfer',
                'create stock-transfer',
                'edit stock-transfer',
                'delete stock-transfer',
                'approve stock-transfer'
            ],

            '25. stock-adjustment-management' => [
                'view stock-adjustment',
                'create stock-adjustment',
                'edit stock-adjustment',
                'delete stock-adjustment'
            ],

            '26. opening-stock-management' => [
                'view opening-stock',
                'create opening-stock',
                'edit opening-stock',
                'import opening-stock',
                'export opening-stock'
            ],

            // 9. Inventory Management
            '27. inventory-management' => [
                'view inventory',
                'adjust inventory',
                'view stock levels',
                'low stock alerts',
                'batch management',
                'imei management'
            ],

            // 10. Location Management
            '28. location-management' => [
                'create location',
                'create sublocation',
                'edit location',
                'view location',
                'delete location',
                'manage location settings'
            ],

            // 11. Discount Management
            '29. discount-management' => [
                'view discount',
                'create discount',
                'edit discount',
                'delete discount'
            ],

            // 12. Sales Rep Management
            '30. sales-rep-management' => [
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
            '31. route-management' => [
                'view routes',
                'create route',
                'edit route',
                'delete route',
                'assign cities to route'
            ],

            // 14. Vehicle Management
            '32. vehicle-management' => [
                'view vehicles',
                'create vehicle',
                'edit vehicle',
                'delete vehicle',
                'track vehicle',
                'assign vehicle to location'
            ],

            // 15. Reports Management
            '33. report-management' => [
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
            '34. settings-management' => [
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
            '35. print-label-management' => [
                'print product-labels',
                'print barcodes',
                'design labels',
                'batch print labels'
            ],

            // 18. Dashboard Management
            '36. dashboard-management' => [
                'view dashboard',
                'view sales-analytics',
                'view purchase-analytics',
                'view stock-analytics',
                'view financial-overview'
            ],

            // 19. Import/Export Management
            '37. import-export-management' => [
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
            '38. profile-management' => [
                'view own-profile',
                'edit own-profile',
                'change own-password'
            ],

            // 21. Master Admin Management (Only for Master Super Admin)
            '39. master-admin-management' => [
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
        $this->command->info('Syncing permissions...');

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $permission) {
                try {
                    Permission::updateOrCreate(
                        ['name' => $permission, 'guard_name' => 'web'],
                        ['group_name' => $group]
                    );
                } catch (\Exception $e) {
                    $this->command->warn("Permission already exists or error: {$permission}");
                    continue;
                }
            }
        }

        // Clean up old permissions that might have incorrect group names
        $this->command->info('Cleaning up old permission group names...');
        $this->cleanupOldPermissions();

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');



        // Clear cache again after permissions inserted
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Roles & give permissions
        $roles = [
            // MASTER SUPER ADMIN - Has ALL permissions and cannot be restricted
            'Master Super Admin' => Permission::all()->pluck('name')->toArray(),

            // REGULAR SUPER ADMIN - Can be customized per shop/location (excludes master admin permissions)
            'Super Admin' => array_filter(Permission::all()->pluck('name')->toArray(), function ($permission) {
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

            'Sales Rep' => [
                // Customer focused
                'view customer',
                'create customer',
                'edit customer',
                'view assigned routes',

                // Sales
                'view own sales',
                'create sale',
                'access pos',
                'cash payment',
                'card payment',
                'credit sale',

                // Products
                'view product',
                'view product details',

                // Locations (needed for POS)
                'view location',

                // Categories and Brands (needed for POS filtering)
                'view main-category',
                'view sub-category',
                'view brand',

                // Pricing Permissions - Default Access to Retail and MRP
                'select retail price',
                'select max retail price',

                // Limited POS
                'access pos',
                'save draft',
                'suspend sale',

                // Sale Orders - Limited Access
                'create sale-order',
                'view sale-order',
                'edit sale-order',

                // Profile
                'view own-profile',
                'edit own-profile',
                'change own-password'
            ],

        ];


        // Now assign roles after all permissions exist
        foreach ($roles as $roleName => $rolePermissions) {
            // Generate role key from name
            $roleKey = strtolower(str_replace(' ', '_', $roleName));

            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['key' => $roleKey]
            );

            $isNewRole = $role->wasRecentlyCreated;

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
                // Master Super Admin ALWAYS gets ALL permissions - never customisable
                $role->syncPermissions($rolePermissions);
                continue;
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

            if ($isNewRole) {
                // First-time creation: set the full default permission set
                $role->syncPermissions($rolePermissions);
                $this->command->info("Role '{$roleName}' created with default permissions.");
            } else {
                // Role already exists: DO NOT sync (would wipe manual customisations).
                // New permissions will be added by assignNewPermissionsToExistingRoles() below.
                $this->command->info("Role '{$roleName}' already exists - preserving custom permissions.");
            }
        }

        // Smart assignment of new permissions to existing roles
        $this->assignNewPermissionsToExistingRoles();

        // Safety net: guarantee Master Super Admin always has ALL permissions
        $this->ensureMasterSuperAdminHasAllPermissions();

        $this->command->info('Roles and Permissions Seeder completed successfully!');
    }

    /**
     * Safety net: ensure Master Super Admin has every permission in the system.
     * Runs after syncPermissions to catch any edge-case misses (e.g. cache issues,
     * permissions added to the DB outside this seeder, etc.).
     */
    private function ensureMasterSuperAdminHasAllPermissions()
    {
        $this->command->info('Verifying Master Super Admin has ALL permissions...');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $masterRole = Role::where('name', 'Master Super Admin')->first();
        if (!$masterRole) {
            $this->command->warn('Master Super Admin role not found. Skipping safety-net check.');
            return;
        }

        $allPermissions    = Permission::all();
        $currentPermIds    = $masterRole->permissions->pluck('id')->toArray();
        $missingPermissions = $allPermissions->filter(fn($p) => !in_array($p->id, $currentPermIds));

        if ($missingPermissions->isEmpty()) {
            $this->command->info('Master Super Admin already has all permissions. ✓');
            return;
        }

        foreach ($missingPermissions as $permission) {
            $masterRole->givePermissionTo($permission);
            $this->command->warn("Added missing permission to Master Super Admin: '{$permission->name}'");
        }

        $this->command->info("Safety net added {$missingPermissions->count()} missing permission(s) to Master Super Admin.");
    }

    /**
     * Assign newly added permissions to existing roles based on their related permissions
     */
    private function assignNewPermissionsToExistingRoles()
    {
        $this->command->info('Assigning new permissions to existing roles...');

        // Define new permissions and their related parent permissions
        $newPermissionLogic = [
            'create sale-order' => ['save draft', 'create sale'],
            'view sale-order' => ['save draft', 'view all sales', 'view own sales'],
            'edit sale-order' => ['save draft', 'create sale'],
            'manage cheque' => ['cheque payment', 'create payment'],
            'view cheque' => ['cheque payment', 'view payments'],
            'approve cheque' => ['cheque payment', 'edit payment'],
            'reject cheque' => ['cheque payment', 'edit payment'],
            'view cheque-management' => ['cheque payment', 'view payments'],

            // Supplier Free Claim (Bonus Free Qty) permissions
            // Any role that can view/create purchases or supplier management gets claim access
            'view supplier claims'    => ['view purchase', 'create purchase', 'view supplier'],
            'create supplier claims'  => ['create purchase', 'edit purchase'],
            'receive supplier claims' => ['create purchase', 'edit purchase', 'view purchase'],

            // Free quantity usage in POS
            'use free quantity' => ['access pos', 'create sale'],
        ];

        // Get all roles
        $allRoles = Role::with('permissions')->get();

        foreach ($allRoles as $role) {
            // Skip Master Super Admin (always gets ALL permissions via syncPermissions above)
            // Skip Super Admin (Master Admin will manually decide which new permissions to grant)
            if (in_array($role->name, ['Master Super Admin', 'Super Admin'])) {
                continue;
            }

            $currentPermissions = $role->permissions->pluck('name')->toArray();
            $permissionsToAdd = [];

            foreach ($newPermissionLogic as $newPermission => $relatedPermissions) {
                // Check if this permission already exists in the role
                if (in_array($newPermission, $currentPermissions)) {
                    continue;
                }

                // Check if role has any of the related permissions
                foreach ($relatedPermissions as $relatedPermission) {
                    if (in_array($relatedPermission, $currentPermissions)) {
                        $permissionsToAdd[] = $newPermission;
                        $this->command->info("Adding '{$newPermission}' to role '{$role->name}' (has '{$relatedPermission}')");
                        break;
                    }
                }
            }

            // Add the new permissions to the role
            if (!empty($permissionsToAdd)) {
                foreach ($permissionsToAdd as $permissionName) {
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission && !$role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }

        $this->command->info('New permissions assigned successfully!');
    }

    /**
     * Clean up existing permissions and roles data
     */
    private function cleanupExistingData()
    {
        // Remove duplicate permissions based on name and guard_name
        $duplicatePermissions = DB::table('permissions')
            ->select('name', 'guard_name', DB::raw('COUNT(*) as count'))
            ->groupBy('name', 'guard_name')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicatePermissions as $duplicate) {
            // Keep the first one, delete the rest
            $permissionsToDelete = DB::table('permissions')
                ->where('name', $duplicate->name)
                ->where('guard_name', $duplicate->guard_name)
                ->orderBy('id')
                ->skip(1)
                ->pluck('id');

            if ($permissionsToDelete->count() > 0) {
                // Remove from role_has_permissions first
                DB::table('role_has_permissions')
                    ->whereIn('permission_id', $permissionsToDelete)
                    ->delete();

                // Remove from model_has_permissions
                DB::table('model_has_permissions')
                    ->whereIn('permission_id', $permissionsToDelete)
                    ->delete();

                // Now delete the permissions
                DB::table('permissions')
                    ->whereIn('id', $permissionsToDelete)
                    ->delete();

                $this->command->warn("Removed duplicate permissions for: {$duplicate->name}");
            }
        }

        // Clean up old permission group names
        $this->cleanupOldPermissions();
    }

    /**
     * Clean up old permissions with incorrect group names
     */
    private function cleanupOldPermissions()
    {
        $oldGroupMappings = [
            '1. user management' => '1. user-management',
            '2. role management' => '2. role-management',
            '3. role & permission-management' => '3. role-permission-management',
            '12. sub-catagory-management' => '12. sub-category-management',
            '18. product-purchase-management' => '18. purchase-management',
            '19. product-purchase-return-management' => '19. purchase-return-management',
            '22. stock-adjustment-management' => '24. stock-adjustment-management',
            '23. stock-adjustment-management' => '24. stock-adjustment-management',
            '27. pos-button-management' => '17. pos-management',
            '26. product-discount-management' => '28. discount-management'
        ];

        foreach ($oldGroupMappings as $oldGroup => $newGroup) {
            $updated = DB::table('permissions')
                ->where('group_name', $oldGroup)
                ->update(['group_name' => $newGroup]);

            if ($updated > 0) {
                $this->command->info("Updated {$updated} permissions from '{$oldGroup}' to '{$newGroup}'");
            }
        }

        // Fix specific permission name inconsistencies
        $permissionMappings = [
            'edit sub-catagory' => 'edit sub-category',
            'view sub-catagory' => 'view sub-category',
            'delete sub-catagory' => 'delete sub-category',
            'Add & Edit Opening Stock product' => 'manage opening stock',
            'product Full History' => 'view product history',
            'show one product details' => 'view product details',
            'all sale' => 'view all sales',
            'own sale' => 'view own sales',
            'pos page' => 'access pos',
            'view return-sale' => 'view sale-return',
            'add return-sale' => 'create sale-return',
            'add stock-transfer' => 'create stock-transfer',
            'add stock-adjustment' => 'create stock-adjustment',
            'add purchase' => 'create purchase',
            'add purchase-return' => 'create purchase-return',
            'view import-product' => 'import product',
            'create import-product' => 'import product'
        ];

        foreach ($permissionMappings as $oldName => $newName) {
            // Check if the new name already exists
            $newExists = DB::table('permissions')
                ->where('name', $newName)
                ->where('guard_name', 'web')
                ->exists();

            $oldExists = DB::table('permissions')
                ->where('name', $oldName)
                ->where('guard_name', 'web')
                ->exists();

            if ($oldExists && $newExists) {
                // Both exist, we need to merge them
                $oldPermissionId = DB::table('permissions')
                    ->where('name', $oldName)
                    ->where('guard_name', 'web')
                    ->value('id');

                $newPermissionId = DB::table('permissions')
                    ->where('name', $newName)
                    ->where('guard_name', 'web')
                    ->value('id');

                // MySQL-safe approach: Get IDs first, then update
                // Get role assignments that need to be moved
                $roleAssignments = DB::table('role_has_permissions')
                    ->where('permission_id', $oldPermissionId)
                    ->select('role_id')
                    ->get();

                foreach ($roleAssignments as $assignment) {
                    // Check if this role already has the new permission
                    $exists = DB::table('role_has_permissions')
                        ->where('role_id', $assignment->role_id)
                        ->where('permission_id', $newPermissionId)
                        ->exists();

                    if (!$exists) {
                        // Insert new assignment
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $assignment->role_id,
                            'permission_id' => $newPermissionId
                        ]);
                    }
                }

                // Remove old role assignments
                DB::table('role_has_permissions')
                    ->where('permission_id', $oldPermissionId)
                    ->delete();

                // Get model assignments that need to be moved
                $modelAssignments = DB::table('model_has_permissions')
                    ->where('permission_id', $oldPermissionId)
                    ->select('model_type', 'model_id')
                    ->get();

                foreach ($modelAssignments as $assignment) {
                    // Check if this model already has the new permission
                    $exists = DB::table('model_has_permissions')
                        ->where('model_type', $assignment->model_type)
                        ->where('model_id', $assignment->model_id)
                        ->where('permission_id', $newPermissionId)
                        ->exists();

                    if (!$exists) {
                        // Insert new assignment
                        DB::table('model_has_permissions')->insert([
                            'permission_id' => $newPermissionId,
                            'model_type' => $assignment->model_type,
                            'model_id' => $assignment->model_id
                        ]);
                    }
                }

                // Remove old model assignments
                DB::table('model_has_permissions')
                    ->where('permission_id', $oldPermissionId)
                    ->delete();

                // Delete the old permission
                DB::table('permissions')->where('id', $oldPermissionId)->delete();

                $this->command->info("Merged permission '{$oldName}' into '{$newName}'");
            } elseif ($oldExists && !$newExists) {
                // Only old exists, safe to rename
                DB::table('permissions')
                    ->where('name', $oldName)
                    ->where('guard_name', 'web')
                    ->update(['name' => $newName]);

                $this->command->info("Renamed permission from '{$oldName}' to '{$newName}'");
            }
        }

        // Handle specific duplicates that need to be merged
        $duplicatesToMerge = [
            'add product' => 'create product',
            'add sale' => 'create sale'
        ];

        foreach ($duplicatesToMerge as $oldName => $newName) {
            $this->mergePermissions($oldName, $newName);
        }
    }

    /**
     * Merge two permissions - move all assignments from old to new and delete old
     */
    private function mergePermissions($oldName, $newName)
    {
        $oldPermission = DB::table('permissions')
            ->where('name', $oldName)
            ->where('guard_name', 'web')
            ->first();

        $newPermission = DB::table('permissions')
            ->where('name', $newName)
            ->where('guard_name', 'web')
            ->first();

        if ($oldPermission && $newPermission) {
            // Get role assignments that need to be moved (don't already exist with new permission)
            $roleAssignmentsToMove = DB::table('role_has_permissions as rhp1')
                ->where('rhp1.permission_id', $oldPermission->id)
                ->whereNotExists(function ($query) use ($newPermission) {
                    $query->select('*')
                        ->from('role_has_permissions as rhp2')
                        ->where('rhp2.permission_id', $newPermission->id)
                        ->whereRaw('rhp2.role_id = rhp1.role_id');
                })
                ->pluck('role_id');

            // Update role assignments
            foreach ($roleAssignmentsToMove as $roleId) {
                DB::table('role_has_permissions')
                    ->where('permission_id', $oldPermission->id)
                    ->where('role_id', $roleId)
                    ->update(['permission_id' => $newPermission->id]);
            }

            // Remove remaining old role assignments
            DB::table('role_has_permissions')
                ->where('permission_id', $oldPermission->id)
                ->delete();

            // Get model assignments that need to be moved (don't already exist with new permission)
            $modelAssignmentsToMove = DB::table('model_has_permissions as mhp1')
                ->where('mhp1.permission_id', $oldPermission->id)
                ->whereNotExists(function ($query) use ($newPermission) {
                    $query->select('*')
                        ->from('model_has_permissions as mhp2')
                        ->where('mhp2.permission_id', $newPermission->id)
                        ->whereRaw('mhp2.model_type = mhp1.model_type')
                        ->whereRaw('mhp2.model_id = mhp1.model_id');
                })
                ->select(['model_type', 'model_id'])
                ->get();

            // Update model assignments
            foreach ($modelAssignmentsToMove as $assignment) {
                DB::table('model_has_permissions')
                    ->where('permission_id', $oldPermission->id)
                    ->where('model_type', $assignment->model_type)
                    ->where('model_id', $assignment->model_id)
                    ->update(['permission_id' => $newPermission->id]);
            }

            // Remove remaining old model assignments
            DB::table('model_has_permissions')
                ->where('permission_id', $oldPermission->id)
                ->delete();

            // Delete the old permission
            DB::table('permissions')->where('id', $oldPermission->id)->delete();

            $this->command->info("Merged permission '{$oldName}' into '{$newName}'");
        } elseif ($oldPermission && !$newPermission) {
            // Just rename if new doesn't exist
            DB::table('permissions')
                ->where('id', $oldPermission->id)
                ->update(['name' => $newName]);

            $this->command->info("Renamed permission from '{$oldName}' to '{$newName}'");
        }
    }
}
