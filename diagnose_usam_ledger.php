<?php
/**
 * 🔍 USAM SHOWROOM LEDGER DIAGNOSTIC - EXECUTABLE VERSION
 * Customer ID: 75 | Mobile: 0777491925
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 🔍 USAM SHOWROOM LEDGER DIAGNOSTIC ===\n\n";

$customerId = 75;

// Query 1: ALL LEDGER ENTRIES
echo "1️⃣ ALL LEDGER ENTRIES FOR CUSTOMER 75\n";
echo str_repeat("=", 80) . "\n";
$ledgers = DB::select("
    SELECT
        id,
        transaction_date,
        transaction_type,
        reference_no,
        debit,
        credit,
        status,
        notes,
        created_at
    FROM ledgers
    WHERE contact_id = ?
        AND contact_type = 'customer'
    ORDER BY transaction_date ASC, id ASC
", [$customerId]);

if (empty($ledgers)) {
    echo "❌ NO LEDGER ENTRIES FOUND!\n\n";
} else {
    echo "Found " . count($ledgers) . " ledger entries:\n";
    foreach ($ledgers as $l) {
        echo sprintf("ID: %-5s | Date: %s | Type: %-15s | Ref: %-15s | Debit: %10.2f | Credit: %10.2f | Status: %s\n",
            $l->id, $l->transaction_date, $l->transaction_type, $l->reference_no, $l->debit, $l->credit, $l->status);
    }
}
echo "\n";

// Query 2: CALCULATE BALANCE
echo "2️⃣ BALANCE CALCULATION FROM LEDGER\n";
echo str_repeat("=", 80) . "\n";
$balance = DB::selectOne("
    SELECT
        COALESCE(SUM(debit), 0) as total_debits,
        COALESCE(SUM(credit), 0) as total_credits,
        COALESCE(SUM(debit) - SUM(credit), 0) as calculated_balance
    FROM ledgers
    WHERE contact_id = ?
        AND contact_type = 'customer'
        AND status = 'active'
", [$customerId]);

echo "Total Debits:  Rs. " . number_format($balance->total_debits, 2) . "\n";
echo "Total Credits: Rs. " . number_format($balance->total_credits, 2) . "\n";
echo "Balance:       Rs. " . number_format($balance->calculated_balance, 2) . "\n\n";

// Query 3: ALL SALES
echo "3️⃣ ALL SALES FOR CUSTOMER 75\n";
echo str_repeat("=", 80) . "\n";
$sales = DB::select("
    SELECT
        id,
        invoice_no,
        sales_date,
        final_total,
        total_paid,
        total_due,
        payment_status,
        transaction_type
    FROM sales
    WHERE customer_id = ?
    ORDER BY sales_date ASC
", [$customerId]);

echo "Found " . count($sales) . " sales:\n";
$totalSales = 0;
$totalPaid = 0;
$totalDue = 0;
foreach ($sales as $s) {
    echo sprintf("ID: %-5s | %s | Date: %s | Total: %10.2f | Paid: %10.2f | Due: %10.2f | Status: %s\n",
        $s->id, $s->invoice_no, $s->sales_date, $s->final_total, $s->total_paid, $s->total_due, $s->payment_status);
    if ($s->transaction_type === 'invoice') {
        $totalSales += $s->final_total;
        $totalPaid += $s->total_paid;
        $totalDue += $s->total_due;
    }
}
echo "\nSales Summary:\n";
echo "Total Sales:  Rs. " . number_format($totalSales, 2) . "\n";
echo "Total Paid:   Rs. " . number_format($totalPaid, 2) . "\n";
echo "Total Due:    Rs. " . number_format($totalDue, 2) . "\n\n";

// Query 4: ALL PAYMENTS
echo "4️⃣ ALL PAYMENTS FOR CUSTOMER 75\n";
echo str_repeat("=", 80) . "\n";
$payments = DB::select("
    SELECT
        id,
        payment_date,
        amount,
        payment_method,
        reference_no,
        notes,
        payment_type,
        reference_id,
        status
    FROM payments
    WHERE customer_id = ?
    ORDER BY payment_date ASC
", [$customerId]);

echo "Found " . count($payments) . " payments:\n";
$totalPayments = 0;
foreach ($payments as $p) {
    echo sprintf("ID: %-5s | Date: %s | Amount: %10.2f | Ref: %-15s | Type: %-10s | Status: %s\n",
        $p->id, $p->payment_date, $p->amount, $p->reference_no, $p->payment_type, $p->status);
    if ($p->status === 'active') {
        $totalPayments += $p->amount;
    }
}
echo "\nTotal Payments: Rs. " . number_format($totalPayments, 2) . "\n\n";

// Query 5: CUSTOMER TABLE
echo "5️⃣ CUSTOMER TABLE DATA\n";
echo str_repeat("=", 80) . "\n";
$customer = DB::selectOne("
    SELECT
        id,
        CONCAT(first_name, ' ', COALESCE(last_name, '')) as customer_name,
        mobile_no,
        opening_balance,
        current_balance,
        credit_limit
    FROM customers
    WHERE id = ?
", [$customerId]);

echo "Customer Name:    " . $customer->customer_name . "\n";
echo "Mobile:           " . $customer->mobile_no . "\n";
echo "Opening Balance:  Rs. " . number_format($customer->opening_balance ?? 0, 2) . "\n";
echo "Current Balance:  Rs. " . number_format($customer->current_balance, 2) . "\n";
echo "Credit Limit:     Rs. " . number_format($customer->credit_limit, 2) . "\n\n";

// Query 6: SALES VS LEDGER DEBITS
echo "6️⃣ SALES VS LEDGER DEBITS (Should Match)\n";
echo str_repeat("=", 80) . "\n";
$comparison = DB::select("
    SELECT
        'Sales Total' as source,
        COALESCE(SUM(final_total), 0) as amount
    FROM sales
    WHERE customer_id = ?
        AND transaction_type = 'invoice'
        AND status = 'final'
    UNION ALL
    SELECT
        'Ledger Debits' as source,
        COALESCE(SUM(debit), 0) as amount
    FROM ledgers
    WHERE contact_id = ?
        AND contact_type = 'customer'
        AND transaction_type = 'sale'
        AND status = 'active'
", [$customerId, $customerId]);

foreach ($comparison as $c) {
    echo sprintf("%-20s Rs. %12.2f\n", $c->source . ":", $c->amount);
}
$diff = abs($comparison[0]->amount - $comparison[1]->amount);
if ($diff > 0.01) {
    echo "❌ MISMATCH: Rs. " . number_format($diff, 2) . "\n";
} else {
    echo "✅ MATCH\n";
}
echo "\n";

// Query 7: PAYMENTS VS LEDGER CREDITS
echo "7️⃣ PAYMENTS VS LEDGER CREDITS (Should Match)\n";
echo str_repeat("=", 80) . "\n";
$paymentComparison = DB::select("
    SELECT
        'Payments Total' as source,
        COALESCE(SUM(amount), 0) as amount
    FROM payments
    WHERE customer_id = ?
        AND status = 'active'
    UNION ALL
    SELECT
        'Ledger Credits' as source,
        COALESCE(SUM(credit), 0) as amount
    FROM ledgers
    WHERE contact_id = ?
        AND contact_type = 'customer'
        AND transaction_type = 'payments'
        AND status = 'active'
", [$customerId, $customerId]);

foreach ($paymentComparison as $c) {
    echo sprintf("%-20s Rs. %12.2f\n", $c->source . ":", $c->amount);
}
$diffPay = abs($paymentComparison[0]->amount - $paymentComparison[1]->amount);
if ($diffPay > 0.01) {
    echo "❌ MISMATCH: Rs. " . number_format($diffPay, 2) . "\n";
} else {
    echo "✅ MATCH\n";
}
echo "\n";

// Query 8: FIND SALES WITHOUT LEDGER
echo "8️⃣ SALES WITHOUT LEDGER ENTRIES\n";
echo str_repeat("=", 80) . "\n";
$missingSales = DB::select("
    SELECT
        s.id as sale_id,
        s.invoice_no,
        s.sales_date,
        s.final_total,
        s.payment_status,
        l.id as ledger_id,
        l.debit as ledger_debit
    FROM sales s
    LEFT JOIN ledgers l ON l.reference_id = s.id
        AND l.contact_type = 'customer'
        AND l.transaction_type = 'sale'
        AND l.status = 'active'
    WHERE s.customer_id = ?
        AND s.transaction_type = 'invoice'
        AND s.status = 'final'
        AND l.id IS NULL
", [$customerId]);

if (empty($missingSales)) {
    echo "✅ All sales have ledger entries\n";
} else {
    echo "❌ Found " . count($missingSales) . " sales WITHOUT ledger entries:\n";
    foreach ($missingSales as $ms) {
        echo sprintf("Sale ID: %s | Invoice: %s | Date: %s | Amount: Rs. %10.2f | Status: %s\n",
            $ms->sale_id, $ms->invoice_no, $ms->sales_date, $ms->final_total, $ms->payment_status);
    }
}
echo "\n";

// Query 9: FIND PAYMENTS WITHOUT LEDGER
echo "9️⃣ PAYMENTS WITHOUT LEDGER ENTRIES\n";
echo str_repeat("=", 80) . "\n";
$missingPayments = DB::select("
    SELECT
        p.id as payment_id,
        p.payment_date,
        p.amount,
        p.reference_no,
        l.id as ledger_id,
        l.credit as ledger_credit
    FROM payments p
    LEFT JOIN ledgers l ON l.reference_id = p.id
        AND l.contact_type = 'customer'
        AND l.transaction_type = 'payments'
        AND l.status = 'active'
    WHERE p.customer_id = ?
        AND p.status = 'active'
        AND l.id IS NULL
", [$customerId]);

if (empty($missingPayments)) {
    echo "✅ All payments have ledger entries\n";
} else {
    echo "❌ Found " . count($missingPayments) . " payments WITHOUT ledger entries:\n";
    foreach ($missingPayments as $mp) {
        echo sprintf("Payment ID: %s | Date: %s | Amount: Rs. %10.2f | Ref: %s\n",
            $mp->payment_id, $mp->payment_date, $mp->amount, $mp->reference_no);
    }
}
echo "\n";

// Query 10: EXPECTED VS ACTUAL BALANCE
echo "🔟 EXPECTED VS ACTUAL BALANCE\n";
echo str_repeat("=", 80) . "\n";
$balanceComparison = DB::select("
    SELECT
        'Expected Balance' as description,
        (
            SELECT COALESCE(SUM(final_total), 0) - COALESCE(SUM(total_paid), 0)
            FROM sales
            WHERE customer_id = ?
                AND transaction_type = 'invoice'
                AND status = 'final'
        ) as amount
    UNION ALL
    SELECT
        'Actual Ledger Balance' as description,
        COALESCE(SUM(debit) - SUM(credit), 0) as amount
    FROM ledgers
    WHERE contact_id = ?
        AND contact_type = 'customer'
        AND status = 'active'
", [$customerId, $customerId]);

foreach ($balanceComparison as $bc) {
    echo sprintf("%-30s Rs. %12.2f\n", $bc->description . ":", $bc->amount);
}
$balDiff = abs($balanceComparison[0]->amount - $balanceComparison[1]->amount);
if ($balDiff > 0.01) {
    echo "❌ BALANCE MISMATCH: Rs. " . number_format($balDiff, 2) . "\n";
} else {
    echo "✅ BALANCES MATCH\n";
}
echo "\n";

// SUMMARY
echo str_repeat("=", 80) . "\n";
echo "🎯 DIAGNOSTIC SUMMARY\n";
echo str_repeat("=", 80) . "\n";

$issues = [];

if (empty($ledgers)) {
    $issues[] = "NO LEDGER ENTRIES EXIST (Critical!)";
}

if ($diff > 0.01) {
    $issues[] = "Sales total doesn't match ledger debits (Rs. " . number_format($diff, 2) . " difference)";
}

if ($diffPay > 0.01) {
    $issues[] = "Payments total doesn't match ledger credits (Rs. " . number_format($diffPay, 2) . " difference)";
}

if (!empty($missingSales)) {
    $issues[] = count($missingSales) . " sales missing ledger entries";
}

if (!empty($missingPayments)) {
    $issues[] = count($missingPayments) . " payments missing ledger entries";
}

if ($balDiff > 0.01) {
    $issues[] = "Balance mismatch: Rs. " . number_format($balDiff, 2);
}

if (abs($customer->current_balance - $balance->calculated_balance) > 0.01) {
    $issues[] = "Customer table balance (Rs. " . number_format($customer->current_balance, 2) .
                ") doesn't match ledger (Rs. " . number_format($balance->calculated_balance, 2) . ")";
}

if (empty($issues)) {
    echo "✅ NO ISSUES FOUND - Ledger is consistent!\n";
} else {
    echo "❌ FOUND " . count($issues) . " ISSUE(S):\n\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". " . $issue . "\n";
    }
    echo "\n";
    echo "👉 Run fix_usam_showroom_ledger.php to fix these issues\n";
}

echo "\n";
