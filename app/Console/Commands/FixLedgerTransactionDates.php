<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FixLedgerTransactionDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:fix-transaction-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix ledger transaction dates by converting created_at to Asia/Colombo timezone';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix ledger transaction dates...');
        
        // Get all ledger entries and show their current state
        $allLedgers = Ledger::orderBy('id')->get();
        
        $this->info("Total ledger entries: {$allLedgers->count()}");
        $this->info('Checking each entry...');
        
        $needFixing = [];
        
        foreach ($allLedgers as $ledger) {
            $transactionDate = Carbon::parse($ledger->transaction_date);
            $createdAt = Carbon::parse($ledger->created_at);
            $colomboTime = $createdAt->setTimezone('Asia/Colombo');
            
            // Show the current state
            $this->line("ID {$ledger->id}:");
            $this->line("  Transaction Date: {$transactionDate->format('Y-m-d H:i:s')}");
            $this->line("  Created At (UTC): {$createdAt->utc()->format('Y-m-d H:i:s')}");
            $this->line("  Created At (Colombo): {$colomboTime->format('Y-m-d H:i:s')}");
            
            // Check if transaction_date has 00:00:00 time but created_at has different time
            if ($transactionDate->format('H:i:s') === '00:00:00' && $colomboTime->format('H:i:s') !== '00:00:00') {
                $this->line("  *** NEEDS FIXING ***");
                $needFixing[] = $ledger;
            } else {
                $this->line("  OK");
            }
            $this->line("");
        }
        
        $this->info("Found " . count($needFixing) . " entries that need fixing.");
        
        if (count($needFixing) > 0) {
            if ($this->confirm('Do you want to proceed with fixing these entries?')) {
                $fixedCount = 0;
                
                foreach ($needFixing as $ledger) {
                    try {
                        $createdAt = Carbon::parse($ledger->created_at);
                        $colomboTime = $createdAt->setTimezone('Asia/Colombo');
                        
                        $this->line("Fixing ledger ID {$ledger->id}: {$ledger->transaction_date} -> {$colomboTime}");
                        
                        $ledger->update([
                            'transaction_date' => $colomboTime
                        ]);
                        
                        $fixedCount++;
                        
                    } catch (\Exception $e) {
                        $this->error("Failed to fix ledger ID {$ledger->id}: " . $e->getMessage());
                    }
                }
                
                $this->info("Successfully fixed {$fixedCount} ledger entries.");
            } else {
                $this->info("Operation cancelled.");
            }
        }
        
        return Command::SUCCESS;
    }
}
