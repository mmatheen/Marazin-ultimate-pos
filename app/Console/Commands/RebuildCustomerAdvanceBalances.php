<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\Ledger\CustomerAdvanceBalanceService;
use Illuminate\Console\Command;

class RebuildCustomerAdvanceBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:rebuild-customer-advance {customer_id? : Optional single customer ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and sync customer advance_balance from active ledger rows';

    public function handle(CustomerAdvanceBalanceService $advanceService): int
    {
        $customerId = $this->argument('customer_id');

        if ($customerId !== null) {
            $advance = $advanceService->syncCustomer((int) $customerId);
            $this->info("Customer {$customerId} synced. advance_balance={$advance}");

            return self::SUCCESS;
        }

        $count = 0;
        Customer::withoutGlobalScopes()
            ->where('id', '>', 1)
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($customers) use ($advanceService, &$count) {
                foreach ($customers as $customer) {
                    $advanceService->syncCustomer((int) $customer->id);
                    $count++;
                }
            });

        $this->info("Synced advance_balance for {$count} customers.");

        return self::SUCCESS;
    }
}

