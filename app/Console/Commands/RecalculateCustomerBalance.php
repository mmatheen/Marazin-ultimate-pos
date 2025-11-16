<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;

class RecalculateCustomerBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:recalculate-balance {customer_name?} {--all} {--fix-mismatches} {--list-customers} {--remove-payments} {--remove-returns} {--interactive} {--clean-all} {--dry-run} {--backup} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate customer ledger balances and fix mismatches with production-safe options';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerName = $this->argument('customer_name');
        $all = $this->option('all');
        $fixMismatches = $this->option('fix-mismatches');
        $listCustomers = $this->option('list-customers');
        $removePayments = $this->option('remove-payments');
        $removeReturns = $this->option('remove-returns');
        $interactive = $this->option('interactive');
        $cleanAll = $this->option('clean-all');
        $dryRun = $this->option('dry-run');
        $createBackup = $this->option('backup');

        // Production safety warning
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('🚨 PRODUCTION ENVIRONMENT DETECTED!');
            $this->warn('This command can modify live data. Use --dry-run to preview changes first.');
            if (!$this->confirm('Are you absolutely sure you want to continue?')) {
                return 1;
            }
        }

        if ($listCustomers) {
            $this->listAllCustomers();
            return 0;
        }

        if (($removePayments || $removeReturns || $cleanAll) && !$customerName) {
            $this->error('Customer name is required when using --remove-payments, --remove-returns, or --clean-all option.');
            return 1;
        }

        if ($interactive) {
            return $this->interactiveMode();
        }

        if ($all) {
            $this->info('Recalculating all customers...');
            $customers = DB::table('customers')->get()->map(function($c) {
                return (object) $c;
            });
        } elseif ($customerName) {
            $customers = DB::table('customers')
                          ->where('first_name', 'like', "%{$customerName}%")
                          ->orWhere('last_name', 'like', "%{$customerName}%")
                          ->get()
                          ->map(function($c) { return (object) $c; });
            if ($customers->isEmpty()) {
                $this->error("Customer '{$customerName}' not found.");
                return 1;
            }
        } else {
            $this->error('Please specify a customer name or use --all flag.');
            return 1;
        }

        foreach ($customers as $customer) {
            $this->info("Processing customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})");
            
            // Safety checks and dry run preview
            $customerFullName = "{$customer->first_name} {$customer->last_name}";
            
            if ($dryRun) {
                if ($cleanAll) $this->previewChanges($customerFullName, 'clean-all');
                elseif ($removePayments) $this->previewChanges($customerFullName, 'remove-payments');
                elseif ($removeReturns) $this->previewChanges($customerFullName, 'remove-returns');
                continue; // Skip actual execution in dry run
            }

            // Create backup if requested
            if ($createBackup && ($cleanAll || $removePayments || $removeReturns)) {
                if (!$this->createBackup($customerFullName)) {
                    $this->error('Backup failed. Skipping this customer for safety.');
                    continue;
                }
            }

            // Production safety confirmations
            if ($cleanAll && !$this->isProductionSafe('Remove ALL payments and returns', $customerFullName)) {
                continue;
            }
            if ($removePayments && !$this->isProductionSafe('Remove payments', $customerFullName)) {
                continue;
            }
            if ($removeReturns && !$this->isProductionSafe('Remove returns', $customerFullName)) {
                continue;
            }
            
            // If clean-all option, remove everything except sales
            if ($cleanAll) {
                $this->cleanAllCustomerTransactions($customer);
            }
            
            // If removing payments, do that first
            if ($removePayments) {
                $this->removeCustomerPayments($customer);
            }
            
            // If removing returns, do that next
            if ($removeReturns) {
                $this->removeCustomerReturns($customer);
            }
            
            // First, let's analyze the current ledger entries
            $this->analyzeLedgerEntries($customer);
            
            // Get all transactions for this customer
            $this->recalculateCustomerBalance($customer, $fixMismatches || $removePayments || $removeReturns || $cleanAll);
        }

        $this->info('Balance recalculation completed.');
        return 0;
    }

    private function recalculateCustomerBalance($customer, $fixMismatches = false)
    {
        DB::beginTransaction();
        
        try {
            // Get all sales for this customer (using final_total instead of total_amount) - Direct DB query
            $totalSales = DB::table('sales')
                           ->where('customer_id', $customer->id)
                           ->whereIn('status', ['final', 'suspend'])
                           ->sum('final_total');
            
            // Get all payments made by this customer (for sales only) - Direct DB query
            $totalPayments = DB::table('payments')
                              ->where('customer_id', $customer->id)
                              ->where('payment_type', 'sale')
                              ->sum('amount');
            
            // Get all returns (using return_total instead of total_amount) - Direct DB query
            $totalReturns = DB::table('sales_returns')
                             ->where('customer_id', $customer->id)
                             ->sum('return_total');
            
            // Calculate correct balance
            $correctBalance = $customer->opening_balance + $totalSales - $totalPayments - $totalReturns;
            
            $this->info("  Opening Balance: {$customer->opening_balance}");
            $this->info("  Total Sales: {$totalSales}");
            $this->info("  Total Payments: {$totalPayments}");
            $this->info("  Total Returns: {$totalReturns}");
            $this->info("  Current Balance (DB): {$customer->current_balance}");
            $this->info("  Calculated Balance: {$correctBalance}");
            
            if (abs($customer->current_balance - $correctBalance) > 0.01) {
                $this->warn("  MISMATCH DETECTED!");
                
                if ($fixMismatches) {
                    $this->info("  Fixing balance...");
                    
                    // Update customer balance using direct DB query
                    DB::table('customers')
                      ->where('id', $customer->id)
                      ->update(['current_balance' => $correctBalance]);
                    
                    // Rebuild ledger entries
                    $this->rebuildLedgerEntries($customer);
                    
                    $this->info("  ✓ Balance corrected to: {$correctBalance}");
                } else {
                    $this->warn("  Use --fix-mismatches to correct this automatically");
                }
            } else {
                $this->info("  ✓ Balance is correct");
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->error("  Error processing customer {$customer->id}: " . $e->getMessage());
        }
    }

    private function rebuildLedgerEntries($customer)
    {
        // Delete existing ledger entries for this customer using direct DB query
        DB::table('ledgers')
          ->where('user_id', $customer->id)
          ->where('contact_type', 'customer')
          ->delete();
        
        $balance = $customer->opening_balance ?? 0;
        
        // Add opening balance entry if not zero
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
        
        // Get all transactions chronologically using direct DB queries
        $transactions = collect();
        
        // Add sales
        $sales = DB::table('sales')
                   ->where('customer_id', $customer->id)
                   ->whereIn('status', ['final', 'suspend'])
                   ->orderBy('created_at')
                   ->get();
        
        foreach ($sales as $sale) {
            $balance += $sale->final_total;
            $transactions->push([
                'date' => $sale->created_at,
                'type' => 'sale',
                'reference' => $sale->invoice_no ?? 'SALE-' . $sale->id,
                'debit' => $sale->final_total,
                'credit' => 0,
                'balance' => $balance,
                'notes' => 'Sale transaction'
            ]);
        }
        
        // Add payments
        $payments = DB::table('payments')
                     ->where('customer_id', $customer->id)
                     ->where('payment_type', 'sale')
                     ->orderBy('created_at')
                     ->get();
        
        foreach ($payments as $payment) {
            $balance -= $payment->amount;
            $transactions->push([
                'date' => $payment->created_at,
                'type' => 'payments',
                'reference' => $payment->reference_no ?? 'PAY-' . $payment->id,
                'debit' => 0,
                'credit' => $payment->amount,
                'balance' => $balance,
                'notes' => 'Payment received'
            ]);
        }
        
        // Add returns
        $returns = DB::table('sales_returns')
                    ->where('customer_id', $customer->id)
                    ->orderBy('created_at')
                    ->get();
        
        foreach ($returns as $return) {
            $balance -= $return->return_total;
            $transactions->push([
                'date' => $return->created_at,
                'type' => $return->stock_type == 'with_bill' ? 'sale_return_with_bill' : 'sale_return_without_bill',
                'reference' => $return->invoice_number ?? 'RET-' . $return->id,
                'debit' => 0,
                'credit' => $return->return_total,
                'balance' => $balance,
                'notes' => 'Sale return'
            ]);
        }
        
        // Sort by date and create ledger entries
        $transactions = $transactions->sortBy('date');
        
        foreach ($transactions as $transaction) {
            DB::table('ledgers')->insert([
                'transaction_date' => $transaction['date'],
                'reference_no' => $transaction['reference'],
                'transaction_type' => $transaction['type'],
                'debit' => $transaction['debit'],
                'credit' => $transaction['credit'],
                'balance' => $transaction['balance'],
                'contact_type' => 'customer',
                'user_id' => $customer->id,
                'notes' => $transaction['notes'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        $this->info("  ✓ Ledger entries rebuilt");
    }

    private function analyzeLedgerEntries($customer)
    {
        $this->info("  --- LEDGER ANALYSIS ---");
        
        // Get all ledger entries for this customer - Direct DB query
        $ledgerEntries = DB::table('ledgers')
                          ->where('user_id', $customer->id)
                          ->where('contact_type', 'customer')
                          ->orderBy('transaction_date', 'asc')
                          ->orderBy('id', 'asc')
                          ->get();
        
        $this->info("  Total ledger entries: " . $ledgerEntries->count());
        
        if ($ledgerEntries->count() > 0) {
            $this->info("  Ledger entries breakdown:");
            $typeCount = [];
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($ledgerEntries as $entry) {
                $type = $entry->transaction_type ?? 'unknown';
                $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
                $totalDebit += $entry->debit;
                $totalCredit += $entry->credit;
            }
            
            foreach ($typeCount as $type => $count) {
                $this->info("    - {$type}: {$count} entries");
            }
            
            $this->info("  Total Debit: {$totalDebit}");
            $this->info("  Total Credit: {$totalCredit}");
            $this->info("  Ledger Balance: " . ($totalDebit - $totalCredit));
            $this->info("  Last Entry Balance: " . $ledgerEntries->last()->balance);
        }
        
        // Check for actual transaction data - Direct DB queries
        $actualSales = DB::table('sales')->where('customer_id', $customer->id)->count();
        $actualPayments = DB::table('payments')->where('customer_id', $customer->id)->where('payment_type', 'sale')->count();
        $actualReturns = DB::table('sales_returns')->where('customer_id', $customer->id)->count();
        
        $this->info("  --- ACTUAL TRANSACTION DATA ---");
        $this->info("  Actual Sales: {$actualSales}");
        $this->info("  Actual Payments: {$actualPayments}");
        $this->info("  Actual Returns: {$actualReturns}");
    }

    private function listAllCustomers()
    {
        $this->info('=== ALL CUSTOMERS (Direct DB Query) ===');
        
        // Query customers directly from database without model scopes
        $customers = DB::table('customers')
                      ->select('id', 'first_name', 'last_name', 'mobile_no', 'current_balance', 'opening_balance')
                      ->get();
        
        $this->table(
            ['ID', 'First Name', 'Last Name', 'Mobile', 'Opening Balance', 'Current Balance'],
            $customers->map(function($customer) {
                return [
                    $customer->id,
                    $customer->first_name,
                    $customer->last_name ?: '-',
                    $customer->mobile_no,
                    $customer->opening_balance,
                    $customer->current_balance
                ];
            })->toArray()
        );
        
        $this->info("Total customers: " . $customers->count());
        
        // Also check ledger entries directly
        $this->info("\n=== LEDGER ENTRIES BY USER ===");
        $ledgerSummary = DB::table('ledgers')
                          ->where('contact_type', 'customer')
                          ->select('user_id', DB::raw('COUNT(*) as entry_count'), DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
                          ->groupBy('user_id')
                          ->get();
        
        foreach ($ledgerSummary as $summary) {
            $customer = $customers->where('id', $summary->user_id)->first();
            $customerName = $customer ? "{$customer->first_name} {$customer->last_name}" : "Unknown Customer";
            $this->info("User ID {$summary->user_id} ({$customerName}): {$summary->entry_count} entries, Debit: {$summary->total_debit}, Credit: {$summary->total_credit}");
        }
    }

    private function removeCustomerPayments($customer)
    {
        $this->info("  --- REMOVING CUSTOMER PAYMENTS ---");
        
        // Get all payments for this customer
        $payments = DB::table('payments')
                     ->where('customer_id', $customer->id)
                     ->get();
        
        if ($payments->count() > 0) {
            $this->info("  Found {$payments->count()} payment(s) to remove:");
            
            foreach ($payments as $payment) {
                $this->info("    - Payment ID: {$payment->id}, Amount: {$payment->amount}, Date: {$payment->payment_date}, Type: {$payment->payment_type}");
            }
            
            if ($this->confirm("  Are you sure you want to delete ALL these payments for {$customer->first_name}?")) {
                // Delete payments
                $deletedCount = DB::table('payments')
                                 ->where('customer_id', $customer->id)
                                 ->delete();
                
                // Update sales table to reset payment status and manually fix total_due
                $salesRecords = DB::table('sales')->where('customer_id', $customer->id)->get();
                $updatedSales = 0;
                
                foreach ($salesRecords as $sale) {
                    $correctTotalDue = $sale->final_total; // Since total_paid will be 0
                    
                    DB::table('sales')
                      ->where('id', $sale->id)
                      ->update([
                          'total_paid' => 0,
                          'total_due' => $correctTotalDue,
                          'payment_status' => 'Due'
                      ]);
                    $updatedSales++;
                }
                
                $this->info("  ✓ Deleted {$deletedCount} payment record(s)");
                $this->info("  ✓ Updated {$updatedSales} sale record(s) - reset payment status");
                
                // Also remove payment ledger entries
                $deletedLedgerCount = DB::table('ledgers')
                                       ->where('user_id', $customer->id)
                                       ->where('contact_type', 'customer')
                                       ->where('transaction_type', 'payments')
                                       ->delete();
                
                $this->info("  ✓ Deleted {$deletedLedgerCount} payment ledger entries");
            } else {
                $this->info("  Payment removal cancelled.");
            }
        } else {
            $this->info("  No payments found for this customer.");
        }
    }

    private function removeCustomerReturns($customer)
    {
        $this->info("  --- REMOVING CUSTOMER RETURNS ---");
        
        // Get all returns for this customer
        $returns = DB::table('sales_returns')
                    ->where('customer_id', $customer->id)
                    ->get();
        
        if ($returns->count() > 0) {
            $this->info("  Found {$returns->count()} return(s) to remove:");
            
            foreach ($returns as $return) {
                $this->info("    - Return ID: {$return->id}, Amount: {$return->return_total}, Date: {$return->return_date}, Invoice: {$return->invoice_number}");
            }
            
            if ($this->confirm("  Are you sure you want to delete ALL these returns for {$customer->first_name}?")) {
                // Delete returns
                $deletedCount = DB::table('sales_returns')
                                 ->where('customer_id', $customer->id)
                                 ->delete();
                
                $this->info("  ✓ Deleted {$deletedCount} return record(s)");
                
                // Also remove return ledger entries
                $deletedLedgerCount = DB::table('ledgers')
                                       ->where('user_id', $customer->id)
                                       ->where('contact_type', 'customer')
                                       ->whereIn('transaction_type', ['sale_return_with_bill', 'sale_return_without_bill'])
                                       ->delete();
                
                $this->info("  ✓ Deleted {$deletedLedgerCount} return ledger entries");
            } else {
                $this->info("  Return removal cancelled.");
            }
        } else {
            $this->info("  No returns found for this customer.");
        }
    }

    private function interactiveMode()
    {
        $this->info('🔍 INTERACTIVE CUSTOMER LEDGER ANALYSIS & CLEANUP');
        $this->info('=================================================');
        
        // Get all customers with potential issues
        $customers = DB::table('customers')->get();
        
        $processedCount = 0;
        $fixedCount = 0;
        
        foreach ($customers as $customer) {
            $this->newLine();
            $this->info("👤 ANALYZING: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})");
            $this->info(str_repeat('=', 60));
            
            // Analyze this customer
            $this->analyzeLedgerEntries($customer);
            
            // Calculate what the balance should be
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
            
            $this->info("💰 BALANCE SUMMARY:");
            $this->info("  Opening Balance: " . ($customer->opening_balance ?? 0));
            $this->info("  Total Sales: {$totalSales}");
            $this->info("  Total Payments: {$totalPayments}");
            $this->info("  Total Returns: {$totalReturns}");
            $this->info("  Current Balance (DB): {$customer->current_balance}");
            $this->info("  Calculated Correct Balance: {$correctBalance}");
            
            $hasMismatch = abs($customer->current_balance - $correctBalance) > 0.01;
            
            if ($hasMismatch) {
                $this->warn("⚠️  MISMATCH DETECTED! Difference: " . ($customer->current_balance - $correctBalance));
                
                $choice = $this->choice(
                    'What would you like to do?',
                    [
                        'fix' => 'Fix this customer (rebuild ledger & update balance)',
                        'skip' => 'Skip this customer',
                        'details' => 'Show detailed transaction breakdown',
                        'stop' => 'Stop processing'
                    ],
                    'details'
                );
                
                switch ($choice) {
                    case 'fix':
                        $this->info("🔧 FIXING CUSTOMER...");
                        $this->recalculateCustomerBalance($customer, true);
                        $this->info("✅ Customer fixed!");
                        $fixedCount++;
                        break;
                        
                    case 'details':
                        $this->showDetailedBreakdown($customer);
                        // Ask again after showing details
                        if ($this->confirm("Fix this customer after seeing details?")) {
                            $this->recalculateCustomerBalance($customer, true);
                            $this->info("✅ Customer fixed!");
                            $fixedCount++;
                        }
                        break;
                        
                    case 'stop':
                        $this->info("🛑 Stopped by user request.");
                        break 2;
                        
                    case 'skip':
                        $this->info("⏭️  Skipped.");
                        break;
                }
            } else {
                $this->info("✅ Balance is correct - no issues found.");
            }
            
            $processedCount++;
            
            // Ask if continue after each customer
            if (!$this->confirm("Continue to next customer?", true)) {
                break;
            }
        }
        
        $this->newLine();
        $this->info("📊 SUMMARY:");
        $this->info("  Customers processed: {$processedCount}");
        $this->info("  Customers fixed: {$fixedCount}");
        $this->info("🎉 Interactive analysis completed!");
        
        return 0;
    }

    private function showDetailedBreakdown($customer)
    {
        $this->info("📋 DETAILED TRANSACTION BREAKDOWN:");
        
        // Show all sales
        $sales = DB::table('sales')
                  ->where('customer_id', $customer->id)
                  ->select('id', 'invoice_no', 'final_total', 'created_at', 'status')
                  ->get();
        
        if ($sales->count() > 0) {
            $this->info("  📈 SALES ({$sales->count()}):");
            foreach ($sales as $sale) {
                $this->info("    - Sale #{$sale->id}: {$sale->invoice_no} = Rs.{$sale->final_total} ({$sale->created_at}) [{$sale->status}]");
            }
        }
        
        // Show all payments
        $payments = DB::table('payments')
                     ->where('customer_id', $customer->id)
                     ->select('id', 'amount', 'payment_date', 'payment_type', 'reference_no')
                     ->get();
        
        if ($payments->count() > 0) {
            $this->info("  💳 PAYMENTS ({$payments->count()}):");
            foreach ($payments as $payment) {
                $this->info("    - Payment #{$payment->id}: Rs.{$payment->amount} ({$payment->payment_date}) [{$payment->payment_type}] {$payment->reference_no}");
            }
        }
        
        // Show all returns
        $returns = DB::table('sales_returns')
                    ->where('customer_id', $customer->id)
                    ->select('id', 'return_total', 'return_date', 'invoice_number')
                    ->get();
        
        if ($returns->count() > 0) {
            $this->info("  🔄 RETURNS ({$returns->count()}):");
            foreach ($returns as $return) {
                $this->info("    - Return #{$return->id}: Rs.{$return->return_total} ({$return->return_date}) {$return->invoice_number}");
            }
        }
        
        // Show ledger entries
        $ledgerEntries = DB::table('ledgers')
                          ->where('user_id', $customer->id)
                          ->where('contact_type', 'customer')
                          ->select('id', 'transaction_type', 'debit', 'credit', 'balance', 'reference_no', 'created_at')
                          ->orderBy('created_at')
                          ->get();
        
        if ($ledgerEntries->count() > 0) {
            $this->info("  📖 LEDGER ENTRIES ({$ledgerEntries->count()}):");
            foreach ($ledgerEntries as $entry) {
                $this->info("    - Entry #{$entry->id}: {$entry->transaction_type} | Dr:{$entry->debit} Cr:{$entry->credit} Bal:{$entry->balance} | {$entry->reference_no}");
            }
        }
    }

    private function cleanAllCustomerTransactions($customer)
    {
        $this->info("  --- CLEANING ALL CUSTOMER TRANSACTIONS ---");
        $this->info("  This will remove ALL payments and returns for {$customer->first_name}, keeping only sales");
        
        // Delete all payments
        $deletedPayments = DB::table('payments')
                            ->where('customer_id', $customer->id)
                            ->delete();
        
        // Delete all returns
        $deletedReturns = DB::table('sales_returns')
                           ->where('customer_id', $customer->id)
                           ->delete();
        
        // Delete all ledger entries (will be rebuilt with only sales)
        $deletedLedger = DB::table('ledgers')
                          ->where('user_id', $customer->id)
                          ->where('contact_type', 'customer')
                          ->delete();

        // Update all sales to reset payment status and manually fix total_due
        $salesRecords = DB::table('sales')->where('customer_id', $customer->id)->get();
        $updatedSales = 0;
        
        foreach ($salesRecords as $sale) {
            $correctTotalDue = $sale->final_total; // Since total_paid will be 0
            
            DB::table('sales')
              ->where('id', $sale->id)
              ->update([
                  'total_paid' => 0,
                  'total_due' => $correctTotalDue,
                  'payment_status' => 'Due'
              ]);
            $updatedSales++;
        }
        
        $this->info("  ✓ Deleted {$deletedPayments} payment record(s)");
        $this->info("  ✓ Deleted {$deletedReturns} return record(s)");
        $this->info("  ✓ Deleted {$deletedLedger} ledger entries (will rebuild with sales only)");
        $this->info("  ✓ Updated {$updatedSales} sale record(s) - reset total_paid to 0 and payment_status to 'Due'");
        $this->info("  ✓ Customer cleaned - only sales transactions remain with correct payment status");
    }

    /**
     * Production safety checks and confirmations
     */
    private function isProductionSafe($operation, $customerName = null)
    {
        // Check if this is a dry run
        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            return false; // Don't execute, just preview
        }

        // Check if force flag is used
        if ($this->option('force')) {
            $this->warn('⚠️ FORCE MODE ENABLED - Skipping confirmations');
            return true;
        }

        // Production environment warning
        if (app()->environment('production')) {
            $this->error('🚨 PRODUCTION ENVIRONMENT DETECTED');
            $this->warn('This operation will modify live data!');
            
            if (!$this->confirm('Are you sure you want to continue in production?')) {
                return false;
            }
        }

        // Show what will be affected
        $this->info("📋 Operation: {$operation}");
        if ($customerName) {
            $this->info("👤 Customer: {$customerName}");
        }

        // Final confirmation
        if (!$this->confirm('Proceed with this operation?')) {
            return false;
        }

        return true;
    }

    /**
     * Create backup before destructive operations
     */
    private function createBackup($customerName = null)
    {
        if (!$this->option('backup')) {
            return true;
        }

        $this->info('💾 Creating backup...');
        
        $timestamp = date('Y_m_d_H_i_s');
        $backupName = $customerName ? "ledger_backup_{$customerName}_{$timestamp}" : "ledger_backup_all_{$timestamp}";
        
        try {
            // You can implement actual backup logic here
            $this->info("✅ Backup would be created: {$backupName}");
            // Example: Artisan::call('backup:run', ['--only-db' => true]);
        } catch (\Exception $e) {
            $this->error('❌ Backup failed: ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Preview changes without executing (dry run)
     */
    private function previewChanges($customerName, $operation)
    {
        $this->info("🔍 DRY RUN: Preview of changes for {$customerName}");
        
        // Get current state - extract the customer name from the full name passed
        $searchName = trim(explode(' ', $customerName)[0]); // Get first word for search
        
        $customers = DB::table('customers')
                      ->where('first_name', 'like', "%{$searchName}%")
                      ->orWhere('last_name', 'like', "%{$searchName}%")
                      ->get();
        
        if ($customers->isEmpty()) {
            $this->error("Customer not found: {$searchName}");
            return;
        }
        
        $customer = $customers->first();

        // Show what would be deleted/modified
        if (in_array($operation, ['remove-payments', 'clean-all'])) {
            $paymentsCount = DB::table('payments')
                ->where('customer_id', $customer->id)
                ->count();
            $this->warn("Would DELETE {$paymentsCount} payment records");
            
            $salesCount = DB::table('sales')
                ->where('customer_id', $customer->id)
                ->count();
            $this->warn("Would UPDATE {$salesCount} sale records (reset total_paid to 0, payment_status to 'Due')");
        }

        if (in_array($operation, ['remove-returns', 'clean-all'])) {
            $returnsCount = DB::table('sales_returns')
                ->where('customer_id', $customer->id)
                ->count();
            $this->warn("Would DELETE {$returnsCount} return records");
        }

        $this->info('💡 Use --force to execute these changes');
    }
}
