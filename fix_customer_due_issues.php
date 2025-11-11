<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "🔧 FIXING CUSTOMER DUE ISSUES\n";
echo "==============================\n\n";

// Create backup first
echo "1. CREATING BACKUP TABLES:\n";
$timestamp = date('Ymd_His');

try {
    DB::statement("CREATE TABLE customers_backup_$timestamp AS SELECT * FROM customers");
    DB::statement("CREATE TABLE sales_backup_$timestamp AS SELECT * FROM sales");
    echo "✅ Backup tables created: customers_backup_$timestamp, sales_backup_$timestamp\n";
} catch (Exception $e) {
    echo "❌ Backup creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. FIXING PAYMENT RECONCILIATION ISSUES:\n";
echo str_repeat("-", 50) . "\n";

// Fix the 3 sales that show paid but have no actual payments
$paymentIssues = [
    ['sale_id' => 218, 'invoice' => 'D/2025/0003', 'total' => 129721.00],
    ['sale_id' => 217, 'invoice' => 'D/2025/0002', 'total' => 64108.00], 
    ['sale_id' => 182, 'invoice' => 'D/2025/0001', 'total' => 741568.70]
];

foreach ($paymentIssues as $issue) {
    echo "Fixing Sale ID: {$issue['sale_id']} | Invoice: {$issue['invoice']}\n";
    
    // Check if there are actual payments for this sale
    $actualPayments = DB::select("
        SELECT SUM(amount) as total_paid 
        FROM payments 
        WHERE reference_id = ? AND payment_type = 'sale'
    ", [$issue['sale_id']]);
    
    $actualPaid = $actualPayments[0]->total_paid ?? 0;
    $correctDue = $issue['total'] - $actualPaid;
    
    echo "  Total: Rs " . number_format($issue['total'], 2) . "\n";
    echo "  Actual Payments: Rs " . number_format($actualPaid, 2) . "\n";
    echo "  Correct Due: Rs " . number_format($correctDue, 2) . "\n";
    
    // Update the sale record
    $paymentStatus = 'Due';
    if ($actualPaid >= $issue['total']) {
        $paymentStatus = 'Paid';
    } elseif ($actualPaid > 0) {
        $paymentStatus = 'Partial';
    }
    
    DB::update("
        UPDATE sales 
        SET total_paid = ?, total_due = ?, payment_status = ? 
        WHERE id = ?
    ", [$actualPaid, $correctDue, $paymentStatus, $issue['sale_id']]);
    
    echo "  ✅ Updated: Paid = Rs " . number_format($actualPaid, 2) . ", Due = Rs " . number_format($correctDue, 2) . ", Status = $paymentStatus\n";
    echo str_repeat("-", 30) . "\n";
}

echo "\n3. FIXING CUSTOMER BALANCE MISMATCHES:\n";
echo str_repeat("-", 50) . "\n";

$balanceIssues = [
    ['customer_id' => 919, 'name' => 'Jahfer Traders - ADD', 'db_balance' => 1050.00, 'ledger_balance' => 43320.00],
    ['customer_id' => 735, 'name' => 'Asleem Brothers - OLV', 'db_balance' => 0.90, 'ledger_balance' => 80314.90]
];

foreach ($balanceIssues as $issue) {
    echo "Fixing Customer ID: {$issue['customer_id']} | Name: {$issue['name']}\n";
    echo "  Current DB Balance: Rs " . number_format($issue['db_balance'], 2) . "\n";
    echo "  Correct Ledger Balance: Rs " . number_format($issue['ledger_balance'], 2) . "\n";
    
    // Update customer balance to match ledger
    DB::update("
        UPDATE customers 
        SET current_balance = ? 
        WHERE id = ?
    ", [$issue['ledger_balance'], $issue['customer_id']]);
    
    echo "  ✅ Customer balance updated to Rs " . number_format($issue['ledger_balance'], 2) . "\n";
    echo str_repeat("-", 30) . "\n";
}

echo "\n4. VERIFICATION AFTER FIXES:\n";
echo str_repeat("-", 50) . "\n";

// Re-check payment reconciliation issues
echo "Checking payment reconciliation fixes:\n";
foreach ($paymentIssues as $issue) {
    $sale = DB::select("
        SELECT total_paid, total_due, payment_status 
        FROM sales 
        WHERE id = ?
    ", [$issue['sale_id']])[0];
    
    echo "  Sale {$issue['sale_id']}: Paid = Rs " . number_format($sale->total_paid, 2) . 
         ", Due = Rs " . number_format($sale->total_due, 2) . ", Status = {$sale->payment_status}\n";
}

// Re-check customer balance fixes
echo "\nChecking customer balance fixes:\n";
foreach ($balanceIssues as $issue) {
    $customer = DB::select("
        SELECT current_balance 
        FROM customers 
        WHERE id = ?
    ", [$issue['customer_id']])[0];
    
    echo "  Customer {$issue['customer_id']}: Balance = Rs " . number_format($customer->current_balance, 2) . "\n";
}

echo "\n5. FINAL TOTALS CHECK:\n";
echo str_repeat("-", 50) . "\n";

$totalDuesFromSales = DB::select("SELECT SUM(total_due) as total FROM sales WHERE customer_id != 1 AND total_due > 0")[0]->total ?? 0;
$totalCustomerBalances = DB::select("SELECT SUM(current_balance) as total FROM customers WHERE current_balance > 0")[0]->total ?? 0;

echo "Total dues from sales table: Rs " . number_format($totalDuesFromSales, 2) . "\n";
echo "Total positive customer balances: Rs " . number_format($totalCustomerBalances, 2) . "\n";
echo "Difference: Rs " . number_format(abs($totalDuesFromSales - $totalCustomerBalances), 2) . "\n";

if (abs($totalDuesFromSales - $totalCustomerBalances) < 1000) {
    echo "✅ Totals are now reasonably close!\n";
} else {
    echo "⚠️  Large difference still exists - may need further investigation\n";
}

echo "\n6. CREATING AUDIT LOG:\n";
echo str_repeat("-", 50) . "\n";

$auditLog = [
    'timestamp' => date('Y-m-d H:i:s'),
    'fixes_applied' => [
        'payment_reconciliation' => count($paymentIssues),
        'customer_balance_sync' => count($balanceIssues)
    ],
    'backup_tables' => [
        "customers_backup_$timestamp",
        "sales_backup_$timestamp"
    ],
    'total_amount_corrected' => array_sum(array_column($paymentIssues, 'total')) + 
                               array_sum(array_column($balanceIssues, 'ledger_balance'))
];

file_put_contents("customer_due_fixes_$timestamp.json", json_encode($auditLog, JSON_PRETTY_PRINT));
echo "✅ Audit log created: customer_due_fixes_$timestamp.json\n";

echo "\n✅ ALL CUSTOMER DUE FIXES COMPLETED!\n";
echo "Summary:\n";
echo "- Fixed " . count($paymentIssues) . " payment reconciliation issues\n";
echo "- Fixed " . count($balanceIssues) . " customer balance mismatches\n";
echo "- Created backup tables for safety\n";
echo "- Total corrections: Rs " . number_format($auditLog['total_amount_corrected'], 2) . "\n";
echo "\nThe customer due system should now be consistent!\n";