<?php
/**
 * Comprehensive Customer 582 Ledger Diagnostic
 * Checks all ledger entries and identifies issues
 */
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================================================\n";
echo "CUSTOMER 582 LEDGER DIAGNOSTIC\n";
echo "========================================================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Get customer info
$customer = DB::table('customers')->where('id', 582)->first();

if (!$customer) {
    die("ERROR: Customer 582 not found!\n");
}

$customerName = $customer->customer_name ?? 'Unknown';
echo "Customer: {$customerName}\n";
echo "Customer ID: 582\n";
echo "Opening Balance: Rs. " . number_format($customer->opening_balance ?? 0, 2) . "\n\n";

// Check all ledger entries
echo "========================================================================\n";
echo "ALL LEDGER ENTRIES (Sorted by Transaction Date)\n";
echo "========================================================================\n\n";

$allEntries = DB::table('ledgers')
    ->where('contact_id', 582)
    ->where('contact_type', 'customer')
    ->orderBy('transaction_date', 'asc')
    ->orderBy('id', 'asc')
    ->get();

$activeDebit = 0;
$activeCredit = 0;
$reversedDebit = 0;
$reversedCredit = 0;

foreach ($allEntries as $entry) {
    $statusIcon = $entry->status === 'active' ? '✅' : '🔄';
    $type = $entry->debit > 0 ? "DR: " . number_format($entry->debit, 2) : "CR: " . number_format($entry->credit, 2);

    echo "{$statusIcon} ID: {$entry->id} | Date: {$entry->transaction_date} | {$entry->reference_no}\n";
    echo "   Type: {$entry->transaction_type} | Amount: {$type} | Status: {$entry->status}\n";
    echo "   Notes: " . substr($entry->notes, 0, 80) . "\n";
    echo "   Created: {$entry->created_at}\n\n";

    if ($entry->status === 'active') {
        $activeDebit += $entry->debit;
        $activeCredit += $entry->credit;
    } else {
        $reversedDebit += $entry->debit;
        $reversedCredit += $entry->credit;
    }
}

echo "========================================================================\n";
echo "BALANCE CALCULATION\n";
echo "========================================================================\n\n";

echo "ACTIVE Entries:\n";
echo "  Total Debits:  Rs. " . number_format($activeDebit, 2) . "\n";
echo "  Total Credits: Rs. " . number_format($activeCredit, 2) . "\n";
echo "  Balance:       Rs. " . number_format($activeDebit - $activeCredit, 2) . " ✅\n\n";

echo "REVERSED Entries (should not affect balance):\n";
echo "  Total Debits:  Rs. " . number_format($reversedDebit, 2) . "\n";
echo "  Total Credits: Rs. " . number_format($reversedCredit, 2) . "\n\n";

// Check for sales with missing active entries
echo "========================================================================\n";
echo "SALES WITHOUT ACTIVE LEDGER ENTRIES\n";
echo "========================================================================\n\n";

$salesWithoutLedger = DB::select("
    SELECT
        s.id as sale_id,
        s.invoice_no,
        s.final_total,
        s.sales_date,
        s.status,
        COUNT(l_active.id) as active_count,
        COUNT(l_reversed.id) as reversed_count
    FROM sales s
    LEFT JOIN ledgers l_active ON (
        l_active.reference_no = s.invoice_no
        AND l_active.transaction_type = 'sale'
        AND l_active.status = 'active'
    )
    LEFT JOIN ledgers l_reversed ON (
        l_reversed.reference_no = s.invoice_no
        AND l_reversed.transaction_type = 'sale'
        AND l_reversed.status = 'reversed'
    )
    WHERE s.customer_id = 582
        AND s.status IN ('final', 'suspend')
    GROUP BY s.id, s.invoice_no, s.final_total, s.sales_date, s.status
    HAVING active_count = 0
    ORDER BY s.sales_date
");

if (empty($salesWithoutLedger)) {
    echo "✅ No issues found - All finalized sales have active ledger entries\n\n";
} else {
    echo "❌ Found " . count($salesWithoutLedger) . " sales without active ledger entries:\n\n";

    foreach ($salesWithoutLedger as $sale) {
        echo "  Sale ID: {$sale->sale_id}\n";
        echo "  Invoice: {$sale->invoice_no}\n";
        echo "  Amount: Rs. " . number_format($sale->final_total, 2) . "\n";
        echo "  Date: {$sale->sales_date}\n";
        echo "  Status: {$sale->status}\n";
        echo "  Active Entries: {$sale->active_count} ❌\n";
        echo "  Reversed Entries: {$sale->reversed_count}\n\n";
    }
}

// Check payments
echo "========================================================================\n";
echo "PAYMENT LEDGER ENTRIES\n";
echo "========================================================================\n\n";

$payments = DB::table('ledgers')
    ->where('contact_id', 582)
    ->where('contact_type', 'customer')
    ->where('transaction_type', 'payments')
    ->where('status', 'active')
    ->orderBy('transaction_date', 'asc')
    ->get();

if ($payments->isEmpty()) {
    echo "No payment ledger entries found\n\n";
} else {
    echo "Found " . count($payments) . " payment entries:\n\n";
    foreach ($payments as $payment) {
        echo "  {$payment->reference_no} | Date: {$payment->transaction_date} | CR: " . number_format($payment->credit, 2) . "\n";
    }
    echo "\n";
}

// Compare with actual payments table
echo "========================================================================\n";
echo "ACTUAL PAYMENTS FROM PAYMENTS TABLE\n";
echo "========================================================================\n\n";

$actualPayments = DB::table('payments')
    ->where('customer_id', 582)
    ->where('payment_type', 'sale')
    ->where('status', '!=', 'deleted')
    ->orderBy('payment_date', 'asc')
    ->get(['id', 'reference_id', 'amount', 'payment_date', 'payment_method', 'status']);

if ($actualPayments->isEmpty()) {
    echo "No payments found in payments table\n\n";
} else {
    echo "Found " . count($actualPayments) . " payments:\n\n";

    $totalPayments = 0;
    foreach ($actualPayments as $payment) {
        echo "  Payment ID: {$payment->id} | Sale ID: {$payment->reference_id} | Rs. " . number_format($payment->amount, 2) . "\n";
        echo "  Date: {$payment->payment_date} | Method: {$payment->payment_method} | Status: {$payment->status}\n\n";
        $totalPayments += $payment->amount;
    }

    echo "Total Payments: Rs. " . number_format($totalPayments, 2) . "\n\n";
}

// Summary
echo "========================================================================\n";
echo "DIAGNOSTIC SUMMARY\n";
echo "========================================================================\n\n";

$currentBalance = $activeDebit - $activeCredit;

echo "Current Ledger Balance: Rs. " . number_format($currentBalance, 2) . "\n";
echo "Total Active Entries: " . count($allEntries->where('status', 'active')) . "\n";
echo "Total Reversed Entries: " . count($allEntries->where('status', 'reversed')) . "\n\n";

if (!empty($salesWithoutLedger)) {
    $missingAmount = array_sum(array_column($salesWithoutLedger, 'final_total'));
    echo "⚠️  WARNING: Missing ledger entries for Rs. " . number_format($missingAmount, 2) . "\n";
    echo "⚠️  Expected balance should be: Rs. " . number_format($currentBalance + $missingAmount, 2) . "\n\n";
    echo "ACTION REQUIRED: Run fix_missing_ledger_entries.php --customer-id=582\n\n";
} else {
    echo "✅ All sales have proper ledger entries\n";
    echo "✅ Ledger is in good state\n\n";
}

echo "========================================================================\n";
echo "To fix missing entries, run:\n";
echo "php fix_missing_ledger_entries.php --customer-id=582\n";
echo "========================================================================\n";
