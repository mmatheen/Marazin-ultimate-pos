<?php
/**
 * ===================================================================
 * 🔍 CUSTOMER 7 QUICK CHECK
 * ===================================================================
 * 
 * Quick verification of what Customer 7 should actually have
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 CUSTOMER 7 QUICK VERIFICATION\n";
echo "==============================\n\n";

$customerId = 7;

// Customer details
$customer = DB::table('customers')->where('id', $customerId)->first();
echo "👤 Customer: {$customer->first_name} {$customer->last_name}\n";
echo "📊 Opening Balance: {$customer->opening_balance}\n\n";

// What SHOULD exist (from actual business tables)
echo "✅ WHAT SHOULD EXIST:\n";
echo "====================\n";

// 1. Actual sales
$sales = DB::table('sales')->where('customer_id', $customerId)->get();
echo "1. Sales from sales table: " . $sales->count() . "\n";
foreach ($sales as $i => $sale) {
    echo "   " . ($i + 1) . ". Invoice: {$sale->invoice_no} | Amount: {$sale->final_total} | Date: {$sale->created_at}\n";
}

// 2. Actual payments
$payments = DB::table('payments')->where('customer_id', $customerId)->get();
echo "\n2. Payments from payments table: " . $payments->count() . "\n";
foreach ($payments as $i => $payment) {
    echo "   " . ($i + 1) . ". Ref: {$payment->reference_no} | Amount: {$payment->amount} | Date: {$payment->created_at}\n";
}

// 3. Opening balance (should be 1)
echo "\n3. Opening balance: 1 entry (Rs. {$customer->opening_balance})\n";

$expectedTotal = $sales->count() + $payments->count() + 1;
echo "\n📋 EXPECTED TOTAL LEDGER ENTRIES: {$expectedTotal}\n\n";

// What ACTUALLY exists in ledger
echo "❌ WHAT ACTUALLY EXISTS IN LEDGER:\n";
echo "==================================\n";

$ledgerEntries = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->get();

echo "Current active ledger entries: " . $ledgerEntries->count() . "\n\n";

// Group by type
$salesLedger = $ledgerEntries->where('transaction_type', 'sale');
$paymentsLedger = $ledgerEntries->whereIn('transaction_type', ['payment', 'payments', 'sale_payment']);
$openingLedger = $ledgerEntries->where('transaction_type', 'opening_balance');

echo "Sales entries: " . $salesLedger->count() . " (should be " . $sales->count() . ")\n";
echo "Payment entries: " . $paymentsLedger->count() . " (should be " . $payments->count() . ")\n";
echo "Opening balance entries: " . $openingLedger->count() . " (should be 1)\n\n";

// Show duplicates
if ($salesLedger->count() > $sales->count()) {
    echo "🔴 SALES DUPLICATES:\n";
    $salesByRef = $salesLedger->groupBy('reference_no');
    foreach ($salesByRef as $ref => $entries) {
        if ($entries->count() > 1) {
            echo "   {$ref}: " . $entries->count() . " entries (IDs: " . $entries->pluck('id')->implode(', ') . ")\n";
        }
    }
}

if ($paymentsLedger->count() > $payments->count()) {
    echo "\n🔴 PAYMENT DUPLICATES:\n";
    $paymentsByRef = $paymentsLedger->groupBy('reference_no');
    foreach ($paymentsByRef as $ref => $entries) {
        if ($entries->count() > 1) {
            echo "   {$ref}: " . $entries->count() . " entries (IDs: " . $entries->pluck('id')->implode(', ') . ")\n";
        }
    }
}

if ($openingLedger->count() > 1) {
    echo "\n🔴 OPENING BALANCE DUPLICATES:\n";
    echo "   " . $openingLedger->count() . " entries (IDs: " . $openingLedger->pluck('id')->implode(', ') . ")\n";
}

// Current balance calculation
$currentBalance = $ledgerEntries->sum(function($entry) {
    return $entry->debit - $entry->credit;
});

echo "\n💰 BALANCE CALCULATION:\n";
echo "Current ledger balance: {$currentBalance}\n";
echo "Expected balance: {$customer->opening_balance} (if only opening balance, no sales/payments)\n";

if ($sales->count() == 0 && $payments->count() == 0) {
    echo "✅ This customer should only have opening balance of {$customer->opening_balance}\n";
} else {
    $expectedBalance = $customer->opening_balance + $sales->sum('final_total') - $payments->sum('amount');
    echo "Expected balance with sales/payments: {$expectedBalance}\n";
}

echo "\n🚀 TO FIX: Run 'php fix_customer7.php'\n";