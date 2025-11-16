<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== COMPREHENSIVE CUSTOMER DATA CONSISTENCY CHECK ===\n\n";

try {
    // Get all customers
    $customers = DB::table('customers')->orderBy('id')->get();
    echo "Total Customers: {$customers->count()}\n\n";
    
    $inconsistentCustomers = [];
    $orphanedRecords = [];
    
    echo "CHECKING EACH CUSTOMER...\n";
    echo str_repeat("=", 80) . "\n";
    
    foreach ($customers as $customer) {
        $issues = [];
        
        // Get customer data
        $sales = DB::table('sales')->where('customer_id', $customer->id)->get();
        $payments = DB::table('payments')->where('customer_id', $customer->id)->get();
        $ledgers = DB::table('ledgers')->where('user_id', $customer->id)->where('contact_type', 'customer')->get();
        $returns = DB::table('sales_returns')->where('customer_id', $customer->id)->get();
        
        // Calculate totals
        $totalSales = $sales->sum('final_total');
        $totalPayments = $payments->sum('amount');
        $totalReturns = $returns->sum('final_total');
        $ledgerBalance = $ledgers->last()->balance ?? 0;
        
        // Calculate expected balance
        $expectedBalance = ($customer->opening_balance ?? 0) + $totalSales - $totalPayments - $totalReturns;
        
        // Check for issues
        $hasData = $sales->count() > 0 || $payments->count() > 0 || $ledgers->count() > 0;
        
        if ($hasData) {
            // Check balance consistency
            if (abs($customer->current_balance - $expectedBalance) > 0.01) {
                $issues[] = "Balance mismatch: DB={$customer->current_balance}, Expected={$expectedBalance}";
            }
            
            // Check ledger consistency
            if (abs($ledgerBalance - $customer->current_balance) > 0.01) {
                $issues[] = "Ledger balance mismatch: Ledger={$ledgerBalance}, Customer={$customer->current_balance}";
            }
            
            // Check sales payment consistency
            foreach ($sales as $sale) {
                $salePayments = $payments->where('reference_no', $sale->invoice_no)->sum('amount');
                if (abs($sale->total_paid - $salePayments) > 0.01) {
                    $issues[] = "Sale {$sale->id} payment mismatch: Sale shows {$sale->total_paid}, Actual payments {$salePayments}";
                }
                
                $expectedDue = $sale->final_total - $sale->total_paid;
                if (abs($sale->total_due - $expectedDue) > 0.01) {
                    $issues[] = "Sale {$sale->id} due amount wrong: Shows {$sale->total_due}, Should be {$expectedDue}";
                }
            }
            
            // Check duplicate ledger entries
            $ledgerGroups = $ledgers->groupBy('reference_no');
            foreach ($ledgerGroups as $refNo => $entries) {
                if ($entries->count() > 1 && $refNo != 'OPENING-' . $customer->id) {
                    $issues[] = "Duplicate ledger entries for {$refNo}: {$entries->count()} entries";
                }
            }
        }
        
        // Display customer info
        $customerName = trim($customer->first_name . ' ' . $customer->last_name);
        
        if ($hasData || !empty($issues)) {
            echo "Customer ID {$customer->id}: {$customerName}\n";
            echo "  Balance: {$customer->current_balance} | Sales: {$sales->count()} | Payments: {$payments->count()} | Ledger: {$ledgers->count()}\n";
            
            if ($totalSales > 0) echo "  Total Sales: {$totalSales}\n";
            if ($totalPayments > 0) echo "  Total Payments: {$totalPayments}\n";
            if ($totalReturns > 0) echo "  Total Returns: {$totalReturns}\n";
            
            if (!empty($issues)) {
                echo "  ❌ ISSUES:\n";
                foreach ($issues as $issue) {
                    echo "     • {$issue}\n";
                }
                $inconsistentCustomers[] = [
                    'customer' => $customer,
                    'issues' => $issues,
                    'sales_count' => $sales->count(),
                    'payments_count' => $payments->count(),
                    'ledger_count' => $ledgers->count()
                ];
            } else {
                echo "  ✅ All consistent\n";
            }
            echo "\n";
        }
    }
    
    // Check for orphaned records
    echo str_repeat("=", 80) . "\n";
    echo "CHECKING FOR ORPHANED RECORDS...\n\n";
    
    // Orphaned sales
    $orphanedSales = DB::table('sales')
        ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
        ->whereNull('customers.id')
        ->select('sales.*')
        ->get();
    
    if ($orphanedSales->count() > 0) {
        echo "❌ ORPHANED SALES ({$orphanedSales->count()}):\n";
        foreach ($orphanedSales as $sale) {
            echo "  Sale ID {$sale->id}: Customer ID {$sale->customer_id} (not found)\n";
        }
        echo "\n";
    }
    
    // Orphaned payments
    $orphanedPayments = DB::table('payments')
        ->leftJoin('customers', 'payments.customer_id', '=', 'customers.id')
        ->whereNull('customers.id')
        ->select('payments.*')
        ->get();
    
    if ($orphanedPayments->count() > 0) {
        echo "❌ ORPHANED PAYMENTS ({$orphanedPayments->count()}):\n";
        foreach ($orphanedPayments as $payment) {
            echo "  Payment ID {$payment->id}: Customer ID {$payment->customer_id} (not found)\n";
        }
        echo "\n";
    }
    
    // Orphaned ledgers
    $orphanedLedgers = DB::table('ledgers')
        ->leftJoin('customers', 'ledgers.user_id', '=', 'customers.id')
        ->where('ledgers.contact_type', 'customer')
        ->whereNull('customers.id')
        ->select('ledgers.*')
        ->get();
    
    if ($orphanedLedgers->count() > 0) {
        echo "❌ ORPHANED LEDGERS ({$orphanedLedgers->count()}):\n";
        foreach ($orphanedLedgers as $ledger) {
            echo "  Ledger ID {$ledger->id}: Customer ID {$ledger->user_id} (not found)\n";
        }
        echo "\n";
    }
    
    // Summary
    echo str_repeat("=", 80) . "\n";
    echo "SUMMARY:\n";
    echo "Total Customers Analyzed: {$customers->count()}\n";
    echo "Customers with Issues: " . count($inconsistentCustomers) . "\n";
    echo "Orphaned Sales: {$orphanedSales->count()}\n";
    echo "Orphaned Payments: {$orphanedPayments->count()}\n";
    echo "Orphaned Ledgers: {$orphanedLedgers->count()}\n\n";
    
    if (count($inconsistentCustomers) > 0) {
        echo "CUSTOMERS NEEDING CLEANUP:\n";
        foreach ($inconsistentCustomers as $item) {
            $customer = $item['customer'];
            $customerName = trim($customer->first_name . ' ' . $customer->last_name);
            echo "• Customer ID {$customer->id}: {$customerName} (" . count($item['issues']) . " issues)\n";
        }
        echo "\nUse: php artisan ledger:cleanup {CUSTOMER_ID} --dry-run\n";
        echo "Then: php artisan ledger:cleanup {CUSTOMER_ID}\n";
    } else {
        echo "✅ All customers are consistent!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}