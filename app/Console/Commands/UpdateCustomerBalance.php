<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Ledger;

class UpdateCustomerBalance extends Command
{
    protected $signature = 'update:customer-balance {customer_id}';
    protected $description = 'Update customer current_balance to match ledger balance';

    public function handle()
    {
        $customerId = $this->argument('customer_id');
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            $this->error("Customer not found!");
            return 1;
        }
        
        $this->info("Current customer balance: {$customer->current_balance}");
        
        // Get the latest ledger balance
        $latestLedgerBalance = Ledger::getLatestBalance($customerId, 'customer');
        $this->info("Latest ledger balance: {$latestLedgerBalance}");
        
        // Update customer balance
        $customer->current_balance = $latestLedgerBalance;
        $customer->save();
        
        $this->info("✓ Customer balance updated to: {$customer->current_balance}");
        
        // Also recalculate using the model's method
        $customer->recalculateCurrentBalance();
        $customer->refresh();
        
        $this->info("✓ After recalculation: {$customer->current_balance}");
        
        return 0;
    }
}