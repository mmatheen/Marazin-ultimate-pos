<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupLedger extends Command
{
    protected $signature = 'ledger:cleanup {customer_name?} {--all} {--dry-run} {--force}';
    protected $description = 'Clean up customer ledger data and fix inconsistencies';

    public function handle()
    {
        $customerName = $this->argument('customer_name');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Production safety check
        if (app()->environment('production') && !$force && !$dryRun) {
            $this->error('🚨 PRODUCTION ENVIRONMENT!');
            $this->warn('Use --dry-run to preview or --force to execute');
            return 1;
        }

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        if ($all) {
            $this->cleanupAllCustomers($dryRun, $force);
        } elseif ($customerName) {
            $this->cleanupCustomer($customerName, $dryRun, $force);
        } else {
            $this->error('Please specify a customer name or use --all flag.');
            return 1;
        }

        return 0;
    }

    private function cleanupCustomer($customerName, $dryRun = false, $force = false)
    {
        $customer = DB::table('customers')
                     ->where('first_name', 'like', "%{$customerName}%")
                     ->orWhere('last_name', 'like', "%{$customerName}%")
                     ->first();

        if (!$customer) {
            $this->error("Customer '{$customerName}' not found.");
            return;
        }

        $this->info("=== CLEANING: {$customer->first_name} {$customer->last_name} ===");
        $this->processCustomerCleanup($customer, $dryRun, $force);
    }

    private function cleanupAllCustomers($dryRun = false, $force = false)
    {
        $this->info('=== CLEANING ALL CUSTOMERS ===');
        
        $customers = DB::table('customers')->get();
        $processedCount = 0;

        foreach ($customers as $customer) {
            if ($this->processCustomerCleanup($customer, $dryRun, $force)) {
                $processedCount++;
            }
        }

        $this->info("\n📊 SUMMARY: {$processedCount} customers processed");
    }

    private function processCustomerCleanup($customer, $dryRun = false, $force = false)
    {
        // Get counts for preview
        $paymentsCount = DB::table('payments')->where('customer_id', $customer->id)->count();
        $returnsCount = DB::table('sales_returns')->where('customer_id', $customer->id)->count();
        $salesCount = DB::table('sales')->where('customer_id', $customer->id)->count();
        $ledgerCount = DB::table('ledgers')->where('user_id', $customer->id)->where('contact_type', 'customer')->count();

        // Calculate correct balance
        $totalSales = DB::table('sales')
                       ->where('customer_id', $customer->id)
                       ->whereIn('status', ['final', 'suspend'])
                       ->sum('final_total');

        $correctBalance = ($customer->opening_balance ?? 0) + $totalSales;
        $hasMismatch = abs($customer->current_balance - $correctBalance) > 0.01;

        if (!$hasMismatch && $paymentsCount == 0 && $returnsCount == 0) {
            return false; // Skip - already clean
        }

        $this->info("👤 {$customer->first_name} {$customer->last_name} (ID: {$customer->id})");
        
        if ($dryRun) {
            $this->warn("Would DELETE: {$paymentsCount} payments, {$returnsCount} returns, {$ledgerCount} ledger entries");
            $this->warn("Would UPDATE: {$salesCount} sales (reset payment status)");
            $this->info("Would SET balance to: {$correctBalance}");
            return true;
        }

        // Confirmation for non-force mode
        if (!$force) {
            $this->warn("Will clean: {$paymentsCount} payments, {$returnsCount} returns");
            if (!$this->confirm("Proceed with cleanup?")) {
                $this->info("Skipped");
                return false;
            }
        }

        DB::beginTransaction();
        
        try {
            // Delete payments
            $deletedPayments = DB::table('payments')->where('customer_id', $customer->id)->delete();
            
            // Delete returns  
            $deletedReturns = DB::table('sales_returns')->where('customer_id', $customer->id)->delete();
            
            // Delete ledger entries
            $deletedLedger = DB::table('ledgers')
                              ->where('user_id', $customer->id)
                              ->where('contact_type', 'customer')
                              ->delete();

            // Update sales table - manually set total_due since generated column may not work
            $salesRecords = DB::table('sales')->where('customer_id', $customer->id)->get();
            $updatedSales = 0;
            
            foreach ($salesRecords as $sale) {
                $correctTotalDue = $sale->final_total - 0; // Since we're setting total_paid to 0
                
                DB::table('sales')
                  ->where('id', $sale->id)
                  ->update([
                      'total_paid' => 0,
                      'total_due' => $correctTotalDue,
                      'payment_status' => 'Due'
                  ]);
                $updatedSales++;
            }

            // Rebuild ledger first to get correct balance
            $finalLedgerBalance = $this->rebuildLedgerForSales($customer, $correctBalance);

            // Update customer balance to match final ledger balance
            DB::table('customers')
              ->where('id', $customer->id)
              ->update(['current_balance' => $finalLedgerBalance]);

            // Rebuild ledger with only sales
            $this->rebuildLedgerForSales($customer, $correctBalance);

            DB::commit();

            $this->info("✅ Cleaned: -{$deletedPayments} payments, -{$deletedReturns} returns, ~{$updatedSales} sales updated");
            
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            $this->error("❌ Error: " . $e->getMessage());
            return false;
        }
    }

    private function rebuildLedgerForSales($customer, $currentBalance)
    {
        $balance = $customer->opening_balance ?? 0;

        // Add opening balance if not zero
        if ($balance != 0) {
            DB::table('ledgers')->insert([
                'transaction_date' => $customer->created_at ?? now(),
                'reference_no' => 'OPENING-' . $customer->id,
                'transaction_type' => 'opening_balance',
                'debit' => $balance > 0 ? $balance : 0,
                'credit' => $balance < 0 ? abs($balance) : 0,
                'balance' => $balance,
                'contact_type' => 'customer',
                'user_id' => $customer->id,
                'notes' => 'Opening balance',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Add sales entries
        $sales = DB::table('sales')
                   ->where('customer_id', $customer->id)
                   ->whereIn('status', ['final', 'suspend'])
                   ->orderBy('created_at')
                   ->get();

        foreach ($sales as $sale) {
            $balance += $sale->final_total;
            
            DB::table('ledgers')->insert([
                'transaction_date' => $sale->created_at,
                'reference_no' => $sale->invoice_no ?? 'SALE-' . $sale->id,
                'transaction_type' => 'sale',
                'debit' => $sale->final_total,
                'credit' => 0,
                'balance' => $balance,
                'contact_type' => 'customer',
                'user_id' => $customer->id,
                'notes' => 'Sale transaction',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Return final balance so customer table can be updated
        return $balance;
    }
}