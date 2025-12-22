<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SalesProduct;
use App\Models\Product;

echo "=== FINAL VERIFICATION ===\n\n";

$totalWithoutBatch = SalesProduct::whereNull('batch_id')->count();
echo "Total sale_products without batch_id: {$totalWithoutBatch}\n";

$unlimitedStockWithoutBatch = SalesProduct::whereNull('batch_id')
    ->join('products', 'sales_products.product_id', '=', 'products.id')
    ->where('products.stock_alert', 0)
    ->count();

echo "All of these are unlimited stock products: {$unlimitedStockWithoutBatch}\n";

if ($totalWithoutBatch === $unlimitedStockWithoutBatch) {
    echo "\n✅ SUCCESS! All regular stock products now have batch_id assigned.\n";
    echo "✅ Only unlimited stock products (stock_alert=0) have NULL batch_id, which is correct.\n";
} else {
    echo "\n⚠️  WARNING: There are still " . ($totalWithoutBatch - $unlimitedStockWithoutBatch) . " regular stock products without batch_id\n";
}
