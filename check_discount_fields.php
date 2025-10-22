<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PURCHASE_PRODUCTS TABLE STRUCTURE ===\n\n";

$columns = DB::select('SHOW COLUMNS FROM purchase_products');

foreach ($columns as $column) {
    echo sprintf("%-20s %-25s %s\n", 
        $column->Field, 
        $column->Type,
        $column->Null === 'YES' ? 'NULL' : 'NOT NULL'
    );
}

echo "\n=== TESTING WITH SAMPLE DATA ===\n\n";

// Check if we have any purchase products with discount data
$products = DB::table('purchase_products')
    ->join('products', 'products.id', '=', 'purchase_products.product_id')
    ->select(
        'purchase_products.id',
        'products.product_name',
        'purchase_products.quantity',
        'purchase_products.price',
        'purchase_products.discount_percent',
        'purchase_products.unit_cost',
        'purchase_products.total'
    )
    ->limit(5)
    ->get();

if ($products->isEmpty()) {
    echo "No purchase products found in database.\n";
} else {
    foreach ($products as $product) {
        echo "Product: {$product->product_name}\n";
        echo "  Quantity: {$product->quantity}\n";
        echo "  Price (before discount): {$product->price}\n";
        echo "  Discount %: {$product->discount_percent}%\n";
        echo "  Unit Cost (after discount): {$product->unit_cost}\n";
        echo "  Total: {$product->total}\n";
        
        // Calculate expected discount amount
        $expectedDiscount = $product->price * ($product->discount_percent / 100);
        $expectedUnitCost = $product->price - $expectedDiscount;
        
        echo "  Expected Unit Cost: " . number_format($expectedUnitCost, 2) . "\n";
        echo "  Match: " . (abs($product->unit_cost - $expectedUnitCost) < 0.01 ? '✓' : '✗') . "\n";
        echo "\n";
    }
}
