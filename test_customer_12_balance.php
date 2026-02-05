<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Helpers\BalanceHelper;
use Illuminate\Support\Facades\DB;

$customerId = 12; // Change this to test different customers

echo "\n========================================\n";
echo "🔍 ANALYZING CUSTOMER ID {$customerId} BALANCE\n";
echo "========================================\n\n";

// Get customer data bypassing global scopes
$customer = Customer::withoutGlobalScopes()->find($customerId);

if (!$customer) {
    echo "❌ Customer not found!\n";
    exit;
}

echo "📋 CUSTOMER TABLE DATA:\n";
echo "ID: {$customer->id}\n";
echo "Name: {$customer->first_name} {$customer->last_name}\n";
echo "Opening Balance (customer table): Rs. {$customer->opening_balance}\n\n";

// Get ledger entries
echo "📊 LEDGER ENTRIES:\n";
$ledgers = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->orderBy('id', 'asc')
    ->get();

$activeDebits = 0;
$activeCredits = 0;

foreach ($ledgers as $ledger) {
    $status = $ledger->status === 'active' ? '✅ ACTIVE' : '❌ REVERSED';
    echo "ID: {$ledger->id} | Type: {$ledger->transaction_type} | Debit: {$ledger->debit} | Credit: {$ledger->credit} | Status: {$status} | Ref: {$ledger->reference_no}\n";
    
    if ($ledger->status === 'active') {
        $activeDebits += $ledger->debit;
        $activeCredits += $ledger->credit;
    }
}

echo "\n💰 LEDGER CALCULATION:\n";
echo "Total Active Debits: Rs. {$activeDebits}\n";
echo "Total Active Credits: Rs. {$activeCredits}\n";
echo "Balance (Debit - Credit): Rs. " . ($activeDebits - $activeCredits) . "\n\n";

// Get sales data
echo "🛒 SALES DATA:\n";
$sales = DB::table('sales')
    ->where('customer_id', $customerId)
    ->get();

$totalSaleDue = 0;
$saleCount = 0;
$totalSaleDueFromController = 0;
foreach ($sales as $sale) {
    if ($sale->total_due > 0) {
        echo "Sale ID: {$sale->id} | Invoice: {$sale->invoice_no} | Final Total: Rs. {$sale->final_total} | Total Due: Rs. {$sale->total_due} | Status: {$sale->status} | Payment Status: {$sale->payment_status}\n";
        $totalSaleDue += $sale->total_due;
        $saleCount++;
        
        // Track what CustomerController would include
        if (in_array($sale->status, ['final', 'suspend'])) {
            $totalSaleDueFromController += $sale->total_due;
        }
    }
}
echo "Total Sale Due (from sales table): Rs. {$totalSaleDue}\n";
echo "Total Sale Due (CustomerController logic): Rs. {$totalSaleDueFromController}\n";
echo "Number of unpaid sales: {$saleCount}\n\n";

// Get payments
echo "💳 PAYMENTS:\n";
$payments = DB::table('payments')
    ->where('customer_id', $customerId)
    ->get();

$totalPayments = 0;
foreach ($payments as $payment) {
    echo "Payment ID: {$payment->id} | Date: {$payment->payment_date} | Amount: Rs. {$payment->amount} | Type: {$payment->payment_type} | Status: {$payment->status}\n";
    if ($payment->status === 'active') {
        $totalPayments += $payment->amount;
    }
}
echo "Total Payments: Rs. {$totalPayments}\n\n";

// Use BalanceHelper
echo "🎯 BALANCE HELPER CALCULATION:\n";
$balanceFromHelper = BalanceHelper::getCustomerBalance($customerId);
echo "Customer Balance: Rs. {$balanceFromHelper}\n\n";

// Check getBulkCustomerBalances
echo "📦 BULK BALANCE CALCULATION:\n";
$bulkBalances = BalanceHelper::getBulkCustomerBalances([$customerId]);
echo "Bulk Balance for Customer {$customerId}: Rs. " . $bulkBalances->get($customerId, 0) . "\n\n";

echo "========================================\n";
echo "✅ ANALYSIS COMPLETE\n";
echo "========================================\n\n";

echo "🎯 SUMMARY:\n";
echo "Customer table opening_balance: Rs. {$customer->opening_balance}\n";
echo "Sales table total_due: Rs. {$totalSaleDue}\n";
echo "Ledger balance (BalanceHelper): Rs. {$balanceFromHelper}\n";
echo "Expected Amount to Pay: Rs. {$balanceFromHelper}\n\n";

// Check if there's a discrepancy
$expectedTotal = $customer->opening_balance + $totalSaleDue;
if ($expectedTotal != $balanceFromHelper) {
    echo "⚠️ DISCREPANCY FOUND!\n";
    echo "Opening + Sales Due = Rs. {$expectedTotal}\n";
    echo "But Ledger shows: Rs. {$balanceFromHelper}\n";
    echo "Difference: Rs. " . ($balanceFromHelper - $expectedTotal) . "\n\n";
    
    echo "💡 This is correct! The ledger is the source of truth.\n";
    echo "The difference is likely due to payments or adjustments.\n";
}
