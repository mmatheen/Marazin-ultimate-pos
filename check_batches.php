<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check batches table structure
$columns = DB::select('DESCRIBE batches');
echo "Batches table columns:\n";
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

echo "\nFirst few batches records:\n";
$batches = DB::table('batches')->limit(3)->get();
foreach ($batches as $batch) {
    echo "Batch ID: {$batch->id}\n";
    foreach ($batch as $key => $value) {
        echo "  $key: $value\n";
    }
    echo "\n";
}