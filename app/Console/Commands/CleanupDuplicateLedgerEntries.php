<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ledger;
use App\Models\Purchase;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupDuplicateLedgerEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:cleanup-duplicates {--dry-run : Run in dry-run mode without making changes} {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate ledger entries while preserving data integrity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Ledger Duplicate Cleanup Tool');
        $this->info('=================================');
        
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        if ($dryRun) {
            $this->warn('🔍 Running in DRY-RUN mode - no changes will be made');
        }

        // Step 1: Analyze duplicates
        $this->info('📊 Analyzing duplicate ledger entries...');
        
        $duplicates = $this->findDuplicateEntries();
        
        if ($duplicates->isEmpty()) {
            $this->info('✅ No duplicate entries found! Ledger is clean.');
            return 0;
        }

        $this->info("Found {$duplicates->count()} groups of duplicate entries:");
        
        // Display analysis
        $this->displayDuplicateAnalysis($duplicates);

        // Confirmation
        if (!$force && !$dryRun) {
            if (!$this->confirm('⚠️  Do you want to proceed with cleanup? This will mark duplicates as "cleaned"')) {
                $this->info('❌ Cleanup cancelled.');
                return 0;
            }
        }

        // Perform cleanup
        if (!$dryRun) {
            $result = $this->performCleanup($duplicates);
            $this->displayCleanupResults($result);
        } else {
            $this->info('🔍 DRY-RUN: Would clean up duplicates but no changes made.');
        }

        return 0;
    }

    /**
     * Find duplicate ledger entries
     */
    private function findDuplicateEntries()
    {
        return DB::table('ledgers')
            ->select([
                'contact_id',
                'contact_type', 
                'reference_no',
                'transaction_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('GROUP_CONCAT(id ORDER BY created_at) as entry_ids'),
                DB::raw('GROUP_CONCAT(CONCAT(debit, "|", credit) ORDER BY created_at) as amounts'),
                DB::raw('GROUP_CONCAT(created_at ORDER BY created_at) as created_dates')
            ])
            ->where('status', 'active')
            ->groupBy(['contact_id', 'contact_type', 'reference_no', 'transaction_type'])
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('reference_no')
            ->orderBy('transaction_type')
            ->get();
    }

    /**
     * Display duplicate analysis
     */
    private function displayDuplicateAnalysis($duplicates)
    {
        $table = [];
        
        foreach ($duplicates as $dup) {
            $table[] = [
                'Reference' => $dup->reference_no,
                'Type' => $dup->transaction_type,
                'Count' => $dup->count,
                'Contact' => $dup->contact_type . ':' . $dup->contact_id,
                'Entry IDs' => $dup->entry_ids
            ];
        }
        
        $this->table([
            'Reference', 'Type', 'Count', 'Contact', 'Entry IDs'
        ], $table);
    }

    /**
     * Perform the actual cleanup
     */
    private function performCleanup($duplicates)
    {
        $totalRemoved = 0;
        $totalKept = 0;
        $errors = [];

        DB::transaction(function () use ($duplicates, &$totalRemoved, &$totalKept, &$errors) {
            
            foreach ($duplicates as $duplicate) {
                try {
                    $this->line("🔍 Processing: {$duplicate->reference_no} - {$duplicate->transaction_type}");
                    
                    $entryIds = explode(',', $duplicate->entry_ids);
                    
                    // Get entries ordered by creation date
                    $entries = Ledger::whereIn('id', $entryIds)
                        ->where('status', 'active')
                        ->orderBy('created_at')
                        ->get();
                        
                    if ($entries->isEmpty()) {
                        $this->warn("   ⚠️  No active entries found for {$duplicate->reference_no}");
                        continue;
                    }
                    
                    // Keep the first (earliest) entry
                    $keepEntry = $entries->first();
                    $removeEntries = $entries->slice(1);
                    
                    $this->info("   ✅ Keeping entry ID: {$keepEntry->id} (created: {$keepEntry->created_at})");
                    $totalKept++;
                    
                    // Mark others as reversed (cleaned)
                    foreach ($removeEntries as $entry) {
                        $entry->update([
                            'status' => 'reversed',
                            'notes' => ($entry->notes ?: '') . " [CLEANED: Duplicate removed on " . Carbon::now()->format('Y-m-d H:i:s') . "]"
                        ]);
                        
                        $this->info("   🗑️  Cleaned entry ID: {$entry->id} (created: {$entry->created_at})");
                        $totalRemoved++;
                    }
                    
                } catch (\Exception $e) {
                    $error = "Error processing {$duplicate->reference_no}: " . $e->getMessage();
                    $errors[] = $error;
                    $this->error("   ❌ " . $error);
                }
            }
        });

        // Log the cleanup
        Log::info('Ledger duplicate cleanup completed', [
            'total_cleaned' => $totalRemoved,
            'total_kept' => $totalKept,
            'errors' => $errors,
            'cleanup_timestamp' => Carbon::now()
        ]);

        return [
            'removed' => $totalRemoved,
            'kept' => $totalKept,
            'errors' => $errors
        ];
    }

    /**
     * Display cleanup results
     */
    private function displayCleanupResults($result)
    {
        $this->info('✅ Cleanup completed successfully!');
        $this->info('📊 Summary:');
        $this->info("   - Entries cleaned: {$result['removed']}");
        $this->info("   - Entries kept: {$result['kept']}");
        
        if (!empty($result['errors'])) {
            $this->warn("   - Errors: " . count($result['errors']));
            foreach ($result['errors'] as $error) {
                $this->error("     • " . $error);
            }
        }

        // Verify cleanup
        $this->info('🔍 Verifying cleanup...');
        $remainingDuplicates = $this->findDuplicateEntries();
        
        if ($remainingDuplicates->isEmpty()) {
            $this->info('✅ Verification successful! No duplicate active entries remain.');
        } else {
            $this->warn("⚠️  Warning: {$remainingDuplicates->count()} duplicate groups still exist.");
            $this->warn('These may require manual review.');
        }

        // Show purchase summary
        $this->displayPurchaseSummary();
    }

    /**
     * Display purchase cleanup summary
     */
    private function displayPurchaseSummary()
    {
        $this->info('📋 Purchase Cleanup Summary:');
        
        $summary = DB::table('ledgers as l')
            ->leftJoin('suppliers as s', function($join) {
                $join->on('l.contact_id', '=', 's.id')
                     ->where('l.contact_type', '=', 'supplier');
            })
            ->select([
                'l.reference_no',
                's.first_name as supplier_name',
                DB::raw('COUNT(CASE WHEN l.status = "active" THEN 1 END) as active_entries'),
                DB::raw('COUNT(CASE WHEN l.status = "reversed" THEN 1 END) as cleaned_entries')
            ])
            ->where('l.reference_no', 'LIKE', 'PUR%')
            ->groupBy('l.reference_no', 's.first_name')
            ->havingRaw('cleaned_entries > 0')
            ->orderBy('l.reference_no')
            ->get();

        $table = [];
        foreach ($summary as $row) {
            $table[] = [
                'Reference' => $row->reference_no,
                'Supplier' => $row->supplier_name ?: 'N/A',
                'Active' => $row->active_entries,
                'Cleaned' => $row->cleaned_entries
            ];
        }

        if (!empty($table)) {
            $this->table(['Reference', 'Supplier', 'Active', 'Cleaned'], $table);
        }

        $this->info('💡 Notes:');
        $this->info('- Cleaned entries preserve audit trail with "reversed" status');
        $this->info('- Future duplicates are prevented by the updated system');
        $this->info('- Check logs for detailed cleanup information');
    }
}