<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

echo "=== CHECKING CUSTOMER ID 6 LEDGER (WITHOUT LOCATION SCOPE) ===\n\n";

// Get customer details
$customer = Customer::withoutLocationScope()->find(6);
if (!$customer) {
    echo "Customer ID 6 not found!\n";
    exit;
}

echo "Customer Name: {$customer->name}\n";
echo "Opening Balance: {$customer->opening_balance}\n";
echo "Cached Balance: {$customer->balance}\n\n";

// Get all ledger entries
echo "=== LEDGER ENTRIES ===\n";
$ledgerEntries = Ledger::where('contact_id', 6)
    ->where('contact_type', 'customer')
    ->orderBy('transaction_date', 'asc')
    ->orderBy('id', 'asc')
    ->get();

if ($ledgerEntries->isEmpty()) {
    echo "No ledger entries found.\n";
} else {
    foreach ($ledgerEntries as $entry) {
        echo sprintf(
            "ID: %d | Date: %s | Type: %s | Ref: %s | Debit: %.2f | Credit: %.2f | Status: %s | Notes: %s\n",
            $entry->id,
            $entry->transaction_date,
            $entry->transaction_type,
            $entry->reference_no,
            $entry->debit,
            $entry->credit,
            $entry->status,
            $entry->notes ?? 'N/A'
        );
    }
}

// Calculate balance from ledger
$activeEntries = $ledgerEntries->where('status', 'active');
$totalDebit = $activeEntries->sum('debit');
$totalCredit = $activeEntries->sum('credit');
$ledgerBalance = $totalDebit - $totalCredit;

echo "\n=== LEDGER SUMMARY ===\n";
echo "Total Debit (Active): {$totalDebit}\n";
echo "Total Credit (Active): {$totalCredit}\n";
echo "Calculated Balance: {$ledgerBalance}\n\n";

// Get all sales/invoices
echo "=== SALES/INVOICES ===\n";
$sales = Sale::withoutGlobalScopes()->where('customer_id', 6)->orderBy('sales_date', 'asc')->get();

if ($sales->isEmpty()) {
    echo "No sales found.\n";
} else {
    foreach ($sales as $sale) {
        echo sprintf(
            "ID: %d | Date: %s | Invoice: %s | Total: %.2f | Paid: %.2f | Due: %.2f | Status: %s\n",
            $sale->id,
            $sale->sales_date,
            $sale->invoice_no,
            $sale->final_total ?? $sale->grand_total ?? 0,
            $sale->total_paid ?? $sale->paid_amount ?? 0,
            $sale->total_due ?? $sale->due_amount ?? 0,
            $sale->payment_status ?? 'N/A'
        );
    }
}

$totalSales = $sales->sum(function($sale) {
    return $sale->final_total ?? $sale->grand_total ?? 0;
});
$totalPaidFromSales = $sales->sum(function($sale) {
    return $sale->total_paid ?? $sale->paid_amount ?? 0;
});
$totalDueFromSales = $sales->sum(function($sale) {
    return $sale->total_due ?? $sale->due_amount ?? 0;
});

echo "\nTotal Sales: {$totalSales}\n";
echo "Total Paid (from sales): {$totalPaidFromSales}\n";
echo "Total Due (from sales): {$totalDueFromSales}\n\n";

// Get all payments
echo "=== PAYMENTS ===\n";
$payments = Payment::withoutGlobalScopes()->where('customer_id', 6)->orderBy('payment_date', 'asc')->get();

if ($payments->isEmpty()) {
    echo "No payments found.\n";
} else {
    foreach ($payments as $payment) {
        echo sprintf(
            "ID: %d | Date: %s | Ref: %s | Amount: %.2f | Type: %s | Sale ID: %s | Status: %s\n",
            $payment->id,
            $payment->payment_date,
            $payment->reference_no ?? 'N/A',
            $payment->amount,
            $payment->payment_type ?? 'N/A',
            $payment->sale_id ?? 'N/A',
            $payment->status ?? 'N/A'
        );
    }
}

$activePayments = $payments->where('status', 'active');
$totalPayments = $activePayments->sum('amount');
$totalAllPayments = $payments->sum('amount');
echo "\nTotal Active Payments: {$totalPayments}\n";
echo "Total All Payments (including deleted): {$totalAllPayments}\n\n";

// Check for discrepancies
echo "=== DISCREPANCY CHECK ===\n";
$expectedBalance = $totalSales - $totalPayments;
echo "Expected Balance (Sales - Payments): {$expectedBalance}\n";
echo "Ledger Balance: {$ledgerBalance}\n";
echo "Cached Balance: {$customer->balance}\n";

if (abs($expectedBalance - $ledgerBalance) > 0.01) {
    echo "\n⚠️ WARNING: Expected balance doesn't match ledger balance!\n";
    echo "Difference: " . ($ledgerBalance - $expectedBalance) . "\n";
}

if (abs($ledgerBalance - $customer->balance) > 0.01) {
    echo "\n⚠️ WARNING: Ledger balance doesn't match cached balance!\n";
    echo "Difference: " . ($customer->balance - $ledgerBalance) . "\n";
}

// Check for duplicate entries
echo "\n=== CHECKING FOR DUPLICATES ===\n";
$duplicateCheck = Ledger::where('contact_id', 6)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->select('reference_no', 'transaction_type', 'debit', 'credit', DB::raw('COUNT(*) as count'))
    ->groupBy('reference_no', 'transaction_type', 'debit', 'credit')
    ->having('count', '>', 1)
    ->get();

if ($duplicateCheck->isEmpty()) {
    echo "No duplicate entries found.\n";
} else {
    echo "⚠️ DUPLICATE ENTRIES FOUND:\n";
    foreach ($duplicateCheck as $dup) {
        echo sprintf(
            "Ref: %s | Type: %s | Debit: %.2f | Credit: %.2f | Count: %d\n",
            $dup->reference_no,
            $dup->transaction_type,
            $dup->debit,
            $dup->credit,
            $dup->count
        );
    }
}

echo "\n=== DONE ===\n";
