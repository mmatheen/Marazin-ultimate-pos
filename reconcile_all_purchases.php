<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

echo "=== PURCHASE RECONCILIATION SCRIPT ===\n";
echo "This script will fix all purchase totals\n\n";

$dryRun = true; // Set to false to actually update
echo "MODE: " . ($dryRun ? "DRY RUN (no changes)" : "LIVE UPDATE") . "\n\n";

$purchases = Purchase::with('purchaseProducts')->get();
$totalPurchases = $purchases->count();
$fixedCount = 0;

echo "Found {$totalPurchases} purchases to check\n\n";

foreach ($purchases as $purchase) {
    echo "----------------------------------------\n";
    echo "Purchase #{$purchase->id} - {$purchase->reference_no}\n";
    echo "Date: {$purchase->purchase_date}\n";
    
    // Calculate correct total from products
    $productsTotal = 0;
    
    foreach ($purchase->purchaseProducts as $product) {
        // If discount_percent exists and price is set, recalculate
        if ($product->price > 0 && $product->discount_percent > 0) {
            $discountAmount = $product->price * ($product->discount_percent / 100);
            $unitCostAfterDiscount = $product->price - $discountAmount;
            $productTotal = $unitCostAfterDiscount * $product->quantity;
            
            echo "  - Product {$product->product_id}: Qty={$product->quantity}, ";
            echo "Price={$product->price}, Discount={$product->discount_percent}%, ";
            echo "Unit Cost={$unitCostAfterDiscount}, Total={$productTotal}\n";
        } else {
            // No discount or old data, use existing unit_cost
            $productTotal = $product->unit_cost * $product->quantity;
            echo "  - Product {$product->product_id}: Qty={$product->quantity}, ";
            echo "Unit Cost={$product->unit_cost}, Total={$productTotal}\n";
        }
        
        $productsTotal += $productTotal;
    }
    
    echo "\nCalculated Products Total: " . number_format($productsTotal, 2) . "\n";
    
    // Apply purchase-level discount
    $discountAmount = 0;
    if ($purchase->discount_type === 'fixed') {
        $discountAmount = $purchase->discount_amount ?? 0;
    } elseif ($purchase->discount_type === 'percent') {
        $discountAmount = ($productsTotal * ($purchase->discount_amount ?? 0)) / 100;
    }
    
    // Apply tax
    $taxAmount = 0;
    if ($purchase->tax_type === 'vat10' || $purchase->tax_type === 'cgst10') {
        $taxAmount = ($productsTotal - $discountAmount) * 0.10;
    }
    
    $calculatedFinalTotal = $productsTotal - $discountAmount + $taxAmount;
    
    echo "Discount ({$purchase->discount_type}): " . number_format($discountAmount, 2) . "\n";
    echo "Tax ({$purchase->tax_type}): " . number_format($taxAmount, 2) . "\n";
    echo "Calculated Final Total: " . number_format($calculatedFinalTotal, 2) . "\n";
    
    echo "\nCurrent in Database:\n";
    echo "  total: " . number_format($purchase->total, 2) . "\n";
    echo "  final_total: " . number_format($purchase->final_total, 2) . "\n";
    
    // Check if needs fixing
    $needsFixing = false;
    if (abs($purchase->total - $productsTotal) > 0.01) {
        echo "  ⚠ total is WRONG (difference: " . number_format($purchase->total - $productsTotal, 2) . ")\n";
        $needsFixing = true;
    }
    
    if (abs($purchase->final_total - $calculatedFinalTotal) > 0.01) {
        echo "  ⚠ final_total is WRONG (difference: " . number_format($purchase->final_total - $calculatedFinalTotal, 2) . ")\n";
        $needsFixing = true;
    }
    
    if ($needsFixing) {
        echo "\n🔧 FIXING THIS PURCHASE...\n";
        
        if (!$dryRun) {
            $purchase->update([
                'total' => $productsTotal,
                'final_total' => $calculatedFinalTotal,
            ]);
            echo "✅ FIXED!\n";
        } else {
            echo "   (Would update: total={$productsTotal}, final_total={$calculatedFinalTotal})\n";
        }
        
        $fixedCount++;
    } else {
        echo "✓ Totals are correct\n";
    }
    
    echo "\n";
}

echo "========================================\n";
echo "SUMMARY:\n";
echo "  Total purchases checked: {$totalPurchases}\n";
echo "  Purchases needing fix: {$fixedCount}\n";
echo "  Mode: " . ($dryRun ? "DRY RUN (no changes made)" : "LIVE UPDATE") . "\n";

if ($dryRun && $fixedCount > 0) {
    echo "\n⚠️  To actually apply these fixes, edit this script and set \$dryRun = false\n";
}

echo "\n=== DONE ===\n";
