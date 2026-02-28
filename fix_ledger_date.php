<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

// ---------------------------------------------------------------
// Find the BLK-S0082 payment ledger entry that has wrong date
// (do NOT hardcode ID — it differs between dev and production)
// ---------------------------------------------------------------
$targetRef    = 'BLK-S0082';
$targetDate   = '2026-01-29 12:36:02';
$customerId   = 1037;

// Find entries for this reference that may have wrong date
$wrongEntries = DB::table('ledgers')
    ->where('reference_no', $targetRef)
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('transaction_date', '!=', $targetDate)
    ->get(['id', 'transaction_date', 'credit']);

if ($wrongEntries->isEmpty()) {
    echo 'No entries with wrong date found for ref='.$targetRef.' — nothing to fix.'.PHP_EOL;
} else {
    foreach ($wrongEntries as $entry) {
        $affected = DB::table('ledgers')
            ->where('id', $entry->id)
            ->update([
                'transaction_date' => $targetDate,
                'updated_at'       => now()
            ]);
        echo 'Fixed ID:'.$entry->id
            .' | old_date:'.$entry->transaction_date
            .' → new_date:'.$targetDate
            .' | credit:'.number_format($entry->credit, 2)
            .' | rows_updated:'.$affected
            .PHP_EOL;
    }
}

echo PHP_EOL.'=== FINAL ORDERED LEDGER FOR CUSTOMER '.$customerId.' ==='.PHP_EOL;
$entries = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->orderBy('transaction_date')
    ->orderBy('id')
    ->get(['id','transaction_date','reference_no','transaction_type','debit','credit']);

$bal = 0;
foreach ($entries as $entry) {
    $bal += $entry->debit - $entry->credit;
    echo 'ID:'.$entry->id
        .' | '.substr($entry->transaction_date,0,10)
        .' | '.$entry->reference_no
        .' | '.$entry->transaction_type
        .' | D:'.number_format($entry->debit,2)
        .' C:'.number_format($entry->credit,2)
        .' | Balance:'.number_format($bal,2)
        .PHP_EOL;
}
echo PHP_EOL.'Outstanding balance: Rs. '.number_format($bal,2).PHP_EOL;
