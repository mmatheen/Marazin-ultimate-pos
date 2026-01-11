<?php

/**
 * Fix payment notes that contain "return" keyword
 * This script updates existing payment records to use safe notes
 * Run: php fix-payment-notes.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

echo "🔍 Finding payments with 'return' in notes...\n\n";

// Find all payments with "return" in notes that are advance_adjustment type
$payments = Payment::where('payment_method', 'advance_adjustment')
    ->where('payment_type', '!=', 'return_payment')
    ->where(function($query) {
        $query->where('notes', 'like', '%Return credit applied to sales%')
              ->orWhere('notes', 'like', '%Return credit applied to sale%');
    })
    ->get();

echo "Found " . $payments->count() . " payments to fix\n\n";

if ($payments->count() == 0) {
    echo "✅ No payments need fixing!\n";
    exit(0);
}

$updated = 0;
foreach ($payments as $payment) {
    echo "Payment ID {$payment->id}:\n";
    echo "  Old Note: {$payment->notes}\n";

    // Update the note
    if (strpos($payment->notes, 'Return credit applied to sales:') !== false) {
        $payment->notes = str_replace(
            'Return credit applied to sales:',
            'Credit adjustment from sales invoice:',
            $payment->notes
        );
    } elseif (strpos($payment->notes, 'Return credit applied to sale:') !== false) {
        $payment->notes = str_replace(
            'Return credit applied to sale:',
            'Advance adjustment applied to sale:',
            $payment->notes
        );
    }

    $payment->save();
    echo "  New Note: {$payment->notes}\n";
    echo "  ✅ Updated\n\n";
    $updated++;
}

echo "\n✅ Updated {$updated} payment records\n";
echo "🔄 Now updating ledger entries...\n\n";

// Fix ledger entries
use App\Models\Ledger;

$ledgers = Ledger::where('transaction_type', 'payments')
    ->where('contact_type', 'customer')
    ->where(function($query) {
        $query->where('notes', 'like', '%Return credit applied%')
              ->orWhere('notes', 'like', '%return credit applied%');
    })
    ->get();

echo "Found " . $ledgers->count() . " ledger entries to fix\n\n";

$ledgerUpdated = 0;
foreach ($ledgers as $ledger) {
    echo "Ledger ID {$ledger->id}:\n";
    echo "  Reference: {$ledger->reference_no}\n";
    echo "  Old Note: {$ledger->notes}\n";
    echo "  Old Debit: {$ledger->debit}, Credit: {$ledger->credit}\n";

    // Update the note
    if (strpos($ledger->notes, 'Return credit applied to sales:') !== false) {
        $ledger->notes = str_replace(
            'Return credit applied to sales:',
            'Credit adjustment from sales invoice:',
            $ledger->notes
        );
    } elseif (strpos($ledger->notes, 'Return credit applied to sale:') !== false) {
        $ledger->notes = str_replace(
            'Return credit applied to sale:',
            'Advance adjustment applied to sale:',
            $ledger->notes
        );
    } elseif (strpos(strtolower($ledger->notes), 'return credit applied') !== false) {
        $ledger->notes = str_replace(
            ['Return credit applied', 'return credit applied'],
            'Credit adjustment applied',
            $ledger->notes
        );
    }

    // Fix debit/credit if wrong (debit should be 0, credit should have the amount)
    if ($ledger->debit > 0 && $ledger->credit == 0) {
        echo "  ⚠️  Swapping DEBIT to CREDIT\n";
        $amount = $ledger->debit;
        $ledger->debit = 0;
        $ledger->credit = $amount;
    }

    $ledger->save();
    echo "  New Note: {$ledger->notes}\n";
    echo "  New Debit: {$ledger->debit}, Credit: {$ledger->credit}\n";
    echo "  ✅ Updated\n\n";
    $ledgerUpdated++;
}

echo "\n✅ Updated {$ledgerUpdated} ledger entries\n";
echo "✅ All done! Please refresh the ledger page.\n";
