<?php
/**
 * 🔧 FIX USAM SHOWROOM DOUBLE PAYMENT ISSUE
 *
 * Problem: Bulk payment BLK-S0001 includes payments for returned invoices
 * Result: Customer has -17,000 balance (advance) instead of +31,500 (due)
 *
 * Solution: Remove duplicate payment ledger entries for returned invoices
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 🔧 FIX USAM SHOWROOM DOUBLE PAYMENT ISSUE ===\n\n";

$customerId = 75;

echo "STEP 1: Identify the problem\n";
echo str_repeat("=", 80) . "\n";

// Check current balance
$currentBalance = DB::selectOne("
    SELECT
        COALESCE(SUM(debit) - SUM(credit), 0) as balance
    FROM ledgers
    WHERE contact_id = ?
        AND contact_type = 'customer'
        AND status = 'active'
", [$customerId]);

echo "Current Ledger Balance: Rs. " . number_format($currentBalance->balance, 2) . "\n";

// Check what it should be
$expectedBalance = DB::selectOne("
    SELECT
        COALESCE(SUM(final_total) - SUM(total_paid), 0) as balance
    FROM sales
    WHERE customer_id = ?
        AND transaction_type = 'invoice'
        AND status = 'final'
", [$customerId]);

echo "Expected Balance (from sales): Rs. " . number_format($expectedBalance->balance, 2) . "\n";
echo "Difference: Rs. " . number_format(abs($currentBalance->balance - $expectedBalance->balance), 2) . "\n\n";

echo "STEP 2: Find duplicate payment entries for returned invoices\n";
echo str_repeat("=", 80) . "\n";

// Find invoices that were returned but also paid via BLK-S0001
$duplicates = DB::select("
    SELECT
        s.invoice_no,
        s.final_total,
        sr.invoice_no as return_invoice,
        p.id as payment_id,
        p.amount,
        p.reference_no,
        l.id as ledger_id,
        l.credit as ledger_credit
    FROM sales s
    -- Find the return for this sale
    LEFT JOIN sales sr ON sr.invoice_no LIKE CONCAT('%', RIGHT(s.invoice_no, 3), '%')
        AND sr.transaction_type = 'sale_return'
        AND sr.customer_id = ?
    -- Find bulk payment for this sale
    LEFT JOIN payments p ON p.reference_no = 'BLK-S0001'
        AND p.customer_id = ?
        AND p.amount = s.final_total
    -- Find ledger entry for the payment
    LEFT JOIN ledgers l ON l.reference_no = 'BLK-S0001'
        AND l.contact_id = ?
        AND l.contact_type = 'customer'
        AND l.transaction_type = 'payments'
        AND l.credit = p.amount
        AND l.status = 'active'
    WHERE s.customer_id = ?
        AND s.transaction_type = 'invoice'
        AND sr.id IS NOT NULL
        AND p.id IS NOT NULL
    ORDER BY s.invoice_no
", [$customerId, $customerId, $customerId, $customerId]);

if (empty($duplicates)) {
    echo "✅ No duplicate payments found\n\n";
} else {
    echo "❌ Found " . count($duplicates) . " duplicate payment(s) for returned invoices:\n\n";
    foreach ($duplicates as $dup) {
        echo sprintf("Invoice: %s (Rs. %s) -> Return: %s\n",
            $dup->invoice_no,
            number_format($dup->final_total, 2),
            $dup->return_invoice
        );
        echo sprintf("  Payment ID: %s | Amount: Rs. %s | Ref: %s\n",
            $dup->payment_id,
            number_format($dup->amount, 2),
            $dup->reference_no
        );
        echo sprintf("  Ledger ID: %s | Credit: Rs. %s\n\n",
            $dup->ledger_id ?? 'NULL',
            number_format($dup->ledger_credit ?? 0, 2)
        );
    }
}

echo "STEP 3: Identify specific ledger entries to reverse\n";
echo str_repeat("=", 80) . "\n";

// The problematic invoices
$problematicInvoices = [
    'CSX-842' => 18500.00,  // Returned as SR-0024
    'CSX-1217' => 30000.00   // Returned as SR-0033 (though invoice shows as CSX-1217 in sales, payment for 1217)
];

$ledgersToReverse = [];

foreach ($problematicInvoices as $invoice => $amount) {
    $ledger = DB::selectOne("
        SELECT id, transaction_date, reference_no, credit, status
        FROM ledgers
        WHERE contact_id = ?
            AND contact_type = 'customer'
            AND transaction_type = 'payments'
            AND reference_no = 'BLK-S0001'
            AND credit = ?
            AND status = 'active'
        LIMIT 1
    ", [$customerId, $amount]);

    if ($ledger) {
        $ledgersToReverse[] = $ledger;
        echo "Found: Ledger ID {$ledger->id} | Amount: Rs. " . number_format($ledger->credit, 2) . " | Ref: {$ledger->reference_no}\n";
    }
}

echo "\n";

if (empty($ledgersToReverse)) {
    echo "⚠️  Could not find specific ledger entries to reverse.\n";
    echo "This might mean the issue has already been fixed or the data structure is different.\n\n";
} else {
    echo "STEP 4: EXECUTE THE FIX\n";
    echo str_repeat("=", 80) . "\n";
    echo "⚠️  WARNING: This will modify your database!\n";
    echo "Ledger entries to be marked as 'reversed':\n\n";

    foreach ($ledgersToReverse as $ledger) {
        echo "- Ledger ID: {$ledger->id} | Credit: Rs. " . number_format($ledger->credit, 2) . "\n";
    }

    echo "\n";
    echo "Do you want to proceed? Type 'YES' to continue: ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);

    if ($confirmation === 'YES') {
        DB::beginTransaction();
        try {
            foreach ($ledgersToReverse as $ledger) {
                DB::update("
                    UPDATE ledgers
                    SET status = 'reversed',
                        notes = CONCAT(COALESCE(notes, ''), ' [REVERSED: Duplicate payment for returned invoice]'),
                        updated_at = NOW()
                    WHERE id = ?
                ", [$ledger->id]);

                echo "✅ Reversed ledger ID: {$ledger->id}\n";
            }

            // Also reverse the corresponding payments
            $paymentIds = [];
            foreach ($problematicInvoices as $invoice => $amount) {
                $payment = DB::selectOne("
                    SELECT id
                    FROM payments
                    WHERE customer_id = ?
                        AND reference_no = 'BLK-S0001'
                        AND amount = ?
                        AND status = 'active'
                    LIMIT 1
                ", [$customerId, $amount]);

                if ($payment) {
                    DB::update("
                        UPDATE payments
                        SET status = 'deleted',
                            notes = CONCAT(COALESCE(notes, ''), ' [DELETED: Duplicate payment for returned invoice]'),
                            updated_at = NOW()
                        WHERE id = ?
                    ", [$payment->id]);

                    echo "✅ Deleted payment ID: {$payment->id}\n";
                }
            }

            DB::commit();
            echo "\n✅ FIX COMPLETED SUCCESSFULLY!\n\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "\n❌ ERROR: " . $e->getMessage() . "\n";
            echo "Changes have been rolled back.\n\n";
        }
    } else {
        echo "\n❌ Fix cancelled by user.\n\n";
    }
}

echo "STEP 5: Verify the fix\n";
echo str_repeat("=", 80) . "\n";

$newBalance = DB::selectOne("
    SELECT
        COALESCE(SUM(debit), 0) as total_debits,
        COALESCE(SUM(credit), 0) as total_credits,
        COALESCE(SUM(debit) - SUM(credit), 0) as balance
    FROM ledgers
    WHERE contact_id = ?
        AND contact_type = 'customer'
        AND status = 'active'
", [$customerId]);

echo "New Ledger Balance:\n";
echo "  Total Debits:  Rs. " . number_format($newBalance->total_debits, 2) . "\n";
echo "  Total Credits: Rs. " . number_format($newBalance->total_credits, 2) . "\n";
echo "  Balance:       Rs. " . number_format($newBalance->balance, 2) . "\n\n";

$expectedBalance2 = DB::selectOne("
    SELECT COALESCE(SUM(final_total) - SUM(total_paid), 0) as balance
    FROM sales
    WHERE customer_id = ?
        AND transaction_type = 'invoice'
        AND status = 'final'
", [$customerId]);

echo "Expected Balance: Rs. " . number_format($expectedBalance2->balance, 2) . "\n";

if (abs($newBalance->balance - $expectedBalance2->balance) < 0.01) {
    echo "✅ BALANCES MATCH! Issue resolved.\n";
} else {
    echo "⚠️  Still a difference of Rs. " . number_format(abs($newBalance->balance - $expectedBalance2->balance), 2) . "\n";
    echo "Further investigation may be needed.\n";
}

echo "\n";

// Update customer table
echo "Updating customer table balance...\n";
DB::update("
    UPDATE customers
    SET current_balance = ?
    WHERE id = ?
", [$newBalance->balance, $customerId]);

echo "✅ Customer table updated.\n\n";

echo "=== SUMMARY ===\n";
echo "The issue was that the bulk payment BLK-S0001 included payments for:\n";
echo "- CSX-842 (Rs. 18,500) which was already returned as SR-0024\n";
echo "- CSX-1217 (Rs. 30,000) which was already returned as SR-0033\n";
echo "\nThis caused a double credit of Rs. 48,500, making the balance incorrect.\n";
echo "The fix reverses these duplicate payment ledger entries.\n\n";
