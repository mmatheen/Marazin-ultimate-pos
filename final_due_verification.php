<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🎯 FINAL CUSTOMER DUE VERIFICATION AFTER FIXES\n";
echo "===============================================\n\n";

// 1. Quick check for any remaining issues
echo "1. CHECKING FOR REMAINING PAYMENT RECONCILIATION ISSUES:\n";
echo str_repeat("-", 60) . "\n";

$remainingPaymentIssues = DB::select("
    SELECT 
        s.id as sale_id,
        s.invoice_no,
        s.final_total,
        s.total_paid as sale_paid,
        COALESCE(SUM(p.amount), 0) as actual_payments,
        s.total_due as recorded_due
    FROM sales s
    LEFT JOIN payments p ON p.reference_id = s.id AND p.payment_type = 'sale'
    WHERE s.customer_id != 1 
    GROUP BY s.id, s.invoice_no, s.final_total, s.total_paid, s.total_due
    HAVING s.total_paid != COALESCE(SUM(p.amount), 0) OR (s.final_total - COALESCE(SUM(p.amount), 0)) != s.total_due
    LIMIT 10
");

if (count($remainingPaymentIssues) > 0) {
    echo "⚠️  Still found " . count($remainingPaymentIssues) . " payment reconciliation issues:\n";
    foreach ($remainingPaymentIssues as $issue) {
        echo "  Sale {$issue->sale_id} | Invoice: {$issue->invoice_no} | ";
        echo "Sale Paid: Rs " . number_format($issue->sale_paid, 2) . " | ";
        echo "Actual: Rs " . number_format($issue->actual_payments, 2) . "\n";
    }
} else {
    echo "✅ No payment reconciliation issues found!\n";
}

echo "\n2. CHECKING FOR REMAINING CUSTOMER BALANCE ISSUES:\n";
echo str_repeat("-", 60) . "\n";

$remainingBalanceIssues = DB::select("
    SELECT 
        c.id,
        c.first_name,
        c.last_name,
        c.current_balance,
        l.balance as ledger_balance
    FROM customers c
    JOIN (
        SELECT 
            user_id,
            balance,
            ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC, id DESC) as rn
        FROM ledgers 
        WHERE contact_type = 'customer'
    ) l ON c.id = l.user_id AND l.rn = 1
    WHERE ABS(c.current_balance - l.balance) > 0.01
    LIMIT 10
");

if (count($remainingBalanceIssues) > 0) {
    echo "⚠️  Still found " . count($remainingBalanceIssues) . " customer balance issues:\n";
    foreach ($remainingBalanceIssues as $issue) {
        echo "  Customer {$issue->id} ({$issue->first_name} {$issue->last_name}) | ";
        echo "DB: Rs " . number_format($issue->current_balance, 2) . " | ";
        echo "Ledger: Rs " . number_format($issue->ledger_balance, 2) . "\n";
    }
} else {
    echo "✅ No customer balance issues found!\n";
}

echo "\n3. SUMMARY OF CURRENT STATE:\n";
echo str_repeat("-", 60) . "\n";

// Current totals
$totalDuesFromSales = DB::select("SELECT SUM(total_due) as total FROM sales WHERE customer_id != 1 AND total_due > 0")[0]->total ?? 0;
$totalCustomerBalances = DB::select("SELECT SUM(current_balance) as total FROM customers WHERE current_balance > 0")[0]->total ?? 0;
$totalSalesCount = DB::select("SELECT COUNT(*) as count FROM sales WHERE customer_id != 1 AND total_due > 0")[0]->count ?? 0;
$totalCustomersWithBalance = DB::select("SELECT COUNT(*) as count FROM customers WHERE current_balance > 0.01")[0]->count ?? 0;

echo "📊 CURRENT STATISTICS:\n";
echo "- Total dues from sales: Rs " . number_format($totalDuesFromSales, 2) . " ($totalSalesCount sales)\n";
echo "- Total customer balances: Rs " . number_format($totalCustomerBalances, 2) . " ($totalCustomersWithBalance customers)\n";
echo "- Difference: Rs " . number_format(abs($totalDuesFromSales - $totalCustomerBalances), 2) . "\n";

$differencePercentage = $totalCustomerBalances > 0 ? (abs($totalDuesFromSales - $totalCustomerBalances) / $totalCustomerBalances) * 100 : 0;
echo "- Difference percentage: " . number_format($differencePercentage, 2) . "%\n";

echo "\n4. TOP CUSTOMERS BY DUE AMOUNT:\n";
echo str_repeat("-", 60) . "\n";

$topDues = DB::select("
    SELECT 
        c.id,
        c.first_name,
        c.last_name,
        c.current_balance,
        COUNT(s.id) as pending_sales,
        SUM(s.total_due) as total_dues_from_sales
    FROM customers c
    LEFT JOIN sales s ON c.id = s.customer_id AND s.total_due > 0
    WHERE c.current_balance > 1000
    GROUP BY c.id, c.first_name, c.last_name, c.current_balance
    ORDER BY c.current_balance DESC
    LIMIT 10
");

foreach ($topDues as $customer) {
    echo "Customer {$customer->id}: {$customer->first_name} {$customer->last_name}\n";
    echo "  Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
    echo "  Pending Sales: {$customer->pending_sales}\n";
    echo "  Total Due from Sales: Rs " . number_format($customer->total_dues_from_sales ?? 0, 2) . "\n";
    echo str_repeat("-", 40) . "\n";
}

echo "\n5. HEALTH CHECK SUMMARY:\n";
echo str_repeat("-", 60) . "\n";

$healthScore = 100;
if (count($remainingPaymentIssues) > 0) {
    $healthScore -= 20;
    echo "❌ Payment reconciliation issues: " . count($remainingPaymentIssues) . "\n";
} else {
    echo "✅ Payment reconciliation: Perfect\n";
}

if (count($remainingBalanceIssues) > 0) {
    $healthScore -= 20;
    echo "❌ Customer balance mismatches: " . count($remainingBalanceIssues) . "\n";
} else {
    echo "✅ Customer balance consistency: Perfect\n";
}

if ($differencePercentage > 10) {
    $healthScore -= 30;
    echo "⚠️  Large difference between sales dues and customer balances\n";
} elseif ($differencePercentage > 5) {
    $healthScore -= 15;
    echo "⚠️  Moderate difference between totals\n";
} else {
    echo "✅ Total amounts are reasonably consistent\n";
}

echo "\n🏆 CUSTOMER DUE SYSTEM HEALTH SCORE: $healthScore/100\n";

if ($healthScore >= 90) {
    echo "🎉 EXCELLENT! Your customer due system is very healthy!\n";
} elseif ($healthScore >= 70) {
    echo "👍 GOOD! Minor issues that can be monitored.\n";
} else {
    echo "⚠️  NEEDS ATTENTION! Significant issues require fixing.\n";
}

echo "\n✅ FINAL VERIFICATION COMPLETE!\n";