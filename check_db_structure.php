<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking sales table structure...\n";

$columns = DB::select('DESCRIBE sales');
foreach ($columns as $col) {
    if (in_array($col->Field, ['total_paid', 'total_due', 'final_total'])) {
        echo "{$col->Field}: {$col->Type}, Extra: {$col->Extra}\n";
    }
}

echo "\nChecking current values for sale 9:\n";
$sale = DB::select('SELECT id, final_total, total_paid, total_due, payment_status FROM sales WHERE id = 9');
foreach ($sale as $s) {
    echo "ID: {$s->id}, Final: {$s->final_total}, Paid: {$s->total_paid}, Due: {$s->total_due}, Status: {$s->payment_status}\n";
}
