<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check all batches and location batches for product 5
$batches = DB::table('batches')->where('product_id', 5)->get();
echo "All batches for product 5:\n";

foreach ($batches as $batch) {
    echo "Batch ID: {$batch->id}, Qty: {$batch->qty}\n";
    
    $locationBatches = DB::table('location_batches')->where('batch_id', $batch->id)->get();
    foreach ($locationBatches as $locBatch) {
        echo "  Location Batch ID: {$locBatch->id}, Location: {$locBatch->location_id}, Qty: {$locBatch->qty}\n";
        
        // Check adjustment entries
        $adjustmentCount = DB::table('stock_histories')
            ->where('loc_batch_id', $locBatch->id)
            ->where('stock_type', 'adjustment')
            ->count();
        
        if ($adjustmentCount > 0) {
            $adjustmentTotal = DB::table('stock_histories')
                ->where('loc_batch_id', $locBatch->id)
                ->where('stock_type', 'adjustment')
                ->sum('quantity');
            echo "    Adjustments: {$adjustmentCount} entries, Total: {$adjustmentTotal}\n";
        }
        
        // Calculate total stock from all stock histories
        $totalStock = DB::table('stock_histories')
            ->where('loc_batch_id', $locBatch->id)
            ->selectRaw('SUM(CASE 
                WHEN stock_type IN ("opening_stock", "purchase", "sales_return_with_bill", "sales_return_without_bill", "adjustment") THEN quantity 
                WHEN stock_type IN ("sale", "purchase_return") THEN -quantity 
                ELSE 0 
            END) as total_stock')
            ->value('total_stock');
        
        echo "    Calculated total stock: {$totalStock}\n";
    }
}