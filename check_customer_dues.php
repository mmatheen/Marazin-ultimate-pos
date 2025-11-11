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

// 1. Check sales with due amounts vs ledger consistency
echo "1. CHECKING SALES DUE VS LEDGER CONSISTENCY:\n";
echo str_repeat("-", 50) . "\n";

$salesWithDue = DB::select("
    SELECT 
        s.id as sale_id,
        s.invoice_no,
        s.customer_id,
        s.final_total,
        s.paid_amount,
        (s.final_total - s.paid_amount) as calculated_due,
        s.due_amount as recorded_due,
        c.first_name,
        c.last_name,
        c.current_balance
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.customer_id != 1 
    AND (s.final_total - s.paid_amount) != 0
    ORDER BY s.created_at DESC
    LIMIT 50
");

$dueIssues = [];
$totalSalesDue = 0;

echo "Recent sales with dues:\n";
foreach ($salesWithDue as $sale) {
    $calculatedDue = $sale->final_total - $sale->paid_amount;
    $totalSalesDue += $calculatedDue;
    
    echo "Sale ID: {$sale->sale_id} | Invoice: {$sale->invoice_no} | ";
    echo "Customer: {$sale->customer_id} ({$sale->first_name} {$sale->last_name}) | ";
    echo "Total: Rs " . number_format($sale->final_total, 2) . " | ";
    echo "Paid: Rs " . number_format($sale->paid_amount, 2) . " | ";
    echo "Due: Rs " . number_format($calculatedDue, 2) . "\n";
    
    // Check if recorded due matches calculated due
    if (abs($sale->recorded_due - $calculatedDue) > 0.01) {
        $dueIssues[] = [
            'sale_id' => $sale->sale_id,
            'invoice' => $sale->invoice_no,
            'customer_id' => $sale->customer_id,
            'calculated_due' => $calculatedDue,
            'recorded_due' => $sale->recorded_due
        ];
    }
}

echo "\nTotal dues from sales: Rs " . number_format($totalSalesDue, 2) . "\n";

if (count($dueIssues) > 0) {
    echo "\n⚠️  FOUND " . count($dueIssues) . " SALES WITH DUE AMOUNT MISMATCHES:\n";
    foreach ($dueIssues as $issue) {
        echo "  Sale {$issue['sale_id']} | Invoice: {$issue['invoice']} | ";
        echo "Calculated: Rs " . number_format($issue['calculated_due'], 2) . " | ";
        echo "Recorded: Rs " . number_format($issue['recorded_due'], 2) . "\n";
    }
} else {
    echo "✅ All sales due amounts are correctly calculated!\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// 2. Check customer balances vs ledger entries
echo "2. CHECKING CUSTOMER BALANCES VS LEDGER ENTRIES:\n";
echo str_repeat("-", 50) . "\n";

$customerBalanceIssues = [];

// Get customers with non-zero balances
$customersWithBalance = DB::select("
    SELECT id, first_name, last_name, current_balance, opening_balance
    FROM customers 
    WHERE ABS(current_balance) > 0.01
    ORDER BY ABS(current_balance) DESC
    LIMIT 20
");

echo "Customers with non-zero balances:\n";
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
        
        if (abs($customer->current_balance - $calculatedBalance) > 0.01) {
            $customerBalanceIssues[] = [
                'customer_id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'db_balance' => $customer->current_balance,
                'ledger_balance' => $calculatedBalance
            ];
        }
    }
}

if (count($customerBalanceIssues) > 0) {
    echo "\n⚠️  FOUND " . count($customerBalanceIssues) . " CUSTOMERS WITH BALANCE MISMATCHES:\n";
    foreach ($customerBalanceIssues as $issue) {
        echo "  Customer {$issue['customer_id']} ({$issue['name']}) | ";
        echo "DB: Rs " . number_format($issue['db_balance'], 2) . " | ";
        echo "Ledger: Rs " . number_format($issue['ledger_balance'], 2) . "\n";
    }
} else {
    echo "✅ All customer balances match their ledger entries!\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// 3. Check for sales without corresponding ledger entries
echo "3. CHECKING SALES WITHOUT LEDGER ENTRIES:\n";
echo str_repeat("-", 50) . "\n";

$salesWithoutLedger = DB::select("
    SELECT 
        s.id,
        s.invoice_no,
        s.customer_id,
        s.final_total,
        s.paid_amount,
        (s.final_total - s.paid_amount) as due_amount,
        c.first_name,
        c.last_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN ledgers l ON s.invoice_no = l.reference_no AND l.contact_type = 'customer'
    WHERE s.customer_id != 1 
    AND s.invoice_no IS NOT NULL
    AND l.id IS NULL
    ORDER BY s.created_at DESC
    LIMIT 20
");

if (count($salesWithoutLedger) > 0) {
    echo "⚠️  FOUND " . count($salesWithoutLedger) . " SALES WITHOUT LEDGER ENTRIES:\n";
    foreach ($salesWithoutLedger as $sale) {
        echo "  Sale ID: {$sale->id} | Invoice: {$sale->invoice_no} | ";
        echo "Customer: {$sale->customer_id} ({$sale->first_name} {$sale->last_name}) | ";
        echo "Amount: Rs " . number_format($sale->final_total, 2) . " | ";
        echo "Due: Rs " . number_format($sale->due_amount, 2) . "\n";
    }
} else {
    echo "✅ All sales have corresponding ledger entries!\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// 4. Check for ledger entries without corresponding sales
echo "4. CHECKING LEDGER ENTRIES WITHOUT SALES:\n";
echo str_repeat("-", 50) . "\n";

$ledgerWithoutSales = DB::select("
    SELECT 
        l.id,
        l.reference_no,
        l.user_id,
        l.debit,
        l.credit,
        l.transaction_type,
        c.first_name,
        c.last_name
    FROM ledgers l
    LEFT JOIN customers c ON l.user_id = c.id
    LEFT JOIN sales s ON l.reference_no = s.invoice_no
    WHERE l.contact_type = 'customer'
    AND l.transaction_type = 'sale'
    AND l.reference_no NOT LIKE 'CLEANUP-%'
    AND l.reference_no NOT LIKE 'OPENING-%'
    AND l.reference_no NOT LIKE 'PAY-%'
    AND s.id IS NULL
    ORDER BY l.created_at DESC
    LIMIT 20
");

if (count($ledgerWithoutSales) > 0) {
    echo "⚠️  FOUND " . count($ledgerWithoutSales) . " LEDGER ENTRIES WITHOUT SALES:\n";
    foreach ($ledgerWithoutSales as $ledger) {
        echo "  Ledger ID: {$ledger->id} | Ref: {$ledger->reference_no} | ";
        echo "Customer: {$ledger->user_id} ({$ledger->first_name} {$ledger->last_name}) | ";
        echo "Debit: Rs " . number_format($ledger->debit, 2) . " | ";
        echo "Credit: Rs " . number_format($ledger->credit, 2) . "\n";
    }
} else {
    echo "✅ All sale ledger entries have corresponding sales!\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// 5. Summary and recommendations
echo "5. SUMMARY AND RECOMMENDATIONS:\n";
echo str_repeat("-", 50) . "\n";

$totalIssues = count($dueIssues) + count($customerBalanceIssues) + count($salesWithoutLedger) + count($ledgerWithoutSales);

if ($totalIssues == 0) {
    echo "🎉 EXCELLENT! No issues found in customer due system!\n";
    echo "✅ All sales dues are correctly calculated\n";
    echo "✅ All customer balances match ledger entries\n";
    echo "✅ All sales have proper ledger entries\n";
    echo "✅ All ledger entries have corresponding sales\n";
} else {
    echo "⚠️  FOUND $totalIssues TOTAL ISSUES:\n";
    echo "- Sales due mismatches: " . count($dueIssues) . "\n";
    echo "- Customer balance mismatches: " . count($customerBalanceIssues) . "\n";
    echo "- Sales without ledger: " . count($salesWithoutLedger) . "\n";
    echo "- Orphaned ledger entries: " . count($ledgerWithoutSales) . "\n";
    
    echo "\n📋 RECOMMENDED ACTIONS:\n";
    
    if (count($dueIssues) > 0) {
        echo "1. Fix due amount calculations in sales table\n";
    }
    
    if (count($customerBalanceIssues) > 0) {
        echo "2. Sync customer balances with ledger entries\n";
    }
    
    if (count($salesWithoutLedger) > 0) {
        echo "3. Create missing ledger entries for sales\n";
    }
    
    if (count($ledgerWithoutSales) > 0) {
        echo "4. Investigate orphaned ledger entries\n";
    }
}

echo "\n✅ CUSTOMER DUE VERIFICATION COMPLETE!\n";