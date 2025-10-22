<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

echo "=== FIXING PURCHASE #1 ===\n\n";

$purchase = Purchase::with('purchaseProducts')->find(1);

if (!$purchase) {
    echo "Purchase not found!\n";
    exit;
}

// Calculate the correct total
$productsSum = $purchase->purchaseProducts->sum('total');
echo "Sum of products: {$productsSum}\n";

// Since discount_type and tax_type are NULL, the final_total SHOULD be equal to products sum
$correctFinalTotal = $productsSum;

echo "Current final_total: {$purchase->final_total}\n";
echo "Correct final_total: {$correctFinalTotal}\n";

// Update the purchase
$purchase->final_total = $correctFinalTotal;
$purchase->save();

echo "\n✅ Purchase #1 has been fixed!\n";
echo "New final_total: {$purchase->final_total}\n";
