<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

echo "🔍 CHECKING CLEANUP RESULTS\n";
echo "=============================\n\n";

// First, let's see what cleanup entries were actually created
echo "1. Checking cleanup reversal entries:\n";
$cleanupEntries = Ledger::where('reference_no', 'LIKE', 'CLEANUP-REV-%')
    ->orderBy('created_at', 'desc')
    ->get();

if ($cleanupEntries->count() > 0) {
    echo "Found " . $cleanupEntries->count() . " cleanup entries:\n";
    foreach ($cleanupEntries as $entry) {
        echo "  User ID: {$entry->user_id} | Ref: {$entry->reference_no} | ";
        echo "Debit: Rs " . number_format($entry->debit, 2) . " | ";
        echo "Credit: Rs " . number_format($entry->credit, 2) . " | ";
        echo "Balance: Rs " . number_format($entry->balance, 2) . "\n";
    }
} else {
    echo "❌ No cleanup entries found!\n";
}

echo "\n2. Checking customers table structure:\n";
try {
    $columns = DB::select("DESCRIBE customers");
    echo "Customer table columns:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field}\n";
    }
} catch (Exception $e) {
    echo "Error getting table structure: " . $e->getMessage() . "\n";
}

echo "\n3. Finding customers with recent ledger activity:\n";
$recentCustomers = Ledger::where('contact_type', 'customer')
    ->where('created_at', '>', now()->subDays(1))
    ->select('user_id')
    ->distinct()
    ->pluck('user_id');

if ($recentCustomers->count() > 0) {
    echo "Customer IDs with recent ledger activity: " . implode(', ', $recentCustomers->toArray()) . "\n";
    
    foreach ($recentCustomers as $customerId) {
        try {
            $customer = Customer::find($customerId);
            if ($customer) {
                echo "\nCustomer ID: $customerId\n";
                echo "Name: {$customer->first_name} {$customer->last_name}\n";
                echo "Current Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
                
                // Get recent entries
                $entries = Ledger::where('user_id', $customerId)
                    ->where('contact_type', 'customer')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                
                echo "Recent entries:\n";
                foreach ($entries as $entry) {
                    $isCleanup = strpos($entry->reference_no, 'CLEANUP-REV') !== false ? " (CLEANUP)" : "";
                    echo "  {$entry->created_at->format('Y-m-d H:i')} | {$entry->reference_no}$isCleanup | ";
                    echo "D: " . number_format($entry->debit, 2) . " | ";
                    echo "C: " . number_format($entry->credit, 2) . " | ";
                    echo "Bal: " . number_format($entry->balance, 2) . "\n";
                }
            }
        } catch (Exception $e) {
            echo "Error processing customer $customerId: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "No customers with recent ledger activity found.\n";
}

echo "\n4. Checking backup table:\n";
try {
    $backupCount = DB::table('ledgers_backup_20251111_114416')->count();
    echo "Backup table has $backupCount entries\n";
} catch (Exception $e) {
    echo "Error checking backup table: " . $e->getMessage() . "\n";
}

echo "\n✅ CLEANUP ANALYSIS COMPLETE\n";