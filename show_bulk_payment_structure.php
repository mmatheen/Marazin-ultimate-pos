<?php

/**
 * Show Bulk Payment Structure - Payments vs Ledgers
 * Demonstrates how Approach 1 keeps payments grouped while ledgers are unique
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║           BULK PAYMENT STRUCTURE - Payments vs Ledgers (Approach 1)           ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$bulkRef = 'BLK-S0075';
$customerId = 1058;

// Get payments
$payments = DB::table('payments')
    ->where('reference_no', $bulkRef)
    ->where('customer_id', $customerId)
    ->orderBy('id')
    ->get();

echo "🎯 BUSINESS SCENARIO:\n";
echo "   Customer: 1058\n";
echo "   Bulk Payment: {$bulkRef}\n";
echo "   Date: 2026-02-10\n";
echo "   Total Payments: " . count($payments) . "\n";
echo "   Multiple cheques for multiple bills submitted together\n\n";

echo str_repeat('=', 120) . "\n";
echo "📋 PAYMENTS TABLE (User-facing - For Reports & Grouping)\n";
echo str_repeat('=', 120) . "\n\n";

printf("%-10s %-18s %-12s %-10s %-15s %-25s\n",
    "Payment ID", "Reference No", "Amount", "Sale ID", "Cheque No", "Purpose");
echo str_repeat('-', 120) . "\n";

$totalPaymentAmount = 0;
foreach ($payments as $payment) {
    $totalPaymentAmount += $payment->amount;

    printf("%-10s %-18s %-12s %-10s %-15s %-25s\n",
        $payment->id,
        $payment->reference_no,  // ← All same: BLK-S0075
        'Rs. ' . number_format($payment->amount, 2),
        $payment->reference_id ?: 'N/A',
        $payment->cheque_number ?: 'cash',
        'Pay bill #' . $payment->reference_id
    );
}

echo str_repeat('-', 120) . "\n";
echo "TOTAL: Rs. " . number_format($totalPaymentAmount, 2) . "\n";
echo str_repeat('=', 120) . "\n\n";

echo "✅ BENEFITS:\n";
echo "   ✓ All payments grouped under same reference: '{$bulkRef}'\n";
echo "   ✓ Easy to query: SELECT * FROM payments WHERE reference_no = '{$bulkRef}'\n";
echo "   ✓ Reports show all 18 payments together\n";
echo "   ✓ User can see complete bulk transaction\n\n";

// Get ledgers (with old format)
$ledgersOld = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('reference_no', $bulkRef)
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

// Get ledgers (with new format if exists)
$ledgersNew = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('reference_no', 'LIKE', $bulkRef . '-PAY%')
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

echo str_repeat('=', 120) . "\n";
echo "📊 LEDGERS TABLE (Accounting - For Balance Calculation)\n";
echo str_repeat('=', 120) . "\n\n";

// Show current (old) format
if (count($ledgersOld) > 0) {
    echo "❌ CURRENT FORMAT (HAS BUG - Missing entries):\n";
    echo str_repeat('-', 120) . "\n";
    printf("%-10s %-30s %-12s %-12s %-40s\n",
        "Ledger ID", "Reference No", "Debit", "Credit", "Notes");
    echo str_repeat('-', 120) . "\n";

    $totalLedgerCredit = 0;
    foreach ($ledgersOld as $ledger) {
        $totalLedgerCredit += $ledger->credit;

        printf("%-10s %-30s %-12s %-12s %-40s\n",
            $ledger->id,
            $ledger->reference_no,  // ← All same: BLK-S0075
            'Rs. ' . number_format($ledger->debit, 2),
            'Rs. ' . number_format($ledger->credit, 2),
            substr($ledger->notes, 0, 40)
        );
    }

    echo str_repeat('-', 120) . "\n";
    echo "TOTAL CREDITS: Rs. " . number_format($totalLedgerCredit, 2) . "\n";
    echo "MISSING: Rs. " . number_format($totalPaymentAmount - $totalLedgerCredit, 2) . " ❌\n";
    echo str_repeat('=', 120) . "\n\n";
}

// Show new format
echo "✅ NEW FORMAT (After Fix - All entries created):\n";
echo str_repeat('-', 120) . "\n";
printf("%-10s %-30s %-12s %-12s %-40s\n",
    "Ledger ID", "Reference No", "Debit", "Credit", "Links to Payment");
echo str_repeat('-', 120) . "\n";

$displayedNew = 0;
$totalNewCredit = 0;

// Show existing new format entries
foreach ($ledgersNew as $ledger) {
    $totalNewCredit += $ledger->credit;
    $displayedNew++;

    // Extract payment ID from reference
    preg_match('/-PAY(\d+)$/', $ledger->reference_no, $matches);
    $paymentId = $matches[1] ?? '?';

    printf("%-10s %-30s %-12s %-12s %-40s\n",
        $ledger->id,
        $ledger->reference_no,  // ← Unique: BLK-S0075-PAY638
        'Rs. ' . number_format($ledger->debit, 2),
        'Rs. ' . number_format($ledger->credit, 2),
        "→ Payment #{$paymentId}"
    );
}

// Show what WILL be created for missing entries
$missingCount = 0;
foreach ($payments as $payment) {
    $expectedRef = $bulkRef . '-PAY' . $payment->id;

    // Check if ledger exists
    $exists = false;
    foreach ($ledgersNew as $ledger) {
        if ($ledger->reference_no === $expectedRef) {
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        $missingCount++;
        $totalNewCredit += $payment->amount;

        printf("%-10s %-30s %-12s %-12s %-40s\n",
            "[NEW]",
            $expectedRef,  // ← Will be created: BLK-S0075-PAY649
            'Rs. 0.00',
            'Rs. ' . number_format($payment->amount, 2),
            "→ Payment #{$payment->id} [TO BE CREATED]"
        );
    }
}

echo str_repeat('-', 120) . "\n";
echo "TOTAL CREDITS (After fix): Rs. " . number_format($totalNewCredit, 2) . " ✅\n";
echo "Existing entries: " . count($ledgersNew) . "\n";
echo "Will create: {$missingCount}\n";
echo str_repeat('=', 120) . "\n\n";

echo "✅ BENEFITS:\n";
echo "   ✓ Each payment gets unique ledger entry: BLK-S0075-PAY638, BLK-S0075-PAY639, etc.\n";
echo "   ✓ No duplicate detection issues\n";
echo "   ✓ Accurate balance calculation\n";
echo "   ✓ Can trace: Ledger → Payment (via -PAY suffix)\n\n";

// Show how queries work
echo str_repeat('=', 120) . "\n";
echo "🔍 HOW TO QUERY DATA:\n";
echo str_repeat('=', 120) . "\n\n";

echo "1️⃣  Get all payments in bulk transaction:\n";
echo "    SELECT * FROM payments WHERE reference_no = '{$bulkRef}';\n";
echo "    → Returns: " . count($payments) . " payments ✅\n\n";

echo "2️⃣  Get all ledger entries for bulk transaction:\n";
echo "    SELECT * FROM ledgers WHERE reference_no LIKE '{$bulkRef}%';\n";
echo "    → Returns: " . (count($ledgersNew) + $missingCount) . " ledgers (after fix) ✅\n\n";

echo "3️⃣  Get specific payment's ledger entry:\n";
echo "    Payment ID = 649\n";
echo "    SELECT * FROM ledgers WHERE reference_no = '{$bulkRef}-PAY649';\n";
echo "    → Returns: 1 ledger entry ✅\n\n";

echo "4️⃣  Customer balance calculation:\n";
echo "    SELECT SUM(debit) - SUM(credit) FROM ledgers \n";
echo "    WHERE contact_id = {$customerId} AND status = 'active';\n";
echo "    → Includes ALL payment credits correctly ✅\n\n";

echo "5️⃣  Bulk payment report (for user):\n";
echo "    SELECT p.*, c.name as customer_name \n";
echo "    FROM payments p \n";
echo "    JOIN customers c ON p.customer_id = c.id \n";
echo "    WHERE p.reference_no = '{$bulkRef}';\n";
echo "    → Shows grouped transaction ✅\n\n";

echo str_repeat('=', 120) . "\n";
echo "📊 SUMMARY:\n";
echo str_repeat('=', 120) . "\n\n";

echo "PAYMENTS TABLE:\n";
echo "  Purpose: User-facing reports, grouping, filtering\n";
echo "  Format:  reference_no = '{$bulkRef}' (same for all)\n";
echo "  Count:   " . count($payments) . " payments\n\n";

echo "LEDGERS TABLE:\n";
echo "  Purpose: Accounting accuracy, balance calculation\n";
echo "  Format:  reference_no = '{$bulkRef}-PAY[ID]' (unique for each)\n";
echo "  Count:   " . (count($ledgersNew) + $missingCount) . " ledgers (after fix)\n\n";

echo "✅ Best of both worlds:\n";
echo "   → Payments grouped for reporting\n";
echo "   → Ledgers unique for accounting accuracy\n\n";

echo "🔧 To create missing " . $missingCount . " ledger entries, run:\n";
echo "   php fix_missing_bulk_payment_ledgers.php\n\n";
