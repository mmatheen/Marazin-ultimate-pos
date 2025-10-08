<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check stock_histories table structure
$columns = DB::select('DESCRIBE stock_histories');
echo "Stock_histories table columns:\n";
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

echo "\nFirst few stock_histories records:\n";
$histories = DB::table('stock_histories')->limit(3)->get();
foreach ($histories as $history) {
    echo "History ID: {$history->id}\n";
    foreach ($history as $key => $value) {
        echo "  $key: $value\n";
    }
    echo "\n";
}