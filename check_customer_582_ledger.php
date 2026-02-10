<?php
/**
 * Focused Customer 582 Ledger Check (No Global Scope)
 * Directly checks this specific customer's ledger issues
 */
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$customerId = 582;

echo "========================================================================\n";
echo "CUSTOMER {$customerId} LEDGER ISSUE CHECK\n";
echo "========================================================================\n\n";

// Get customer info
$customer = DB::table('customers')->where('id', $customerId)->first();
echo "Customer: " . ($customer->customer_name ?? 'Unknown') . "\n";
echo "Opening Balance: Rs. " . number_format($customer->opening_balance ?? 0, 2) . "\n\n";

// Get all sales for this customer
$sales = DB::table('sales')
    ->where('customer_id', $customerId)
    ->where('status', 'final')
    ->where('transaction_type', '!=', 'sale_order')
    ->orderBy('sales_date', 'asc')
    ->get(['id', 'invoice_no', 'final_total', 'sales_date', 'created_at', 'updated_at']);

echo "Total Final Sales: " . count($sales) . "\n\n";

echo "========================================================================\n";
echo "CHECKING EACH SALE'S LEDGER STATUS\n";
echo "========================================================================\n\n";

$issues = [];
$totalExpected = 0;
$totalInLedger = 0;

foreach ($sales as $sale) {
    // Check for ACTIVE ledger entry
    $activeLedger = DB::table('ledgers')
        ->where('reference_no', $sale->invoice_no)
        ->where('transaction_type', 'sale')
        ->where('contact_id', $customerId)
        ->where('status', 'active')
        ->first();
    
    // Check for REVERSED ledger entries
    $reversedLedgers = DB::table('ledgers')
        ->where('reference_no', $sale->invoice_no)
        ->where('transaction_type', 'sale')
        ->where('contact_id', $customerId)
        ->where('status', 'reversed')
        ->count();
    
    $status = $activeLedger ? '✅' : '❌';
    $totalExpected += $sale->final_total;
    
    if ($activeLedger) {
        $totalInLedger += $activeLedger->debit;
        echo "{$status} {$sale->invoice_no} | Rs. " . number_format($sale->final_total, 2) . " | {$sale->sales_date}\n";
        if ($reversedLedgers > 0) {
            echo "   Note: Has {$reversedLedgers} reversed entries (edited sale)\n";
        }
    } else {
        echo "{$status} {$sale->invoice_no} | Rs. " . number_format($sale->final_total, 2) . " | {$sale->sales_date}\n";
        echo "   ⚠️  MISSING ACTIVE LEDGER ENTRY!\n";
        if ($reversedLedgers > 0) {
            echo "   Found {$reversedLedgers} reversed entries - ledger creation failed after edit\n";
            
            // Get the reversed entry details
            $reversedEntry = DB::table('ledgers')
                ->where('reference_no', $sale->invoice_no)
                ->where('transaction_type', 'sale')
                ->where('status', 'reversed')
                ->first();
            
            if ($reversedEntry) {
                echo "   Reversed Entry ID: {$reversedEntry->id} | Original Amount: Rs. " . number_format($reversedEntry->debit, 2) . "\n";
                echo "   Reversed On: {$reversedEntry->updated_at}\n";
            }
        } else {
            echo "   No ledger entry at all - creation failed on initial save\n";
        }
        
        $issues[] = [
            'sale_id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'amount' => $sale->final_total,
            'date' => $sale->sales_date,
            'reversed_count' => $reversedLedgers
        ];
    }
    echo "\n";
}

echo "========================================================================\n";
echo "SUMMARY\n";
echo "========================================================================\n\n";

echo "Total Sales Amount: Rs. " . number_format($totalExpected, 2) . "\n";
echo "Amount in Active Ledger: Rs. " . number_format($totalInLedger, 2) . "\n";
echo "Missing from Ledger: Rs. " . number_format($totalExpected - $totalInLedger, 2) . "\n\n";

if (!empty($issues)) {
    echo "❌ FOUND " . count($issues) . " SALES WITH MISSING LEDGER ENTRIES:\n\n";
    
    $missingTotal = 0;
    foreach ($issues as $issue) {
        echo "  • {$issue['invoice_no']} (Sale ID: {$issue['sale_id']})\n";
        echo "    Amount: Rs. " . number_format($issue['amount'], 2) . "\n";
        echo "    Date: {$issue['date']}\n";
        echo "    Type: " . ($issue['reversed_count'] > 0 ? "Failed after edit" : "Failed on create") . "\n\n";
        $missingTotal += $issue['amount'];
    }
    
    echo "Total Missing: Rs. " . number_format($missingTotal, 2) . "\n\n";
} else {
    echo "✅ ALL SALES HAVE PROPER ACTIVE LEDGER ENTRIES\n\n";
}

// Check payments
echo "========================================================================\n";
echo "PAYMENT CHECK\n";
echo "========================================================================\n\n";

$paymentLedgers = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('transaction_type', 'payments')
    ->where('status', 'active')
    ->sum('credit');

$actualPayments = DB::table('payments')
    ->where('customer_id', $customerId)
    ->where('payment_type', 'sale')
    ->where('status', '!=', 'deleted')
    ->sum('amount');

echo "Payments in Ledger: Rs. " . number_format($paymentLedgers, 2) . "\n";
echo "Payments in Payments Table: Rs. " . number_format($actualPayments, 2) . "\n";

if (abs($paymentLedgers - $actualPayments) > 0.01) {
    echo "⚠️  Payment mismatch: " . number_format(abs($paymentLedgers - $actualPayments), 2) . "\n\n";
} else {
    echo "✅ Payments match\n\n";
}

// Calculate final balance
echo "========================================================================\n";
echo "BALANCE CALCULATION\n";
echo "========================================================================\n\n";

$currentLedgerBalance = DB::selectOne("
    SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance
    FROM ledgers
    WHERE contact_id = ?
        AND contact_type = 'customer'
        AND status = 'active'
", [$customerId])->balance;

$expectedBalance = ($customer->opening_balance ?? 0) + $totalExpected - $paymentLedgers;

echo "Current Ledger Balance: Rs. " . number_format($currentLedgerBalance, 2) . "\n";
echo "Expected Balance: Rs. " . number_format($expectedBalance, 2) . "\n";
echo "Difference: Rs. " . number_format($expectedBalance - $currentLedgerBalance, 2) . "\n\n";

if (abs($expectedBalance - $currentLedgerBalance) > 0.01) {
    echo "❌ BALANCE MISMATCH - Ledger needs fixing\n\n";
} else {
    echo "✅ Balance is correct\n\n";
}

// Root cause analysis
if (!empty($issues)) {
    echo "========================================================================\n";
    echo "ROOT CAUSE ANALYSIS\n";
    echo "========================================================================\n\n";
    
    foreach ($issues as $issue) {
        echo "Issue: {$issue['invoice_no']}\n";
        
        if ($issue['reversed_count'] > 0) {
            echo "Problem: Sale was edited but new ledger entry creation FAILED\n";
            echo "Cause: UnifiedLedgerService->updateSale() called reverseSale() but\n";
            echo "       recordNewSaleEntry() either failed silently or returned null\n";
            echo "Fix: Create active ledger entry with transaction_date from original sale\n";
        } else {
            echo "Problem: Initial ledger entry creation FAILED\n";
            echo "Cause: recordSale() may have failed or been skipped due to status check\n";
            echo "Fix: Create active ledger entry with original sale date\n";
        }
        echo "\n";
    }
    
    echo "========================================================================\n";
    echo "FIX COMMAND\n";
    echo "========================================================================\n";
    echo "Run: php fix_missing_ledger_entries.php --customer-id={$customerId}\n";
    echo "========================================================================\n";
}
