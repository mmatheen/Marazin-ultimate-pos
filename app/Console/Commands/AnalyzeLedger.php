<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeLedger extends Command
{
    protected $signature = 'ledger:analyze {customer_name?} {--all}';
    protected $description = 'Analyze customer ledger balances and find mismatches (READ-ONLY)';

    public function handle()
    {
        $customerName = $this->argument('customer_name');
        $all = $this->option('all');

        if ($all) {
            $this->analyzeAllCustomers();
        } elseif ($customerName) {
            $this->analyzeCustomer($customerName);
        } else {
            $this->error('Please specify a customer name or use --all flag.');
            return 1;
        }

        return 0;
    }

    private function analyzeCustomer($customerName)
    {
        $customer = DB::table('customers')
                     ->where('first_name', 'like', "%{$customerName}%")
                     ->orWhere('last_name', 'like', "%{$customerName}%")
                     ->first();

        if (!$customer) {
            $this->error("Customer '{$customerName}' not found.");
            return;
        }

        $this->info("=== ANALYZING: {$customer->first_name} {$customer->last_name} ===");
        $this->analyzeCustomerData($customer);
    }

    private function analyzeAllCustomers()
    {
        $this->info('=== ANALYZING ALL CUSTOMERS ===');
        
        $customers = DB::table('customers')->get();
        $mismatchCount = 0;

        foreach ($customers as $customer) {
            $hasMismatch = $this->analyzeCustomerData($customer, false);
            if ($hasMismatch) {
                $mismatchCount++;
            }
        }

        $this->info("\n📊 SUMMARY:");
        $this->info("Total customers: " . $customers->count());
        $this->info("Customers with mismatches: {$mismatchCount}");
        
        if ($mismatchCount > 0) {
            $this->warn("\n🔧 Use 'php artisan ledger:cleanup' to fix issues");
        }
    }

    private function analyzeCustomerData($customer, $detailed = true)
    {
        // Calculate correct balance
        $totalSales = DB::table('sales')
                       ->where('customer_id', $customer->id)
                       ->whereIn('status', ['final', 'suspend'])
                       ->sum('final_total');

        $totalPayments = DB::table('payments')
                          ->where('customer_id', $customer->id)
                          ->where('payment_type', 'sale')
                          ->sum('amount');

        $totalReturns = DB::table('sales_returns')
                         ->where('customer_id', $customer->id)
                         ->sum('return_total');

        $correctBalance = ($customer->opening_balance ?? 0) + $totalSales - $totalPayments - $totalReturns;
        $hasMismatch = abs($customer->current_balance - $correctBalance) > 0.01;

        if ($detailed) {
            $this->info("👤 Customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})");
            $this->info("💰 Opening Balance: " . ($customer->opening_balance ?? 0));
            $this->info("📈 Total Sales: {$totalSales}");
            $this->info("💳 Total Payments: {$totalPayments}");
            $this->info("🔄 Total Returns: {$totalReturns}");
            $this->info("🏦 Current Balance (DB): {$customer->current_balance}");
            $this->info("🧮 Calculated Balance: {$correctBalance}");

            if ($hasMismatch) {
                $difference = $customer->current_balance - $correctBalance;
                $this->warn("⚠️  MISMATCH DETECTED! Difference: {$difference}");
                
                // Check sales table consistency
                $salesWithIncorrectPaid = DB::table('sales')
                    ->where('customer_id', $customer->id)
                    ->where('total_paid', '>', 0)
                    ->count();
                
                if ($salesWithIncorrectPaid > 0 && $totalPayments == 0) {
                    $this->warn("🚨 Sales table shows payments but no actual payment records found!");
                }
            } else {
                $this->info("✅ Balance is correct");
            }
            $this->info(str_repeat('-', 50));
        } else {
            if ($hasMismatch) {
                $difference = $customer->current_balance - $correctBalance;
                $this->warn("⚠️  {$customer->first_name} {$customer->last_name}: Difference {$difference}");
            }
        }

        return $hasMismatch;
    }
}