<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING LEDGER ORDER FOR CUSTOMER 582 ===\n\n";

// Get the newly created ledger entry
$newEntry = DB::table('ledgers')->where('id', 2859)->first();

echo "Newly Created Entry (ID: 2859):\n";
echo "Transaction Date: {$newEntry->transaction_date}\n";
echo "Created At: {$newEntry->created_at}\n";
echo "Reference: {$newEntry->reference_no}\n";
echo "Debit: {$newEntry->debit}\n";
echo "Status: {$newEntry->status}\n\n";

echo "=== CHECKING FOR ORDER ISSUES ===\n\n";

// Check all active ledger entries for customer 582 sorted by transaction_date
echo "1. Sorted by TRANSACTION_DATE (CORRECT - for balance calculation):\n";
$ledgersByTransDate = DB::table('ledgers')
    ->where('contact_id', 582)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->orderBy('transaction_date', 'asc')
    ->orderBy('id', 'asc')
    ->get(['id', 'transaction_date', 'reference_no', 'transaction_type', 'debit', 'credit', 'created_at']);

foreach ($ledgersByTransDate as $entry) {
    $amount = $entry->debit > 0 ? "DR: {$entry->debit}" : "CR: {$entry->credit}";
    echo "ID: {$entry->id} | Date: {$entry->transaction_date} | {$entry->reference_no} | {$amount} | Created: {$entry->created_at}\n";
}

echo "\n2. Sorted by CREATED_AT (Potential issue if used for reports):\n";
$ledgersByCreatedAt = DB::table('ledgers')
    ->where('contact_id', 582)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->orderBy('created_at', 'asc')
    ->get(['id', 'transaction_date', 'reference_no', 'transaction_type', 'debit', 'credit', 'created_at']);

$outOfOrder = false;
foreach ($ledgersByCreatedAt as $entry) {
    $amount = $entry->debit > 0 ? "DR: {$entry->debit}" : "CR: {$entry->credit}";
    $marker = "";

    // Check if transaction_date is significantly earlier than created_at
    $transDate = new DateTime($entry->transaction_date);
    $createdDate = new DateTime($entry->created_at);
    $daysDiff = $createdDate->diff($transDate)->days;

    if ($daysDiff > 7 && $transDate < $createdDate) {
        $marker = " ⚠️ OUT OF ORDER (trans_date: {$entry->transaction_date})";
        $outOfOrder = true;
    }

    echo "ID: {$entry->id} | Created: {$entry->created_at} | {$entry->reference_no} | {$amount}{$marker}\n";
}

if ($outOfOrder) {
    echo "\n⚠️ WARNING: Some entries have transaction_date much earlier than created_at\n";
    echo "This could cause issues if reports/queries sort by created_at instead of transaction_date\n\n";
}

echo "\n=== BALANCE CALCULATION TEST ===\n";

// Calculate balance using transaction_date ordering (CORRECT)
$runningBalance = 0;
echo "Running Balance (by transaction_date):\n";
foreach ($ledgersByTransDate as $entry) {
    $runningBalance += $entry->debit;
    $runningBalance -= $entry->credit;
    $amount = $entry->debit > 0 ? "+{$entry->debit}" : "-{$entry->credit}";
    echo "{$entry->transaction_date} | {$entry->reference_no} | {$amount} | Balance: {$runningBalance}\n";
}

echo "\nFinal Balance: Rs. {$runningBalance}\n";

// Check if this matches the customer model calculation
$customer = DB::table('customers')->where('id', 582)->first();
echo "\n=== COMPARISON ===\n";
echo "Ledger Calculation: Rs. {$runningBalance}\n";

// Calculate using Customer model method
$customerModel = App\Models\Customer::find(582);
if ($customerModel) {
    $modelBalance = $customerModel->calculateBalanceFromLedger();
    echo "Customer Model: Rs. {$modelBalance}\n";

    if (abs($runningBalance - $modelBalance) < 0.01) {
        echo "✅ BALANCES MATCH - No ordering issues affecting calculations\n";
    } else {
        echo "❌ BALANCES DON'T MATCH - Potential ordering or calculation issue!\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. The ledger entry uses transaction_date = {$newEntry->transaction_date} (correct)\n";
echo "2. All balance calculations should use ORDER BY transaction_date, id\n";
echo "3. Reports should use transaction_date for chronological order\n";
echo "4. The created_at timestamp is for audit trail only\n";
