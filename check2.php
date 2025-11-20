<?php
/**
 * ===================================================================
 * 🔍 CUSTOMER 2 (SITHIK STORE) QUICK CHECK
 * ===================================================================
 * 
 * Quick verification of Customer 2's ledger status
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 CUSTOMER 2 (SITHIK STORE) QUICK CHECK\n";
echo "=======================================\n\n";

$customerId = 2;

// Customer details
$customer = DB::table('customers')->where('id', $customerId)->first();
echo "👤 Customer: {$customer->first_name} {$customer->last_name}\n";
echo "📊 Opening Balance: {$customer->opening_balance}\n\n";

// Check actual business data
$sales = DB::table('sales')->where('customer_id', $customerId)->get();
$payments = DB::table('payments')->where('customer_id', $customerId)->get();

$totalSales = $sales->sum('final_total');
$totalPayments = $payments->sum('amount');
$netAmount = $totalSales - $totalPayments;

echo "💼 ACTUAL BUSINESS DATA:\n";
echo "Total Sales: {$sales->count()} records = Rs. {$totalSales}\n";
echo "Total Payments: {$payments->count()} records = Rs. {$totalPayments}\n";
echo "Net Outstanding: Rs. {$netAmount}\n";

if (abs($netAmount) < 1) {
    echo "✅ ALL BILLS ARE SETTLED!\n";
    echo "Expected Balance: Rs. {$customer->opening_balance} (only opening balance)\n";
} else {
    echo "⚠️  Outstanding amount: Rs. {$netAmount}\n";
    echo "Expected Balance: Rs. " . ($customer->opening_balance + $netAmount) . "\n";
}
echo "\n";

// Check ledger data
$ledgerEntries = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->get();

$currentBalance = $ledgerEntries->sum(function($entry) {
    return $entry->debit - $entry->credit;
});

echo "📋 CURRENT LEDGER STATUS:\n";
echo "Active Ledger Entries: {$ledgerEntries->count()}\n";
echo "Current Balance: Rs. {$currentBalance}\n";

$expectedBalance = abs($netAmount) < 1 ? $customer->opening_balance : ($customer->opening_balance + $netAmount);
$balanceDiff = $currentBalance - $expectedBalance;

if (abs($balanceDiff) < 1) {
    echo "✅ Balance is CORRECT!\n";
} else {
    echo "❌ Balance is WRONG! Difference: Rs. {$balanceDiff}\n";
    echo "This suggests duplicate entries in ledger.\n";
}

echo "\n📊 LEDGER BREAKDOWN:\n";
$salesLedger = $ledgerEntries->where('transaction_type', 'sale');
$paymentsLedger = $ledgerEntries->whereIn('transaction_type', ['payment', 'payments', 'sale_payment']);
$openingLedger = $ledgerEntries->where('transaction_type', 'opening_balance');

echo "Sales entries: {$salesLedger->count()} (should be {$sales->count()})\n";
echo "Payment entries: {$paymentsLedger->count()} (should be {$payments->count()})\n";
echo "Opening balance entries: {$openingLedger->count()} (should be 1)\n";

// Show potential duplicates
$totalExpected = $sales->count() + $payments->count() + 1; // +1 for opening balance
$totalActual = $ledgerEntries->count();

if ($totalActual > $totalExpected) {
    $duplicates = $totalActual - $totalExpected;
    echo "\n🔴 PROBLEM: {$duplicates} extra entries (likely duplicates)\n";
    echo "🔧 SOLUTION: Run 'php fix_customer2.php' to clean up\n";
} else {
    echo "\n✅ Number of entries looks correct\n";
}

// Quick summary
echo "\n📋 SUMMARY:\n";
if (abs($netAmount) < 1 && abs($balanceDiff) < 1) {
    echo "✅ Customer 2 ledger is HEALTHY\n";
    echo "   - All bills are settled\n";
    echo "   - Balance matches opening balance\n";
    echo "   - No duplicate issues\n";
} elseif (abs($netAmount) < 1 && abs($balanceDiff) > 1) {
    echo "🚨 Customer 2 ledger has DUPLICATE ISSUES\n";
    echo "   - All bills are settled (good)\n";
    echo "   - But balance is wrong due to duplicates (bad)\n";
    echo "   - Run 'php fix_customer2.php' to fix\n";
} elseif (abs($netAmount) > 1 && abs($balanceDiff) < 1) {
    echo "⚠️  Customer 2 has OUTSTANDING BILLS\n";
    echo "   - Not all bills are settled\n";
    echo "   - But ledger balance is correct\n";
    echo "   - Outstanding: Rs. {$netAmount}\n";
} else {
    echo "🚨 Customer 2 has MULTIPLE ISSUES\n";
    echo "   - Outstanding bills: Rs. {$netAmount}\n";
    echo "   - Wrong balance due to duplicates: Rs. {$balanceDiff}\n";
    echo "   - Run 'php fix_customer2.php' to investigate\n";
}

echo "\n🚀 TO FIX: Run 'php fix_customer2.php'\n";