<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fixing Sale ABS-041 ===\n\n";

// Get the sale
$sale = DB::table('sales')->where('invoice_no', 'ABS-041')->first();

echo "BEFORE:\n";
echo "Subtotal: {$sale->subtotal}\n";
echo "Discount: {$sale->discount_amount} ({$sale->discount_type})\n";
echo "Final Total: {$sale->final_total}\n";
echo "Total Paid: {$sale->total_paid}\n";
echo "Total Due: {$sale->total_due}\n\n";

// Calculate correct subtotal from sales_products
$products = DB::table('sales_products')->where('sale_id', $sale->id)->get();
$correct_subtotal = 0;
foreach($products as $p) {
    $correct_subtotal += $p->quantity * $p->price;
}

echo "Correct subtotal should be: {$correct_subtotal}\n\n";

// Calculate correct final_total
$discount_amount = $sale->discount_amount;
if ($sale->discount_type === 'percentage') {
    $final_total = $correct_subtotal - ($correct_subtotal * $discount_amount / 100);
} else {
    $final_total = $correct_subtotal - $discount_amount;
}

// Add shipping if any
$final_total += ($sale->shipping_charges ?? 0);

// Calculate total_due
$total_due = $final_total - $sale->total_paid;

echo "Updating sale with:\n";
echo "- Subtotal: {$correct_subtotal}\n";
echo "- Final Total: {$final_total}\n";
echo "- Total Due: {$total_due}\n\n";

// Update the sale
DB::table('sales')
    ->where('id', $sale->id)
    ->update([
        'subtotal' => $correct_subtotal,
        'final_total' => $final_total,
        'total_due' => $total_due,
        'updated_at' => now(),
    ]);

echo "✅ Sale ABS-041 has been corrected!\n\n";

// Verify
$updated = DB::table('sales')->where('invoice_no', 'ABS-041')->first();
echo "AFTER:\n";
echo "Subtotal: {$updated->subtotal}\n";
echo "Final Total: {$updated->final_total}\n";
echo "Total Due: {$updated->total_due}\n";
