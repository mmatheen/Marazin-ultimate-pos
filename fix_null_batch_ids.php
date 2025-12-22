<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SalesProduct;
use App\Models\Batch;
use App\Models\LocationBatch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "=== FIXING NULL BATCH_ID IN SALES_PRODUCTS ===\n\n";

// Get all sale_products with NULL batch_id
$productsWithoutBatch = SalesProduct::whereNull('batch_id')->get();

echo "Found " . $productsWithoutBatch->count() . " sale_products without batch_id\n\n";

$fixed = 0;
$skipped = 0;
$errors = 0;

DB::beginTransaction();

try {
    foreach ($productsWithoutBatch as $saleProduct) {
        $product = Product::find($saleProduct->product_id);
        
        if (!$product) {
            echo "❌ Sale Product ID {$saleProduct->id}: Product not found\n";
            $errors++;
            continue;
        }
        
        // Check if product has unlimited stock
        if ($product->stock_alert == 0) {
            echo "⏭️  Sale Product ID {$saleProduct->id}: Product '{$product->product_name}' has unlimited stock, keeping batch_id NULL\n";
            $skipped++;
            continue;
        }
        
        // Try to find a batch for this product at the same location
        $batch = Batch::where('product_id', $saleProduct->product_id)
            ->orderBy('created_at', 'asc')  // FIFO - oldest first
            ->first();
        
        if (!$batch) {
            echo "⚠️  Sale Product ID {$saleProduct->id}: No batch found for product '{$product->product_name}' (ID: {$product->id})\n";
            $errors++;
            continue;
        }
        
        // Check if this batch exists in the location
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $saleProduct->location_id)
            ->first();
        
        if (!$locationBatch) {
            // Try to find any batch for this product in this location
            $locationBatch = LocationBatch::where('location_id', $saleProduct->location_id)
                ->whereHas('batch', function($q) use ($saleProduct) {
                    $q->where('product_id', $saleProduct->product_id);
                })
                ->first();
            
            if ($locationBatch) {
                $batch = $locationBatch->batch;
            } else {
                echo "⚠️  Sale Product ID {$saleProduct->id}: No location_batch found for product '{$product->product_name}'\n";
                $errors++;
                continue;
            }
        }
        
        // Update the batch_id
        $saleProduct->batch_id = $batch->id;
        $saleProduct->save();
        
        echo "✅ Sale Product ID {$saleProduct->id}: Updated with Batch ID {$batch->id} (Batch No: {$batch->batch_no})\n";
        $fixed++;
    }
    
    DB::commit();
    
    echo "\n=== SUMMARY ===\n";
    echo "✅ Fixed: {$fixed}\n";
    echo "⏭️  Skipped (unlimited stock): {$skipped}\n";
    echo "❌ Errors: {$errors}\n";
    echo "\nTotal processed: " . ($fixed + $skipped + $errors) . "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
