<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ledger;

class CleanupZeroAmountReversals extends Command
{
    protected $signature = 'cleanup:zero-reversals {--dry-run : Preview changes without executing}';
    protected $description = 'Clean up unnecessary ₹0.00 reversal entries created during sale edits';

    public function handle()
    {
        $this->info('=== Cleaning Up Zero Amount Reversal Entries ===');
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            if (!$isDryRun) {
                DB::beginTransaction();
            }

            // Find reversal entries with 0 amounts
            $zeroReversals = Ledger::where('notes', 'LIKE', 'REVERSAL: Sale Edit - Payment ₹0.00%')
                ->orWhere(function($query) {
                    $query->where('notes', 'LIKE', 'REVERSAL:%')
                          ->where('debit', 0)
                          ->where('credit', 0);
                })
                ->get();

            $this->info("Found {$zeroReversals->count()} zero amount reversal entries to clean up.");

            if ($zeroReversals->count() === 0) {
                $this->info('✅ No zero amount reversal entries found. Database is clean!');
                return 0;
            }

            foreach ($zeroReversals as $reversal) {
                $this->line("Processing Reversal Entry ID: {$reversal->id}");
                $this->line("- Reference: {$reversal->reference_no}");
                $this->line("- Type: {$reversal->transaction_type}");
                $this->line("- Debit: ₹{$reversal->debit}, Credit: ₹{$reversal->credit}");
                $this->line("- Notes: {$reversal->notes}");
                
                if (!$isDryRun) {
                    $reversal->delete();
                    $this->info("✅ Deleted reversal entry ID: {$reversal->id}");
                } else {
                    $this->info("✅ Would delete reversal entry ID: {$reversal->id}");
                }
            }

            if (!$isDryRun) {
                DB::commit();
            }

            $this->info('=== Cleanup Complete! ===');
            $this->info("✅ " . ($isDryRun ? "Would clean up" : "Cleaned up") . " {$zeroReversals->count()} zero amount reversal entries");
            $this->info('🎉 Your ledger is now optimized and clean!');

            if ($isDryRun) {
                $this->warn('This was a dry run. To actually execute the cleanup, run:');
                $this->warn('php artisan cleanup:zero-reversals');
            }

        } catch (\Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            $this->error("❌ Error during cleanup: " . $e->getMessage());
            $this->error("Transaction rolled back. No changes made.");
            return 1;
        }

        return 0;
    }
}