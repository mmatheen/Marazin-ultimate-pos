<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Carbon\Carbon;

class TestCustomerOpeningBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:customer-opening-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test customer opening balance timezone handling';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing customer opening balance timezone handling...');
        
        // Show current time in different timezones
        $now = Carbon::now();
        $this->info("Current UTC time: {$now->utc()->format('Y-m-d H:i:s')}");
        $this->info("Current Asia/Colombo time: {$now->setTimezone('Asia/Colombo')->format('Y-m-d H:i:s')}");
        
        // Create a test customer with opening balance
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'mobile_no' => '1234567890',
            'opening_balance' => 1000,
            'credit_limit' => 5000,
        ]);
        
        $this->info("Created customer ID: {$customer->id}");
        
        // Check the ledger entry
        $ledgerEntry = \App\Models\Ledger::where('user_id', $customer->id)
            ->where('transaction_type', 'opening_balance')
            ->first();
            
        if ($ledgerEntry) {
            $this->info("Ledger entry created:");
            $this->info("  Transaction Date: {$ledgerEntry->transaction_date}");
            $this->info("  Created At: {$ledgerEntry->created_at}");
            $this->info("  Reference: {$ledgerEntry->reference_no}");
            
            // Check if timezone conversion worked
            $transactionTime = Carbon::parse($ledgerEntry->transaction_date);
            $createdTime = Carbon::parse($customer->created_at)->setTimezone('Asia/Colombo');
            
            $this->info("Customer created_at (UTC): {$customer->created_at}");
            $this->info("Customer created_at (Colombo): {$createdTime->format('Y-m-d H:i:s')}");
            
            if ($transactionTime->format('H:i:s') !== '00:00:00') {
                $this->info("✅ SUCCESS: Transaction date preserves time component!");
            } else {
                $this->error("❌ ISSUE: Transaction date still shows midnight time");
            }
        } else {
            $this->error("No ledger entry found for customer");
        }
        
        // Clean up test customer
        $customer->delete();
        if ($ledgerEntry) {
            $ledgerEntry->delete();
        }
        $this->info("Test customer cleaned up");
        
        return Command::SUCCESS;
    }
}