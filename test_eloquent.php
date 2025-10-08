<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== TESTING EXACT ELOQUENT QUERY ===\n\n";

$product = Product::with([
    'locationBatches.stockHistories'
])->find(1);

$stockHistories = $product->locationBatches->flatMap(function ($locBatch) {
    return $locBatch->stockHistories;
});

echo "Total stock histories found: " . $stockHistories->count() . "\n";

$stockTypeSums = $stockHistories->groupBy('stock_type')->map(function ($group) {
    return $group->sum('quantity');
});

echo "Stock Type Sums:\n";
foreach ($stockTypeSums as $type => $total) {
    echo "- {$type}: {$total}\n";
}

$adjustmentValue = $stockTypeSums['adjustment'] ?? 0;
echo "\nAdjustment value: {$adjustmentValue}\n";

if ($adjustmentValue == 18) {
    echo "✅ Eloquent query is working correctly!\n";
} else {
    echo "❌ Eloquent query is not finding adjustments!\n";
}
?>