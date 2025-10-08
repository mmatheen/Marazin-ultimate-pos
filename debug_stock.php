<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$product = App\Models\Product::where('sku', '2464')->first();
if ($product) {
    echo "Found Product: {$product->product_name} (ID: {$product->id})\n";
    
    $stockHistories = App\Models\StockHistory::whereHas('locationBatch.batch', function($q) use ($product) {
        $q->where('product_id', $product->id);
    })->with('locationBatch.batch')->orderBy('created_at')->get();
    
    echo "Stock History Count: {$stockHistories->count()}\n";
    echo "Recent 10 records:\n";
    
    $runningStock = 0;
    foreach ($stockHistories->take(10) as $history) {
        if (in_array($history->stock_type, ['opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'transfer_in'])) {
            $runningStock += $history->quantity;
        } else {
            $runningStock -= abs($history->quantity);
        }
        
        echo "{$history->stock_type} | Qty: {$history->quantity} | Running: {$runningStock} | Date: {$history->created_at}\n";
    }
    
    // Show total calculation
    echo "\nTotal Calculation:\n";
    $groupedByType = $stockHistories->groupBy('stock_type');
    foreach ($groupedByType as $type => $histories) {
        $total = $histories->sum('quantity');
        echo "{$type}: {$total}\n";
    }
} else {
    echo "Product with SKU 2464 not found\n";
}