<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ledger;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "\n=== FIXING DELETED PAYMENT #1310 LEDGER ===\n";

DB::transaction(function () {

    // Payment #1310 was deleted but ledger entry #1267 is still active
    $payment = Payment::withoutGlobalScopes()->find(1310);

    if (!$payment) {
        echo "❌ Payment #1310 not found\n";
        return;
    }

    echo "Payment #1310: {$payment->reference_no}, Amount: Rs.{$payment->amount}, Status: {$payment->status}\n";

    // Find the active ledger entry for this payment
    $activeLedger = Ledger::where('reference_no', $payment->reference_no)
        ->where('contact_id', $payment->customer_id)
        ->where('contact_type', 'customer')
        ->where('transaction_type', 'payments')
        ->where('status', 'active')
        ->first();

    if ($activeLedger) {
        echo "\nFound active ledger entry #
{$activeLedger->id} that should be reversed\n";

        // Mark original entry as reversed
        $activeLedger->update([
            'status' => 'reversed',
            'notes' => ($activeLedger->notes ?? '') . ' [REVERSED: Payment deleted on ' . now()->format('Y-m-d H:i:s') . ']'
        ]);
        echo "✅ Marked ledger #{$activeLedger->id} as reversed\n";

        // Create reversal entry
        $reversalEntry = Ledger::create([
            'contact_id' => $payment->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => now(),
            'reference_no' => $payment->reference_no . '-DEL-' . time(),
            'transaction_type' => 'payments',
            'debit' => $payment->amount, // DEBIT to reverse the CREDIT payment
            'credit' => 0,
            'status' => 'reversed',
            'notes' => 'REVERSAL: Payment Deleted - Cancel amount Rs.' . number_format($payment->amount, 2) . ' | Reason: this payment dont want',
            'created_by' => 2
        ]);

        echo "✅ Created reversal entry #
{$reversalEntry->id}\n";

    } else {
        echo "❌ No active ledger entry found for this payment\n";
    }

    echo "\n--- Updated Ledger for Customer 83 ---\n";
    $ledgers = Ledger::where('contact_id', 83)
        ->where('contact_type', 'customer')
        ->where('reference_no', 'LIKE', 'BLK-S0001%')
        ->orderBy('id')
        ->get();

    $balance = 0;
    foreach ($ledgers as $ledger) {
        if ($ledger->status === 'active') {
            $balance += $ledger->debit - $ledger->credit;
        }
        echo "#{$ledger->id}: {$ledger->reference_no} | D:{$ledger->debit} C:{$ledger->credit} | Status: {$ledger->status}\n";
    }

    echo "\nActive Balance: Rs.{$balance}\n";
});

echo "\n=== FIX COMPLETED ===\n";
