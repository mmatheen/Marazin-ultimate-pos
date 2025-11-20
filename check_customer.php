<?php
/**
 * ===================================================================
 * 👤 CUSTOMER LEDGER CHECKER
 * ===================================================================
 * 
 * Check specific customer's ledger for issues
 * 
 * USAGE: php check_customer.php [customer_id]
 * Example: php check_customer.php 25
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Sale;
use App\Models\Payment;

// Get customer ID from command line or ask for it
$customerId = null;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $customerId = (int)$argv[1];
} else {
    echo "Enter Customer ID: ";
    $handle = fopen("php://stdin", "r");
    $customerId = (int)trim(fgets($handle));
    fclose($handle);
}

if (!$customerId || $customerId <= 1) {
    echo "❌ Invalid customer ID. Please provide a valid customer ID greater than 1.\n";
    exit(1);
}

echo "🔍 CHECKING CUSTOMER LEDGER (ID: {$customerId})\n";
echo "=============================================\n\n";

// Get customer details
$customer = Customer::withoutGlobalScopes()->find($customerId);
if (!$customer) {
    echo "❌ Customer not found!\n";
    
    // Let's check if customer exists with different scopes
    echo "🔍 Searching in all customers (including with scopes)...\n";
    $customerCheck = DB::table('customers')->where('id', $customerId)->first();
    if ($customerCheck) {
        echo "✅ Customer found in database but blocked by scope!\n";
        echo "   Name: {$customerCheck->first_name} {$customerCheck->last_name}\n";
        echo "   This might be due to location filtering or soft deletes.\n\n";
        // Continue with database customer data
        $customer = (object) [
            'id' => $customerCheck->id,
            'first_name' => $customerCheck->first_name,
            'last_name' => $customerCheck->last_name,
            'mobile_no' => $customerCheck->mobile_no ?? 'N/A',
            'opening_balance' => $customerCheck->opening_balance ?? 0
        ];
    } else {
        exit(1);
    }
}

echo "👤 Customer Details:\n";
echo "   Name: {$customer->first_name} {$customer->last_name}\n";
echo "   Mobile: {$customer->mobile_no}\n";
echo "   Opening Balance: {$customer->opening_balance}\n\n";

// Get all sales for this customer
echo "🛒 Sales Records:\n";
$sales = Sale::where('customer_id', $customerId)->orderBy('created_at', 'desc')->get();
echo "   Total Sales: {$sales->count()}\n";
if ($sales->count() > 0) {
    echo "   Recent Sales:\n";
    foreach ($sales->take(5) as $sale) {
        echo "     - ID: {$sale->id} | Invoice: {$sale->invoice_no} | Amount: {$sale->final_total} | Date: {$sale->created_at}\n";
    }
}
echo "\n";

// Get all payments for this customer
echo "💰 Payment Records:\n";
$payments = Payment::where('customer_id', $customerId)->orderBy('created_at', 'desc')->get();
echo "   Total Payments: {$payments->count()}\n";
if ($payments->count() > 0) {
    echo "   Recent Payments:\n";
    foreach ($payments->take(5) as $payment) {
        echo "     - ID: {$payment->id} | Ref: {$payment->reference_no} | Amount: {$payment->amount} | Date: {$payment->created_at}\n";
    }
}
echo "\n";

// Get all ledger entries
echo "📋 Ledger Entries:\n";
$ledgerEntries = Ledger::where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->orderBy('created_at', 'desc')
    ->get();

echo "   Total Ledger Entries: {$ledgerEntries->count()}\n";
echo "   Active Entries: {$ledgerEntries->where('status', 'active')->count()}\n";
echo "   Reversed Entries: {$ledgerEntries->where('status', 'reversed')->count()}\n\n";

// Check for duplicates
echo "🔍 Checking for Duplicates:\n";
$duplicates = DB::select("
    SELECT 
        reference_no, 
        transaction_type,
        COUNT(*) as count,
        GROUP_CONCAT(id ORDER BY created_at) as ledger_ids,
        GROUP_CONCAT(CONCAT('D:', debit, ' C:', credit) SEPARATOR ' | ') as amounts
    FROM ledgers 
    WHERE contact_id = ? 
        AND contact_type = 'customer' 
        AND status = 'active'
        AND reference_no NOT LIKE '%-REV%'
        AND reference_no NOT LIKE '%-OLD%'
    GROUP BY reference_no, transaction_type
    HAVING COUNT(*) > 1
    ORDER BY count DESC
", [$customerId]);

if (!empty($duplicates)) {
    echo "❌ DUPLICATES FOUND:\n";
    foreach ($duplicates as $dup) {
        echo "   Reference: {$dup->reference_no} | Type: {$dup->transaction_type}\n";
        echo "   Count: {$dup->count} | IDs: {$dup->ledger_ids}\n";
        echo "   Amounts: {$dup->amounts}\n\n";
    }
} else {
    echo "✅ No duplicates found\n\n";
}

// Calculate balance using BalanceHelper
echo "💰 Balance Calculation:\n";
try {
    $balanceHelper = app(\App\Helpers\BalanceHelper::class);
    $currentBalance = $balanceHelper::getCustomerBalance($customerId);
    echo "   Current Balance (BalanceHelper): {$currentBalance}\n";
} catch (Exception $e) {
    echo "   ❌ Error calculating balance: " . $e->getMessage() . "\n";
}

// Calculate balance manually from ledger
$manualBalance = $ledgerEntries->where('status', 'active')->sum(function($entry) {
    return $entry->debit - $entry->credit;
});
echo "   Manual Balance (from ledger): {$manualBalance}\n";

// Show balance difference
$balanceDiff = abs(($currentBalance ?? 0) - $manualBalance);
if ($balanceDiff > 0.01) {
    echo "   ⚠️  Balance mismatch detected! Difference: {$balanceDiff}\n";
} else {
    echo "   ✅ Balances match\n";
}

echo "\n";

// Show recent ledger entries in detail
echo "📝 Recent Ledger Entries (last 10):\n";
foreach ($ledgerEntries->take(10) as $i => $entry) {
    $status = $entry->status === 'active' ? '✅' : '❌';
    echo "   {$status} " . ($i + 1) . ". ID: {$entry->id} | {$entry->transaction_type}\n";
    echo "      Reference: {$entry->reference_no}\n";
    echo "      Amount: Debit: {$entry->debit} | Credit: {$entry->credit}\n";
    echo "      Date: {$entry->created_at}\n";
    echo "      Notes: " . substr($entry->notes, 0, 80) . "\n\n";
}

// Check for missing ledger entries (sales without ledger)
echo "🔍 Checking for Missing Ledger Entries:\n";
$missingSales = [];
foreach ($sales as $sale) {
    $invoiceRef = $sale->invoice_no ?: "INV-{$sale->id}";
    $ledgerExists = $ledgerEntries->where('reference_no', $invoiceRef)
        ->where('transaction_type', 'sale')
        ->where('status', 'active')
        ->count() > 0;
    
    if (!$ledgerExists) {
        $missingSales[] = $sale;
    }
}

if (!empty($missingSales)) {
    echo "❌ SALES WITHOUT LEDGER ENTRIES:\n";
    foreach ($missingSales as $sale) {
        echo "   Sale ID: {$sale->id} | Invoice: {$sale->invoice_no} | Amount: {$sale->final_total}\n";
    }
} else {
    echo "✅ All sales have corresponding ledger entries\n";
}

echo "\n";

// Summary and recommendations
echo "📋 SUMMARY AND RECOMMENDATIONS:\n";
echo "================================\n";

if (!empty($duplicates)) {
    echo "⚠️  Action Required: Remove duplicate ledger entries\n";
    echo "   Run: php fix_duplicate_ledger.php --customer={$customerId}\n";
}

if (!empty($missingSales)) {
    echo "⚠️  Action Required: Create missing ledger entries for sales\n";
}

if ($balanceDiff > 0.01) {
    echo "⚠️  Action Required: Investigate balance mismatch\n";
}

if (empty($duplicates) && empty($missingSales) && $balanceDiff <= 0.01) {
    echo "✅ Customer ledger appears to be healthy!\n";
}

echo "\nChecking completed at " . date('Y-m-d H:i:s') . "\n";