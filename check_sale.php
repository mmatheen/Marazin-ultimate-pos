<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Sale ABS-041 ===\n\n";

$sale = DB::table('sales')->where('invoice_no', 'ABS-041')->first();
echo "Sale ID: {$sale->id}\n";
echo "Subtotal in DB: {$sale->subtotal}\n";
echo "Discount: {$sale->discount_amount} ({$sale->discount_type})\n";
echo "Final Total: {$sale->final_total}\n";
echo "User ID: {$sale->user_id}\n";
echo "Created: {$sale->created_at}\n\n";

echo "=== Sales Products ===\n";
$products = DB::table('sales_products')->where('sale_id', $sale->id)->get();

$total_calculated = 0;
foreach($products as $p) {
    $calculated = $p->quantity * $p->price;
    $total_calculated += $calculated;
    echo "Product {$p->product_id}: Qty={$p->quantity}, Price={$p->price}, Calculated={$calculated}\n";
}

echo "\nTotal Calculated from products: {$total_calculated}\n";
echo "Subtotal in sales table: {$sale->subtotal}\n";
echo "Difference: " . ($sale->subtotal - $total_calculated) . "\n\n";

echo "=== Expected Calculation ===\n";
echo "67 units × Rs. 250 = Rs. 16,750 (EXPECTED)\n";
echo "But database shows: Rs. {$sale->subtotal} (WRONG!)\n";
echo "Difference: Rs. " . ($sale->subtotal - 16750) . "\n";
