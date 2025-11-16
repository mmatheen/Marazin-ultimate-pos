<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== OTHER CUSTOMERS STATUS (EXCLUDING WALK-IN) ===\n\n";

try {
    // Get all customers except Walk-in Customer (ID 1)
    $customers = DB::table('customers')->where('id', '!=', 1)->orderBy('id')->get();
    echo "Total Credit Customers: {$customers->count()}\n\n";
    
    $inconsistentCustomers = [];
    $fixedCustomers = [];
    
    echo "CHECKING EACH CREDIT CUSTOMER...\n";
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
        
        // Check for issues (only for credit customers)
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
        
        if ($hasData) {
            echo "Customer ID {$customer->id}: {$customerName}\n";
            echo "  Balance: {$customer->current_balance} | Sales: {$sales->count()} | Payments: {$payments->count()} | Ledger: {$ledgers->count()}\n";
            
            if ($totalSales > 0) echo "  Total Sales: {$totalSales}\n";
            if ($totalPayments > 0) echo "  Total Payments: {$totalPayments}\n";
            if ($totalReturns > 0) echo "  Total Returns: {$totalReturns}\n";
            
            if (!empty($issues)) {
                echo "  ❌ ISSUES (" . count($issues) . "):\n";
                foreach (array_slice($issues, 0, 3) as $issue) {  // Show only first 3 issues
                    echo "     • {$issue}\n";
                }
                if (count($issues) > 3) {
                    echo "     • ... and " . (count($issues) - 3) . " more issues\n";
                }
                $inconsistentCustomers[] = [
                    'id' => $customer->id,
                    'name' => $customerName,
                    'issues_count' => count($issues),
                    'sales_count' => $sales->count(),
                    'payments_count' => $payments->count(),
                    'ledger_count' => $ledgers->count()
                ];
            } else {
                echo "  ✅ All consistent\n";
                $fixedCustomers[] = [
                    'id' => $customer->id,
                    'name' => $customerName
                ];
            }
            echo "\n";
        }
    }
    
    // Summary
    echo str_repeat("=", 80) . "\n";
    echo "SUMMARY:\n";
    echo "Total Credit Customers: {$customers->count()}\n";
    echo "Customers Already Fixed: " . count($fixedCustomers) . "\n";
    echo "Customers Needing Cleanup: " . count($inconsistentCustomers) . "\n\n";
    
    if (count($fixedCustomers) > 0) {
        echo "✅ CUSTOMERS ALREADY FIXED:\n";
        foreach ($fixedCustomers as $customer) {
            echo "• Customer ID {$customer['id']}: {$customer['name']}\n";
        }
        echo "\n";
    }
    
    if (count($inconsistentCustomers) > 0) {
        echo "❌ CUSTOMERS NEEDING CLEANUP:\n";
        
        // Group by severity
        $critical = array_filter($inconsistentCustomers, fn($c) => $c['issues_count'] >= 4);
        $moderate = array_filter($inconsistentCustomers, fn($c) => $c['issues_count'] >= 2 && $c['issues_count'] < 4);
        $minor = array_filter($inconsistentCustomers, fn($c) => $c['issues_count'] == 1);
        
        if (count($critical) > 0) {
            echo "\n🔥 CRITICAL (4+ issues):\n";
            foreach ($critical as $customer) {
                echo "• Customer ID {$customer['id']}: {$customer['name']} ({$customer['issues_count']} issues)\n";
            }
        }
        
        if (count($moderate) > 0) {
            echo "\n⚠️ MODERATE (2-3 issues):\n";
            foreach ($moderate as $customer) {
                echo "• Customer ID {$customer['id']}: {$customer['name']} ({$customer['issues_count']} issues)\n";
            }
        }
        
        if (count($minor) > 0) {
            echo "\n📝 MINOR (1 issue):\n";
            foreach ($minor as $customer) {
                echo "• Customer ID {$customer['id']}: {$customer['name']} ({$customer['issues_count']} issues)\n";
            }
        }
        
        echo "\nNEXT STEPS:\n";
        echo "1. Fix critical customers first using: php artisan ledger:cleanup {CUSTOMER_ID} --dry-run\n";
        echo "2. Then fix moderate and minor issues\n";
        echo "3. Don't forget about 178 orphaned payments that need cleanup\n";
    } else {
        echo "✅ All credit customers are consistent!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}