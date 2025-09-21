<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Ledger;

class CheckCustomers extends Command
{
    protected $signature = 'check:customers';
    protected $description = 'Check all customers and their balances';

    public function handle()
    {
        $customers = Customer::all();
        
        $this->info("All Customers:");
        foreach ($customers as $customer) {
            $this->info("ID: {$customer->id} - {$customer->first_name} {$customer->last_name} - Opening Balance: {$customer->opening_balance} - Current Balance: {$customer->current_balance}");
            
            // Show ledger entries
            $ledgers = Ledger::where('user_id', $customer->id)
                ->where('contact_type', 'customer')
                ->orderBy('transaction_date', 'asc')
                ->get();
                
            if ($ledgers->count() > 0) {
                $this->info("  Ledger entries:");
                foreach ($ledgers as $ledger) {
                    $this->info("  - {$ledger->transaction_date}: {$ledger->transaction_type} | Debit: {$ledger->debit} | Credit: {$ledger->credit} | Balance: {$ledger->balance}");
                }
            } else {
                $this->warn("  No ledger entries found");
            }
            $this->line("");
        }
    }
}