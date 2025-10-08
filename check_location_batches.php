<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check table structure
$columns = Schema::getColumnListing('location_batches');
echo "LocationBatch table columns:\n";
foreach ($columns as $column) {
    echo "- $column\n";
}

echo "\nFirst few LocationBatch records:\n";
$batches = DB::table('location_batches')->limit(3)->get();
foreach ($batches as $batch) {
    echo "Batch ID: {$batch->id}\n";
    foreach ($batch as $key => $value) {
        echo "  $key: $value\n";
    }
    echo "\n";
}