<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check product ID 5 specifically
$batch = DB::table('batches')->where('product_id', 5)->first();
echo "Batch for product 5: " . ($batch ? $batch->id : 'Not found') . "\n";

if ($batch) {
    $locationBatch = DB::table('location_batches')->where('batch_id', $batch->id)->first();
    echo "Location batch: " . ($locationBatch ? $locationBatch->id : 'Not found') . "\n";
    
    if ($locationBatch) {
        echo "Current qty in location_batches: {$locationBatch->qty}\n";
        
        // Count adjustments for this location batch
        $adjustmentCount = DB::table('stock_histories')
            ->where('loc_batch_id', $locationBatch->id)
            ->where('stock_type', 'adjustment')
            ->count();
        
        echo "Number of adjustment entries: {$adjustmentCount}\n";
        
        if ($adjustmentCount > 0) {
            $adjustmentTotal = DB::table('stock_histories')
                ->where('loc_batch_id', $locationBatch->id)
                ->where('stock_type', 'adjustment')
                ->sum('quantity');
            echo "Total adjustment quantity: {$adjustmentTotal}\n";
        }
    }
}