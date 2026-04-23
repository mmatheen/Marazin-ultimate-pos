<?php

/**
 * Safe fixer for a wrong ledger payment sign (debit vs credit).
 *
 * Default: DRY RUN (no DB writes).
 * Use: php scripts/fix-ledger-payment-sign.php --apply
 *
 * Optional args:
 *   --customer=1059
 *   --ledger=10840
 *   --reference=BLK-S1177-PAY3640
 *   --amount=29550
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

function arg(string $name, $default = null) {
    foreach ($GLOBALS['argv'] as $a) {
        if ($a === '--' . $name) return true;
        if (str_starts_with($a, '--' . $name . '=')) {
            return substr($a, strlen('--' . $name . '='));
        }
    }
    return $default;
}

$apply = (bool) arg('apply', false);
$customerId = (int) arg('customer', 1059);
$ledgerId = (int) arg('ledger', 10840);
$referenceNo = (string) arg('reference', 'BLK-S1177-PAY3640');
$amount = (float) arg('amount', 29550);
$contactType = 'customer';
$transactionType = 'payments';

echo "=== Ledger payment sign fixer ===\n";
echo "Mode: " . ($apply ? "APPLY (will update DB)\n" : "DRY RUN (no DB changes)\n");
echo "Target: ledgers.id={$ledgerId}, contact_id={$customerId}, ref={$referenceNo}, amount={$amount}\n\n";

DB::beginTransaction();
try {
    $row = DB::table('ledgers')
        ->where('id', $ledgerId)
        ->where('contact_id', $customerId)
        ->where('contact_type', $contactType)
        ->first();

    if (!$row) {
        throw new RuntimeException("Ledger row not found for id={$ledgerId}, contact_id={$customerId}");
    }

    echo "--- BEFORE ---\n";
    echo "id={$row->id}\n";
    echo "reference_no={$row->reference_no}\n";
    echo "transaction_type={$row->transaction_type}\n";
    echo "status={$row->status}\n";
    echo "debit={$row->debit}\n";
    echo "credit={$row->credit}\n";
    echo "notes=" . ($row->notes ?? '') . "\n\n";

    // Safety checks: only fix if it matches the known-bad pattern
    $errors = [];
    if ((string) $row->reference_no !== $referenceNo) $errors[] = "reference_no mismatch";
    if ((string) $row->transaction_type !== $transactionType) $errors[] = "transaction_type mismatch";
    if ((string) $row->status !== 'active') $errors[] = "status not active";
    if ((float) $row->debit !== (float) $amount) $errors[] = "debit is not expected amount";
    if ((float) $row->credit !== 0.0) $errors[] = "credit is not 0";

    if ($errors) {
        echo "❌ Safety checks failed. No update will be performed.\n";
        foreach ($errors as $e) {
            echo "- {$e}\n";
        }
        DB::rollBack();
        exit(2);
    }

    echo "✅ Safety checks passed.\n";
    echo "Planned change: debit {$row->debit} -> 0.00, credit 0.00 -> {$row->debit}\n\n";

    if ($apply) {
        $updated = DB::table('ledgers')
            ->where('id', $ledgerId)
            ->where('contact_id', $customerId)
            ->where('contact_type', $contactType)
            ->where('reference_no', $referenceNo)
            ->where('transaction_type', $transactionType)
            ->where('status', 'active')
            ->where('debit', $amount)
            ->where('credit', 0)
            ->update([
                'credit' => DB::raw('debit'),
                'debit' => 0,
                'updated_at' => now(),
            ]);

        echo "UPDATE affected rows: {$updated}\n\n";
    } else {
        echo "(dry-run) Skipping UPDATE.\n\n";
    }

    $after = DB::table('ledgers')
        ->where('id', $ledgerId)
        ->where('contact_id', $customerId)
        ->where('contact_type', $contactType)
        ->first();

    echo "--- AFTER ---\n";
    echo "id={$after->id}\n";
    echo "reference_no={$after->reference_no}\n";
    echo "transaction_type={$after->transaction_type}\n";
    echo "status={$after->status}\n";
    echo "debit={$after->debit}\n";
    echo "credit={$after->credit}\n\n";

    $summary = DB::table('ledgers')
        ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit, SUM(debit - credit) as balance_due')
        ->where('contact_id', $customerId)
        ->where('contact_type', $contactType)
        ->where('status', 'active')
        ->first();

    echo "--- CUSTOMER BALANCE (active ledgers) ---\n";
    echo "total_debit={$summary->total_debit}\n";
    echo "total_credit={$summary->total_credit}\n";
    echo "balance_due={$summary->balance_due}\n\n";

    if ($apply) {
        DB::commit();
        echo "✅ Done. Transaction committed.\n";
    } else {
        DB::rollBack();
        echo "✅ Dry run complete. Transaction rolled back (no changes).\n";
        echo "Run again with --apply to perform the update.\n";
    }
} catch (Throwable $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

