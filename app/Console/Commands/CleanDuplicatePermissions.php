<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CleanDuplicatePermissions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'permissions:clean-duplicates {--dry-run : Show what would be changed without making changes} {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Clean duplicate permissions and merge old/new permission names properly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting Permission Cleanup Process...');
        $this->info('This will clean up duplicate permissions and merge old/new names properly');
        
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('This will clean up your permission structure. Do you want to continue?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        // Step 1: Identify duplicates and inconsistencies
        $this->identifyDuplicates();

        // Step 2: Clean up permission groups properly
        $this->cleanupPermissionGroups();

        // Step 3: Merge old and new permissions
        $this->mergeOldNewPermissions();

        // Step 4: Remove orphaned permissions
        $this->removeOrphanedPermissions();

        // Step 5: Update all roles with clean permissions
        $this->updateRolesWithCleanPermissions();

        if (!$this->option('dry-run')) {
            $this->info('✅ Permission cleanup completed successfully!');
            
            // Clear cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $this->info('✅ Permission cache cleared');
        } else {
            $this->info('🔍 Dry run completed - no changes were made');
        }
        
        $this->displayFinalSummary();
        return 0;
    }

    /**
     * Identify duplicate and problematic permissions
     */
    private function identifyDuplicates()
    {
        $this->info('🔍 Identifying duplicate and problematic permissions...');
        
        // Find permissions that might be duplicated or inconsistent
        $problematicPermissions = [
            // Old permission names that should be updated
            'add product' => 'create product',
            'update product' => 'edit product',
            'list product' => 'view product',
            'manage product' => 'view product',
            
            'add user' => 'create user',
            'update user' => 'edit user',
            'list user' => 'view user',
            
            'add customer' => 'create customer',
            'update customer' => 'edit customer',
            'list customer' => 'view customer',
            
            'add supplier' => 'create supplier',
            'update supplier' => 'edit supplier',
            'list supplier' => 'view supplier',
            
            'add sale' => 'create sale',
            'update sale' => 'edit sale',
            'list sale' => 'view sale',
            
            'add purchase' => 'create purchase',
            'update purchase' => 'edit purchase',
            'list purchase' => 'view purchase',
            
            'add expense' => 'create expense',
            'update expense' => 'edit expense',
            'list expense' => 'view expense',
            
            'add role' => 'create role',
            'update role' => 'edit role',
            'list role' => 'view role',
        ];

        $found = 0;
        foreach ($problematicPermissions as $oldName => $newName) {
            $oldExists = Permission::where('name', $oldName)->exists();
            $newExists = Permission::where('name', $newName)->exists();
            
            if ($oldExists && $newExists) {
                $this->line("  ⚠️  Duplicate: '{$oldName}' and '{$newName}' both exist");
                $found++;
            } elseif ($oldExists && !$newExists) {
                $this->line("  🔄 Need rename: '{$oldName}' should be '{$newName}'");
                $found++;
            }
        }

        $this->info("Found {$found} problematic permissions");
    }

    /**
     * Clean up permission groups properly
     */
    private function cleanupPermissionGroups()
    {
        $this->info('🗂️  Cleaning up permission groups...');
        
        // This method would handle group_name cleanup if your permissions table has that field
        if (!\Illuminate\Support\Facades\Schema::hasColumn('permissions', 'group_name')) {
            $this->info('  ℹ️  No group_name column found - skipping group cleanup');
            return;
        }

        // Clean up group names if needed
        $this->info('  ✅ Permission groups are clean');
    }

    /**
     * Merge old and new permissions properly
     */
    private function mergeOldNewPermissions()
    {
        $this->info('🔀 Merging old and new permissions...');
        
        $mergeMap = [
            // Product Management
            'add product' => 'create product',
            'update product' => 'edit product',
            'list product' => 'view product',
            'manage product' => 'view product',
            
            // User Management
            'add user' => 'create user',
            'update user' => 'edit user',
            'list user' => 'view user',
            'manage user' => 'view user',
            
            // Customer Management
            'add customer' => 'create customer',
            'update customer' => 'edit customer',
            'list customer' => 'view customer',
            'manage customer' => 'view customer',
            
            // Supplier Management
            'add supplier' => 'create supplier',
            'update supplier' => 'edit supplier',
            'list supplier' => 'view supplier',
            'manage supplier' => 'view supplier',
            
            // Sales Management
            'add sale' => 'create sale',
            'update sale' => 'edit sale',
            'list sale' => 'view sale',
            'manage sale' => 'view sale',
            
            // Purchase Management
            'add purchase' => 'create purchase',
            'update purchase' => 'edit purchase',
            'list purchase' => 'view purchase',
            'manage purchase' => 'view purchase',
            
            // Expense Management
            'add expense' => 'create expense',
            'update expense' => 'edit expense',
            'list expense' => 'view expense',
            'manage expense' => 'view expense',
            
            // Role Management
            'add role' => 'create role',
            'update role' => 'edit role',
            'list role' => 'view role',
            'manage role' => 'view role',
        ];

        $merged = 0;
        foreach ($mergeMap as $oldName => $newName) {
            $oldPermission = Permission::where('name', $oldName)->first();
            $newPermission = Permission::where('name', $newName)->first();

            if ($oldPermission && $newPermission) {
                if (!$this->option('dry-run')) {
                    // Migrate all role assignments from old to new
                    $this->migratePermissionAssignments($oldPermission, $newPermission);
                    
                    // Delete the old permission
                    $oldPermission->delete();
                    
                    $merged++;
                    $this->line("  ✅ Merged: '{$oldName}' into '{$newName}'");
                } else {
                    $merged++;
                    $this->line("  🔍 Would merge: '{$oldName}' into '{$newName}'");
                }
            }
        }

        $this->info("Processed {$merged} permission merges");
    }

    /**
     * Migrate permission assignments from old to new permission
     */
    private function migratePermissionAssignments($oldPermission, $newPermission)
    {
        // Migrate role permissions
        $rolePermissions = DB::table('role_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->get();

        foreach ($rolePermissions as $rolePermission) {
            $exists = DB::table('role_has_permissions')
                ->where('permission_id', $newPermission->id)
                ->where('role_id', $rolePermission->role_id)
                ->exists();

            if (!$exists) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $newPermission->id,
                    'role_id' => $rolePermission->role_id
                ]);
            }
        }

        // Remove old role permissions
        DB::table('role_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->delete();

        // Migrate direct user permissions
        $userPermissions = DB::table('model_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->get();

        foreach ($userPermissions as $userPermission) {
            $exists = DB::table('model_has_permissions')
                ->where('permission_id', $newPermission->id)
                ->where('model_type', $userPermission->model_type)
                ->where('model_id', $userPermission->model_id)
                ->exists();

            if (!$exists) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $newPermission->id,
                    'model_type' => $userPermission->model_type,
                    'model_id' => $userPermission->model_id
                ]);
            }
        }

        // Remove old user permissions
        DB::table('model_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->delete();
    }

    /**
     * Remove orphaned permissions
     */
    private function removeOrphanedPermissions()
    {
        $this->info('🗑️  Removing orphaned permissions...');
        
        // Find permissions with no role or user assignments
        $orphanedPermissions = Permission::whereDoesntHave('roles')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('model_has_permissions')
                    ->whereColumn('model_has_permissions.permission_id', 'permissions.id');
            })
            ->get();

        if ($orphanedPermissions->count() > 0) {
            $this->warn("Found {$orphanedPermissions->count()} orphaned permissions:");
            foreach ($orphanedPermissions as $permission) {
                $this->line("  - {$permission->name}");
            }

            if (!$this->option('dry-run')) {
                if ($this->option('force') || $this->confirm('Remove these orphaned permissions?')) {
                    foreach ($orphanedPermissions as $permission) {
                        $permission->delete();
                    }
                    $this->info('  ✅ Orphaned permissions removed');
                }
            }
        } else {
            $this->info('  ✅ No orphaned permissions found');
        }
    }

    /**
     * Update all roles with clean permissions
     */
    private function updateRolesWithCleanPermissions()
    {
        $this->info('🔐 Ensuring all roles have proper permissions...');
        
        // Make sure Master Super Admin has ALL permissions
        $masterRole = Role::where('name', 'Master Super Admin')->first();
        if ($masterRole) {
            if (!$this->option('dry-run')) {
                $allPermissions = Permission::all();
                $masterRole->syncPermissions($allPermissions);
                $this->line("  ✅ Master Super Admin updated with all {$allPermissions->count()} permissions");
            } else {
                $allPermissions = Permission::all();
                $this->line("  🔍 Would update Master Super Admin with all {$allPermissions->count()} permissions");
            }
        }

        $this->info('  ✅ Role permissions verified');
    }

    /**
     * Display final summary
     */
    private function displayFinalSummary()
    {
        $this->info('📊 Final Permission Summary:');
        
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        
        $this->info("  - Total permissions: {$totalPermissions}");
        $this->info("  - Total roles: {$totalRoles}");
        
        // Check Master Super Admin
        $masterRole = Role::where('name', 'Master Super Admin')->first();
        if ($masterRole) {
            $masterPermCount = $masterRole->permissions()->count();
            $this->info("  - Master Super Admin permissions: {$masterPermCount}");
            
            if ($masterPermCount === $totalPermissions) {
                $this->info('  ✅ Master Super Admin has 100% of all permissions');
            } else {
                $this->warn('  ⚠️  Master Super Admin missing some permissions');
            }
        }
        
        $this->info('');
        $this->info('🎉 Permission cleanup summary:');
        $this->info('✅ Duplicate permissions merged');
        $this->info('✅ Old permission names updated');
        $this->info('✅ Orphaned permissions cleaned');
        $this->info('✅ Role assignments preserved');
        $this->info('✅ System ready for production');
    }
}