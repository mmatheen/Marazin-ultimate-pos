<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 COMPREHENSIVE SALES-LEDGER-PAYMENT VERIFICATION\n";
echo "==================================================\n\n";

// 1. Sales vs Ledger Consistency Check
echo "1. SALES VS LEDGER CONSISTENCY:\n";
echo str_repeat("-", 60) . "\n";

$salesLedgerMismatches = DB::select("
    SELECT 
        s.id as sale_id,
        s.invoice_no,
        s.customer_id,
        s.final_total,
        s.total_paid,
        s.total_due,
        s.payment_status,
        c.first_name,
        c.last_name,
        c.current_balance,
        -- Check for corresponding ledger entries
        (SELECT COUNT(*) FROM ledgers l 
         WHERE l.reference_no = s.invoice_no 
         AND l.contact_type = 'customer' 
         AND l.transaction_type = 'sale') as ledger_sale_count,
        (SELECT SUM(l.debit) FROM ledgers l 
         WHERE l.reference_no = s.invoice_no 
         AND l.contact_type = 'customer' 
         AND l.transaction_type = 'sale') as ledger_sale_amount,
        (SELECT GROUP_CONCAT(DISTINCT l.user_id) FROM ledgers l 
         WHERE l.reference_no = s.invoice_no 
         AND l.contact_type = 'customer' 
         AND l.transaction_type = 'sale') as ledger_customers
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.customer_id != 1 
    AND s.invoice_no IS NOT NULL
    ORDER BY s.created_at DESC
    LIMIT 50
");

$salesIssues = [];
foreach ($salesLedgerMismatches as $sale) {
    $hasIssue = false;
    $issueType = [];
    
    // Check if sale has no ledger entry
    if ($sale->ledger_sale_count == 0) {
        $hasIssue = true;
        $issueType[] = 'no_ledger_entry';
    }
    
    // Check if ledger amount doesn't match sale amount
    if ($sale->ledger_sale_count > 0 && abs($sale->ledger_sale_amount - $sale->final_total) > 0.01) {
        $hasIssue = true;
        $issueType[] = 'amount_mismatch';
    }
    
    // Check if ledger customer doesn't match sale customer
    if ($sale->ledger_sale_count > 0 && $sale->ledger_customers != $sale->customer_id) {
        $hasIssue = true;
        $issueType[] = 'customer_mismatch';
    }
    
    if ($hasIssue) {
        $salesIssues[] = array_merge((array)$sale, ['issues' => $issueType]);
    }
}

if (count($salesIssues) > 0) {
    echo "⚠️  FOUND " . count($salesIssues) . " SALES-LEDGER MISMATCHES:\n";
    foreach ($salesIssues as $issue) {
        echo "Sale ID: {$issue['sale_id']} | Invoice: {$issue['invoice_no']}\n";
        echo "  Customer: {$issue['customer_id']} ({$issue['first_name']} {$issue['last_name']})\n";
        echo "  Sale Amount: Rs " . number_format($issue['final_total'], 2) . "\n";
        echo "  Ledger Amount: Rs " . number_format($issue['ledger_sale_amount'] ?? 0, 2) . "\n";
        echo "  Ledger Count: {$issue['ledger_sale_count']}\n";
        echo "  Ledger Customers: {$issue['ledger_customers']}\n";
        echo "  Issues: " . implode(', ', $issue['issues']) . "\n";
        echo str_repeat("-", 50) . "\n";
    }
} else {
    echo "✅ All sales have correct ledger entries!\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 2. Payment vs Ledger Consistency Check
echo "2. PAYMENT VS LEDGER CONSISTENCY:\n";
echo str_repeat("-", 60) . "\n";

$paymentLedgerMismatches = DB::select("
    SELECT 
        p.id as payment_id,
        p.reference_id as sale_id,
        p.amount,
        p.payment_method,
        p.payment_status,
        p.cheque_status,
        p.customer_id,
        c.first_name,
        c.last_name,
        s.invoice_no,
        -- Check for corresponding ledger entries
        (SELECT COUNT(*) FROM ledgers l 
         WHERE l.reference_no LIKE CONCAT('PAY-%', p.id)
         AND l.contact_type = 'customer' 
         AND l.transaction_type = 'payment') as ledger_payment_count,
        (SELECT SUM(l.credit) FROM ledgers l 
         WHERE l.reference_no LIKE CONCAT('PAY-%', p.id)
         AND l.contact_type = 'customer' 
         AND l.transaction_type = 'payment') as ledger_payment_amount,
        (SELECT l.user_id FROM ledgers l 
         WHERE l.reference_no LIKE CONCAT('PAY-%', p.id)
         AND l.contact_type = 'customer' 
         AND l.transaction_type = 'payment' 
         LIMIT 1) as ledger_customer
    FROM payments p
    LEFT JOIN customers c ON p.customer_id = c.id
    LEFT JOIN sales s ON p.reference_id = s.id AND p.payment_type = 'sale'
    WHERE p.payment_type = 'sale'
    ORDER BY p.created_at DESC
    LIMIT 50
");

$paymentIssues = [];
foreach ($paymentLedgerMismatches as $payment) {
    $hasIssue = false;
    $issueType = [];
    
    // For completed payments (not pending cheques)
    if ($payment->payment_status == 'completed' || 
        ($payment->payment_method == 'cheque' && in_array($payment->cheque_status, ['cleared', 'deposited']))) {
        
        // Check if payment has no ledger entry
        if ($payment->ledger_payment_count == 0) {
            $hasIssue = true;
            $issueType[] = 'no_ledger_entry';
        }
        
        // Check if ledger amount doesn't match payment amount
        if ($payment->ledger_payment_count > 0 && abs($payment->ledger_payment_amount - $payment->amount) > 0.01) {
            $hasIssue = true;
            $issueType[] = 'amount_mismatch';
        }
        
        // Check if ledger customer doesn't match payment customer
        if ($payment->ledger_payment_count > 0 && $payment->ledger_customer != $payment->customer_id) {
            $hasIssue = true;
            $issueType[] = 'customer_mismatch';
        }
    }
    
    // For pending payments (especially cheques)
    if ($payment->payment_method == 'cheque' && $payment->cheque_status == 'pending') {
        // Should NOT have ledger entry
        if ($payment->ledger_payment_count > 0) {
            $hasIssue = true;
            $issueType[] = 'pending_has_ledger';
        }
    }
    
    // For failed/bounced payments
    if ($payment->payment_status == 'failed' || 
        ($payment->payment_method == 'cheque' && $payment->cheque_status == 'bounced')) {
        // Should NOT have ledger entry
        if ($payment->ledger_payment_count > 0) {
            $hasIssue = true;
            $issueType[] = 'failed_has_ledger';
        }
    }
    
    if ($hasIssue) {
        $paymentIssues[] = array_merge((array)$payment, ['issues' => $issueType]);
    }
}

if (count($paymentIssues) > 0) {
    echo "⚠️  FOUND " . count($paymentIssues) . " PAYMENT-LEDGER MISMATCHES:\n";
    foreach ($paymentIssues as $issue) {
        echo "Payment ID: {$issue['payment_id']} | Sale ID: {$issue['sale_id']}\n";
        echo "  Customer: {$issue['customer_id']} ({$issue['first_name']} {$issue['last_name']})\n";
        echo "  Invoice: {$issue['invoice_no']}\n";
        echo "  Payment Amount: Rs " . number_format($issue['amount'], 2) . "\n";
        echo "  Payment Method: {$issue['payment_method']}\n";
        echo "  Payment Status: {$issue['payment_status']}\n";
        if ($issue['payment_method'] == 'cheque') {
            echo "  Cheque Status: {$issue['cheque_status']}\n";
        }
        echo "  Ledger Count: {$issue['ledger_payment_count']}\n";
        echo "  Ledger Amount: Rs " . number_format($issue['ledger_payment_amount'] ?? 0, 2) . "\n";
        echo "  Issues: " . implode(', ', $issue['issues']) . "\n";
        echo str_repeat("-", 50) . "\n";
    }
} else {
    echo "✅ All payments have correct ledger entries!\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 3. Payment Method Analysis
echo "3. PAYMENT METHOD ANALYSIS:\n";
echo str_repeat("-", 60) . "\n";

$paymentMethodAnalysis = DB::select("
    SELECT 
        p.payment_method,
        p.payment_status,
        CASE 
            WHEN p.payment_method = 'cheque' THEN p.cheque_status 
            ELSE 'N/A' 
        END as cheque_status,
        COUNT(*) as count,
        SUM(p.amount) as total_amount,
        SUM(CASE 
            WHEN EXISTS(
                SELECT 1 FROM ledgers l 
                WHERE l.reference_no LIKE CONCAT('PAY-%', p.id)
                AND l.contact_type = 'customer' 
                AND l.transaction_type = 'payment'
            ) THEN 1 ELSE 0 END) as has_ledger_entry
    FROM payments p
    WHERE p.payment_type = 'sale'
    GROUP BY p.payment_method, p.payment_status, 
             CASE WHEN p.payment_method = 'cheque' THEN p.cheque_status ELSE 'N/A' END
    ORDER BY p.payment_method, p.payment_status
");

echo "Payment method breakdown:\n\n";
foreach ($paymentMethodAnalysis as $method) {
    echo "Method: {$method->payment_method} | Status: {$method->payment_status}";
    if ($method->cheque_status != 'N/A') {
        echo " | Cheque: {$method->cheque_status}";
    }
    echo "\n";
    echo "  Count: {$method->count} payments\n";
    echo "  Total: Rs " . number_format($method->total_amount, 2) . "\n";
    echo "  With Ledger: {$method->has_ledger_entry} / {$method->count}\n";
    
    // Calculate percentage
    $percentage = $method->count > 0 ? ($method->has_ledger_entry / $method->count) * 100 : 0;
    echo "  Ledger %: " . number_format($percentage, 1) . "%\n";
    
    // Flag issues
    if ($method->payment_status == 'completed' && $percentage < 100) {
        echo "  ⚠️  Some completed payments missing ledger entries!\n";
    }
    if (($method->payment_status == 'pending' || $method->cheque_status == 'pending') && $percentage > 0) {
        echo "  ⚠️  Pending payments should not have ledger entries!\n";
    }
    
    echo str_repeat("-", 40) . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 4. Customer Balance Verification
echo "4. CUSTOMER BALANCE VERIFICATION:\n";
echo str_repeat("-", 60) . "\n";

$customerBalanceCheck = DB::select("
    SELECT 
        c.id,
        c.first_name,
        c.last_name,
        c.current_balance as db_balance,
        COALESCE(l.balance, c.opening_balance, 0) as ledger_balance,
        -- Calculate expected balance from sales and payments
        COALESCE(sales_due.total_due, 0) as calculated_due,
        COALESCE(sales_due.sale_count, 0) as pending_sales
    FROM customers c
    LEFT JOIN (
        SELECT 
            user_id,
            balance,
            ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC, id DESC) as rn
        FROM ledgers 
        WHERE contact_type = 'customer'
    ) l ON c.id = l.user_id AND l.rn = 1
    LEFT JOIN (
        SELECT 
            customer_id,
            SUM(total_due) as total_due,
            COUNT(*) as sale_count
        FROM sales 
        WHERE customer_id != 1 AND total_due > 0
        GROUP BY customer_id
    ) sales_due ON c.id = sales_due.customer_id
    WHERE ABS(c.current_balance - COALESCE(l.balance, c.opening_balance, 0)) > 0.01
    OR c.current_balance != COALESCE(sales_due.total_due, 0)
    ORDER BY ABS(c.current_balance - COALESCE(l.balance, c.opening_balance, 0)) DESC
    LIMIT 20
");

if (count($customerBalanceCheck) > 0) {
    echo "⚠️  FOUND " . count($customerBalanceCheck) . " CUSTOMER BALANCE DISCREPANCIES:\n";
    foreach ($customerBalanceCheck as $customer) {
        echo "Customer ID: {$customer->id} | {$customer->first_name} {$customer->last_name}\n";
        echo "  DB Balance: Rs " . number_format($customer->db_balance, 2) . "\n";
        echo "  Ledger Balance: Rs " . number_format($customer->ledger_balance, 2) . "\n";
        echo "  Calculated Due: Rs " . number_format($customer->calculated_due, 2) . "\n";
        echo "  Pending Sales: {$customer->pending_sales}\n";
        
        if (abs($customer->db_balance - $customer->ledger_balance) > 0.01) {
            echo "  ❌ DB vs Ledger mismatch: Rs " . number_format($customer->db_balance - $customer->ledger_balance, 2) . "\n";
        }
        if (abs($customer->db_balance - $customer->calculated_due) > 0.01) {
            echo "  ❌ DB vs Calculated mismatch: Rs " . number_format($customer->db_balance - $customer->calculated_due, 2) . "\n";
        }
        
        echo str_repeat("-", 50) . "\n";
    }
} else {
    echo "✅ All customer balances are consistent!\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 5. Summary and Recommendations
echo "5. SUMMARY AND RECOMMENDATIONS:\n";
echo str_repeat("-", 60) . "\n";

$totalIssues = count($salesIssues) + count($paymentIssues) + count($customerBalanceCheck);
echo "📊 ISSUE SUMMARY:\n";
echo "- Sales-Ledger mismatches: " . count($salesIssues) . "\n";
echo "- Payment-Ledger mismatches: " . count($paymentIssues) . "\n";
echo "- Customer balance discrepancies: " . count($customerBalanceCheck) . "\n";
echo "- TOTAL ISSUES: $totalIssues\n\n";

if ($totalIssues == 0) {
    echo "🎉 EXCELLENT! All records are consistent!\n";
    echo "✅ Sales have proper ledger entries\n";
    echo "✅ Payments have correct ledger handling\n";
    echo "✅ Customer balances are accurate\n";
} else {
    echo "⚠️  ISSUES REQUIRE ATTENTION!\n\n";
    
    echo "📋 RECOMMENDED ACTIONS:\n";
    if (count($salesIssues) > 0) {
        echo "1. Fix sales without ledger entries\n";
        echo "2. Correct sales-ledger amount mismatches\n";
        echo "3. Resolve customer assignment errors in ledger\n";
    }
    
    if (count($paymentIssues) > 0) {
        echo "4. Create missing ledger entries for completed payments\n";
        echo "5. Remove ledger entries for pending/failed payments\n";
        echo "6. Fix payment-ledger amount discrepancies\n";
    }
    
    if (count($customerBalanceCheck) > 0) {
        echo "7. Sync customer balances with ledger entries\n";
        echo "8. Recalculate customer dues from sales\n";
    }
    
    echo "\n💡 PRIORITY ORDER:\n";
    echo "1. Fix payment method issues (especially cheques)\n";
    echo "2. Sync customer balances\n";
    echo "3. Resolve sales-ledger mismatches\n";
}

echo "\n✅ COMPREHENSIVE VERIFICATION COMPLETE!\n";
echo "For detailed fixes, run the specific correction scripts.\n";