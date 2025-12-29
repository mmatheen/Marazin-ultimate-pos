<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CUSTOMER 44 ANALYSIS ===\n\n";

// Get ledger entries
echo "LEDGER ENTRIES:\n";
$ledgers = DB::table('ledgers')
    ->where('contact_id', 44)
    ->where('contact_type', 'customer')
    ->orderBy('transaction_date')
    ->orderBy('id')
    ->get(['id', 'transaction_date', 'transaction_type', 'reference_no', 'debit', 'credit', 'status']);

$runningBalance = 0;
foreach ($ledgers as $ledger) {
    if ($ledger->status === 'active') {
        $runningBalance += $ledger->debit - $ledger->credit;
    }
    echo sprintf(
        "ID: %4d | Date: %s | Type: %-30s | Ref: %-30s | Debit: %10.2f | Credit: %10.2f | Status: %s | Balance: %10.2f\n",
        $ledger->id,
        $ledger->transaction_date,
        $ledger->transaction_type,
        $ledger->reference_no,
        $ledger->debit,
        $ledger->credit,
        $ledger->status,
        $runningBalance
    );
}

echo "\n\nSALES:\n";
$sales = DB::table('sales')->where('customer_id', 44)->get(['id', 'invoice_no', 'final_total', 'total_paid', 'total_due']);
foreach ($sales as $sale) {
    echo sprintf(
        "ID: %4d | Invoice: %-10s | Total: %10.2f | Paid: %10.2f | Due: %10.2f\n",
        $sale->id,
        $sale->invoice_no,
        $sale->final_total,
        $sale->total_paid,
        $sale->total_due
    );
}

echo "\n\nPAYMENTS:\n";
$payments = DB::table('payments')->where('customer_id', 44)->get(['id', 'payment_date', 'amount', 'payment_type', 'reference_id', 'reference_no']);
foreach ($payments as $payment) {
    echo sprintf(
        "ID: %4d | Date: %s | Amount: %10.2f | Type: %-20s | Ref ID: %s | Ref No: %s\n",
        $payment->id,
        $payment->payment_date,
        $payment->amount,
        $payment->payment_type,
        $payment->reference_id ?? 'NULL',
        $payment->reference_no
    );
}

echo "\n\nCUSTOMER BALANCE:\n";
$customer = DB::table('customers')->where('id', 44)->first(['id', 'prefix', 'first_name', 'last_name', 'opening_balance', 'current_balance']);
echo sprintf(
    "ID: %d | Name: %s %s %s | Opening: %10.2f | Current: %10.2f\n",
    $customer->id,
    $customer->prefix,
    $customer->first_name,
    $customer->last_name,
    $customer->opening_balance,
    $customer->current_balance
);
