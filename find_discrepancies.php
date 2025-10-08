<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find products where calculated stock doesn't match actual stock
echo "Finding products with actual stock discrepancies:\n\n";

$products = DB::table('batches')
    ->join('location_batches', 'batches.id', '=', 'location_batches.batch_id')
    ->where('location_batches.qty', '>', 0) // Only check where there's actual stock
    ->select('batches.product_id', 'location_batches.id as loc_batch_id', 'location_batches.qty as actual_qty')
    ->limit(10)
    ->get();

foreach ($products as $item) {
    $calculatedQty = DB::table('stock_histories')
        ->where('loc_batch_id', $item->loc_batch_id)
        ->selectRaw('SUM(CASE 
            WHEN stock_type IN ("opening_stock", "purchase", "sales_return_with_bill", "sales_return_without_bill", "adjustment") THEN quantity 
            WHEN stock_type IN ("sale", "purchase_return") THEN -quantity 
            ELSE 0 
        END) as total_stock')
        ->value('total_stock') ?? 0;
    
    if ($calculatedQty != $item->actual_qty) {
        $productName = DB::table('products')->where('id', $item->product_id)->value('product_name');
        echo "Product ID {$item->product_id}: {$productName}\n";
        echo "  Location Batch ID: {$item->loc_batch_id}\n";
        echo "  Actual: {$item->actual_qty}, Calculated: {$calculatedQty}\n";
        echo "  Difference: " . ($item->actual_qty - $calculatedQty) . "\n\n";
    }
}