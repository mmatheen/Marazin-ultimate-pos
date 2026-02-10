<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$deleted = DB::table('ledgers')
    ->where('contact_id', 1058)
    ->where('reference_no', 'BLK-S0075')
    ->where('status', 'active')
    ->delete();

echo "Deleted {$deleted} old format ledgers\n";

$newCount = DB::table('ledgers')
    ->where('contact_id', 1058)
    ->where('reference_no', 'LIKE', 'BLK-S0075-PAY%')
    ->where('status', 'active')
    ->count();

echo "New format ledgers: {$newCount}\n";

$totalCredits = DB::table('ledgers')
    ->where('contact_id', 1058)
    ->where('reference_no', 'LIKE', 'BLK-S0075%')
    ->where('status', 'active')
    ->sum('credit');

echo "Total credits: Rs. " . number_format($totalCredits, 2) . "\n";
