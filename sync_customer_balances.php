<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 COMPREHENSIVE CUSTOMER BALANCE SYNC\n";
echo "======================================\n\n";

// 1. Create backup
echo "1. CREATING BACKUP:\n";
$timestamp = date('Ymd_His');
try {
    DB::statement("CREATE TABLE customers_balance_backup_$timestamp AS SELECT * FROM customers");
    echo "✅ Backup created: customers_balance_backup_$timestamp\n";
} catch (Exception $e) {
    echo "❌ Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. FINDING ALL CUSTOMER BALANCE MISMATCHES:\n";
echo str_repeat("-", 60) . "\n";

// Get all customers with balance mismatches
$balanceMismatches = DB::select("
    SELECT 
        c.id,
        c.first_name,
        c.last_name,
        c.current_balance as db_balance,
        COALESCE(l.balance, c.opening_balance, 0) as correct_balance
    FROM customers c
    LEFT JOIN (
        SELECT 
            user_id,
            balance,
            ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC, id DESC) as rn
        FROM ledgers 
        WHERE contact_type = 'customer'
    ) l ON c.id = l.user_id AND l.rn = 1
    WHERE ABS(c.current_balance - COALESCE(l.balance, c.opening_balance, 0)) > 0.01
    ORDER BY ABS(c.current_balance - COALESCE(l.balance, c.opening_balance, 0)) DESC
");

if (count($balanceMismatches) > 0) {
    echo "Found " . count($balanceMismatches) . " customers with balance mismatches:\n\n";
    
    $totalCorrectionAmount = 0;
    $correctionList = [];
    
    foreach ($balanceMismatches as $customer) {
        $difference = $customer->correct_balance - $customer->db_balance;
        $totalCorrectionAmount += abs($difference);
        
        echo "Customer ID: {$customer->id} | {$customer->first_name} {$customer->last_name}\n";
        echo "  Current DB Balance: Rs " . number_format($customer->db_balance, 2) . "\n";
        echo "  Correct Balance: Rs " . number_format($customer->correct_balance, 2) . "\n";
        echo "  Correction: Rs " . number_format($difference, 2) . "\n";
        
        $correctionList[] = [
            'customer_id' => $customer->id,
            'name' => $customer->first_name . ' ' . $customer->last_name,
            'old_balance' => $customer->db_balance,
            'new_balance' => $customer->correct_balance,
            'correction' => $difference
        ];
        
        echo str_repeat("-", 50) . "\n";
    }
    
    echo "\nTotal correction amount: Rs " . number_format($totalCorrectionAmount, 2) . "\n";
    
    echo "\n3. APPLYING BALANCE CORRECTIONS:\n";
    echo str_repeat("-", 60) . "\n";
    
    $successCount = 0;
    foreach ($correctionList as $correction) {
        try {
            DB::update("
                UPDATE customers 
                SET current_balance = ? 
                WHERE id = ?
            ", [$correction['new_balance'], $correction['customer_id']]);
            
            echo "✅ Updated Customer {$correction['customer_id']}: Rs " . 
                 number_format($correction['old_balance'], 2) . " → Rs " . 
                 number_format($correction['new_balance'], 2) . "\n";
            
            $successCount++;
        } catch (Exception $e) {
            echo "❌ Failed to update Customer {$correction['customer_id']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Successfully updated $successCount out of " . count($correctionList) . " customers\n";
    
    // Save correction log
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'backup_table' => "customers_balance_backup_$timestamp",
        'total_corrections' => count($correctionList),
        'successful_corrections' => $successCount,
        'total_amount_corrected' => $totalCorrectionAmount,
        'corrections' => $correctionList
    ];
    
    file_put_contents("balance_sync_log_$timestamp.json", json_encode($logData, JSON_PRETTY_PRINT));
    echo "✅ Correction log saved: balance_sync_log_$timestamp.json\n";
    
} else {
    echo "✅ No balance mismatches found - all customers are already synced!\n";
}

echo "\n4. POST-SYNC VERIFICATION:\n";
echo str_repeat("-", 60) . "\n";

// Check if any mismatches remain
$remainingMismatches = DB::select("
    SELECT COUNT(*) as count
    FROM customers c
    LEFT JOIN (
        SELECT 
            user_id,
            balance,
            ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC, id DESC) as rn
        FROM ledgers 
        WHERE contact_type = 'customer'
    ) l ON c.id = l.user_id AND l.rn = 1
    WHERE ABS(c.current_balance - COALESCE(l.balance, c.opening_balance, 0)) > 0.01
")[0]->count;

if ($remainingMismatches == 0) {
    echo "🎉 PERFECT! All customer balances are now synced with ledger entries!\n";
} else {
    echo "⚠️  Still found $remainingMismatches mismatches - may need manual review\n";
}

// Get updated totals
$totalDuesFromSales = DB::select("SELECT SUM(total_due) as total FROM sales WHERE customer_id != 1 AND total_due > 0")[0]->total ?? 0;
$totalCustomerBalances = DB::select("SELECT SUM(current_balance) as total FROM customers WHERE current_balance > 0")[0]->total ?? 0;

echo "\nUpdated totals:\n";
echo "- Total dues from sales: Rs " . number_format($totalDuesFromSales, 2) . "\n";
echo "- Total customer balances: Rs " . number_format($totalCustomerBalances, 2) . "\n";
echo "- Difference: Rs " . number_format(abs($totalDuesFromSales - $totalCustomerBalances), 2) . "\n";

$differencePercentage = $totalCustomerBalances > 0 ? (abs($totalDuesFromSales - $totalCustomerBalances) / $totalCustomerBalances) * 100 : 0;
echo "- Difference percentage: " . number_format($differencePercentage, 2) . "%\n";

if ($differencePercentage < 5) {
    echo "✅ Totals are now very close - excellent consistency!\n";
} elseif ($differencePercentage < 10) {
    echo "✅ Totals are reasonably consistent\n";
} else {
    echo "⚠️  Still significant difference - may indicate other issues\n";
}

echo "\n✅ CUSTOMER BALANCE SYNC COMPLETE!\n";
echo "All customer balances have been synchronized with their ledger entries.\n";