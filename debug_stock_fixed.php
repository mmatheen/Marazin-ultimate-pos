<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$product = App\Models\Product::where('product_name', 'LIKE', '%Steel Water Bottle%')->first();
if ($product) {
    echo "Found Product: {$product->product_name} (ID: {$product->id})\n";
    
    $stockHistories = App\Models\StockHistory::whereHas('locationBatch.batch', function($q) use ($product) {
        $q->where('product_id', $product->id);
    })->orderBy('created_at')->get();
    
    echo "Stock History Count: {$stockHistories->count()}\n";
    echo "Corrected Running Balance Calculation:\n";
    
    $runningStock = 0;
    foreach ($stockHistories as $history) {
        // Using corrected logic: sale_reversal should ADD to stock
        if (in_array($history->stock_type, ['opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'sale_reversal', 'transfer_in'])) {
            $runningStock += $history->quantity;
            $operation = 'ADD';
        } else {
            $runningStock -= abs($history->quantity);
            $operation = 'SUB';
        }
        
        echo "{$history->stock_type} | Qty: {$history->quantity} | {$operation} | Running: {$runningStock} | Date: {$history->created_at}\n";
    }
    
    echo "\n=== BACKEND CALCULATION TEST ===\n";
    
    // Test the backend calculation logic that we just fixed
    $inTypes = ['opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'sale_reversal', 'transfer_in'];
    $outTypes = ['sale', 'adjustment', 'purchase_return', 'transfer_out'];
    
    $stockTypeSums = $stockHistories->groupBy('stock_type')->map(function ($group) {
        return $group->sum('quantity');
    });
    
    $quantitiesIn = $stockTypeSums->filter(function($val, $key) use ($inTypes) {
        return in_array($key, $inTypes);
    })->sum();
    
    $quantitiesOut = $stockTypeSums->filter(function($val, $key) use ($outTypes) {
        return in_array($key, $outTypes);
    })->sum(function($val) {
        return abs($val);
    });
    
    $currentStock = $quantitiesIn - $quantitiesOut;
    
    echo "Quantities IN: {$quantitiesIn}\n";
    echo "Quantities OUT: {$quantitiesOut}\n";
    echo "Current Stock: {$currentStock}\n";
    echo "Final Running Stock: {$runningStock}\n";
    echo "Match: " . ($currentStock == $runningStock ? "YES" : "NO") . "\n";
    
} else {
    echo "Steel Water Bottle product not found\n";
}