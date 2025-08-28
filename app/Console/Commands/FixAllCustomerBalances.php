<?php

// app/Console/Commands/FixAllCustomerBalances.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;

class FixAllCustomerBalances extends Command
{
    protected $signature = 'customer:fix-balances';
    protected $description = 'Recalculate and fix all customer current_balance';

    public function handle()
    {
        $customers = Customer::all();

        foreach ($customers as $customer) {
            $old = $customer->current_balance;
            if ($customer->id == 1) {
                $customer->current_balance = 0;
            } else {
                $customer->current_balance = $customer->getCurrentDueAttribute();
            }
            $customer->saveQuietly();
            $this->info("Fixed {$customer->full_name}: {$old} → {$customer->current_balance}");
        }

        $this->info('✅ All customer balances fixed.');
    }
}
