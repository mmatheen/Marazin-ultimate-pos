<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VerifyPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that new permissions have been added correctly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║           Permission Verification Report                 ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Check for new permissions
        $newPermissions = [
            'create sale-order',
            'view sale-order',
            'manage cheque',
            'view cheque',
            'approve cheque',
            'reject cheque',
            'view cheque-management'
        ];

        $this->info('🔍 Checking for New Permissions:');
        $this->newLine();

        $allFound = true;
        foreach ($newPermissions as $permName) {
            $exists = Permission::where('name', $permName)->exists();
            if ($exists) {
                $this->line("  ✅ {$permName}");
            } else {
                $this->line("  ❌ {$permName} - NOT FOUND");
                $allFound = false;
            }
        }
        $this->newLine();

        if (!$allFound) {
            $this->warn('⚠️  Some permissions are missing. Run: php artisan permissions:update');
            $this->newLine();
            return 1;
        }

        // Check role assignments
        $this->info('📋 Role Assignment Report:');
        $this->newLine();

        $roles = Role::with('permissions')->get();

        $headers = ['Role', 'Sale Order Perms', 'Cheque Perms', 'Total Permissions'];
        $rows = [];

        foreach ($roles as $role) {
            $rolePerms = $role->permissions->pluck('name')->toArray();

            $saleOrderPerms = array_intersect($rolePerms, ['create sale-order', 'view sale-order']);
            $chequePerms = array_intersect($rolePerms, [
                'manage cheque',
                'view cheque',
                'approve cheque',
                'reject cheque',
                'view cheque-management'
            ]);

            $rows[] = [
                $role->name,
                count($saleOrderPerms) . '/2',
                count($chequePerms) . '/5',
                count($rolePerms)
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        // Detailed breakdown for each role
        $this->info('📊 Detailed Breakdown:');
        $this->newLine();

        foreach ($roles as $role) {
            $rolePerms = $role->permissions->pluck('name')->toArray();

            $this->info("Role: {$role->name}");

            // Sale Order permissions
            $this->line('  Sale Order:');
            foreach (['create sale-order', 'view sale-order'] as $perm) {
                $status = in_array($perm, $rolePerms) ? '✅' : '❌';
                $this->line("    {$status} {$perm}");
            }

            // Cheque permissions
            $this->line('  Cheque Management:');
            foreach (['manage cheque', 'view cheque', 'approve cheque', 'reject cheque', 'view cheque-management'] as $perm) {
                $status = in_array($perm, $rolePerms) ? '✅' : '❌';
                $this->line("    {$status} {$perm}");
            }

            $this->newLine();
        }

        // Summary
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                    Verification Summary                   ');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $totalRoles = $roles->count();
        $rolesWithSaleOrder = $roles->filter(function($role) {
            return $role->hasPermissionTo('create sale-order') || $role->hasPermissionTo('view sale-order');
        })->count();

        $rolesWithCheque = $roles->filter(function($role) {
            return $role->hasPermissionTo('view cheque-management');
        })->count();

        $this->info("  Total Roles: {$totalRoles}");
        $this->info("  Roles with Sale Order Access: {$rolesWithSaleOrder}");
        $this->info("  Roles with Cheque Management Access: {$rolesWithCheque}");
        $this->newLine();

        $this->info('✅ Verification Complete!');
        $this->newLine();

        return 0;
    }
}
