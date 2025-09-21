<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

class FixCustomerBalance extends Command
{
    protected $signature = 'fix:customer-balance {customer_id}';
    protected $description = 'Fix customer balance by setting correct opening balance and recalculating ledger';

    public function handle()
    {
        $customerId = $this->argument('customer_id');
        
        $this->info("Fixing customer balance for customer ID: {$customerId}");
        
        DB::transaction(function () use ($customerId) {
            // Step 1: Set the customer's opening balance to 4500
            $customer = Customer::find($customerId);
            $this->info("Current opening balance: {$customer->opening_balance}");
            
            $customer->opening_balance = 4500;
            $customer->saveQuietly(); // Save without triggering events
            
            $this->info("Updated opening balance to: 4500");
            
            // Step 2: Create proper opening balance ledger entry
            // First delete the incorrect opening_balance_payment entry
            $incorrectEntry = Ledger::where('user_id', $customerId)
                ->where('transaction_type', 'opening_balance_payment')
                ->first();
                
            if ($incorrectEntry) {
                $this->info("Deleting incorrect opening_balance_payment entry");
                $incorrectEntry->delete();
            }
            
            // Create proper opening balance entry
            Ledger::create([
                'user_id' => $customerId,
                'contact_type' => 'customer',
                'transaction_date' => '2025-09-21 00:00:00', // Before other transactions
                'reference_no' => 'OPENING-' . $customerId,
                'transaction_type' => 'opening_balance',
                'debit' => 4500,
                'credit' => 0,
                'balance' => 4500,
                'notes' => 'Opening Balance for Customer: Jane Smith',
            ]);
            
            $this->info("Created proper opening balance ledger entry");
            
            // Step 3: Recalculate all balances
            Ledger::calculateBalance($customerId, 'customer');
            $this->info("Recalculated all ledger balances");
            
            // Step 4: Update customer current balance
            $customer->refresh();
            $customer->recalculateCurrentBalance();
            $this->info("Updated customer current balance");
        });
        
        $this->info("✓ Customer balance fix completed!");
        
        // Show final state
        $this->info("\nFinal ledger state:");
        $ledgerEntries = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
        foreach ($ledgerEntries as $entry) {
            $this->info("- Date: {$entry->transaction_date}, Type: {$entry->transaction_type}, Debit: {$entry->debit}, Credit: {$entry->credit}, Balance: {$entry->balance}");
        }
        
        $customer = Customer::find($customerId);
        $this->info("\nCustomer final balance: {$customer->current_balance}");
    }
}