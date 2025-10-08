<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check product ID 5 - Fun Souls kids shoe that should show 8 Pcs
$product = App\Models\Product::find(5);
$batch = App\Models\Batch::where('product_id', 5)->first();
$locationBatch = $batch ? App\Models\LocationBatch::where('batch_id', $batch->id)->first() : null;

if ($locationBatch && $batch) {
    echo "Product: {$product->name}\n";
    echo "Batch ID: {$batch->id}\n";
    echo "Location Batch ID: {$locationBatch->id}\n";
    echo "Actual stock in location_batches: {$locationBatch->qty}\n";
    
    // Calculate total stock from stock_histories
    $totalStock = App\Models\StockHistory::where('loc_batch_id', $locationBatch->id)
        ->selectRaw('SUM(CASE 
            WHEN stock_type IN ("opening_stock", "purchase", "sales_return_with_bill", "sales_return_without_bill", "adjustment") THEN quantity 
            WHEN stock_type IN ("sale", "purchase_return") THEN -quantity 
            ELSE 0 
        END) as total_stock')
        ->value('total_stock');
    
    echo "Calculated stock from stock_histories: {$totalStock}\n";
    
    // Show recent stock_histories for this product
    echo "\nRecent stock_histories entries:\n";
    $histories = App\Models\StockHistory::where('loc_batch_id', $locationBatch->id)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($histories as $history) {
        echo "- {$history->stock_type}: {$history->quantity} ({$history->created_at})\n";
    }
    
    echo "\nStock calculation should now match between actual and calculated!\n";
}