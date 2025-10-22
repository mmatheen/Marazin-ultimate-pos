<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

echo "=================================================\n";
echo "Purchase Data Diagnostic Check\n";
echo "=================================================\n\n";

// Get latest purchases
$purchases = Purchase::with('purchaseProducts.product')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

if ($purchases->isEmpty()) {
    echo "No purchases found in database.\n";
    exit;
}

echo "Found " . $purchases->count() . " recent purchase(s)\n\n";

foreach ($purchases as $purchase) {
    echo "─────────────────────────────────────────────────\n";
    echo "Purchase ID: {$purchase->id}\n";
    echo "Reference: {$purchase->reference_no}\n";
    echo "Date: {$purchase->purchase_date}\n";
    echo "Status: {$purchase->purchasing_status}\n";
    echo "\n";
    
    // Check stored values
    echo "STORED VALUES:\n";
    echo "  Total: " . number_format($purchase->total, 2) . "\n";
    echo "  Discount Type: " . ($purchase->discount_type ?: 'none') . "\n";
    echo "  Discount Amount: " . number_format($purchase->discount_amount ?? 0, 2) . "\n";
    echo "  Final Total: " . number_format($purchase->final_total, 2) . "\n";
    echo "\n";
    
    // Calculate from products
    $productCount = $purchase->purchaseProducts->count();
    echo "PRODUCTS ({$productCount} items):\n";
    
    $calculatedSum = 0;
    foreach ($purchase->purchaseProducts as $index => $pp) {
        $productName = $pp->product ? $pp->product->product_name : "Product ID {$pp->product_id}";
        $calculatedSum += $pp->total;
        
        echo sprintf(
            "  %d. %s\n     Qty: %s | Unit Cost: %s | Total: %s\n",
            $index + 1,
            substr($productName, 0, 50),
            number_format($pp->quantity, 2),
            number_format($pp->unit_cost, 2),
            number_format($pp->total, 2)
        );
    }
    
    echo "\n";
    echo "CALCULATED VALUES:\n";
    echo "  Sum of Product Totals: " . number_format($calculatedSum, 2) . "\n";
    
    // Apply discount
    $discountAmount = 0;
    if ($purchase->discount_type === 'fixed') {
        $discountAmount = $purchase->discount_amount ?? 0;
    } elseif ($purchase->discount_type === 'percent' || $purchase->discount_type === 'percentage') {
        $discountAmount = ($calculatedSum * ($purchase->discount_amount ?? 0)) / 100;
    }
    
    if ($discountAmount > 0) {
        echo "  Minus Discount: -" . number_format($discountAmount, 2) . "\n";
    }
    
    $calculatedFinalTotal = $calculatedSum - $discountAmount;
    echo "  Calculated Final Total: " . number_format($calculatedFinalTotal, 2) . "\n";
    echo "\n";
    
    // Check for mismatch
    $difference = abs($purchase->final_total - $calculatedFinalTotal);
    
    if ($difference > 0.5) {
        echo "⚠️  MISMATCH DETECTED!\n";
        echo "  Stored Final Total:     " . number_format($purchase->final_total, 2) . "\n";
        echo "  Calculated Final Total: " . number_format($calculatedFinalTotal, 2) . "\n";
        echo "  DIFFERENCE:             " . number_format($difference, 2) . "\n";
        echo "\n";
        
        // Try to identify the issue
        echo "POSSIBLE CAUSES:\n";
        
        if ($purchase->total != $calculatedSum) {
            echo "  ✗ Stored 'total' ({$purchase->total}) doesn't match sum of products ({$calculatedSum})\n";
        }
        
        if (abs($purchase->final_total - $calculatedSum) < 0.5) {
            echo "  ✗ Final total seems to ignore discount calculation\n";
        }
        
        if (abs($purchase->final_total - ($calculatedSum * 1.33)) < 100) {
            echo "  ✗ Final total might have incorrect tax/multiplier applied\n";
        }
        
        $ratio = $purchase->final_total / $calculatedSum;
        if (abs($ratio - 1.33) < 0.01) {
            echo "  ✗ Final total is ~133% of product sum (possible double-counting or wrong tax)\n";
        } elseif (abs($ratio - 0.75) < 0.01) {
            echo "  ✗ Final total is ~75% of product sum (possible incorrect discount)\n";
        }
        
    } else {
        echo "✓ Totals match correctly!\n";
    }
    
    echo "\n";
}

echo "=================================================\n";
echo "Diagnostic check complete.\n";
echo "=================================================\n";
