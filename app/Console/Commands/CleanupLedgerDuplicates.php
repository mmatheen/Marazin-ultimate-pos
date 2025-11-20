<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

class CleanupLedgerDuplicates extends Command
{
    protected $signature = 'ledger:cleanup-duplicates {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Remove duplicate ledger entries safely';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No data will be deleted');
        } else {
            $this->error('⚠️  LIVE MODE - Data WILL be deleted!');
            if (!$this->confirm('Are you sure you want to proceed?')) {
                return;
            }
        }
        
        $this->newLine();
        $this->info('🧹 CLEANING UP LEDGER DUPLICATES...');
        $this->newLine();

        // Find exact duplicates
        $this->cleanExactDuplicates($dryRun);
        
        $this->newLine();
        $this->info($dryRun ? '✅ DRY RUN COMPLETE!' : '✅ CLEANUP COMPLETE!');
    }

    private function cleanExactDuplicates($dryRun)
    {
        $this->info('🔍 Finding exact duplicates...');
        
        // Find groups of exact duplicates
        $duplicateGroups = DB::select("
            SELECT reference_no, transaction_date, contact_id, transaction_type, 
                   debit, credit, COUNT(*) as count,
                   MIN(id) as keep_id
            FROM ledgers 
            GROUP BY reference_no, transaction_date, contact_id, transaction_type, debit, credit
            HAVING COUNT(*) > 1
            ORDER BY count DESC
        ");
        
        $totalDeleted = 0;
        
        foreach ($duplicateGroups as $group) {
            $this->line("Processing: Ref {$group->reference_no} | Contact {$group->contact_id} | Count: {$group->count}");
            
            // Get all IDs for this duplicate group
            $allIds = Ledger::where('reference_no', $group->reference_no)
                ->where('transaction_date', $group->transaction_date)
                ->where('contact_id', $group->contact_id)
                ->where('transaction_type', $group->transaction_type)
                ->where('debit', $group->debit)
                ->where('credit', $group->credit)
                ->pluck('id')
                ->toArray();
                
            // Keep the oldest record (smallest ID), delete the rest
            $idsToDelete = array_filter($allIds, function($id) use ($group) {
                return $id != $group->keep_id;
            });
            
            $deleteCount = count($idsToDelete);
            
            if ($deleteCount > 0) {
                $this->warn("  → Will delete {$deleteCount} duplicates, keeping ID {$group->keep_id}");
                
                if (!$dryRun) {
                    Ledger::whereIn('id', $idsToDelete)->delete();
                    $this->info("  ✅ Deleted {$deleteCount} duplicates");
                }
                
                $totalDeleted += $deleteCount;
            }
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("📊 DRY RUN SUMMARY: Would delete {$totalDeleted} duplicate records");
        } else {
            $this->info("📊 CLEANUP SUMMARY: Deleted {$totalDeleted} duplicate records");
        }
    }
}