<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sale = App\Models\Sale::where('invoice_no', 'CSX-1430')->first();

if ($sale) {
    echo "Sale ID: {$sale->id}\n";
    echo "Sale Invoice: {$sale->invoice_no}\n\n";

    $products = $sale->products;

    echo "Products in sale:\n";
    echo str_repeat('-', 80) . "\n";
    foreach ($products as $p) {
        $productName = $p->product ? $p->product->product_name : 'N/A';
        echo "Product ID: {$p->product_id}, Batch ID: {$p->batch_id}, Quantity: {$p->quantity}, Name: {$productName}\n";
    }
    echo str_repeat('-', 80) . "\n\n";

    // Check if there are any existing returns
    $returns = App\Models\SalesReturn::where('sale_id', $sale->id)->get();
    if ($returns->count() > 0) {
        echo "Existing returns for this sale:\n";
        echo str_repeat('-', 80) . "\n";
        foreach ($returns as $return) {
            echo "Return ID: {$return->id}, Date: {$return->return_date}, Total: {$return->return_total}\n";
            $returnProducts = $return->returnProducts;
            foreach ($returnProducts as $rp) {
                echo "  - Product ID: {$rp->product_id}, Batch ID: {$rp->batch_id}, Quantity: {$rp->quantity}\n";
            }
        }
    } else {
        echo "No existing returns found.\n";
    }
} else {
    echo "Sale not found\n";
}
