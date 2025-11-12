<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ledger;
use App\Models\Customer;
use App\Services\UnifiedLedgerService;

echo "=== CHECKING CUSTOMER 871 BALANCE AFTER CLEANUP ===\n\n";

$customerId = 871;

// Check if customer exists
$customer = Customer::find($customerId);
if ($customer) {
    echo "Customer 871: {$customer->first_name} {$customer->last_name}\n";
} else {
    echo "⚠ Customer 871 not found in customers table\n";
}

// Get all ledger entries for customer 871
$ledgerEntries = Ledger::where('user_id', $customerId)
    ->where('contact_type', 'customer')
    ->orderBy('created_at', 'asc')
    ->orderBy('id', 'asc')
    ->get();

echo "Total ledger entries: " . $ledgerEntries->count() . "\n\n";

$runningTotal = 0;
$mlxFound = false;

echo "Ledger entries for customer 871:\n";
echo "ID | Date | Reference | Type | Debit | Credit | Stored Balance | Calculated Running\n";
echo "---|------|-----------|------|-------|--------|----------------|-------------------\n";

foreach ($ledgerEntries as $entry) {
    $runningTotal += $entry->debit - $entry->credit;
    
    if ($entry->reference_no === 'MLX-050') {
        $mlxFound = true;
        echo ">>> ";
    }
    
    echo "{$entry->id} | {$entry->transaction_date} | {$entry->reference_no} | {$entry->transaction_type} | {$entry->debit} | {$entry->credit} | {$entry->balance} | {$runningTotal}\n";
}

echo "\n=== BALANCE ANALYSIS ===\n";
echo "Calculated running balance: {$runningTotal}\n";
echo "Last stored balance: " . ($ledgerEntries->last() ? $ledgerEntries->last()->balance : '0') . "\n";

if ($mlxFound) {
    echo "❌ PROBLEM: MLX-050 entry still exists in ledger!\n";
    echo "This explains why the balance is not recalculated.\n";
} else {
    echo "✅ MLX-050 entry successfully removed from ledger\n";
}

// Check current balance using the service
$currentBalance = Ledger::getLatestBalance($customerId, 'customer');
echo "Service-reported current balance: {$currentBalance}\n";

// Check for any issues in balance calculation
$discrepancy = abs($runningTotal - ($ledgerEntries->last() ? $ledgerEntries->last()->balance : 0));
if ($discrepancy > 0.01) {
    echo "\n❌ BALANCE DISCREPANCY FOUND: {$discrepancy}\n";
    echo "Need to recalculate all balances for this customer.\n";
    
    echo "\nRecalculating balances...\n";
    try {
        Ledger::recalculateAllBalances($customerId, 'customer');
        
        $newBalance = Ledger::getLatestBalance($customerId, 'customer');
        echo "✅ Balance recalculated. New balance: {$newBalance}\n";
    } catch (Exception $e) {
        echo "❌ Error recalculating balance: " . $e->getMessage() . "\n";
    }
} else {
    echo "✅ Balance calculations are consistent\n";
}

echo "\n=== CHECKING FOR SPECIFIC MLX-050 ENTRIES ===\n";
$mlx050Entries = DB::select('
    SELECT * FROM ledgers 
    WHERE reference_no = "MLX-050" 
    AND user_id = ? 
    AND contact_type = "customer"
', [$customerId]);

if (count($mlx050Entries) > 0) {
    echo "❌ Found " . count($mlx050Entries) . " MLX-050 entries that should have been deleted:\n";
    foreach ($mlx050Entries as $entry) {
        echo "  - ID: {$entry->id}, Amount: {$entry->debit}, Date: {$entry->created_at}\n";
    }
    
    echo "\nDeleting these orphaned MLX-050 entries...\n";
    $deleted = DB::table('ledgers')
        ->where('reference_no', 'MLX-050')
        ->where('user_id', $customerId)
        ->where('contact_type', 'customer')
        ->delete();
    
    echo "Deleted {$deleted} MLX-050 entries.\n";
    
    // Recalculate balance after deletion
    echo "Recalculating balance after deletion...\n";
    Ledger::recalculateAllBalances($customerId, 'customer');
    
    $finalBalance = Ledger::getLatestBalance($customerId, 'customer');
    echo "✅ Final balance after cleanup: {$finalBalance}\n";
    
} else {
    echo "✅ No MLX-050 entries found - cleanup was successful\n";
}

echo "\nAnalysis complete.\n";