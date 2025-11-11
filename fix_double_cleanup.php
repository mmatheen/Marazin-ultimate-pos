<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

echo "🔧 FIXING DOUBLE CLEANUP EXECUTION\n";
echo "===================================\n\n";

// The cleanup was run twice, causing double corrections
// Let's identify and fix this

echo "1. Analyzing double cleanup entries:\n";
$duplicateGroups = DB::select("
    SELECT 
        user_id, 
        SUBSTRING(reference_no, 1, 19) as base_ref,
        COUNT(*) as count,
        SUM(credit) as total_credit
    FROM ledgers 
    WHERE reference_no LIKE 'CLEANUP-REV-%'
    GROUP BY user_id, SUBSTRING(reference_no, 1, 19)
    HAVING COUNT(*) > 1
");

if (count($duplicateGroups) > 0) {
    echo "Found duplicate cleanup entries:\n";
    foreach ($duplicateGroups as $group) {
        echo "  User {$group->user_id} | {$group->base_ref} | Count: {$group->count} | Total: Rs " . number_format($group->total_credit, 2) . "\n";
    }
    
    echo "\n2. Removing duplicate entries (keeping only the first set):\n";
    
    // Remove the second set of cleanup entries (the ones from 11:44:16)
    $removed = DB::delete("
        DELETE FROM ledgers 
        WHERE reference_no LIKE 'CLEANUP-REV-%' 
        AND created_at >= '2025-11-11 11:44:00'
    ");
    
    echo "✅ Removed $removed duplicate cleanup entries\n";
} else {
    echo "No duplicate entries found.\n";
}

echo "\n3. Recalculating all affected customer balances:\n";
$affectedCustomers = [3, 146, 871, 916, 921, 935];

foreach ($affectedCustomers as $customerId) {
    echo "Processing Customer ID: $customerId\n";
    
    // Get all ledger entries for this customer in chronological order
    $entries = DB::select("
        SELECT id, debit, credit, created_at
        FROM ledgers 
        WHERE user_id = ? AND contact_type = 'customer' 
        ORDER BY created_at ASC, id ASC
    ", [$customerId]);
    
    if (count($entries) > 0) {
        $runningBalance = 0;
        $updateQueries = [];
        
        foreach ($entries as $entry) {
            $runningBalance += ($entry->debit - $entry->credit);
            $updateQueries[] = "UPDATE ledgers SET balance = $runningBalance WHERE id = {$entry->id}";
        }
        
        // Execute all balance updates
        foreach ($updateQueries as $query) {
            DB::statement($query);
        }
        
        // Update customer's current balance
        DB::update("UPDATE customers SET current_balance = ? WHERE id = ?", [$runningBalance, $customerId]);
        
        echo "  ✅ Final balance: Rs " . number_format($runningBalance, 2) . "\n";
    } else {
        echo "  ❌ No ledger entries found\n";
    }
}

echo "\n4. Final verification - Customer 3 (2Star - STR):\n";
$customer3 = DB::select("SELECT * FROM customers WHERE id = 3")[0];
echo "Customer: {$customer3->first_name} {$customer3->last_name}\n";
echo "Final Balance: Rs " . number_format($customer3->current_balance, 2) . "\n";

if ($customer3->current_balance == 0) {
    echo "🎉 SUCCESS! The balance is now Rs 0.00 as expected!\n";
} else {
    echo "Current balance: Rs " . number_format($customer3->current_balance, 2) . "\n";
    echo "Checking ledger calculation...\n";
    
    // Show recent entries
    $recentEntries = DB::select("
        SELECT reference_no, debit, credit, balance, created_at
        FROM ledgers 
        WHERE user_id = 3 AND contact_type = 'customer'
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    foreach ($recentEntries as $entry) {
        $type = strpos($entry->reference_no, 'CLEANUP') !== false ? " (CLEANUP)" : "";
        echo "  {$entry->created_at} | {$entry->reference_no}$type | ";
        echo "D: " . number_format($entry->debit, 2) . " | ";
        echo "C: " . number_format($entry->credit, 2) . " | ";
        echo "Bal: " . number_format($entry->balance, 2) . "\n";
    }
}

echo "\n✅ CLEANUP CORRECTION COMPLETE!\n";
echo "The duplicate cleanup entries have been removed and balances recalculated.\n";