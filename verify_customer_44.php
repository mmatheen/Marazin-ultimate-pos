<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CUSTOMER 44 FINAL VERIFICATION ===\n\n";

$customer = DB::table('customers')->where('id', 44)->first();

echo "CUSTOMER SUMMARY:\n";
echo "  Name: {$customer->prefix} {$customer->first_name} {$customer->last_name}\n";
echo "  Opening Balance: Rs." . number_format($customer->opening_balance, 2) . "\n";
echo "  Current Balance: Rs." . number_format($customer->current_balance, 2) . "\n";
echo "  Status: " . ($customer->current_balance == $customer->opening_balance ? "✓ CORRECT" : "✗ INCORRECT") . "\n";

echo "\nSALES SUMMARY:\n";
$sales = DB::table('sales')->where('customer_id', 44)->get();
$totalSales = 0;
$totalPaid = 0;
$totalDue = 0;
$allPaid = true;

foreach ($sales as $sale) {
    $totalSales += $sale->final_total;
    $totalPaid += $sale->total_paid;
    $totalDue += $sale->total_due;

    $status = $sale->total_due == 0 ? '✓ Paid' : '✗ Due: Rs.' . number_format($sale->total_due, 2);
    echo "  {$sale->invoice_no}: Rs." . number_format($sale->final_total, 2) . " - $status\n";

    if ($sale->total_due > 0) {
        $allPaid = false;
    }
}

echo "\n  Total Sales: Rs." . number_format($totalSales, 2) . "\n";
echo "  Total Paid: Rs." . number_format($totalPaid, 2) . "\n";
echo "  Total Due: Rs." . number_format($totalDue, 2) . "\n";
echo "  All Sales Paid: " . ($allPaid ? "✓ YES" : "✗ NO") . "\n";

echo "\nPAYMENT SUMMARY:\n";
$payments = DB::table('payments')->where('customer_id', 44)->get();
$salePayments = 0;
$openingBalancePayments = 0;

foreach ($payments as $payment) {
    if ($payment->payment_type == 'sale') {
        $salePayments += $payment->amount;
        echo "  Sale Payment: Rs." . number_format($payment->amount, 2) . " ({$payment->reference_no})\n";
    } elseif ($payment->payment_type == 'opening_balance') {
        $openingBalancePayments += $payment->amount;
        echo "  Opening Balance Payment: Rs." . number_format($payment->amount, 2) . " ({$payment->reference_no})\n";
    }
}

echo "\n  Total Sale Payments: Rs." . number_format($salePayments, 2) . "\n";
echo "  Total Opening Balance Payments: Rs." . number_format($openingBalancePayments, 2) . "\n";

echo "\nLEDGER VALIDATION:\n";
$ledgerBalance = DB::table('ledgers')
    ->where('contact_id', 44)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->first()
    ->balance;

echo "  Ledger Balance: Rs." . number_format($ledgerBalance, 2) . "\n";
echo "  Matches Customer Balance: " . ($ledgerBalance == $customer->current_balance ? "✓ YES" : "✗ NO") . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "FINAL STATUS:\n";
echo str_repeat("=", 60) . "\n";

if ($customer->current_balance == $customer->opening_balance &&
    $allPaid &&
    $ledgerBalance == $customer->current_balance &&
    $openingBalancePayments == 0) {
    echo "✓✓✓ ALL CHECKS PASSED ✓✓✓\n";
    echo "✓ Current balance equals opening balance (Rs." . number_format($customer->opening_balance, 2) . ")\n";
    echo "✓ All sales are fully paid\n";
    echo "✓ Ledger is correctly balanced\n";
    echo "✓ No incorrect opening balance payments\n";
    echo "✓ Only opening balance remains as customer due\n";
} else {
    echo "✗ ISSUES DETECTED:\n";
    if ($customer->current_balance != $customer->opening_balance) {
        echo "  ✗ Current balance doesn't match opening balance\n";
    }
    if (!$allPaid) {
        echo "  ✗ Some sales are not fully paid\n";
    }
    if ($ledgerBalance != $customer->current_balance) {
        echo "  ✗ Ledger balance doesn't match customer balance\n";
    }
    if ($openingBalancePayments > 0) {
        echo "  ✗ Opening balance payments exist (should be 0)\n";
    }
}
