<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Database connection setup
$capsule = new DB;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'marazin_pos_db',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== CUSTOMER-BASED DATA ANALYSIS ===\n\n";

echo "📊 ANALYZING SALES, PAYMENTS, AND LEDGERS BY CUSTOMER ID\n";
echo "========================================================\n\n";

// Get all customers with their transaction counts
echo "1. CUSTOMER SUMMARY:\n";
echo "==================\n";

$customers = DB::select("
    SELECT 
        c.id,
        c.name,
        c.mobile,
        -- Sales count and totals
        COUNT(DISTINCT s.id) as total_sales,
        COALESCE(SUM(s.final_total), 0) as total_sale_amount,
        COALESCE(SUM(s.total_paid), 0) as total_paid_amount,
        COALESCE(SUM(s.total_due), 0) as total_due_amount,
        
        -- Payment count and totals
        COUNT(DISTINCT p.id) as total_payments,
        COALESCE(SUM(p.amount), 0) as total_payment_amount,
        
        -- Ledger count and balance
        COUNT(DISTINCT l.id) as total_ledger_entries,
        COALESCE(SUM(CASE WHEN l.transaction_type = 'sale' THEN l.debit_amount ELSE 0 END), 0) as ledger_sale_debit,
        COALESCE(SUM(CASE WHEN l.transaction_type = 'payment' THEN l.credit_amount ELSE 0 END), 0) as ledger_payment_credit,
        
        -- Customer balance
        c.balance as customer_balance
        
    FROM customers c
    LEFT JOIN sales s ON c.id = s.customer_id
    LEFT JOIN payments p ON c.id = p.customer_id AND p.payment_type = 'sale'
    LEFT JOIN ledgers l ON c.id = l.user_id AND l.contact_type = 'customer'
    WHERE c.id != 1  -- Exclude Walk-In customer
    GROUP BY c.id, c.name, c.mobile, c.balance
    HAVING total_sales > 0 OR total_payments > 0 OR total_ledger_entries > 0
    ORDER BY c.id
");

printf("%-4s %-20s %-12s %8s %12s %12s %12s %8s %12s %8s %12s %12s %12s\n",
    "ID", "Name", "Mobile", "Sales", "Sale Amt", "Paid Amt", "Due Amt", "Pymnts", "Pymnt Amt", "Ledger", "L.Debit", "L.Credit", "Balance");
echo str_repeat("-", 180) . "\n";

foreach ($customers as $customer) {
    printf("%-4d %-20s %-12s %8d %12.2f %12.2f %12.2f %8d %12.2f %8d %12.2f %12.2f %12.2f\n",
        $customer->id,
        substr($customer->name, 0, 20),
        $customer->mobile ?? 'N/A',
        $customer->total_sales,
        $customer->total_sale_amount,
        $customer->total_paid_amount,
        $customer->total_due_amount,
        $customer->total_payments,
        $customer->total_payment_amount,
        $customer->total_ledger_entries,
        $customer->ledger_sale_debit,
        $customer->ledger_payment_credit,
        $customer->customer_balance
    );
}

echo "\n2. DETAILED CUSTOMER ANALYSIS:\n";
echo "=============================\n";

// Check for inconsistencies
$inconsistencies = DB::select("
    SELECT 
        c.id as customer_id,
        c.name as customer_name,
        
        -- Sales data
        COUNT(DISTINCT s.id) as sales_count,
        COALESCE(SUM(s.final_total), 0) as sales_total,
        COALESCE(SUM(s.total_paid), 0) as sales_paid,
        COALESCE(SUM(s.total_due), 0) as sales_due,
        
        -- Payments data
        COUNT(DISTINCT p.id) as payments_count,
        COALESCE(SUM(p.amount), 0) as payments_total,
        
        -- Ledger data
        COALESCE(SUM(CASE WHEN l.transaction_type = 'sale' THEN l.debit_amount ELSE 0 END), 0) as ledger_sales,
        COALESCE(SUM(CASE WHEN l.transaction_type = 'payment' THEN l.credit_amount ELSE 0 END), 0) as ledger_payments,
        
        -- Customer balance
        c.balance as stored_balance,
        
        -- Calculated balance (sales - payments)
        (COALESCE(SUM(s.final_total), 0) - COALESCE(SUM(p.amount), 0)) as calculated_balance,
        
        -- Ledger balance (debits - credits)
        (COALESCE(SUM(CASE WHEN l.transaction_type = 'sale' THEN l.debit_amount ELSE 0 END), 0) - 
         COALESCE(SUM(CASE WHEN l.transaction_type = 'payment' THEN l.credit_amount ELSE 0 END), 0)) as ledger_balance
        
    FROM customers c
    LEFT JOIN sales s ON c.id = s.customer_id
    LEFT JOIN payments p ON c.id = p.customer_id AND p.payment_type = 'sale'
    LEFT JOIN ledgers l ON c.id = l.user_id AND l.contact_type = 'customer'
    WHERE c.id != 1
    GROUP BY c.id, c.name, c.balance
    HAVING sales_count > 0 OR payments_count > 0
    ORDER BY c.id
");

$issueCount = 0;
foreach ($inconsistencies as $row) {
    $salesMismatch = abs($row->sales_total - $row->ledger_sales) > 0.01;
    $paymentsMismatch = abs($row->payments_total - $row->ledger_payments) > 0.01;
    $balanceMismatch = abs($row->stored_balance - $row->calculated_balance) > 0.01;
    $ledgerMismatch = abs($row->ledger_balance - $row->calculated_balance) > 0.01;
    
    if ($salesMismatch || $paymentsMismatch || $balanceMismatch || $ledgerMismatch) {
        if ($issueCount == 0) {
            echo "⚠️  FOUND INCONSISTENCIES:\n";
            echo "==========================\n";
        }
        $issueCount++;
        
        echo "\n🔍 Customer ID: {$row->customer_id} - {$row->customer_name}\n";
        echo "   Sales: Count={$row->sales_count}, Total=₹" . number_format($row->sales_total, 2) . 
             ", Paid=₹" . number_format($row->sales_paid, 2) . 
             ", Due=₹" . number_format($row->sales_due, 2) . "\n";
        echo "   Payments: Count={$row->payments_count}, Total=₹" . number_format($row->payments_total, 2) . "\n";
        echo "   Ledger: Sales=₹" . number_format($row->ledger_sales, 2) . 
             ", Payments=₹" . number_format($row->ledger_payments, 2) . "\n";
        echo "   Balance: Stored=₹" . number_format($row->stored_balance, 2) . 
             ", Calculated=₹" . number_format($row->calculated_balance, 2) . 
             ", Ledger=₹" . number_format($row->ledger_balance, 2) . "\n";
        
        if ($salesMismatch) {
            echo "   ❌ Sales-Ledger mismatch: ₹" . number_format($row->sales_total - $row->ledger_sales, 2) . "\n";
        }
        if ($paymentsMismatch) {
            echo "   ❌ Payments-Ledger mismatch: ₹" . number_format($row->payments_total - $row->ledger_payments, 2) . "\n";
        }
        if ($balanceMismatch) {
            echo "   ❌ Balance mismatch: ₹" . number_format($row->stored_balance - $row->calculated_balance, 2) . "\n";
        }
        if ($ledgerMismatch) {
            echo "   ❌ Ledger balance inconsistent: ₹" . number_format($row->ledger_balance - $row->calculated_balance, 2) . "\n";
        }
    }
}

if ($issueCount == 0) {
    echo "✅ No inconsistencies found! All customer data is properly synchronized.\n";
} else {
    echo "\n⚠️  Total inconsistencies found: {$issueCount}\n";
}

echo "\n3. SAMPLE RECORDS BY CUSTOMER:\n";
echo "=============================\n";

// Show detailed records for first few customers with issues
$sampleCustomers = DB::select("
    SELECT DISTINCT c.id, c.name 
    FROM customers c 
    LEFT JOIN sales s ON c.id = s.customer_id 
    WHERE c.id != 1 AND s.id IS NOT NULL 
    ORDER BY c.id 
    LIMIT 3
");

foreach ($sampleCustomers as $customer) {
    echo "\n📋 Customer: {$customer->name} (ID: {$customer->id})\n";
    echo "========================================\n";
    
    // Sales for this customer
    $sales = DB::select("
        SELECT id, invoice_no, final_total, total_paid, total_due, created_at
        FROM sales 
        WHERE customer_id = ? 
        ORDER BY id DESC 
        LIMIT 5
    ", [$customer->id]);
    
    echo "Sales Records:\n";
    if (empty($sales)) {
        echo "  No sales found.\n";
    } else {
        foreach ($sales as $sale) {
            echo "  Sale #{$sale->id} | {$sale->invoice_no} | ₹" . number_format($sale->final_total, 2) . 
                 " | Paid: ₹" . number_format($sale->total_paid, 2) . 
                 " | Due: ₹" . number_format($sale->total_due, 2) . 
                 " | {$sale->created_at}\n";
        }
    }
    
    // Payments for this customer
    $payments = DB::select("
        SELECT id, amount, payment_method, payment_status, cheque_status, created_at
        FROM payments 
        WHERE customer_id = ? AND payment_type = 'sale'
        ORDER BY id DESC 
        LIMIT 5
    ", [$customer->id]);
    
    echo "\nPayment Records:\n";
    if (empty($payments)) {
        echo "  No payments found.\n";
    } else {
        foreach ($payments as $payment) {
            $status = $payment->payment_method === 'cheque' ? 
                      "({$payment->payment_method}-{$payment->cheque_status})" : 
                      "({$payment->payment_method})";
            echo "  Payment #{$payment->id} | ₹" . number_format($payment->amount, 2) . 
                 " | {$status} | {$payment->payment_status} | {$payment->created_at}\n";
        }
    }
    
    // Ledger entries for this customer
    $ledgers = DB::select("
        SELECT id, reference_no, transaction_type, debit_amount, credit_amount, created_at
        FROM ledgers 
        WHERE user_id = ? AND contact_type = 'customer'
        ORDER BY id DESC 
        LIMIT 5
    ", [$customer->id]);
    
    echo "\nLedger Records:\n";
    if (empty($ledgers)) {
        echo "  No ledger entries found.\n";
    } else {
        foreach ($ledgers as $ledger) {
            $amount = $ledger->debit_amount > 0 ? 
                     "Debit: ₹" . number_format($ledger->debit_amount, 2) : 
                     "Credit: ₹" . number_format($ledger->credit_amount, 2);
            echo "  Ledger #{$ledger->id} | {$ledger->reference_no} | {$ledger->transaction_type} | {$amount} | {$ledger->created_at}\n";
        }
    }
}

echo "\n4. CHEQUE PAYMENT ANALYSIS:\n";
echo "==========================\n";

// Check cheque payments specifically
$chequePayments = DB::select("
    SELECT 
        p.id as payment_id,
        p.customer_id,
        c.name as customer_name,
        p.amount,
        p.cheque_status,
        s.id as sale_id,
        s.invoice_no,
        s.final_total,
        s.total_paid,
        s.total_due
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN sales s ON p.reference_id = s.id AND p.payment_type = 'sale'
    WHERE p.payment_method = 'cheque'
    ORDER BY p.created_at DESC
    LIMIT 10
");

if (empty($chequePayments)) {
    echo "No cheque payments found.\n";
} else {
    echo "Recent Cheque Payments:\n";
    printf("%-8s %-4s %-15s %-10s %-12s %-8s %-12s %-10s %-10s %-10s\n",
        "Pay ID", "Cust", "Customer", "Status", "Amount", "Sale ID", "Invoice", "Total", "Paid", "Due");
    echo str_repeat("-", 120) . "\n";
    
    foreach ($chequePayments as $cheque) {
        printf("%-8d %-4d %-15s %-10s %-12.2f %-8s %-12s %-10.2f %-10.2f %-10.2f\n",
            $cheque->payment_id,
            $cheque->customer_id,
            substr($cheque->customer_name, 0, 15),
            $cheque->cheque_status ?? 'pending',
            $cheque->amount,
            $cheque->sale_id ?? 'N/A',
            $cheque->invoice_no ?? 'N/A',
            $cheque->final_total ?? 0,
            $cheque->total_paid ?? 0,
            $cheque->total_due ?? 0
        );
    }
}

echo "\n5. SUMMARY STATISTICS:\n";
echo "=====================\n";

$stats = DB::select("
    SELECT 
        (SELECT COUNT(*) FROM customers WHERE id != 1) as total_customers,
        (SELECT COUNT(*) FROM sales WHERE customer_id != 1) as total_sales,
        (SELECT COUNT(*) FROM payments WHERE customer_id != 1 AND payment_type = 'sale') as total_payments,
        (SELECT COUNT(*) FROM ledgers WHERE contact_type = 'customer') as total_ledger_entries,
        (SELECT COUNT(*) FROM payments WHERE payment_method = 'cheque' AND customer_id != 1) as total_cheque_payments,
        (SELECT COUNT(*) FROM payments WHERE payment_method = 'cheque' AND cheque_status = 'pending' AND customer_id != 1) as pending_cheques,
        (SELECT SUM(balance) FROM customers WHERE id != 1) as total_customer_balance,
        (SELECT SUM(final_total) FROM sales WHERE customer_id != 1) as total_sales_amount,
        (SELECT SUM(amount) FROM payments WHERE customer_id != 1 AND payment_type = 'sale') as total_payments_amount
")[0];

echo "Total Customers: {$stats->total_customers}\n";
echo "Total Sales: {$stats->total_sales}\n";
echo "Total Payments: {$stats->total_payments}\n";
echo "Total Ledger Entries: {$stats->total_ledger_entries}\n";
echo "Total Cheque Payments: {$stats->total_cheque_payments}\n";
echo "Pending Cheques: {$stats->pending_cheques}\n";
echo "Total Customer Balance: ₹" . number_format($stats->total_customer_balance, 2) . "\n";
echo "Total Sales Amount: ₹" . number_format($stats->total_sales_amount, 2) . "\n";
echo "Total Payments Amount: ₹" . number_format($stats->total_payments_amount, 2) . "\n";
echo "Outstanding Balance: ₹" . number_format($stats->total_sales_amount - $stats->total_payments_amount, 2) . "\n";

echo "\n=== ANALYSIS COMPLETE ===\n";

?>