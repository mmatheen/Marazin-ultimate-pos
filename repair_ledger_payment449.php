<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo '=== REPAIR: Missing Ledger Entry for Payment 449 ==='.PHP_EOL;

// Confirm payment 449 exists
$payment = DB::table('payments')->where('id', 449)->first();
if (!$payment) {
    echo 'ERROR: Payment 449 not found!'.PHP_EOL;
    exit(1);
}

echo 'Payment found: ID='.$payment->id
    .' | amount='.$payment->amount
    .' | method='.$payment->payment_method
    .' | ref='.$payment->reference_no
    .' | customer_id='.$payment->customer_id
    .PHP_EOL;

// Count existing BLK-S0082 payment credits for customer 1037
$existing = DB::table('ledgers')
    ->where('contact_id', 1037)
    ->where('contact_type', 'customer')
    ->where('reference_no', 'BLK-S0082')
    ->where('transaction_type', 'payments')
    ->where('status', 'active')
    ->get(['id','credit','debit','reference_no','transaction_date','notes']);

echo PHP_EOL.'Existing BLK-S0082 payment ledger entries ('.count($existing).'):'.PHP_EOL;
$totalCredited = 0;
foreach ($existing as $e) {
    $totalCredited += $e->credit;
    echo '  ID:'.$e->id
        .' | credit:'.$e->credit
        .' | date:'.$e->transaction_date
        .' | notes:'.$e->notes
        .PHP_EOL;
}
echo '  Total credited so far: Rs.'.$totalCredited.PHP_EOL;

// 3 payments exist: 200000 + 95405 + 200000 = 495405 total should be credited
// Currently only 200000 + 95405 = 295405 - missing 200000
if ($totalCredited >= 495405) {
    echo PHP_EOL.'✅ All 3 payments already recorded in ledger. Nothing to repair.'.PHP_EOL;
    exit(0);
}

// Insert missing entry — use same reference BLK-S0082 for consistency with the other two entries
$transactionDate = Carbon::parse($payment->payment_date, 'Asia/Colombo');

$newId = DB::table('ledgers')->insertGetId([
    'contact_id'       => 1037,
    'contact_type'     => 'customer',
    'transaction_date' => $transactionDate->toDateTimeString(),
    'reference_no'     => 'BLK-S0082',
    'transaction_type' => 'payments',
    'debit'            => 0.00,
    'credit'           => 200000.00,
    'status'           => 'active',
    'notes'            => 'Payment for sale #BLK-S0082 (Cheque 042868 / HNB KTVR - recovered missing entry for payment ID 449)',
    'created_by'       => 1,
    'created_at'       => '2026-01-29 12:36:02',  // Match original payment timestamp
    'updated_at'       => now(),
]);

echo PHP_EOL.'✅ Created missing ledger entry ID='.$newId.' (ref=BLK-S0082, credit=200,000)'.PHP_EOL;

// Verify final state
echo PHP_EOL.'=== FINAL LEDGER STATE FOR CUSTOMER 1037 (active entries) ==='.PHP_EOL;
$entries = DB::table('ledgers')
    ->where('contact_id', 1037)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->orderBy('transaction_date')
    ->orderBy('id')
    ->get(['id','transaction_date','reference_no','transaction_type','debit','credit']);

$runningBalance = 0;
foreach ($entries as $e) {
    $runningBalance += $e->debit - $e->credit;
    echo 'ID:'.$e->id
        .' | '.substr($e->transaction_date,0,10)
        .' | '.$e->reference_no
        .' | '.$e->transaction_type
        .' | D:'.number_format($e->debit,2)
        .' C:'.number_format($e->credit,2)
        .' | Balance:'.number_format($runningBalance,2)
        .PHP_EOL;
}
echo PHP_EOL.'Final outstanding balance: Rs. '.number_format($runningBalance,2).PHP_EOL;
