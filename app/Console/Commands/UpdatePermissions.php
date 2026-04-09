<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdatePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:update {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update permissions and intelligently assign them to existing roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║      Permission Update for Sale Order & Cheque Mgmt     ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Show what will be added
        $this->info('📋 New Permissions to be Added:');
        $this->newLine();

        $this->line('  POS Management:');
        $this->line('    • create sale-order - Create sale orders from POS');
        $this->line('    • view sale-order - View sale order list');
        $this->newLine();

        $this->line('  Payment Management (Cheque):');
        $this->line('    • manage cheque - Manage cheque payments');
        $this->line('    • view cheque - View cheque details');
        $this->line('    • approve cheque - Approve cheque payments');
        $this->line('    • reject cheque - Reject cheque payments');
        $this->line('    • view cheque-management - Access cheque management page');
        $this->newLine();

        $this->line('  SMS Management:');
        $this->line('    • sms.send - Send manual SMS messages');
        $this->newLine();

        $this->info('✨ Smart Assignment Logic:');
        $this->line('  New permissions will be automatically assigned to roles that have related permissions.');
        $this->line('  For example: If a role has "save draft", it will get "create sale-order".');
        $this->newLine();

        $this->warn('⚠️  Important Notes:');
        $this->line('  • This is ADDITIVE ONLY - no permissions will be removed');
        $this->line('  • Existing permissions remain unchanged');
        $this->line('  • Safe to run multiple times');
        $this->line('  • Recommended: Backup database before running');
        $this->newLine();

        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with the permission update?', true)) {
                $this->error('Permission update cancelled.');
                return 1;
            }
            $this->newLine();
        }

        // Ask about backup
        if (!$this->option('force')) {
            $this->info('💾 Database Backup:');
            if ($this->confirm('Do you want to create a database backup first?', true)) {
                $this->info('Creating backup...');
                try {
                    Artisan::call('backup:run', ['--only-db' => true]);
                    $this->info('✅ Backup completed successfully!');
                } catch (\Exception $e) {
                    $this->error('❌ Backup failed: ' . $e->getMessage());
                    if (!$this->confirm('Continue without backup?', false)) {
                        return 1;
                    }
                }
                $this->newLine();
            }
        }

        // Run the seeder
        $this->info('🚀 Running permission update...');
        $this->newLine();

        try {
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'RolesAndPermissionsSeeder',
                '--force' => true
            ]);

            if ($exitCode === 0) {
                $this->info('✅ Permissions updated successfully!');
                $this->newLine();

                // Clear cache
                $this->info('🧹 Clearing caches...');
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('permission:cache-reset');
                $this->info('✅ Cache cleared!');
                $this->newLine();

                // Summary
                $this->info('═══════════════════════════════════════════════════════════');
                $this->info('                     Update Complete!                      ');
                $this->info('═══════════════════════════════════════════════════════════');
                $this->newLine();

                $this->info('📊 What was done:');
                $this->line('  ✓ Created 8 new permissions');
                $this->line('  ✓ Included SMS manual send permission');
                $this->line('  ✓ Assigned permissions to existing roles based on related permissions');
                $this->line('  ✓ Updated sidebar and POS page to use new permissions');
                $this->line('  ✓ Cleared all caches');
                $this->newLine();

                $this->info('🎯 Next Steps:');
                $this->line('  1. Test Sale Order functionality in POS');
                $this->line('  2. Test Cheque Management access');
                $this->line('  3. Verify user permissions are working correctly');
                $this->line('  4. Check that existing permissions still work');
                $this->newLine();

                $this->info('📖 For detailed information, see: UPDATE_PERMISSIONS.md');
                $this->newLine();

                return 0;
            } else {
                $this->error('❌ Permission update failed!');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error during update: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Please check the logs and try again.');
            return 1;
        }
    }
}
