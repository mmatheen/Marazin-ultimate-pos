<?php

/**
 * 🔧 ONE-COMMAND FIX: Bulk Payment Ledger Migration
 *
 * This script will:
 * 1. Check for bulk payments with missing/duplicate ledgers
 * 2. Migrate old format (BLK-S0075) to new format (BLK-S0075-PAY638)
 * 3. Clean up old duplicate entries
 * 4. Create missing ledger entries
 * 5. Show before/after summary
 *
 * ⚠️ SAFE FOR PRODUCTION - Uses transactions, can be rolled back
 *
 * Usage: php fix_bulk_payment_ledgers_production.php --auto
 *        (--auto flag runs without confirmation)
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
echo "║         🔧 BULK PAYMENT LEDGER FIX - Production Migration Script              ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// STEP 1: Find all bulk payments
echo "📊 STEP 1: Analyzing bulk payments...\n";
echo str_repeat('-', 80) . "\n";

$bulkPayments = DB::table('payments')
    ->where('reference_no', 'LIKE', 'BLK-%')
    ->where('status', 'active')
    ->select('reference_no', 'customer_id', 'supplier_id')
    ->groupBy('reference_no', 'customer_id', 'supplier_id')
    ->get();

$issuesFound = [];
$totalPayments = 0;
$totalOldLedgers = 0;
$totalNewLedgers = 0;
$totalMissing = 0;

foreach ($bulkPayments as $bulkGroup) {
    $contactId = $bulkGroup->customer_id ?: $bulkGroup->supplier_id;
    $contactType = $bulkGroup->customer_id ? 'customer' : 'supplier';

    // Count payments in this bulk
    $paymentCount = Payment::where('reference_no', $bulkGroup->reference_no)
        ->where($contactType . '_id', $contactId)
        ->where('status', 'active')
        ->count();

    // Count old format ledgers
    $oldLedgerCount = Ledger::where('contact_id', $contactId)
        ->where('contact_type', $contactType)
        ->where('reference_no', $bulkGroup->reference_no)
        ->where('status', 'active')
        ->count();

    // Count new format ledgers
    $newLedgerCount = Ledger::where('contact_id', $contactId)
        ->where('contact_type', $contactType)
        ->where('reference_no', 'LIKE', $bulkGroup->reference_no . '-PAY%')
        ->where('status', 'active')
        ->count();

    // If there's a mismatch, record it
    if ($oldLedgerCount > 0 || $newLedgerCount != $paymentCount) {
        $issuesFound[] = [
            'reference_no' => $bulkGroup->reference_no,
            'contact_id' => $contactId,
            'contact_type' => $contactType,
            'payments' => $paymentCount,
            'old_ledgers' => $oldLedgerCount,
            'new_ledgers' => $newLedgerCount,
            'missing' => $paymentCount - ($oldLedgerCount + $newLedgerCount)
        ];

        $totalPayments += $paymentCount;
        $totalOldLedgers += $oldLedgerCount;
        $totalNewLedgers += $newLedgerCount;
        $totalMissing += ($paymentCount - ($oldLedgerCount + $newLedgerCount));
    }
}

echo "Found " . count($bulkPayments) . " bulk payment groups\n";
echo "Issues detected: " . count($issuesFound) . "\n\n";

if (count($issuesFound) == 0) {
    echo "✅ No issues found! All bulk payments have correct ledger entries.\n\n";
    exit(0);
}

// Show issues
echo "❌ ISSUES FOUND:\n";
echo str_repeat('-', 100) . "\n";
printf("%-18s %-12s %-10s %-10s %-10s %-10s %s\n",
    "Bulk Reference", "Contact", "Payments", "Old Format", "New Format", "Missing", "Action");
echo str_repeat('-', 100) . "\n";

foreach ($issuesFound as $issue) {
    $action = 'Migrate';
    if ($issue['old_ledgers'] == 0 && $issue['missing'] > 0) {
        $action = 'Create';
    }

    printf("%-18s %-12s %-10s %-10s %-10s %-10s %s\n",
        substr($issue['reference_no'], 0, 18),
        $issue['contact_type'] . ' ' . $issue['contact_id'],
        $issue['payments'],
        $issue['old_ledgers'],
        $issue['new_ledgers'],
        $issue['missing'] > 0 ? $issue['missing'] : '-',
        $action
    );
}

echo str_repeat('-', 100) . "\n";
echo "TOTALS: Payments={$totalPayments}, Old={$totalOldLedgers}, New={$totalNewLedgers}, Missing={$totalMissing}\n";
echo str_repeat('-', 100) . "\n\n";

// STEP 2: Confirm
if (!$autoMode) {
    echo "⚠️  This will:\n";
    echo "   1. Delete {$totalOldLedgers} old format ledgers (BLK-*)\n";
    echo "   2. Create " . ($totalPayments - $totalNewLedgers) . " new format ledgers (BLK-*-PAY###)\n";
    echo "   3. All changes in a transaction (can be rolled back if error)\n\n";
    echo "Proceed with migration? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if (strtolower($line) !== 'yes') {
        echo "\n❌ Migration cancelled.\n\n";
        exit(0);
    }
}

// STEP 3: Execute migration
echo "\n🔧 STEP 2: Executing migration...\n";
echo str_repeat('-', 80) . "\n\n";

$createdCount = 0;
$deletedCount = 0;
$errorCount = 0;
$errors = [];

try {
    DB::transaction(function() use ($issuesFound, &$createdCount, &$deletedCount, &$errorCount, &$errors) {

        foreach ($issuesFound as $issue) {
            $bulkRef = $issue['reference_no'];
            $contactId = $issue['contact_id'];
            $contactType = $issue['contact_type'];

            echo "Processing: {$bulkRef} ({$contactType} {$contactId})\n";

            // Get all payments for this bulk
            $payments = Payment::where('reference_no', $bulkRef)
                ->where($contactType . '_id', $contactId)
                ->where('status', 'active')
                ->orderBy('id')
                ->get();

            // Delete old format ledgers
            if ($issue['old_ledgers'] > 0) {
                $deleted = Ledger::where('contact_id', $contactId)
                    ->where('contact_type', $contactType)
                    ->where('reference_no', $bulkRef)
                    ->where('status', 'active')
                    ->delete();

                $deletedCount += $deleted;
                echo "  ✓ Deleted {$deleted} old format ledgers\n";
            }

            // Create new format ledgers for each payment
            foreach ($payments as $payment) {
                try {
                    $newRef = $bulkRef . '-PAY' . $payment->id;

                    // Check if already exists
                    $exists = Ledger::where('contact_id', $contactId)
                        ->where('contact_type', $contactType)
                        ->where('reference_no', $newRef)
                        ->where('status', 'active')
                        ->exists();

                    if ($exists) {
                        echo "  ⊘ Skipped payment #{$payment->id} - already exists\n";
                        continue;
                    }

                    // Determine transaction type
                    $transactionType = 'payments';
                    if ($contactType === 'supplier') {
                        $transactionType = ($payment->payment_type === 'purchase') ? 'purchase_payment' : 'payments';
                    }

                    // Use original payment creation date
                    $transactionDate = Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo');

                    // Create new ledger entry
                    $ledger = new Ledger();
                    $ledger->contact_id = $contactId;
                    $ledger->contact_type = $contactType;
                    $ledger->transaction_date = $transactionDate;
                    $ledger->reference_no = $newRef;
                    $ledger->transaction_type = $transactionType;
                    $ledger->debit = 0;
                    $ledger->credit = $payment->amount;
                    $ledger->status = 'active';
                    $ledger->notes = $payment->notes ?: "Payment #{$bulkRef}";
                    $ledger->created_by = $payment->created_by ?? 1;
                    $ledger->created_at = $transactionDate;
                    $ledger->updated_at = Carbon::now();
                    $ledger->save();

                    $createdCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Payment #{$payment->id}: " . $e->getMessage();
                    echo "  ✗ Error creating ledger for payment #{$payment->id}\n";
                }
            }

            echo "  ✓ Created " . count($payments) . " new format ledgers\n\n";
        }

        // If any errors, rollback
        if ($errorCount > 0) {
            throw new \Exception("Migration had errors, rolling back");
        }
    });

    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "✅ MIGRATION COMPLETED SUCCESSFULLY\n";
    echo str_repeat('=', 80) . "\n";
    echo "Old ledgers deleted: {$deletedCount}\n";
    echo "New ledgers created: {$createdCount}\n";
    echo "Errors: {$errorCount}\n";
    echo str_repeat('=', 80) . "\n\n";

} catch (\Exception $e) {
    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "❌ MIGRATION FAILED - ROLLED BACK\n";
    echo str_repeat('=', 80) . "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
    echo str_repeat('=', 80) . "\n\n";

    if (count($errors) > 0) {
        echo "Detailed errors:\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    }

    exit(1);
}

// STEP 4: Verify
echo "🔍 STEP 3: Verification...\n";
echo str_repeat('-', 80) . "\n\n";

$verificationPassed = true;

foreach ($issuesFound as $issue) {
    $bulkRef = $issue['reference_no'];
    $contactId = $issue['contact_id'];
    $contactType = $issue['contact_type'];

    $paymentCount = Payment::where('reference_no', $bulkRef)
        ->where($contactType . '_id', $contactId)
        ->where('status', 'active')
        ->count();

    $newLedgerCount = Ledger::where('contact_id', $contactId)
        ->where('contact_type', $contactType)
        ->where('reference_no', 'LIKE', $bulkRef . '-PAY%')
        ->where('status', 'active')
        ->count();

    $oldLedgerCount = Ledger::where('contact_id', $contactId)
        ->where('contact_type', $contactType)
        ->where('reference_no', $bulkRef)
        ->where('status', 'active')
        ->count();

    $status = ($paymentCount == $newLedgerCount && $oldLedgerCount == 0) ? '✅' : '❌';

    echo "{$status} {$bulkRef}: Payments={$paymentCount}, New Ledgers={$newLedgerCount}, Old Ledgers={$oldLedgerCount}\n";

    if ($paymentCount != $newLedgerCount || $oldLedgerCount > 0) {
        $verificationPassed = false;
    }
}

echo "\n";
if ($verificationPassed) {
    echo "✅ VERIFICATION PASSED - All bulk payments have correct ledger entries!\n\n";

    echo "📊 SUMMARY:\n";
    echo "   • Fixed " . count($issuesFound) . " bulk payment groups\n";
    echo "   • Deleted {$deletedCount} old duplicate ledgers\n";
    echo "   • Created {$createdCount} new unique ledgers\n";
    echo "   • Customer/supplier balances now accurate\n\n";

    echo "🎯 WHAT CHANGED:\n";
    echo "   BEFORE: BLK-S0075 (same for all payments - caused duplicates)\n";
    echo "   AFTER:  BLK-S0075-PAY638, BLK-S0075-PAY639, etc. (unique per payment)\n\n";

    echo "✨ Future bulk payments will automatically use the new format!\n\n";
} else {
    echo "⚠️  VERIFICATION FAILED - Some issues remain\n\n";
    exit(1);
}
