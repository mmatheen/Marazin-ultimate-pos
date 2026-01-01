<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

echo "\n=== FIXING CUSTOMER 83 LEDGER ===\n";

DB::transaction(function () {

    // Fix #1: Mark reversal entry #1261 as 'reversed' (currently 'active')
    $ledger1261 = Ledger::find(1261);
    if ($ledger1261 && $ledger1261->status === 'active') {
        $ledger1261->update([
            'status' => 'reversed',
            'notes' => 'REVERSAL: Payment Edit - Original amount Rs2100.00 (ID: 1260)'
        ]);
        echo "✅ Fixed Ledger #1261 - Changed status from 'active' to 'reversed'\n";
    }

    // Fix #2: Mark all reversal entries with '-REV' as 'reversed'
    $activeReversals = Ledger::where('contact_id', 83)
        ->where('contact_type', 'customer')
        ->where('reference_no', 'LIKE', '%-REV%')
        ->where('status', 'active')
        ->get();

    foreach ($activeReversals as $reversal) {
        $reversal->update(['status' => 'reversed']);
        echo "✅ Fixed Ledger #{$reversal->id} ({$reversal->reference_no}) - Marked as 'reversed'\n";
    }

    echo "\n--- Recalculating Balance ---\n";
    $balance = Ledger::where('contact_id', 83)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->sum(DB::raw('debit - credit'));

    echo "Active entries balance: Rs.{$balance}\n";

    // List all active entries
    echo "\n--- Active Ledger Entries ---\n";
    $activeLedgers = Ledger::where('contact_id', 83)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->orderBy('id')
        ->get();

    $runningBalance = 0;
    foreach ($activeLedgers as $ledger) {
        $runningBalance += $ledger->debit - $ledger->credit;
        echo "#{$ledger->id}: {$ledger->reference_no} | D:{$ledger->debit} C:{$ledger->credit} | Balance: Rs.{$runningBalance}\n";
    }
});

echo "\n=== FIX COMPLETED ===\n";
