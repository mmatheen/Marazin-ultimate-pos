<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Ledger;

class FixOpeningBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:opening-balance {customer_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing opening balance ledger entries for customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->argument('customer_id');
        
        if ($customerId) {
            // Fix specific customer
            $customer = Customer::find($customerId);
            if (!$customer) {
                $this->error("Customer with ID {$customerId} not found.");
                return 1;
            }
            $this->fixCustomerOpeningBalance($customer);
        } else {
            // Fix all customers
            $customers = Customer::where('id', '!=', 1) // Exclude Walk-In Customer
                ->where('opening_balance', '!=', 0)
                ->get();
                
            $this->info("Found " . $customers->count() . " customers with opening balances to fix.");
            
            foreach ($customers as $customer) {
                $this->fixCustomerOpeningBalance($customer);
            }
        }
        
        $this->info("Opening balance fix completed!");
        return 0;
    }
    
    private function fixCustomerOpeningBalance($customer)
    {
        $this->info("Processing customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})");
        $this->info("Opening Balance: {$customer->opening_balance}");
        $this->info("Current Balance: {$customer->current_balance}");
        
        // Show all customer details for debugging
        $this->info("Customer Data: " . json_encode($customer->toArray()));
        
        // Check if opening balance entry exists
        $existingEntry = Ledger::where('user_id', $customer->id)
            ->where('contact_type', 'customer')
            ->where('transaction_type', 'opening_balance')
            ->first();
            
        if ($existingEntry) {
            $this->warn("Opening balance entry already exists:");
            $this->info("Debit: {$existingEntry->debit}, Credit: {$existingEntry->credit}, Balance: {$existingEntry->balance}");
        } else {
            $this->info("No opening balance entry found");
        }
        
        // Show current ledger entries
        $ledgerEntries = Ledger::where('user_id', $customer->id)
            ->where('contact_type', 'customer')
            ->orderBy('transaction_date', 'asc')
            ->get();
            
        $this->info("Current ledger entries:");
        foreach ($ledgerEntries as $entry) {
            $this->info("- Date: {$entry->transaction_date}, Type: {$entry->transaction_type}, Debit: {$entry->debit}, Credit: {$entry->credit}, Balance: {$entry->balance}");
        }
        
        if ($customer->opening_balance != 0) {
            $customer->syncOpeningBalanceToLedger();
            $this->info("✓ Opening balance synced to ledger");
        } else {
            $this->warn("Opening balance is 0, nothing to sync");
        }
        
        $this->line("");
    }
}
