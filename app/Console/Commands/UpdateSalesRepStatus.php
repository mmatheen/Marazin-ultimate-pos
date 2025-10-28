<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalesRep;
use Carbon\Carbon;

class UpdateSalesRepStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales-rep:update-status 
                            {--dry-run : Show what would be updated without making changes}
                            {--days-warning=3 : Days before expiry to warn about}
                            {--force : Force update even if no changes detected}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically update sales rep assignment statuses based on dates (active/expired/upcoming)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Sales Rep Status Update...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $warningDays = (int) $this->option('days-warning');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get all assignments except cancelled ones
        $assignments = SalesRep::with(['user', 'subLocation', 'route'])
            ->where('status', '!=', SalesRep::STATUS_CANCELLED)
            ->get();

        if ($assignments->isEmpty()) {
            $this->warn('📭 No assignments found to process.');
            return Command::SUCCESS;
        }

        $this->info("📊 Processing {$assignments->count()} assignments...");
        $this->newLine();

        $stats = [
            'total' => $assignments->count(),
            'updated' => 0,
            'active' => 0,
            'expired' => 0,
            'upcoming' => 0,
            'expiring_soon' => 0,
            'unchanged' => 0,
        ];

        // Create progress bar
        $progressBar = $this->output->createProgressBar($assignments->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $updatedAssignments = [];
        $expiringSoonAssignments = [];

        foreach ($assignments as $assignment) {
            $oldStatus = $assignment->status;
            $newStatus = $assignment->getCalculatedStatus();

            // Check if assignment is expiring soon (for active assignments only)
            if ($newStatus === SalesRep::STATUS_ACTIVE && $assignment->isExpiringSoon($warningDays)) {
                $expiringSoonAssignments[] = $assignment;
                $stats['expiring_soon']++;
            }

            // Update statistics
            $stats[$newStatus]++;

            // Check if status needs updating
            if ($oldStatus !== $newStatus || $force) {
                if (!$dryRun) {
                    $assignment->update(['status' => $newStatus]);
                }

                $updatedAssignments[] = [
                    'assignment' => $assignment,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ];
                $stats['updated']++;
            } else {
                $stats['unchanged']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults($stats, $updatedAssignments, $expiringSoonAssignments, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * Display the results of the status update
     */
    private function displayResults(array $stats, array $updatedAssignments, array $expiringSoonAssignments, bool $dryRun): void
    {
        // Summary statistics
        $this->info('📈 SUMMARY STATISTICS');
        $this->table([
            'Status', 'Count', 'Percentage'
        ], [
            ['Active', $stats['active'], round(($stats['active'] / $stats['total']) * 100, 1) . '%'],
            ['Expired', $stats['expired'], round(($stats['expired'] / $stats['total']) * 100, 1) . '%'],
            ['Upcoming', $stats['upcoming'], round(($stats['upcoming'] / $stats['total']) * 100, 1) . '%'],
            ['Expiring Soon', $stats['expiring_soon'], round(($stats['expiring_soon'] / $stats['total']) * 100, 1) . '%'],
            ['---', '---', '---'],
            ['Updated', $stats['updated'], round(($stats['updated'] / $stats['total']) * 100, 1) . '%'],
            ['Unchanged', $stats['unchanged'], round(($stats['unchanged'] / $stats['total']) * 100, 1) . '%'],
        ]);

        $this->newLine();

        // Show updated assignments if any
        if (!empty($updatedAssignments)) {
            $this->info($dryRun ? '🔄 WOULD BE UPDATED:' : '✅ UPDATED ASSIGNMENTS:');
            
            $tableData = [];
            foreach ($updatedAssignments as $update) {
                $assignment = $update['assignment'];
                $tableData[] = [
                    'ID' => $assignment->id,
                    'User' => $assignment->user->user_name ?? 'N/A',
                    'Location' => $assignment->subLocation->name ?? 'N/A',
                    'Route' => $assignment->route->name ?? 'N/A',
                    'Old Status' => strtoupper($update['old_status']),
                    'New Status' => strtoupper($update['new_status']),
                    'End Date' => $assignment->end_date ? Carbon::parse($assignment->end_date)->format('Y-m-d') : 'Ongoing',
                ];
            }

            $this->table([
                'ID', 'User', 'Location', 'Route', 'Old Status', 'New Status', 'End Date'
            ], $tableData);

            $this->newLine();
        }

        // Show expiring assignments if any
        if (!empty($expiringSoonAssignments)) {
            $this->warn('⚠️  ASSIGNMENTS EXPIRING SOON:');
            
            $tableData = [];
            foreach ($expiringSoonAssignments as $assignment) {
                $daysLeft = $assignment->getDaysUntilExpiry();
                $tableData[] = [
                    'ID' => $assignment->id,
                    'User' => $assignment->user->user_name ?? 'N/A',
                    'Location' => $assignment->subLocation->name ?? 'N/A',
                    'Route' => $assignment->route->name ?? 'N/A',
                    'End Date' => Carbon::parse($assignment->end_date)->format('Y-m-d'),
                    'Days Left' => $daysLeft,
                ];
            }

            $this->table([
                'ID', 'User', 'Location', 'Route', 'End Date', 'Days Left'
            ], $tableData);

            $this->newLine();
        }

        // Final message
        if ($stats['updated'] > 0) {
            if ($dryRun) {
                $this->info("🔍 Dry run completed. {$stats['updated']} assignment(s) would be updated.");
                $this->comment('💡 Run without --dry-run to apply changes.');
            } else {
                $this->info("✅ Status update completed successfully! {$stats['updated']} assignment(s) updated.");
            }
        } else {
            $this->comment('💡 All assignments are already up to date.');
        }

        if (!empty($expiringSoonAssignments)) {
            $this->warn("⚠️  {$stats['expiring_soon']} assignment(s) are expiring soon. Consider extending or reassigning them.");
        }

        $this->newLine();
        $this->comment('🕐 Last updated: ' . Carbon::now()->format('Y-m-d H:i:s'));
    }
}
