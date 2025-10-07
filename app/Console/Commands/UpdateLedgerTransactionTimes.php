<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateLedgerTransactionTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:update-transaction-times {--force : Force update without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update ledger transaction_date to match created_at in Asia/Colombo timezone';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating all ledger transaction dates to Asia/Colombo timezone...');
        
        $allLedgers = Ledger::all();
        $this->info("Found {$allLedgers->count()} ledger entries to process.");
        
        $force = $this->option('force');
        
        if (!$force && !$this->confirm('This will update ALL ledger transaction_date fields to match their created_at time in Asia/Colombo timezone. Continue?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
        
        $updatedCount = 0;
        
        foreach ($allLedgers as $ledger) {
            try {
                // Get created_at time and convert to Asia/Colombo
                $createdAt = Carbon::parse($ledger->created_at);
                $colomboTime = $createdAt->setTimezone('Asia/Colombo');
                
                // Show what we're doing
                $this->line("Updating ledger ID {$ledger->id}:");
                $this->line("  From: {$ledger->transaction_date}");
                $this->line("  To:   {$colomboTime->format('Y-m-d H:i:s')}");
                
                // Update the transaction_date
                $ledger->update([
                    'transaction_date' => $colomboTime
                ]);
                
                $updatedCount++;
                
            } catch (\Exception $e) {
                $this->error("Failed to update ledger ID {$ledger->id}: " . $e->getMessage());
            }
        }
        
        $this->info("Successfully updated {$updatedCount} ledger entries.");
        
        // Also update the UnifiedLedgerService to ensure future entries use correct timezone
        $this->info("Note: Make sure UnifiedLedgerService properly handles timezone conversion for new entries.");
        
        return Command::SUCCESS;
    }
}