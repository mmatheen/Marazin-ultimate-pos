<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

echo "=== FIXING ALL PURCHASES ===\n\n";

$purchases = Purchase::with('purchaseProducts')->get();

foreach ($purchases as $purchase) {
    echo "Purchase #{$purchase->id} (Ref: {$purchase->reference_no})\n";
    
    // Calculate correct total (sum of products)
    $calculatedTotal = $purchase->purchaseProducts->sum('total');
    echo "  Sum of products: {$calculatedTotal}\n";
    
    // Calculate discount
    $discountAmount = 0;
    if ($purchase->discount_type === 'fixed') {
        $discountAmount = $purchase->discount_amount ?? 0;
    } elseif ($purchase->discount_type === 'percent') {
        $discountAmount = ($calculatedTotal * ($purchase->discount_amount ?? 0)) / 100;
    }
    
    // Calculate tax
    $taxAmount = 0;
    if ($purchase->tax_type === 'vat10' || $purchase->tax_type === 'cgst10') {
        $taxAmount = ($calculatedTotal - $discountAmount) * 0.10;
    }
    
    // Calculate final total
    $finalTotal = $calculatedTotal - $discountAmount + $taxAmount;
    
    echo "  Discount: {$discountAmount}\n";
    echo "  Tax: {$taxAmount}\n";
    echo "  Calculated final_total: {$finalTotal}\n";
    
    echo "  Current total: {$purchase->total}\n";
    echo "  Current final_total: {$purchase->final_total}\n";
    
    // Update if different
    if (abs($purchase->total - $calculatedTotal) > 0.01 || abs($purchase->final_total - $finalTotal) > 0.01) {
        $purchase->update([
            'total' => $calculatedTotal,
            'final_total' => $finalTotal,
        ]);
        echo "  ✅ FIXED!\n";
    } else {
        echo "  ✓ Already correct\n";
    }
    echo "\n";
}

echo "=== DONE ===\n";
