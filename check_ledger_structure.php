<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== LEDGERS TABLE STRUCTURE ===\n\n";

$columns = DB::select('SHOW COLUMNS FROM ledgers');

foreach ($columns as $col) {
    echo sprintf("%-25s | %-20s | Null: %-3s | Key: %-3s\n",
        $col->Field, $col->Type, $col->Null, $col->Key);
}

echo "\n=== SAMPLE LEDGER RECORDS FOR CUSTOMER 75 ===\n\n";

$samples = DB::select('SELECT * FROM ledgers WHERE contact_id = 75 AND contact_type = "customer" LIMIT 3');

if (!empty($samples)) {
    $first = $samples[0];
    echo "Columns in ledgers table:\n";
    foreach ($first as $key => $value) {
        echo "- $key\n";
    }
}
