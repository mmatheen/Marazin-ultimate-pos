<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\Payment;
use App\Models\Ledger;

echo "\n=== CUSTOMER 83 (Aasat) - FULL ANALYSIS ===\n";

echo "\n--- SALES TABLE ---\n";
$sales = Sale::where('customer_id', 83)->orderBy('id')->get();
foreach ($sales as $sale) {
    echo "Sale #{$sale->id}: {$sale->invoice_no}\n";
    echo "  Amount: Rs.{$sale->final_total}, Paid: Rs.{$sale->total_paid}, Due: Rs." . ($sale->final_total - $sale->total_paid) . "\n";
    echo "  Status: {$sale->status}, Payment Status: {$sale->payment_status}\n";
    echo "  Date: {$sale->sales_date}\n\n";
}

echo "\n--- PAYMENTS TABLE ---\n";
$payments = Payment::where('customer_id', 83)
    ->where('payment_type', 'sale')
    ->orderBy('id')
    ->get();
foreach ($payments as $payment) {
    echo "Payment #{$payment->id}: {$payment->reference_no}\n";
    echo "  Amount: Rs.{$payment->amount}\n";
    echo "  Method: {$payment->payment_method}\n";
    echo "  Status: {$payment->status}, Payment Status: {$payment->payment_status}\n";
    echo "  Date: {$payment->payment_date}\n\n";
}

echo "\n--- LEDGER TABLE ---\n";
$ledgers = Ledger::where('contact_id', 83)
    ->where('contact_type', 'customer')
    ->orderBy('id')
    ->get();

$runningBalance = 0;
foreach ($ledgers as $ledger) {
    $runningBalance += $ledger->debit - $ledger->credit;

    echo "Ledger #{$ledger->id}: {$ledger->reference_no}\n";
    echo "  Type: {$ledger->transaction_type}\n";
    echo "  Debit: Rs.{$ledger->debit}, Credit: Rs.{$ledger->credit}\n";
    echo "  Status: {$ledger->status}\n";
    echo "  Running Balance: Rs.{$runningBalance}\n";
    echo "  Date: {$ledger->transaction_date}\n";
    if ($ledger->notes) {
        echo "  Notes: {$ledger->notes}\n";
    }
    echo "\n";
}

echo "\n--- ISSUES DETECTED ---\n";
$issues = [];

// Check for active reversed entries
$activeReversals = Ledger::where('contact_id', 83)
    ->where('contact_type', 'customer')
    ->where('reference_no', 'LIKE', '%-REV%')
    ->where('status', 'active')
    ->count();
if ($activeReversals > 0) {
    $issues[] = "Found {$activeReversals} reversal entries with status='active' (should be 'reversed')";
}

// Check if payment in payments table matches ledger
$activePayment = Payment::where('customer_id', 83)
    ->where('payment_type', 'sale')
    ->where('status', 'active')
    ->first();

if ($activePayment) {
    // Check if ledger entry exists for this payment
    $paymentLedger = Ledger::where('contact_id', 83)
        ->where('contact_type', 'customer')
        ->where('reference_no', $activePayment->reference_no)
        ->where('transaction_type', 'payments')
        ->where('status', 'active')
        ->where('credit', $activePayment->amount)
        ->exists();

    if (!$paymentLedger) {
        $issues[] = "Payment #{$activePayment->id} (Rs.{$activePayment->amount}) exists but no matching active ledger entry";
    }
}

if (count($issues) > 0) {
    foreach ($issues as $issue) {
        echo "❌ {$issue}\n";
    }
} else {
    echo "✅ No issues detected\n";
}

echo "\n--- FINAL BALANCE ---\n";
$activeBalance = Ledger::where('contact_id', 83)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->sum(\DB::raw('debit - credit'));

$allEntriesBalance = Ledger::where('contact_id', 83)
    ->where('contact_type', 'customer')
    ->sum(\DB::raw('debit - credit'));

echo "Active Entries Balance: Rs.{$activeBalance}\n";
echo "All Entries Balance (including reversed): Rs.{$allEntriesBalance}\n";
echo "\n✅ Correct balance to use: Rs.{$activeBalance} (only active entries count)\n";

echo "\n=== END OF ANALYSIS ===\n";
