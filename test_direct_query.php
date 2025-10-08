<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING DIRECT ADJUSTMENT QUERY ===\n\n";

try {
    $productId = 1;
    $locationId = 3;
    
    $adjustmentTotal = DB::table('stock_histories as sh')
        ->join('location_batches as lb', 'sh.loc_batch_id', '=', 'lb.id')
        ->join('batches as b', 'lb.batch_id', '=', 'b.id')
        ->where('b.product_id', $productId)
        ->where('sh.stock_type', 'adjustment')
        ->when($locationId, function ($query) use ($locationId) {
            return $query->where('lb.location_id', $locationId);
        })
        ->sum('sh.quantity');
    
    echo "Direct query adjustment total: {$adjustmentTotal}\n";
    echo "Expected: 18\n";
    echo "Match: " . ($adjustmentTotal == 18 ? "✅ YES" : "❌ NO") . "\n";
    
    // Also test without location filter
    $adjustmentTotalAll = DB::table('stock_histories as sh')
        ->join('location_batches as lb', 'sh.loc_batch_id', '=', 'lb.id')
        ->join('batches as b', 'lb.batch_id', '=', 'b.id')
        ->where('b.product_id', $productId)
        ->where('sh.stock_type', 'adjustment')
        ->sum('sh.quantity');
    
    echo "\nWithout location filter: {$adjustmentTotalAll}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>