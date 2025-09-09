<?php

// app/Console/Commands/FixCustomerBalances.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;

class FixCustomerBalances extends Command
{
    protected $signature = 'customer:fix-balances';
    protected $description = 'Fix all customer current_balance to match calculated due';

    public function handle()
    {
        $customers = Customer::where('id', '!=', 1)->get();

        foreach ($customers as $customer) {
            $old = $customer->current_balance;
            $customer->recalculateCurrentBalance();
            $this->info("Fixed {$customer->full_name}: {$old} → {$customer->current_balance}");
        }

        // Force Walk-in to 0
        $walkIn = Customer::find(1);
        if ($walkIn) {
            $walkIn->current_balance = 0;
            $walkIn->saveQuietly();
            $this->info("Walk-in Customer balance reset to 0");
        }

        $this->info('All customer balances fixed.');
    }
}
