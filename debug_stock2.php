<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find products with similar names
$products = App\Models\Product::where('product_name', 'LIKE', '%Steel Water Bottle%')->get();
echo "Found {$products->count()} products matching 'Steel Water Bottle':\n";

foreach ($products as $product) {
    echo "- {$product->product_name} | SKU: {$product->sku} | ID: {$product->id}\n";
}

// Find all products with negative stock issues
echo "\nChecking all products for negative stock issues...\n";
$allProducts = App\Models\Product::take(10)->get();

foreach ($allProducts as $product) {
    $stockHistories = App\Models\StockHistory::whereHas('locationBatch.batch', function($q) use ($product) {
        $q->where('product_id', $product->id);
    })->get();
    
    if ($stockHistories->count() > 0) {
        $runningStock = 0;
        foreach ($stockHistories as $history) {
            if (in_array($history->stock_type, ['opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'transfer_in'])) {
                $runningStock += $history->quantity;
            } else {
                $runningStock -= abs($history->quantity);
            }
        }
        
        if ($runningStock < 0) {
            echo "NEGATIVE STOCK: {$product->product_name} (SKU: {$product->sku}) = {$runningStock}\n";
        }
    }
}