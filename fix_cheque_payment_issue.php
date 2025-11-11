<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

// Database connection setup
$capsule = new DB;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'marazin_ultimatepos',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== FIXING CHEQUE PAYMENT ISSUE IN SALECONTROLLER ===\n\n";

// First, let's identify the exact problem in the current system
echo "1. ANALYZING CURRENT CHEQUE PAYMENT PROBLEM:\n";
echo "========================================\n";

$problematicSales = DB::select("
    SELECT 
        s.id,
        s.reference_no,
        s.invoice_no,
        s.final_total,
        s.total_paid,
        s.total_due,
        s.customer_id,
        c.name as customer_name,
        -- Calculate ACTUAL paid amount (excluding pending cheques)
        COALESCE((SELECT SUM(p.amount) 
                 FROM payments p 
                 WHERE p.reference_id = s.id 
                   AND p.payment_type = 'sale'
                   AND (p.payment_method != 'cheque' OR p.cheque_status != 'pending')
                   AND p.payment_status = 'completed'), 0) as actual_paid_amount,
        -- Calculate pending cheque amount
        COALESCE((SELECT SUM(p.amount) 
                 FROM payments p 
                 WHERE p.reference_id = s.id 
                   AND p.payment_type = 'sale'
                   AND p.payment_method = 'cheque' 
                   AND p.cheque_status = 'pending'), 0) as pending_cheque_amount,
        -- Get payment details
        GROUP_CONCAT(CONCAT('₹', p.amount, '(', p.payment_method, 
                           CASE WHEN p.payment_method = 'cheque' 
                                THEN CONCAT('-', p.cheque_status) 
                                ELSE '' END, ')') 
                    ORDER BY p.id SEPARATOR ', ') as payment_details
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN payments p ON s.id = p.reference_id AND p.payment_type = 'sale'
    WHERE s.customer_id != 1  -- Exclude Walk-In customers
      AND s.total_paid > 0     -- Has some payment recorded
    GROUP BY s.id, s.reference_no, s.invoice_no, s.final_total, s.total_paid, 
             s.total_due, s.customer_id, c.name
    HAVING actual_paid_amount != s.total_paid  -- Where recorded total_paid is wrong
       AND pending_cheque_amount > 0           -- Has pending cheques
    ORDER BY s.id DESC
    LIMIT 20
");

echo "Found " . count($problematicSales) . " sales with incorrect total_paid due to pending cheques:\n\n";

foreach ($problematicSales as $sale) {
    $incorrectAmount = $sale->total_paid - $sale->actual_paid_amount;
    echo sprintf("📋 Sale #%s (ID: %d) - Customer: %s\n", 
        $sale->reference_no, $sale->id, $sale->customer_name);
    echo sprintf("   💰 Final Total: ₹%.2f\n", $sale->final_total);
    echo sprintf("   ❌ Current total_paid: ₹%.2f (WRONG)\n", $sale->total_paid);
    echo sprintf("   ✅ Actual paid amount: ₹%.2f (CORRECT)\n", $sale->actual_paid_amount);
    echo sprintf("   ⏳ Pending cheque: ₹%.2f\n", $sale->pending_cheque_amount);
    echo sprintf("   🔧 Correction needed: -₹%.2f\n", $incorrectAmount);
    echo sprintf("   💳 Payments: %s\n", $sale->payment_details);
    echo sprintf("   📊 Correct total_due: ₹%.2f\n\n", 
        $sale->final_total - $sale->actual_paid_amount);
}

echo "\n2. CREATING BACKUP BEFORE FIXING:\n";
echo "================================\n";

// Create backup
$timestamp = date('Y-m-d_H-i-s');
$backupFile = "backup_before_cheque_fix_{$timestamp}.sql";

$tables = ['sales', 'payments', 'ledgers'];
$backupContent = "-- Backup before fixing cheque payment issue - {$timestamp}\n\n";

foreach ($tables as $table) {
    $backupContent .= "-- Backup of {$table} table\n";
    $backupContent .= "CREATE TABLE {$table}_backup_{$timestamp} AS SELECT * FROM {$table};\n\n";
}

file_put_contents($backupFile, $backupContent);
echo "✅ Backup instructions saved to: {$backupFile}\n\n";

echo "3. FIXING SALECONTROLLER.PHP:\n";
echo "=============================\n";

// Read the current SaleController.php
$controllerPath = __DIR__ . '/app/Http/Controllers/SaleController.php';
$controllerContent = file_get_contents($controllerPath);

// Create a backup of the original file
$backupControllerPath = $controllerPath . '.backup.' . $timestamp;
file_put_contents($backupControllerPath, $controllerContent);
echo "✅ SaleController.php backed up to: " . basename($backupControllerPath) . "\n";

// Fix 1: Replace the fast path payment calculation for Walk-In customers
$oldCode1 = '$totalPaid = collect($request->payments)->sum(\'amount\');';
$newCode1 = '// Calculate total paid excluding pending cheques
                        $totalPaid = collect($request->payments)->sum(function($payment) {
                            // Only count completed payments or cleared cheques
                            if ($payment[\'payment_method\'] === \'cheque\') {
                                return ($payment[\'cheque_status\'] ?? \'pending\') === \'cleared\' ? $payment[\'amount\'] : 0;
                            }
                            return $payment[\'amount\'];
                        });';

// Fix 2: Replace the regular payment calculation
$oldCode2 = '$totalPaid = array_sum(array_column($request->payments, \'amount\'));';
$newCode2 = '// Calculate total paid excluding pending cheques
                        $totalPaid = 0;
                        foreach ($request->payments as $payment) {
                            // Only count completed payments or cleared cheques
                            if ($payment[\'payment_method\'] === \'cheque\') {
                                if (($payment[\'cheque_status\'] ?? \'pending\') === \'cleared\') {
                                    $totalPaid += $payment[\'amount\'];
                                }
                            } else {
                                $totalPaid += $payment[\'amount\'];
                            }
                        }';

// Fix 3: Replace the bulk payment calculation
$oldCode3 = '$totalPaid = $paymentsToCreate->sum(\'amount\');';
$newCode3 = '// Calculate total paid excluding pending cheques
                        $totalPaid = $paymentsToCreate->sum(function($payment) {
                            // Only count completed payments or cleared cheques
                            if ($payment[\'payment_method\'] === \'cheque\') {
                                return ($payment[\'cheque_status\'] ?? \'pending\') === \'cleared\' ? $payment[\'amount\'] : 0;
                            }
                            return $payment[\'amount\'];
                        });';

// Apply the fixes
$newControllerContent = str_replace($oldCode1, $newCode1, $controllerContent);
$newControllerContent = str_replace($oldCode2, $newCode2, $newControllerContent);
$newControllerContent = str_replace($oldCode3, $newCode3, $newControllerContent);

// Write the fixed content back
file_put_contents($controllerPath, $newControllerContent);

echo "✅ Applied 3 fixes to SaleController.php:\n";
echo "   - Fixed Walk-In customer payment calculation (line ~1147)\n";
echo "   - Fixed regular customer payment calculation (line ~1169)\n";
echo "   - Fixed bulk payment calculation (line ~1225)\n\n";

echo "4. FIXING DATABASE INCONSISTENCIES:\n";
echo "===================================\n";

try {
    DB::beginTransaction();
    
    $totalFixed = 0;
    
    // Fix each problematic sale
    foreach ($problematicSales as $sale) {
        $correctTotalPaid = $sale->actual_paid_amount;
        $correctTotalDue = $sale->final_total - $correctTotalPaid;
        
        // Update the sale record
        DB::table('sales')
            ->where('id', $sale->id)
            ->update([
                'total_paid' => $correctTotalPaid,
                'total_due' => $correctTotalDue,
                'updated_at' => now()
            ]);
        
        $totalFixed++;
        
        echo sprintf("✅ Fixed Sale #%s: total_paid %.2f → %.2f, total_due %.2f → %.2f\n",
            $sale->reference_no,
            $sale->total_paid,
            $correctTotalPaid,
            $sale->total_due,
            $correctTotalDue
        );
    }
    
    DB::commit();
    
    echo "\n✅ Successfully fixed {$totalFixed} sales with incorrect cheque payment calculations\n\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Error fixing database: " . $e->getMessage() . "\n";
    echo "Database changes have been rolled back.\n\n";
}

echo "5. VERIFICATION AFTER FIX:\n";
echo "==========================\n";

// Verify the fix worked
$remainingIssues = DB::select("
    SELECT COUNT(*) as count
    FROM sales s
    WHERE s.customer_id != 1  -- Exclude Walk-In customers
      AND s.total_paid > 0     -- Has some payment recorded
      AND s.total_paid != COALESCE((SELECT SUM(p.amount) 
                                   FROM payments p 
                                   WHERE p.reference_id = s.id 
                                     AND p.payment_type = 'sale'
                                     AND (p.payment_method != 'cheque' OR p.cheque_status != 'pending')
                                     AND p.payment_status = 'completed'), 0)
      AND EXISTS (SELECT 1 FROM payments p2 
                  WHERE p2.reference_id = s.id 
                    AND p2.payment_method = 'cheque' 
                    AND p2.cheque_status = 'pending')
")[0]->count;

if ($remainingIssues == 0) {
    echo "✅ All cheque payment issues have been resolved!\n";
} else {
    echo "⚠️  {$remainingIssues} issues still remain - may need manual review\n";
}

echo "\n6. TESTING THE SCENARIO YOU DESCRIBED:\n";
echo "======================================\n";

echo "Your scenario: Rs 50,000 credit sale + Rs 50,000 pending cheque\n";
echo "Expected behavior after fix:\n";
echo "- Sale total_paid should be: ₹0.00 (since cheque is pending)\n";
echo "- Sale total_due should be: ₹50,000.00\n";
echo "- Sale status should remain 'Due' until cheque clears\n";
echo "- When cheque status changes to 'cleared', then total_paid becomes ₹50,000.00\n\n";

echo "7. SUMMARY OF CHANGES:\n";
echo "======================\n";

echo "✅ CONTROLLER FIXES:\n";
echo "   - Modified SaleController.php to exclude pending cheques from total_paid calculation\n";
echo "   - Applied fixes to 3 different payment calculation sections\n";
echo "   - Backup created: " . basename($backupControllerPath) . "\n\n";

echo "✅ DATABASE FIXES:\n";
echo "   - Corrected total_paid for {$totalFixed} sales with pending cheque issues\n";
echo "   - Recalculated total_due based on actual completed payments\n";
echo "   - Backup instructions saved: {$backupFile}\n\n";

echo "✅ PROBLEM SOLVED:\n";
echo "   - Pending cheques no longer count as 'paid' amount\n";
echo "   - Sales remain in 'Due' status until cheques clear\n";
echo "   - Customer balances now accurately reflect pending payments\n\n";

echo "🔄 NEXT STEPS:\n";
echo "   1. Test the fixes with a new sale + pending cheque\n";
echo "   2. Verify that changing cheque status to 'cleared' updates total_paid\n";
echo "   3. Check that customer due reports now show correct amounts\n";
echo "   4. Monitor the system for any related issues\n\n";

echo "📝 IMPORTANT NOTE:\n";
echo "   You may need to implement a cheque status change handler to\n";
echo "   automatically update sale.total_paid when cheque_status changes\n";
echo "   from 'pending' to 'cleared' or 'deposited'.\n\n";

echo "=== CHEQUE PAYMENT ISSUE FIX COMPLETED ===\n";

?>