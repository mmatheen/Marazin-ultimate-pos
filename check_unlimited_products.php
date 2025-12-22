<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Batch;
use App\Models\SalesProduct;

echo "=== CHECKING UNLIMITED STOCK PRODUCTS ===\n\n";

// Get products with stock_alert = 0 (unlimited stock)
$unlimitedProducts = Product::where('stock_alert', 0)->get();

echo "Found " . $unlimitedProducts->count() . " products with unlimited stock (stock_alert = 0)\n\n";

foreach ($unlimitedProducts as $product) {
    echo "Product ID: {$product->id} - {$product->product_name}\n";
    
    // Check if this product has any batches
    $batches = Batch::where('product_id', $product->id)->get();
    echo "  Batches: " . $batches->count() . "\n";
    
    // Check if this product has any sale_products without batch_id
    $salesWithoutBatch = SalesProduct::where('product_id', $product->id)
        ->whereNull('batch_id')
        ->count();
    echo "  Sale records without batch_id: {$salesWithoutBatch}\n\n";
}

echo "\n=== SALE PRODUCTS WITHOUT BATCH_ID (Sample 20) ===\n\n";

$productsWithoutBatch = SalesProduct::with('product')
    ->whereNull('batch_id')
    ->limit(20)
    ->get();

foreach ($productsWithoutBatch as $sp) {
    $product = $sp->product;
    echo "Sale Product ID: {$sp->id} | Sale ID: {$sp->sale_id} | Product: {$product->product_name} (ID: {$product->id}) | Stock Alert: {$product->stock_alert}\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total sale_products without batch_id: " . SalesProduct::whereNull('batch_id')->count() . "\n";
echo "Total products with stock_alert = 0: " . Product::where('stock_alert', 0)->count() . "\n";
