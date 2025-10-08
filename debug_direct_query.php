<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DEBUGGING DIRECT QUERY ===\n\n";

try {
    $productId = 1;
    
    // Step 1: Check if adjustments exist at all
    $adjustmentsExist = DB::table('stock_histories')
        ->where('stock_type', 'adjustment')
        ->count();
    
    echo "Total adjustment records in database: {$adjustmentsExist}\n";
    
    // Step 2: Check adjustments for product 1
    $adjustmentsForProduct = DB::table('stock_histories as sh')
        ->join('location_batches as lb', 'sh.loc_batch_id', '=', 'lb.id')
        ->join('batches as b', 'lb.batch_id', '=', 'b.id')
        ->where('b.product_id', $productId)
        ->where('sh.stock_type', 'adjustment')
        ->select('sh.id', 'sh.quantity', 'lb.location_id')
        ->get();
    
    echo "\nAdjustment records for product {$productId}: " . $adjustmentsForProduct->count() . "\n";
    
    foreach ($adjustmentsForProduct as $adj) {
        echo "  - ID: {$adj->id}, Quantity: {$adj->quantity}, Location: {$adj->location_id}\n";
    }
    
    // Step 3: Get the sum
    $total = $adjustmentsForProduct->sum('quantity');
    echo "\nCalculated total: {$total}\n";
    
    // Step 4: Test the exact same query structure
    $directTotal = DB::table('stock_histories as sh')
        ->join('location_batches as lb', 'sh.loc_batch_id', '=', 'lb.id')
        ->join('batches as b', 'lb.batch_id', '=', 'b.id')
        ->where('b.product_id', $productId)
        ->where('sh.stock_type', 'adjustment')
        ->sum('sh.quantity');
    
    echo "Direct sum query result: {$directTotal}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>