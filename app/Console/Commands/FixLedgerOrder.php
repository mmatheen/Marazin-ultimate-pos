<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

class FixLedgerOrder extends Command
{
    protected $signature = 'fix:ledger-order {customer_id}';
    protected $description = 'Fix ledger entry ordering for proper balance calculation';

    public function handle()
    {
        $customerId = $this->argument('customer_id');
        
        DB::transaction(function () use ($customerId) {
            // Update opening balance entry to have earlier timestamp
            $openingEntry = Ledger::where('user_id', $customerId)
                ->where('transaction_type', 'opening_balance')
                ->first();
                
            if ($openingEntry) {
                $openingEntry->transaction_date = '2025-09-20 23:59:59'; // Day before
                $openingEntry->save();
                $this->info("Updated opening balance timestamp");
            }
            
            // Update other entries to have proper sequence
            $saleEntry = Ledger::where('user_id', $customerId)
                ->where('transaction_type', 'sale')
                ->first();
            if ($saleEntry) {
                $saleEntry->transaction_date = '2025-09-21 01:00:00';
                $saleEntry->save();
            }
            
            $paymentEntries = Ledger::where('user_id', $customerId)
                ->where('transaction_type', 'payments')
                ->orderBy('id')
                ->get();
                
            foreach ($paymentEntries as $index => $payment) {
                $payment->transaction_date = '2025-09-21 0' . (2 + $index) . ':00:00';
                $payment->save();
            }
            
            // Recalculate balances with proper order
            Ledger::calculateBalance($customerId, 'customer');
            $this->info("Recalculated balances with proper order");
        });
        
        // Show final corrected state
        $this->info("\nCorrected ledger state:");
        $ledgerEntries = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
        foreach ($ledgerEntries as $entry) {
            $this->info("- Date: {$entry->transaction_date}, Type: {$entry->transaction_type}, Debit: {$entry->debit}, Credit: {$entry->credit}, Balance: {$entry->balance}");
        }
        
        $customer = Customer::find($customerId);
        $customer->recalculateCurrentBalance();
        $this->info("\nFinal customer balance: {$customer->current_balance}");
    }
}