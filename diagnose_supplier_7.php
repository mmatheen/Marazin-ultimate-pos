<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 100) . "\n";
echo "DETAILED ANALYSIS: SUPPLIER 7 (WESCO GAS) LEDGER MISMATCH\n";
echo str_repeat("=", 100) . "\n\n";

$supplierId = 7;

$supplier = DB::table('suppliers')->where('id', $supplierId)->first();

echo "Supplier: {$supplier->first_name} {$supplier->last_name}\n";
echo "Mobile: {$supplier->mobile_no}\n";
echo "Opening Balance: " . number_format($supplier->opening_balance ?? 0, 2) . "\n\n";

// Get ALL ledger entries (including reversed)
echo str_repeat("=", 100) . "\n";
echo "ALL LEDGER ENTRIES (Including Reversed)\n";
echo str_repeat("=", 100) . "\n";

$allLedgers = DB::table('ledgers')
    ->where('contact_id', $supplierId)
    ->where('contact_type', 'supplier')
    ->orderBy('id')
    ->get();

echo sprintf("%-6s %-20s %-20s %15s %15s %15s %-10s %s\n",
    "ID", "Date", "Type", "Debit", "Credit", "Balance", "Status", "Ref");
echo str_repeat("-", 100) . "\n";

$balance = 0;
foreach ($allLedgers as $ledger) {
    $balance += ($ledger->credit - $ledger->debit);
    echo sprintf("%-6d %-20s %-20s %15.2f %15.2f %15.2f %-10s %s\n",
        $ledger->id,
        $ledger->transaction_date,
        $ledger->transaction_type,
        $ledger->debit,
        $ledger->credit,
        $balance,
        $ledger->status,
        $ledger->reference_no
    );
}

$activeBalance = $allLedgers->where('status', 'active')->sum('credit') - 
                 $allLedgers->where('status', 'active')->sum('debit');
$totalBalance = $allLedgers->sum('credit') - $allLedgers->sum('debit');

echo str_repeat("-", 100) . "\n";
echo "Active Ledger Balance: " . number_format($activeBalance, 2) . "\n";
echo "Total Ledger Balance (inc. reversed): " . number_format($totalBalance, 2) . "\n\n";

// Get ALL purchases
echo str_repeat("=", 100) . "\n";
echo "ALL PURCHASES\n";
echo str_repeat("=", 100) . "\n";

$purchases = DB::table('purchases')
    ->where('supplier_id', $supplierId)
    ->orderBy('id')
    ->get();

echo sprintf("%-6s %-15s %-12s %15s %15s %15s %s\n",
    "ID", "Ref", "Date", "Total", "Paid", "Due", "Status");
echo str_repeat("-", 100) . "\n";

foreach ($purchases as $purchase) {
    echo sprintf("%-6d %-15s %-12s %15.2f %15.2f %15.2f %s\n",
        $purchase->id,
        $purchase->reference_no,
        $purchase->purchase_date,
        $purchase->final_total,
        $purchase->total_paid,
        $purchase->total_due,
        $purchase->payment_status
    );
}

echo str_repeat("-", 100) . "\n";
echo "Total Purchases: " . number_format($purchases->sum('final_total'), 2) . "\n";
echo "Total Paid: " . number_format($purchases->sum('total_paid'), 2) . "\n";
echo "Total Due: " . number_format($purchases->sum('total_due'), 2) . "\n\n";

// Get ALL payments
echo str_repeat("=", 100) . "\n";
echo "ALL PAYMENTS (Including Deleted)\n";
echo str_repeat("=", 100) . "\n";

$allPayments = DB::table('payments')
    ->where('supplier_id', $supplierId)
    ->orderBy('id')
    ->get();

echo sprintf("%-6s %-12s %-15s %15s %-10s %-10s %s\n",
    "ID", "Date", "Ref", "Amount", "Method", "Status", "Notes");
echo str_repeat("-", 100) . "\n";

foreach ($allPayments as $payment) {
    echo sprintf("%-6d %-12s %-15s %15.2f %-10s %-10s %s\n",
        $payment->id,
        $payment->payment_date,
        $payment->reference_no,
        $payment->amount,
        $payment->payment_method,
        $payment->status,
        substr($payment->notes ?? '', 0, 30)
    );
}

$activePaymentTotal = $allPayments->where('status', 'active')->sum('amount');
$totalPayments = $allPayments->sum('amount');

echo str_repeat("-", 100) . "\n";
echo "Active Payments Total: " . number_format($activePaymentTotal, 2) . "\n";
echo "Total Payments (inc. deleted): " . number_format($totalPayments, 2) . "\n\n";

// Analysis
echo str_repeat("=", 100) . "\n";
echo "PROBLEM ANALYSIS\n";
echo str_repeat("=", 100) . "\n\n";

$purchaseLedgers = $allLedgers->where('status', 'active')->whereIn('transaction_type', ['purchase']);
$paymentLedgers = $allLedgers->where('status', 'active')->whereIn('transaction_type', ['payment', 'payments']);

echo "Purchase Ledger Entries (credit): " . number_format($purchaseLedgers->sum('credit'), 2) . "\n";
echo "Payment Ledger Entries (debit): " . number_format($paymentLedgers->sum('debit'), 2) . "\n";
echo "Net Balance: " . number_format($purchaseLedgers->sum('credit') - $paymentLedgers->sum('debit'), 2) . "\n\n";

echo "Expected from Purchases:\n";
echo "  Total Purchases: " . number_format($purchases->sum('final_total'), 2) . "\n";
echo "  Total Paid: " . number_format($purchases->sum('total_paid'), 2) . "\n";
echo "  Total Due: " . number_format($purchases->sum('total_due'), 2) . "\n\n";

// Check for missing purchase ledger entries
echo "Checking for missing PURCHASE ledger entries:\n";
$missingPurchaseLedgers = 0;
foreach ($purchases as $purchase) {
    $ledgerExists = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('transaction_type', 'purchase')
        ->where('reference_no', $purchase->reference_no)
        ->where('credit', $purchase->final_total)
        ->where('status', 'active')
        ->exists();
    
    if (!$ledgerExists) {
        echo "  ⚠ Missing purchase ledger for {$purchase->reference_no} (Amount: {$purchase->final_total})\n";
        $missingPurchaseLedgers++;
    }
}

if ($missingPurchaseLedgers == 0) {
    echo "  ✓ All purchases have ledger entries\n";
}

echo "\nChecking for missing PAYMENT ledger entries:\n";
$activePayments = $allPayments->where('status', 'active');
$missingPaymentLedgers = 0;
foreach ($activePayments as $payment) {
    $ledgerExists = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('transaction_type', 'payments')
        ->where('reference_no', $payment->reference_no)
        ->where('debit', $payment->amount)
        ->where('status', 'active')
        ->exists();
    
    if (!$ledgerExists) {
        echo "  ⚠ Missing payment ledger for Payment #{$payment->id} {$payment->reference_no} (Amount: {$payment->amount})\n";
        $missingPaymentLedgers++;
    }
}

if ($missingPaymentLedgers == 0) {
    echo "  ✓ All active payments have ledger entries\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "MISMATCH REASON:\n";
echo str_repeat("=", 100) . "\n";

$discrepancy = $activeBalance - $purchases->sum('total_due');
echo "\nLedger shows: " . number_format($activeBalance, 2) . "\n";
echo "Purchases show: " . number_format($purchases->sum('total_due'), 2) . "\n";
echo "Discrepancy: " . number_format($discrepancy, 2) . "\n\n";

if ($discrepancy > 0) {
    echo "⚠ Ledger balance is HIGHER than purchase balance by " . number_format($discrepancy, 2) . "\n";
    echo "This suggests:\n";
    echo "  - Extra purchase ledger entries that shouldn't be there\n";
    echo "  - OR missing payment ledger entries\n";
    echo "  - OR opening balance issue\n";
} elseif ($discrepancy < 0) {
    echo "⚠ Ledger balance is LOWER than purchase balance by " . number_format(abs($discrepancy), 2) . "\n";
    echo "This suggests:\n";
    echo "  - Missing purchase ledger entries\n";
    echo "  - OR extra payment ledger entries\n";
}

echo "\n" . str_repeat("=", 100) . "\n\n";
