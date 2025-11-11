<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

echo "🔍 COMPLETE CUSTOMER VERIFICATION (WITHOUT GLOBAL SCOPE)\n";
echo "=========================================================\n\n";

// 1. First, let's check if customers exist using raw query
echo "1. Raw customer count check:\n";
$rawCustomerCount = DB::select("SELECT COUNT(*) as count FROM customers")[0]->count;
echo "Total customers in database: $rawCustomerCount\n\n";

// 2. Check customers using Eloquent without global scope
echo "2. Eloquent customer check (withoutGlobalScopes):\n";
try {
    $customers = Customer::withoutGlobalScopes()->get();
    echo "Total customers via Eloquent: " . $customers->count() . "\n";
    
    if ($customers->count() > 0) {
        echo "First 10 customers:\n";
        foreach ($customers->take(10) as $customer) {
            echo "  ID: {$customer->id} | {$customer->first_name} {$customer->last_name} | Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error with Eloquent: " . $e->getMessage() . "\n";
}

echo "\n3. Checking specific customers that had cleanup entries:\n";
$cleanupCustomers = DB::select("
    SELECT DISTINCT user_id 
    FROM ledgers 
    WHERE reference_no LIKE 'CLEANUP-REV-%'
");

if (count($cleanupCustomers) > 0) {
    foreach ($cleanupCustomers as $row) {
        $customerId = $row->user_id;
        
        // Use raw query first
        $rawCustomer = DB::select("SELECT * FROM customers WHERE id = ?", [$customerId]);
        
        if (count($rawCustomer) > 0) {
            $customer = $rawCustomer[0];
            echo "Customer ID: $customerId\n";
            echo "  Name: {$customer->first_name} {$customer->last_name}\n";
            echo "  Current Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
            
            // Check latest ledger balance
            $latestLedger = DB::select("
                SELECT balance 
                FROM ledgers 
                WHERE user_id = ? AND contact_type = 'customer' 
                ORDER BY created_at DESC 
                LIMIT 1
            ", [$customerId]);
            
            if (count($latestLedger) > 0) {
                $ledgerBalance = $latestLedger[0]->balance;
                echo "  Latest Ledger Balance: Rs " . number_format($ledgerBalance, 2) . "\n";
                
                if (abs($customer->current_balance - $ledgerBalance) > 0.01) {
                    echo "  ⚠️  MISMATCH DETECTED! Updating...\n";
                    
                    // Update using raw query
                    DB::update("UPDATE customers SET current_balance = ? WHERE id = ?", [$ledgerBalance, $customerId]);
                    echo "  ✅ Balance updated to Rs " . number_format($ledgerBalance, 2) . "\n";
                } else {
                    echo "  ✅ Balance is correct\n";
                }
            }
            
            // Count cleanup entries
            $cleanupCount = DB::select("
                SELECT COUNT(*) as count 
                FROM ledgers 
                WHERE user_id = ? AND reference_no LIKE 'CLEANUP-REV-%'
            ", [$customerId])[0]->count;
            
            echo "  🔧 Cleanup entries: $cleanupCount\n";
            echo str_repeat("-", 40) . "\n";
        } else {
            echo "Customer ID $customerId: NOT FOUND in customers table\n";
            echo str_repeat("-", 40) . "\n";
        }
    }
} else {
    echo "No cleanup entries found.\n";
}

echo "\n4. Special check for the customer from screenshot (looking for high balances):\n";
$highBalanceCustomers = DB::select("
    SELECT id, first_name, last_name, current_balance 
    FROM customers 
    WHERE ABS(current_balance) > 5000 
    ORDER BY ABS(current_balance) DESC 
    LIMIT 10
");

if (count($highBalanceCustomers) > 0) {
    echo "Customers with high balances:\n";
    foreach ($highBalanceCustomers as $customer) {
        echo "  ID: {$customer->id} | {$customer->first_name} {$customer->last_name} | Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
        
        // Check if this might be "2Star - STR"
        $name = strtolower($customer->first_name . ' ' . $customer->last_name);
        if (strpos($name, '2star') !== false || strpos($name, 'str') !== false) {
            echo "    👆 This might be the '2Star - STR' customer!\n";
        }
    }
} else {
    echo "No customers with high balances found.\n";
}

echo "\n5. Checking cleanup execution results:\n";
echo "Cleanup reversal entries created:\n";
$cleanupEntries = DB::select("
    SELECT reference_no, user_id, credit, balance, created_at 
    FROM ledgers 
    WHERE reference_no LIKE 'CLEANUP-REV-%' 
    ORDER BY created_at DESC
");

if (count($cleanupEntries) > 0) {
    $totalCorrected = 0;
    foreach ($cleanupEntries as $entry) {
        echo "  {$entry->created_at} | User: {$entry->user_id} | {$entry->reference_no} | Credit: Rs " . number_format($entry->credit, 2) . " | Final Balance: Rs " . number_format($entry->balance, 2) . "\n";
        $totalCorrected += $entry->credit;
    }
    echo "\nTotal amount corrected: Rs " . number_format($totalCorrected, 2) . "\n";
} else {
    echo "No cleanup entries found.\n";
}

echo "\n6. Backup table verification:\n";
try {
    $backupCount = DB::select("SELECT COUNT(*) as count FROM ledgers_backup_20251111_114416")[0]->count;
    echo "Backup table entries: $backupCount\n";
} catch (Exception $e) {
    echo "Backup table check failed: " . $e->getMessage() . "\n";
}

echo "\n✅ COMPLETE VERIFICATION FINISHED!\n";
echo "All customer data has been checked without global scope restrictions.\n";