<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ledger;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "🧹 EXECUTING SAFE LEDGER CLEANUP\n";
echo "================================\n\n";

echo "⚠️  CREATING BACKUP TABLE FIRST...\n";
try {
    $backupTable = 'ledgers_backup_' . date('Ymd_His');
    DB::statement("CREATE TABLE $backupTable AS SELECT * FROM ledgers");
    echo "✅ Backup created: $backupTable\n\n";
} catch (Exception $e) {
    echo "❌ Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Execute the cleanup for each problematic reference
$problematicRefs = ['ATF-017', 'ATF-020', 'ATF-027', 'MLX-050'];
$totalFixed = 0;
$affectedCustomers = [];

foreach ($problematicRefs as $refNo) {
    echo "🔧 FIXING INVOICE: $refNo\n";
    echo str_repeat("-", 30) . "\n";
    
    $entries = Ledger::where('reference_no', $refNo)
        ->where('contact_type', 'customer')
        ->get();
    
    if ($entries->isEmpty()) {
        echo "No entries found for $refNo\n\n";
        continue;
    }
    
    foreach ($entries as $entry) {
        $customer = Customer::find($entry->user_id);
        $customerName = $customer ? $customer->full_name : "Customer {$entry->user_id}";
        
        echo "Creating reversal for Customer {$entry->user_id} ($customerName)\n";
        echo "Original - Debit: Rs " . number_format($entry->debit, 2) . ", Credit: Rs " . number_format($entry->credit, 2) . "\n";
        
        // Create reversal entry
        $reversalEntry = new Ledger();
        $reversalEntry->transaction_date = Carbon::now();
        $reversalEntry->reference_no = "CLEANUP-REV-{$refNo}-{$entry->id}";
        $reversalEntry->transaction_type = 'adjustment_credit';
        $reversalEntry->debit = $entry->credit; // Swap to reverse
        $reversalEntry->credit = $entry->debit; // Swap to reverse
        $reversalEntry->balance = 0; // Will be recalculated
        $reversalEntry->contact_type = 'customer';
        $reversalEntry->user_id = $entry->user_id;
        $reversalEntry->notes = "CLEANUP: Reversal of orphaned entry for deleted sale {$refNo}";
        $reversalEntry->save();
        
        echo "✅ Reversal created - Debit: Rs " . number_format($reversalEntry->debit, 2) . ", Credit: Rs " . number_format($reversalEntry->credit, 2) . "\n";
        
        $affectedCustomers[$entry->user_id] = true;
        $totalFixed++;
    }
    echo "\n";
}

echo "🔄 RECALCULATING CUSTOMER BALANCES...\n";
foreach (array_keys($affectedCustomers) as $customerId) {
    echo "Recalculating balance for Customer $customerId\n";
    Ledger::recalculateAllBalances($customerId, 'customer');
    
    // Update customer model balance
    $customer = Customer::find($customerId);
    if ($customer) {
        $newBalance = Ledger::getLatestBalance($customerId, 'customer');
        $customer->current_balance = $newBalance;
        $customer->save();
        echo "✅ Customer $customerId balance updated to Rs " . number_format($newBalance, 2) . "\n";
    }
}

echo "\n✅ CLEANUP COMPLETED!\n";
echo "======================\n";
echo "Total reversal entries created: $totalFixed\n";
echo "Affected customers: " . count($affectedCustomers) . "\n";
echo "Backup table: $backupTable\n\n";

echo "🎯 VERIFICATION:\n";
echo "Check customer '2Star - STR' ledger again - the balance should now be correct!\n";
echo "The orphaned entries have been safely reversed with complete audit trail.\n";