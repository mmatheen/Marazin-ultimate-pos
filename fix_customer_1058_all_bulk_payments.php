<?php

/**
 * Fix Customer 1058 Bulk Payments - BLK-S0075 and BLK-S0076
 *
 * Fixes both bulk payments for customer 1058:
 * - BLK-S0075 (18 cheque payments)
 * - BLK-S0076 (1 discount/return credit payment)
 *
 * Usage: php fix_customer_1058_all_bulk_payments.php --auto
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Ledger;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$autoMode = in_array('--auto', $argv);

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║           🔧 FIX CUSTOMER 1058 ALL BULK PAYMENTS LEDGER ISSUES                ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$customerId = 1058;
$bulkRefs = ['BLK-S0075', 'BLK-S0076'];

$issuesFound = [];
$totalPayments = 0;
$totalOldLedgers = 0;
$totalNewLedgers = 0;

echo "📊 Analyzing customer 1058 bulk payments...\n";
echo str_repeat('-', 100) . "\n\n";

foreach ($bulkRefs as $bulkRef) {
    // Get payments
    $payments = Payment::where('reference_no', $bulkRef)
        ->where('customer_id', $customerId)
        ->where('status', 'active')
        ->get();

    if (count($payments) == 0) {
        continue;
    }

    // Get old format ledgers
    $oldLedgers = Ledger::where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('reference_no', $bulkRef)
        ->where('status', 'active')
        ->get();

    // Get new format ledgers
    $newLedgers = Ledger::where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('reference_no', 'LIKE', $bulkRef . '-PAY%')
        ->where('status', 'active')
        ->get();

    $paymentCount = count($payments);
    $oldCount = count($oldLedgers);
    $newCount = count($newLedgers);
    $missing = $paymentCount - ($oldCount + $newCount);

    // Check if needs fixing
    if ($oldCount > 0 || $newCount != $paymentCount) {
        $issuesFound[] = [
            'reference' => $bulkRef,
            'payments' => $paymentCount,
            'old_ledgers' => $oldCount,
            'new_ledgers' => $newCount,
            'missing' => $missing,
            'payment_objects' => $payments,
            'old_ledger_objects' => $oldLedgers
        ];

        $totalPayments += $paymentCount;
        $totalOldLedgers += $oldCount;
        $totalNewLedgers += $newCount;
    }

    // Show status
    $status = ($oldCount > 0 || $newCount != $paymentCount) ? '❌' : '✅';
    echo "{$status} {$bulkRef}: Payments={$paymentCount}, Old Ledgers={$oldCount}, New Ledgers={$newCount}\n";
}

echo "\n";

if (count($issuesFound) == 0) {
    echo "✅ No issues found! All bulk payments for customer 1058 are already fixed.\n\n";
    exit(0);
}

// Show detailed issues
echo "📋 Issues Found:\n";
echo str_repeat('-', 100) . "\n";

foreach ($issuesFound as $issue) {
    echo "\n{$issue['reference']}:\n";
    printf("%-10s %-15s %-12s %-15s %-15s\n", "Payment ID", "Amount", "Method", "Sale ID", "Type");
    echo str_repeat('-', 100) . "\n";

    foreach ($issue['payment_objects'] as $payment) {
        printf("%-10s %-15s %-12s %-15s %-15s\n",
            $payment->id,
            'Rs. ' . number_format($payment->amount, 2),
            $payment->payment_method ?: 'N/A',
            $payment->reference_id ?: 'N/A',
            $payment->payment_type ?: 'N/A'
        );
    }
    echo str_repeat('-', 100) . "\n";
}

echo "\n";
echo "TOTALS: Payments={$totalPayments}, Old Ledgers={$totalOldLedgers}, New Ledgers={$totalNewLedgers}\n\n";

if (!$autoMode) {
    echo "⚠️  This will:\n";
    echo "   1. Delete {$totalOldLedgers} old format ledgers\n";
    echo "   2. Create " . ($totalPayments - $totalNewLedgers) . " new format ledgers with unique references\n";
    echo "   3. Fix customer 1058 balance calculation\n\n";
    echo "Proceed? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if (strtolower($line) !== 'yes') {
        echo "\n❌ Operation cancelled.\n\n";
        exit(0);
    }
}

echo "\n🔧 Executing fix...\n";
echo str_repeat('-', 80) . "\n\n";

$createdCount = 0;
$deletedCount = 0;

try {
    DB::transaction(function() use ($issuesFound, $customerId, &$createdCount, &$deletedCount) {

        foreach ($issuesFound as $issue) {
            $bulkRef = $issue['reference'];
            $payments = $issue['payment_objects'];

            echo "Processing: {$bulkRef}\n";

            // Step 1: Delete old format ledgers
            if ($issue['old_ledgers'] > 0) {
                $deleted = Ledger::where('contact_id', $customerId)
                    ->where('contact_type', 'customer')
                    ->where('reference_no', $bulkRef)
                    ->where('status', 'active')
                    ->delete();

                $deletedCount += $deleted;
                echo "  ✓ Deleted {$deleted} old format ledgers\n";
            }

            // Step 2: Create new format ledgers for each payment
            foreach ($payments as $payment) {
                $newRef = $bulkRef . '-PAY' . $payment->id;

                // Check if already exists
                $exists = Ledger::where('contact_id', $customerId)
                    ->where('contact_type', 'customer')
                    ->where('reference_no', $newRef)
                    ->where('status', 'active')
                    ->exists();

                if ($exists) {
                    echo "  ⊘ Payment #{$payment->id} - ledger already exists\n";
                    continue;
                }

                // Use original payment creation date
                $transactionDate = Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo');

                // Create new ledger entry
                $ledger = new Ledger();
                $ledger->contact_id = $customerId;
                $ledger->contact_type = 'customer';
                $ledger->transaction_date = $transactionDate;
                $ledger->reference_no = $newRef;
                $ledger->transaction_type = 'payments';
                $ledger->debit = 0;
                $ledger->credit = $payment->amount;
                $ledger->status = 'active';
                $ledger->notes = $payment->notes ?: "Payment #{$bulkRef}";
                $ledger->created_by = $payment->created_by ?? 1;
                $ledger->created_at = $transactionDate;
                $ledger->updated_at = Carbon::now();
                $ledger->save();

                echo "  ✓ Payment #{$payment->id} → {$newRef} (Rs. " . number_format($payment->amount, 2) . ") [{$payment->payment_method}]\n";
                $createdCount++;
            }

            echo "\n";
        }
    });

    echo str_repeat('=', 80) . "\n";
    echo "✅ FIX COMPLETED SUCCESSFULLY\n";
    echo str_repeat('=', 80) . "\n";
    echo "Old ledgers deleted: {$deletedCount}\n";
    echo "New ledgers created: {$createdCount}\n";
    echo str_repeat('=', 80) . "\n\n";

} catch (\Exception $e) {
    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "❌ FIX FAILED - ROLLED BACK\n";
    echo str_repeat('=', 80) . "\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Verification
echo "🔍 Verification:\n";
echo str_repeat('-', 80) . "\n\n";

$allGood = true;

foreach ($bulkRefs as $bulkRef) {
    $paymentCount = Payment::where('reference_no', $bulkRef)
        ->where('customer_id', $customerId)
        ->where('status', 'active')
        ->count();

    if ($paymentCount == 0) continue;

    $newLedgerCount = Ledger::where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('reference_no', 'LIKE', $bulkRef . '-PAY%')
        ->where('status', 'active')
        ->count();

    $oldLedgerCount = Ledger::where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('reference_no', $bulkRef)
        ->where('status', 'active')
        ->count();

    $status = ($paymentCount == $newLedgerCount && $oldLedgerCount == 0) ? '✅' : '❌';
    echo "{$status} {$bulkRef}: Payments={$paymentCount}, New Ledgers={$newLedgerCount}, Old Ledgers={$oldLedgerCount}\n";

    if ($paymentCount != $newLedgerCount || $oldLedgerCount > 0) {
        $allGood = false;
    }
}

// Calculate total credits
$totalCredits = Ledger::where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('reference_no', 'LIKE', 'BLK-S%')
    ->where('status', 'active')
    ->sum('credit');

echo "\nTotal bulk payment credits: Rs. " . number_format($totalCredits, 2) . "\n\n";

if ($allGood) {
    echo "✅ VERIFICATION PASSED!\n\n";
    echo "📊 Result:\n";
    echo "   • Customer 1058 all bulk payments fixed\n";
    echo "   • BLK-S0075 (18 cheque payments) ✅\n";
    echo "   • BLK-S0076 (1 discount payment) ✅\n";
    echo "   • All payments have unique ledger entries\n";
    echo "   • Customer balance now accurate\n\n";
    echo "✨ Done!\n\n";
} else {
    echo "⚠️  VERIFICATION WARNING - Some issues remain\n\n";
}
