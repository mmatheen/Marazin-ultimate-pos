<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "🔍 COMPREHENSIVE CUSTOMER DUE VERIFICATION\n";
echo "==========================================\n\n";

// 1. Check sales with due amounts vs actual payment calculations
echo "1. CHECKING SALES DUE AMOUNTS VS PAYMENT CALCULATIONS:\n";
echo str_repeat("-", 60) . "\n";

$salesWithDue = DB::select("
    SELECT 
        s.id as sale_id,
        s.invoice_no,
        s.customer_id,
        s.final_total,
        s.total_paid,
        s.total_due,
        (s.final_total - s.total_paid) as calculated_due,
        s.payment_status,
        c.first_name,
        c.last_name,
        c.current_balance
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.customer_id != 1 
    AND s.total_due > 0
    ORDER BY s.created_at DESC
    LIMIT 30
");

$dueIssues = [];
$totalSalesDue = 0;

echo "Recent sales with dues:\n";
foreach ($salesWithDue as $sale) {
    $calculatedDue = $sale->final_total - $sale->total_paid;
    $totalSalesDue += $sale->total_due;
    
    echo "Sale ID: {$sale->sale_id} | Invoice: {$sale->invoice_no} | ";
    echo "Customer: {$sale->customer_id} ({$sale->first_name} {$sale->last_name}) | ";
    echo "Total: Rs " . number_format($sale->final_total, 2) . " | ";
    echo "Paid: Rs " . number_format($sale->total_paid, 2) . " | ";
    echo "Due (recorded): Rs " . number_format($sale->total_due, 2) . " | ";
    echo "Due (calculated): Rs " . number_format($calculatedDue, 2) . " | ";
    echo "Status: {$sale->payment_status}\n";
    
    // Check if recorded due matches calculated due
    if (abs($sale->total_due - $calculatedDue) > 0.01) {
        $dueIssues[] = [
            'sale_id' => $sale->sale_id,
            'invoice' => $sale->invoice_no,
            'customer_id' => $sale->customer_id,
            'calculated_due' => $calculatedDue,
            'recorded_due' => $sale->total_due,
            'difference' => $sale->total_due - $calculatedDue
        ];
    }
}

echo "\nTotal dues from sales table: Rs " . number_format($totalSalesDue, 2) . "\n";

if (count($dueIssues) > 0) {
    echo "\n⚠️  FOUND " . count($dueIssues) . " SALES WITH DUE AMOUNT MISMATCHES:\n";
    foreach ($dueIssues as $issue) {
        echo "  Sale {$issue['sale_id']} | Invoice: {$issue['invoice']} | ";
        echo "Calculated: Rs " . number_format($issue['calculated_due'], 2) . " | ";
        echo "Recorded: Rs " . number_format($issue['recorded_due'], 2) . " | ";
        echo "Diff: Rs " . number_format($issue['difference'], 2) . "\n";
    }
} else {
    echo "✅ All sales due amounts are correctly calculated!\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 2. Check sales vs payments reconciliation
echo "2. CHECKING SALES VS PAYMENTS RECONCILIATION:\n";
echo str_repeat("-", 60) . "\n";

$salesPaymentCheck = DB::select("
    SELECT 
        s.id as sale_id,
        s.invoice_no,
        s.customer_id,
        s.final_total,
        s.total_paid as sale_paid,
        COALESCE(SUM(p.amount), 0) as actual_payments,
        (s.final_total - COALESCE(SUM(p.amount), 0)) as actual_due,
        s.total_due as recorded_due,
        c.first_name,
        c.last_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN payments p ON p.reference_id = s.id AND p.payment_type = 'sale'
    WHERE s.customer_id != 1 
    GROUP BY s.id, s.invoice_no, s.customer_id, s.final_total, s.total_paid, s.total_due, c.first_name, c.last_name
    HAVING (s.final_total - COALESCE(SUM(p.amount), 0)) != s.total_due OR s.total_paid != COALESCE(SUM(p.amount), 0)
    ORDER BY s.created_at DESC
    LIMIT 20
");

if (count($salesPaymentCheck) > 0) {
    echo "⚠️  FOUND " . count($salesPaymentCheck) . " SALES WITH PAYMENT RECONCILIATION ISSUES:\n";
    foreach ($salesPaymentCheck as $issue) {
        echo "  Sale ID: {$issue->sale_id} | Invoice: {$issue->invoice_no} | ";
        echo "Customer: {$issue->customer_id} ({$issue->first_name} {$issue->last_name})\n";
        echo "    Total: Rs " . number_format($issue->final_total, 2) . " | ";
        echo "Sale Paid: Rs " . number_format($issue->sale_paid, 2) . " | ";
        echo "Actual Payments: Rs " . number_format($issue->actual_payments, 2) . "\n";
        echo "    Recorded Due: Rs " . number_format($issue->recorded_due, 2) . " | ";
        echo "Actual Due: Rs " . number_format($issue->actual_due, 2) . "\n";
        echo str_repeat("-", 60) . "\n";
    }
} else {
    echo "✅ All sales payment reconciliation is correct!\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 3. Check customer balances vs ledger consistency
echo "3. CHECKING CUSTOMER BALANCES VS LEDGER CONSISTENCY:\n";
echo str_repeat("-", 60) . "\n";

$customerBalanceIssues = [];

// Get customers with non-zero balances
$customersWithBalance = DB::select("
    SELECT id, first_name, last_name, current_balance, opening_balance
    FROM customers 
    WHERE ABS(current_balance) > 0.01
    ORDER BY ABS(current_balance) DESC
    LIMIT 15
");

echo "Customers with non-zero balances (Top 15):\n";
foreach ($customersWithBalance as $customer) {
    echo "Customer ID: {$customer->id} | {$customer->first_name} {$customer->last_name} | ";
    echo "Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
    
    // Calculate balance from ledger entries
    $ledgerBalance = DB::select("
        SELECT balance 
        FROM ledgers 
        WHERE user_id = ? AND contact_type = 'customer'
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ", [$customer->id]);
    
    if (count($ledgerBalance) > 0) {
        $calculatedBalance = $ledgerBalance[0]->balance;
        
        echo "  Ledger Balance: Rs " . number_format($calculatedBalance, 2);
        
        if (abs($customer->current_balance - $calculatedBalance) > 0.01) {
            echo " ❌ MISMATCH!\n";
            $customerBalanceIssues[] = [
                'customer_id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'db_balance' => $customer->current_balance,
                'ledger_balance' => $calculatedBalance,
                'difference' => $customer->current_balance - $calculatedBalance
            ];
        } else {
            echo " ✅ Match\n";
        }
    } else {
        echo "  No ledger entries found ❌\n";
    }
}

if (count($customerBalanceIssues) > 0) {
    echo "\n⚠️  FOUND " . count($customerBalanceIssues) . " CUSTOMERS WITH BALANCE MISMATCHES:\n";
    foreach ($customerBalanceIssues as $issue) {
        echo "  Customer {$issue['customer_id']} ({$issue['name']}) | ";
        echo "DB: Rs " . number_format($issue['db_balance'], 2) . " | ";
        echo "Ledger: Rs " . number_format($issue['ledger_balance'], 2) . " | ";
        echo "Diff: Rs " . number_format($issue['difference'], 2) . "\n";
    }
} else {
    echo "\n✅ All customer balances match their ledger entries!\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 4. Check for sales without corresponding ledger entries
echo "4. CHECKING SALES WITHOUT LEDGER ENTRIES:\n";
echo str_repeat("-", 60) . "\n";

$salesWithoutLedger = DB::select("
    SELECT 
        s.id,
        s.invoice_no,
        s.customer_id,
        s.final_total,
        s.total_due,
        s.payment_status,
        c.first_name,
        c.last_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN ledgers l ON s.invoice_no = l.reference_no AND l.contact_type = 'customer' AND l.transaction_type = 'sale'
    WHERE s.customer_id != 1 
    AND s.invoice_no IS NOT NULL
    AND l.id IS NULL
    ORDER BY s.created_at DESC
    LIMIT 10
");

if (count($salesWithoutLedger) > 0) {
    echo "⚠️  FOUND " . count($salesWithoutLedger) . " SALES WITHOUT LEDGER ENTRIES:\n";
    foreach ($salesWithoutLedger as $sale) {
        echo "  Sale ID: {$sale->id} | Invoice: {$sale->invoice_no} | ";
        echo "Customer: {$sale->customer_id} ({$sale->first_name} {$sale->last_name}) | ";
        echo "Amount: Rs " . number_format($sale->final_total, 2) . " | ";
        echo "Due: Rs " . number_format($sale->total_due, 2) . " | ";
        echo "Status: {$sale->payment_status}\n";
    }
} else {
    echo "✅ All sales have corresponding ledger entries!\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 5. Summary and totals
echo "5. SUMMARY AND TOTALS:\n";
echo str_repeat("-", 60) . "\n";

// Get total customer dues from different sources
$totalDuesFromSales = DB::select("SELECT SUM(total_due) as total FROM sales WHERE customer_id != 1 AND total_due > 0")[0]->total ?? 0;
$totalCustomerBalances = DB::select("SELECT SUM(current_balance) as total FROM customers WHERE current_balance > 0")[0]->total ?? 0;

echo "Total dues from sales table: Rs " . number_format($totalDuesFromSales, 2) . "\n";
echo "Total positive customer balances: Rs " . number_format($totalCustomerBalances, 2) . "\n";
echo "Difference: Rs " . number_format(abs($totalDuesFromSales - $totalCustomerBalances), 2) . "\n";

$totalIssues = count($dueIssues) + count($salesPaymentCheck) + count($customerBalanceIssues) + count($salesWithoutLedger);

echo "\nTOTAL ISSUES FOUND: $totalIssues\n";
echo "- Sales due calculation issues: " . count($dueIssues) . "\n";
echo "- Payment reconciliation issues: " . count($salesPaymentCheck) . "\n";
echo "- Customer balance mismatches: " . count($customerBalanceIssues) . "\n";
echo "- Sales without ledger entries: " . count($salesWithoutLedger) . "\n";

if ($totalIssues == 0) {
    echo "\n🎉 EXCELLENT! Customer due system is completely consistent!\n";
} else {
    echo "\n⚠️  Issues found that need attention!\n";
}

echo "\n✅ CUSTOMER DUE VERIFICATION COMPLETE!\n";