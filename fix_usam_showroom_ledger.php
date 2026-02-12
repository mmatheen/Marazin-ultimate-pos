<?php
/**
 * 🔧 FIX USAM SHOWROOM LEDGER MISMATCH
 * Customer ID: 75 | Mobile: 0777491925
 *
 * ⚠️ RUN THIS AFTER REVIEWING THE DIAGNOSTIC OUTPUT!
 */

echo "=== 🔧 USAM SHOWROOM LEDGER FIX ===\n\n";

$customerId = 75;

echo "-- STEP 1: Check current customer balance in table\n";
echo "SELECT id, first_name, current_balance FROM customers WHERE id = {$customerId};\n\n";

echo "-- STEP 2: Calculate correct balance from ledger\n";
echo "SELECT
    COALESCE(SUM(debit), 0) as total_debits,
    COALESCE(SUM(credit), 0) as total_credits,
    COALESCE(SUM(debit) - SUM(credit), 0) as correct_balance
FROM ledgers
WHERE contact_id = {$customerId}
    AND contact_type = 'customer'
    AND status = 'active';\n\n";

echo "-- STEP 3: Update customer table with correct balance\n";
echo "-- NOTE: The system uses ledger for calculations, but let's sync the table\n";
echo "UPDATE customers
SET current_balance = (
    SELECT COALESCE(SUM(debit) - SUM(credit), 0)
    FROM ledgers
    WHERE contact_id = {$customerId}
        AND contact_type = 'customer'
        AND status = 'active'
)
WHERE id = {$customerId};\n\n";

echo "=== 🔍 SPECIFIC ISSUES TO FIX ===\n\n";

echo "-- ISSUE 1: Check if CSX-2079 has a ledger entry\n";
echo "SELECT
    s.id, s.invoice_no, s.final_total, s.payment_status,
    l.id as ledger_id, l.debit
FROM sales s
LEFT JOIN ledgers l ON l.reference_id = s.id
    AND l.contact_type = 'customer'
    AND l.transaction_type = 'sale'
WHERE s.invoice_no = 'CSX-2079'
    AND s.customer_id = {$customerId};\n\n";

echo "-- If above query shows NULL ledger_id, CREATE THE MISSING LEDGER ENTRY:\n";
echo "INSERT INTO ledgers (
    contact_id,
    contact_type,
    transaction_date,
    transaction_type,
    reference_no,
    reference_id,
    debit,
    credit,
    status,
    notes,
    created_by,
    created_at,
    updated_at
)
SELECT
    {$customerId},
    'customer',
    s.sales_date,
    'sale',
    s.invoice_no,
    s.id,
    s.final_total,
    0.00,
    'active',
    CONCAT('Sale invoice #', s.invoice_no),
    s.user_id,
    s.created_at,
    NOW()
FROM sales s
WHERE s.invoice_no = 'CSX-2079'
    AND s.customer_id = {$customerId}
    AND NOT EXISTS (
        SELECT 1 FROM ledgers l
        WHERE l.reference_id = s.id
            AND l.contact_type = 'customer'
            AND l.transaction_type = 'sale'
    );\n\n";

echo "-- ISSUE 2: Check if all payments have ledger entries\n";
echo "SELECT
    p.id, p.payment_date, p.amount, p.reference_no,
    l.id as ledger_id, l.credit
FROM payments p
LEFT JOIN ledgers l ON l.reference_id = p.id
    AND l.contact_type = 'customer'
    AND l.transaction_type = 'payments'
WHERE p.customer_id = {$customerId}
    AND p.status = 'active'
    AND l.id IS NULL;\n\n";

echo "-- If above query returns rows, CREATE MISSING PAYMENT LEDGER ENTRIES:\n";
echo "INSERT INTO ledgers (
    contact_id,
    contact_type,
    transaction_date,
    transaction_type,
    reference_no,
    reference_id,
    debit,
    credit,
    status,
    notes,
    created_by,
    created_at,
    updated_at
)
SELECT
    {$customerId},
    'customer',
    p.payment_date,
    'payments',
    p.reference_no,
    p.id,
    0.00,
    p.amount,
    'active',
    CONCAT('Payment ', p.reference_no, COALESCE(CONCAT(' - ', p.notes), '')),
    p.created_by,
    p.created_at,
    NOW()
FROM payments p
WHERE p.customer_id = {$customerId}
    AND p.status = 'active'
    AND NOT EXISTS (
        SELECT 1 FROM ledgers l
        WHERE l.reference_id = p.id
            AND l.contact_type = 'customer'
            AND l.transaction_type = 'payments'
    );\n\n";

echo "-- ISSUE 3: Check for sale_return entries\n";
echo "SELECT
    s.id, s.invoice_no, s.final_total, s.transaction_type,
    l.id as ledger_id, l.credit as ledger_credit
FROM sales s
LEFT JOIN ledgers l ON l.reference_id = s.id
    AND l.contact_type = 'customer'
    AND l.transaction_type = 'sale_return'
WHERE s.customer_id = {$customerId}
    AND s.transaction_type = 'sale_return'
    AND s.status = 'final';\n\n";

echo "-- If returns are missing from ledger, CREATE THEM:\n";
echo "INSERT INTO ledgers (
    contact_id,
    contact_type,
    transaction_date,
    transaction_type,
    reference_no,
    reference_id,
    debit,
    credit,
    status,
    notes,
    created_by,
    created_at,
    updated_at
)
SELECT
    {$customerId},
    'customer',
    s.sales_date,
    'sale_return',
    s.invoice_no,
    s.id,
    0.00,
    s.final_total,
    'active',
    CONCAT('Sale return #', s.invoice_no),
    s.user_id,
    s.created_at,
    NOW()
FROM sales s
WHERE s.customer_id = {$customerId}
    AND s.transaction_type = 'sale_return'
    AND s.status = 'final'
    AND NOT EXISTS (
        SELECT 1 FROM ledgers l
        WHERE l.reference_id = s.id
            AND l.contact_type = 'customer'
            AND l.transaction_type = 'sale_return'
    );\n\n";

echo "-- FINAL STEP: Verify the fix\n";
echo "SELECT
    COALESCE(SUM(debit), 0) as total_debits,
    COALESCE(SUM(credit), 0) as total_credits,
    COALESCE(SUM(debit) - SUM(credit), 0) as final_balance
FROM ledgers
WHERE contact_id = {$customerId}
    AND contact_type = 'customer'
    AND status = 'active';\n\n";

echo "-- Update customer table one more time\n";
echo "UPDATE customers
SET current_balance = (
    SELECT COALESCE(SUM(debit) - SUM(credit), 0)
    FROM ledgers
    WHERE contact_id = {$customerId}
        AND contact_type = 'customer'
        AND status = 'active'
)
WHERE id = {$customerId};\n\n";

echo "=== ✅ VERIFICATION QUERIES ===\n\n";

echo "-- Check sales totals\n";
echo "SELECT
    COUNT(*) as sale_count,
    SUM(final_total) as total_sales,
    SUM(total_paid) as total_paid,
    SUM(total_due) as total_due
FROM sales
WHERE customer_id = {$customerId}
    AND transaction_type = 'invoice'
    AND status = 'final';\n\n";

echo "-- Check payment totals\n";
echo "SELECT
    COUNT(*) as payment_count,
    SUM(amount) as total_payments
FROM payments
WHERE customer_id = {$customerId}
    AND status = 'active';\n\n";

echo "-- Check return totals\n";
echo "SELECT
    COUNT(*) as return_count,
    SUM(final_total) as total_returns
FROM sales
WHERE customer_id = {$customerId}
    AND transaction_type = 'sale_return'
    AND status = 'final';\n\n";

echo "-- Final balance check (should all match)\n";
echo "SELECT
    'From Ledger' as source,
    COALESCE(SUM(debit) - SUM(credit), 0) as balance
FROM ledgers
WHERE contact_id = {$customerId}
    AND contact_type = 'customer'
    AND status = 'active'
UNION ALL
SELECT
    'From Customer Table' as source,
    current_balance as balance
FROM customers
WHERE id = {$customerId}
UNION ALL
SELECT
    'Calculated from Sales/Payments' as source,
    (
        (SELECT COALESCE(SUM(final_total), 0) FROM sales
         WHERE customer_id = {$customerId} AND transaction_type = 'invoice' AND status = 'final')
        -
        (SELECT COALESCE(SUM(amount), 0) FROM payments
         WHERE customer_id = {$customerId} AND status = 'active')
        -
        (SELECT COALESCE(SUM(final_total), 0) FROM sales
         WHERE customer_id = {$customerId} AND transaction_type = 'sale_return' AND status = 'final')
    ) as balance;\n\n";

echo "=== 🎯 SUMMARY ===\n";
echo "1. Run the diagnostic script first\n";
echo "2. Identify missing ledger entries\n";
echo "3. Run the INSERT statements to create missing entries\n";
echo "4. Run the UPDATE statement to sync customer table\n";
echo "5. Verify all three balance calculations match\n";
echo "\n✅ All balances should be equal after the fix!\n\n";
