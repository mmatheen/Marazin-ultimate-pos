<?php
/**
 * 🔍 USAM SHOWROOM LEDGER DIAGNOSTIC SCRIPT
 * Customer ID: 75 | Mobile: 0777491925
 */

echo "=== 🔍 USAM SHOWROOM LEDGER DIAGNOSTIC ===\n\n";

// Customer ID
$customerId = 75;

echo "📋 SQL QUERIES TO RUN:\n\n";

// Query 1: Check all ledger entries
echo "-- 1️⃣ ALL LEDGER ENTRIES FOR CUSTOMER 75\n";
echo "SELECT
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
WHERE contact_id = {$customerId}
    AND contact_type = 'customer'
ORDER BY transaction_date ASC, id ASC;

";

// Query 2: Calculate balance from ledger
echo "-- 2️⃣ CALCULATE BALANCE FROM LEDGER\n";
echo "SELECT
    COALESCE(SUM(debit), 0) as total_debits,
    COALESCE(SUM(credit), 0) as total_credits,
    COALESCE(SUM(debit) - SUM(credit), 0) as calculated_balance
FROM ledgers
WHERE contact_id = {$customerId}
    AND contact_type = 'customer'
    AND status = 'active';

";

// Query 3: Check all sales
echo "-- 3️⃣ ALL SALES FOR CUSTOMER 75\n";
echo "SELECT
    id,
    invoice_no,
    sales_date,
    final_total,
    total_paid,
    total_due,
    payment_status,
    transaction_type
FROM sales
WHERE customer_id = {$customerId}
ORDER BY sales_date ASC;

";

// Query 4: Check all payments
echo "-- 4️⃣ ALL PAYMENTS FOR CUSTOMER 75\n";
echo "SELECT
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
WHERE customer_id = {$customerId}
ORDER BY payment_date ASC;

";

// Query 5: Check customer current_balance
echo "-- 5️⃣ CUSTOMER TABLE BALANCE\n";
echo "SELECT
    id,
    CONCAT(first_name, ' ', COALESCE(last_name, '')) as customer_name,
    mobile_no,
    opening_balance,
    current_balance,
    credit_limit
FROM customers
WHERE id = {$customerId};

";

// Query 6: Cross-check sales vs ledger debits
echo "-- 6️⃣ SALES VS LEDGER DEBITS (Should match)\n";
echo "SELECT
    'Sales Total' as source,
    COALESCE(SUM(final_total), 0) as amount
FROM sales
WHERE customer_id = {$customerId}
    AND transaction_type = 'invoice'
    AND status = 'final'
UNION ALL
SELECT
    'Ledger Debits' as source,
    COALESCE(SUM(debit), 0) as amount
FROM ledgers
WHERE contact_id = {$customerId}
    AND contact_type = 'customer'
    AND transaction_type = 'sale'
    AND status = 'active';

";

// Query 7: Check payments vs ledger credits
echo "-- 7️⃣ PAYMENTS VS LEDGER CREDITS (Should match)\n";
echo "SELECT
    'Payments Total' as source,
    COALESCE(SUM(amount), 0) as amount
FROM payments
WHERE customer_id = {$customerId}
    AND status = 'active'
UNION ALL
SELECT
    'Ledger Credits' as source,
    COALESCE(SUM(credit), 0) as amount
FROM ledgers
WHERE contact_id = {$customerId}
    AND contact_type = 'customer'
    AND transaction_type = 'payments'
    AND status = 'active';

";

// Query 8: Find missing ledger entries
echo "-- 8️⃣ FIND SALES WITHOUT LEDGER ENTRIES\n";
echo "SELECT
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
WHERE s.customer_id = {$customerId}
    AND s.transaction_type = 'invoice'
    AND s.status = 'final'
    AND l.id IS NULL;

";

// Query 9: Find payments without ledger entries
echo "-- 9️⃣ FIND PAYMENTS WITHOUT LEDGER ENTRIES\n";
echo "SELECT
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
WHERE p.customer_id = {$customerId}
    AND p.status = 'active'
    AND l.id IS NULL;

";

// Query 10: Summary
echo "-- 🔟 EXPECTED VS ACTUAL BALANCE\n";
echo "SELECT
    'Expected Balance' as description,
    (
        SELECT COALESCE(SUM(final_total), 0) - COALESCE(SUM(total_paid), 0)
        FROM sales
        WHERE customer_id = {$customerId}
            AND transaction_type = 'invoice'
            AND status = 'final'
    ) as amount
UNION ALL
SELECT
    'Actual Ledger Balance' as description,
    COALESCE(SUM(debit) - SUM(credit), 0) as amount
FROM ledgers
WHERE contact_id = {$customerId}
    AND contact_type = 'customer'
    AND status = 'active';

";

echo "\n=== 🎯 WHAT TO LOOK FOR ===\n";
echo "1. Check if Query #8 returns any rows (sales without ledger entries)\n";
echo "2. Check if Query #9 returns any rows (payments without ledger entries)\n";
echo "3. Compare Query #6 - Sales total should equal ledger debits\n";
echo "4. Compare Query #7 - Payments total should equal ledger credits\n";
echo "5. Query #10 should show the same balance for both rows\n";
echo "\n🚨 If there are mismatches, the ledger entries are incomplete!\n\n";
