<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Purchase;

$purchase = Purchase::with('purchaseProducts')->first();

if (!$purchase) {
    echo "No purchases found in database.\n";
    exit;
}

echo "=== PURCHASE DETAILS ===\n";
echo "Purchase ID: {$purchase->id}\n";
echo "Reference: {$purchase->reference_no}\n";
echo "Date: {$purchase->date}\n";
echo "\n";

echo "=== STORED VALUES ===\n";
echo "Final Total (stored): {$purchase->final_total}\n";
echo "Discount Type: {$purchase->discount_type}\n";
echo "Discount Amount: {$purchase->discount_amount}\n";
echo "Tax Amount: {$purchase->tax_amount}\n";
echo "Tax Type: {$purchase->tax_type}\n";
echo "\n";

echo "=== PRODUCTS ===\n";
$productsSum = 0;
foreach ($purchase->purchaseProducts as $index => $product) {
    echo "Product " . ($index + 1) . ":\n";
    echo "  - Product ID: {$product->product_id}\n";
    echo "  - Quantity: {$product->quantity}\n";
    echo "  - Unit Cost: {$product->unit_cost}\n";
    echo "  - Price: {$product->price}\n";
    echo "  - Total: {$product->total}\n";
    $productsSum += $product->total;
}
echo "\n";

echo "=== CALCULATION ===\n";
echo "Sum of all products: {$productsSum}\n";

// Calculate what the final total SHOULD be
$calculatedTotal = $productsSum;

// Apply discount
if ($purchase->discount_type === 'fixed') {
    $calculatedTotal -= $purchase->discount_amount;
} elseif ($purchase->discount_type === 'percent') {
    $discountAmount = ($productsSum * $purchase->discount_amount) / 100;
    $calculatedTotal -= $discountAmount;
}

// Apply tax
if ($purchase->tax_type === 'vat10' || $purchase->tax_type === 'cgst10') {
    $taxAmount = ($calculatedTotal * 10) / 100;
    $calculatedTotal += $taxAmount;
}

echo "After discount & tax: {$calculatedTotal}\n";
echo "\n";

echo "=== COMPARISON ===\n";
echo "Stored final_total: {$purchase->final_total}\n";
echo "Calculated total: {$calculatedTotal}\n";
$difference = abs($purchase->final_total - $calculatedTotal);
echo "Difference: {$difference}\n";

if ($difference > 0.5) {
    echo "\n⚠️  WARNING: Significant difference detected!\n";
} else {
    echo "\n✅ Totals match!\n";
}
