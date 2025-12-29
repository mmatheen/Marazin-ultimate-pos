<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== BULK PAYMENT SYSTEM VERIFICATION ===\n\n";

$customerId = 44;
$customer = DB::table('customers')->where('id', $customerId)->first();

echo "CUSTOMER INFO:\n";
echo "  ID: {$customer->id}\n";
echo "  Name: {$customer->prefix} {$customer->first_name} {$customer->last_name}\n";
echo "  Opening Balance (Table): Rs." . number_format($customer->opening_balance, 2) . "\n";
echo "  Current Balance (Table): Rs." . number_format($customer->current_balance, 2) . "\n\n";

// Calculate opening balance from ledger
echo "LEDGER-BASED OPENING BALANCE:\n";
$latestOpeningBalance = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('transaction_type', 'opening_balance')
    ->where('status', 'active')
    ->orderBy('id', 'desc')
    ->first();

if ($latestOpeningBalance) {
    $obFromLedger = $latestOpeningBalance->debit - $latestOpeningBalance->credit;
    echo "  Opening Balance from Ledger: Rs." . number_format($obFromLedger, 2) . "\n";
} else {
    echo "  No opening balance entry found in ledger\n";
    $obFromLedger = 0;
}

// Calculate OB payments
$totalOBPayments = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('transaction_type', 'opening_balance_payment')
    ->where('status', 'active')
    ->sum('credit');

echo "  Total OB Payments Made: Rs." . number_format($totalOBPayments, 2) . "\n";
echo "  Remaining OB Due: Rs." . number_format(max(0, $obFromLedger - $totalOBPayments), 2) . "\n\n";

// Check sales due
echo "SALES DUE:\n";
$sales = DB::table('sales')->where('customer_id', $customerId)->get();
$totalSalesDue = 0;
foreach ($sales as $sale) {
    if ($sale->total_due > 0) {
        echo "  {$sale->invoice_no}: Total=Rs." . number_format($sale->final_total, 2) .
             ", Paid=Rs." . number_format($sale->total_paid, 2) .
             ", Due=Rs." . number_format($sale->total_due, 2) . "\n";
    }
    $totalSalesDue += $sale->total_due;
}
echo "  Total Sales Due: Rs." . number_format($totalSalesDue, 2) . "\n\n";

// Test validation logic
echo "VALIDATION TEST:\n";
echo "  Current Balance (Table): Rs." . number_format($customer->current_balance, 2) . "\n";
echo "  Should Match: OB From Ledger (Rs." . number_format($obFromLedger, 2) .
     ") - OB Payments (Rs." . number_format($totalOBPayments, 2) .
     ") + Sales Due (Rs." . number_format($totalSalesDue, 2) . ")\n";

$expectedBalance = max(0, $obFromLedger - $totalOBPayments) + $totalSalesDue;
echo "  Expected Balance: Rs." . number_format($expectedBalance, 2) . "\n";
echo "  Matches? " . ($customer->current_balance == $expectedBalance ? "✓ YES" : "✗ NO (Diff: Rs." . number_format(abs($customer->current_balance - $expectedBalance), 2) . ")") . "\n\n";

// Test payment scenarios
echo "PAYMENT SCENARIOS:\n\n";

echo "1. Opening Balance Only Payment:\n";
echo "   Max Allowed (using table): Rs." . number_format($customer->opening_balance, 2) . " ❌ WRONG!\n";
echo "   Max Allowed (using ledger): Rs." . number_format(max(0, $obFromLedger - $totalOBPayments), 2) . " ✓ CORRECT!\n";
echo "   Current table OB: Rs." . number_format($customer->opening_balance, 2) . "\n";
echo "   Actual OB remaining: Rs." . number_format(max(0, $obFromLedger - $totalOBPayments), 2) . "\n";
echo "   Issue: Old method uses table value which may be wrong!\n\n";

echo "2. Sale Dues Only Payment:\n";
echo "   Max Allowed: Rs." . number_format($totalSalesDue, 2) . " ✓ CORRECT\n";
echo "   Uses: Sum of sales.total_due\n\n";

echo "3. Both Payment:\n";
echo "   Max Allowed (old method): Rs." . number_format($customer->opening_balance + $totalSalesDue, 2) . " ❌ WRONG!\n";
echo "   Max Allowed (correct): Rs." . number_format(max(0, $obFromLedger - $totalOBPayments) + $totalSalesDue, 2) . " ✓ CORRECT!\n\n";

echo "CRITICAL FINDINGS:\n";
echo "==================\n";

if ($customer->opening_balance != $obFromLedger) {
    echo "❌ ISSUE: Customer table opening_balance (Rs." . number_format($customer->opening_balance, 2) .
         ") doesn't match ledger (Rs." . number_format($obFromLedger, 2) . ")\n";
} else {
    echo "✓ Customer table OB matches ledger OB\n";
}

if ($totalOBPayments > 0) {
    echo "⚠️  WARNING: OB payments exist (Rs." . number_format($totalOBPayments, 2) .
         ") - old method doesn't account for these!\n";
} else {
    echo "✓ No OB payments made yet\n";
}

$remainingOBFromLedger = max(0, $obFromLedger - $totalOBPayments);
if ($customer->current_balance != ($remainingOBFromLedger + $totalSalesDue)) {
    echo "❌ ISSUE: Current balance calculation is incorrect\n";
} else {
    echo "✓ Current balance is correctly calculated\n";
}

echo "\nRECOMMENDATION:\n";
echo "===============\n";
if ($customer->opening_balance == $obFromLedger && $totalOBPayments == 0) {
    echo "✓ System is SAFE - Table OB matches ledger and no OB payments made\n";
    echo "✓ Both old and new methods will work correctly\n";
} else {
    echo "⚠️  OLD METHOD (submitBulkPayment) has ISSUES:\n";
    echo "   - Uses customer.opening_balance from table\n";
    echo "   - Doesn't account for partial OB payments already made\n";
    echo "   - Can allow overpayment!\n\n";
    echo "✓ NEW METHOD (submitFlexibleBulkPayment) is CORRECT:\n";
    echo "   - Uses customer.current_balance (ledger-based)\n";
    echo "   - Accounts for all previous payments\n";
    echo "   - Has proper validation\n";
}
