<?php

/**
 * Sync all product prices with their latest batch prices
 * Run this once to fix any cached/stale pricing data
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;

echo "Starting product price synchronization...\n";

$productIds = Batch::distinct()->pluck('product_id');
$updated = 0;
$skipped = 0;

foreach ($productIds as $productId) {
    $latestBatch = Batch::where('product_id', $productId)
        ->whereHas('locationBatches', function($q) {
            $q->where('qty', '>', 0);
        })
        ->orderBy('created_at', 'desc')
        ->first();

    if ($latestBatch) {
        Product::where('id', $productId)->update([
            'retail_price' => $latestBatch->retail_price,
            'whole_sale_price' => $latestBatch->wholesale_price,
            'special_price' => $latestBatch->special_price,
            'max_retail_price' => $latestBatch->max_retail_price,
        ]);
        $updated++;
        echo "✅ Updated Product ID {$productId} - Retail: {$latestBatch->retail_price}\n";
    } else {
        $skipped++;
    }
}

echo "\n";
echo "====================================\n";
echo "✅ Synchronization complete!\n";
echo "Updated: {$updated} products\n";
echo "Skipped: {$skipped} products (no batches with stock)\n";
echo "====================================\n";
