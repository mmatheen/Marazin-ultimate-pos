<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

echo '=== LEDGER ENTRIES FOR CUSTOMER 1037 ==='.PHP_EOL;
$entries = DB::table('ledgers')
    ->where('contact_id', 1037)
    ->where('contact_type', 'customer')
    ->orderBy('transaction_date')
    ->orderBy('id')
    ->get();

foreach($entries as $e) {
    echo 'ID:'.$e->id
        .' | date:'.$e->transaction_date
        .' | ref:'.$e->reference_no
        .' | type:'.$e->transaction_type
        .' | debit:'.$e->debit
        .' | credit:'.$e->credit
        .' | status:'.$e->status
        .PHP_EOL;
}
echo PHP_EOL.'Total entries: '.count($entries).PHP_EOL;

echo PHP_EOL.'=== PAYMENTS FOR CUSTOMER 1037 (NO SCOPE) ==='.PHP_EOL;
$payments = DB::table('payments')
    ->where('customer_id', 1037)
    ->orderBy('id')
    ->get(['id','payment_date','amount','payment_method','reference_no','reference_id','cheque_number','cheque_status','payment_status','status','payment_type']);

foreach($payments as $p) {
    echo 'ID:'.$p->id
        .' | date:'.$p->payment_date
        .' | amt:'.$p->amount
        .' | method:'.$p->payment_method
        .' | ref:'.$p->reference_no
        .' | ref_id:'.$p->reference_id
        .' | cheque:'.$p->cheque_number
        .' | cheque_status:'.$p->cheque_status
        .' | pay_status:'.$p->payment_status
        .' | status:'.$p->status
        .PHP_EOL;
}

echo PHP_EOL.'=== CHECKING LEDGER FOR BULK REF BLK-S0082 ==='.PHP_EOL;
$blkEntries = DB::table('ledgers')
    ->where('reference_no', 'like', 'BLK-S0082%')
    ->get();
foreach($blkEntries as $e) {
    echo 'ID:'.$e->id
        .' | contact:'.$e->contact_id
        .' | ref:'.$e->reference_no
        .' | type:'.$e->transaction_type
        .' | debit:'.$e->debit
        .' | credit:'.$e->credit
        .' | status:'.$e->status
        .' | created:'.$e->created_at
        .PHP_EOL;
}
