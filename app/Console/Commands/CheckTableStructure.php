<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckTableStructure extends Command
{
    protected $signature = 'debug:table-structure';
    protected $description = 'Check table structures for debugging';

    public function handle()
    {
        $this->info('📋 CHECKING TABLE STRUCTURES...');
        $this->newLine();
        
        // Check ledger table structure
        $this->info('LEDGER TABLE COLUMNS:');
        $ledgerColumns = Schema::getColumnListing('ledgers');
        foreach ($ledgerColumns as $column) {
            $this->line("- {$column}");
        }
        $this->newLine();
        
        // Check ledger data directly
        $this->info('LEDGER TRANSACTION TYPES (Direct Query):');
        try {
            $types = DB::table('ledgers')
                ->selectRaw('transaction_type, COUNT(*) as count')
                ->groupBy('transaction_type')
                ->get();
            
            foreach ($types as $type) {
                $this->line("{$type->transaction_type}: {$type->count} records");
            }
        } catch (\Exception $e) {
            $this->error("Error querying ledger types: " . $e->getMessage());
        }
        $this->newLine();
        
        // Check payments table
        $this->info('PAYMENTS TABLE COLUMNS:');
        $paymentColumns = Schema::getColumnListing('payments');
        foreach ($paymentColumns as $column) {
            $this->line("- {$column}");
        }
        $this->newLine();
        
        // Check sales table
        $this->info('SALES TABLE COLUMNS:');
        $salesColumns = Schema::getColumnListing('sales');
        foreach ($salesColumns as $column) {
            $this->line("- {$column}");
        }
        $this->newLine();
        
        // Check sales_returns table
        $this->info('SALES_RETURNS TABLE COLUMNS:');
        $returnsColumns = Schema::getColumnListing('sales_returns');
        foreach ($returnsColumns as $column) {
            $this->line("- {$column}");
        }
        
        return Command::SUCCESS;
    }
}