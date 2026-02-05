<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 100) . "\n";
echo "FIX SUPPLIER 7 (WESCO GAS) LEDGER ISSUES\n";
echo str_repeat("=", 100) . "\n\n";

$supplierId = 7;

try {
    DB::beginTransaction();

    echo "ISSUE 1: Duplicate Purchase Ledger Entry for PUR066\n";
    echo str_repeat("-", 100) . "\n";

    // Get purchase 90 (PUR066)
    $purchase66 = DB::table('purchases')->where('id', 90)->first();
    echo "Purchase PUR066 (ID: 90) actual amount: " . number_format($purchase66->final_total, 2) . "\n\n";

    // Get ledger entries for PUR066
    $ledgers66 = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('reference_no', 'PUR066')
        ->where('transaction_type', 'purchase')
        ->where('status', 'active')
        ->orderBy('id')
        ->get();

    echo "Found " . $ledgers66->count() . " ledger entries for PUR066:\n";
    foreach ($ledgers66 as $ledger) {
        echo "  Ledger ID: {$ledger->id} | Credit: " . number_format($ledger->credit, 2) .
             " | Date: {$ledger->transaction_date}\n";
    }

    if ($ledgers66->count() > 1) {
        echo "\n⚠ DUPLICATE DETECTED! Marking older entry as reversed...\n";

        // Keep the one with correct amount (200700), mark 160560 as reversed
        $incorrectLedger = $ledgers66->where('credit', 160560)->first();
        if ($incorrectLedger) {
            DB::table('ledgers')
                ->where('id', $incorrectLedger->id)
                ->update([
                    'status' => 'reversed',
                    'notes' => ($incorrectLedger->notes ?? '') . ' [REVERSED: Duplicate entry - actual purchase is 200,700]',
                    'updated_at' => now()
                ]);
            echo "  ✓ Marked ledger #{$incorrectLedger->id} (160,560) as reversed\n";
        }
    }

    echo "\n" . str_repeat("=", 100) . "\n";
    echo "ISSUE 2: Missing Payment Ledger Entry for Payment #849\n";
    echo str_repeat("-", 100) . "\n";

    $payment849 = DB::table('payments')->where('id', 849)->first();

    if ($payment849 && $payment849->status == 'active') {
        echo "Payment #849 Details:\n";
        echo "  Date: {$payment849->payment_date}\n";
        echo "  Reference: {$payment849->reference_no}\n";
        echo "  Amount: " . number_format($payment849->amount, 2) . "\n";
        echo "  Method: {$payment849->payment_method}\n\n";

        // Check if ledger exists
        $ledgerExists = DB::table('ledgers')
            ->where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'payments')
            ->where('reference_no', $payment849->reference_no)
            ->where('debit', $payment849->amount)
            ->where('status', 'active')
            ->exists();

        if (!$ledgerExists) {
            echo "⚠ Missing ledger entry! Creating...\n";

            $ledgerData = [
                'contact_id' => $supplierId,
                'transaction_date' => $payment849->payment_date,
                'reference_no' => $payment849->reference_no,
                'transaction_type' => 'payments',
                'debit' => $payment849->amount,
                'credit' => 0,
                'status' => 'active',
                'contact_type' => 'supplier',
                'notes' => 'Missing ledger entry restored for payment #849',
                'created_by' => 2,
                'created_at' => $payment849->created_at,
                'updated_at' => now()
            ];

            $newLedgerId = DB::table('ledgers')->insertGetId($ledgerData);
            echo "  ✓ Created ledger entry #{$newLedgerId}\n";
        } else {
            echo "  ✓ Ledger entry already exists\n";
        }
    }

    echo "\n" . str_repeat("=", 100) . "\n";
    echo "VERIFICATION\n";
    echo str_repeat("=", 100) . "\n\n";

    // Recalculate balance
    $activeLedgers = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('status', 'active')
        ->get();

    $ledgerBalance = $activeLedgers->sum('credit') - $activeLedgers->sum('debit');

    $purchases = DB::table('purchases')
        ->where('supplier_id', $supplierId)
        ->get();

    $purchaseBalance = $purchases->sum('total_due');

    echo "After Fix:\n";
    echo "  Ledger Balance: " . number_format($ledgerBalance, 2) . "\n";
    echo "  Purchase Balance: " . number_format($purchaseBalance, 2) . "\n";
    echo "  Difference: " . number_format(abs($ledgerBalance - $purchaseBalance), 2) . "\n\n";

    if (abs($ledgerBalance - $purchaseBalance) < 0.01) {
        echo "✓✓✓ SUCCESS! Ledger is now balanced! ✓✓✓\n";
        DB::commit();
        echo "✓ Changes committed to database\n";
    } else {
        echo "⚠ Still has mismatch. Review needed.\n";
        echo "\nCommit changes anyway? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        if (strtolower($line) === 'yes' || strtolower($line) === 'y') {
            DB::commit();
            echo "✓ Changes committed\n";
        } else {
            DB::rollBack();
            echo "✗ Changes rolled back\n";
        }
    }

    echo "\n" . str_repeat("=", 100) . "\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}
