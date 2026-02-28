<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$affected = DB::table('ledgers')
    ->where('id', 3432)
    ->update([
        'transaction_date' => '2026-01-29 12:36:02',
        'updated_at'       => now()
    ]);
echo 'Updated rows: '.$affected.PHP_EOL;

$e = DB::table('ledgers')->where('id', 3432)->first();
echo 'ID:'.$e->id.' | transaction_date:'.$e->transaction_date.' | credit:'.$e->credit.PHP_EOL;

echo PHP_EOL.'=== FINAL ORDERED LEDGER FOR CUSTOMER 1037 ==='.PHP_EOL;
$entries = DB::table('ledgers')
    ->where('contact_id', 1037)
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
