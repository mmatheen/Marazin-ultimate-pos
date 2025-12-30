<?php
/**
 * Stock Diagnosis Script
 * Run with: php diagnose_stock_issue.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== STOCK DIAGNOSIS ===\n\n";

// Get the product that user mentioned (you'll need to replace with actual product_id)
echo "Enter Product ID to diagnose: ";
$productId = trim(fgets(STDIN));

$product = DB::table('products')->where('id', $productId)->first();
if (!$product) {
    echo "Product not found!\n";
    exit;
}

echo "Product: {$product->product_name} (SKU: {$product->sku})\n";
echo "Stock Alert Enabled: " . ($product->stock_alert ? 'YES' : 'NO (Unlimited)') . "\n\n";

// Get all batches for this product
$batches = DB::table('batches')->where('product_id', $productId)->get();
echo "Total Batches: " . count($batches) . "\n\n";

$grandTotal = 0;

foreach ($batches as $batch) {
    echo "--- Batch ID: {$batch->id} (Batch No: {$batch->batch_no}) ---\n";

    // Get location batches for this batch
    $locationBatches = DB::table('location_batches')
        ->where('batch_id', $batch->id)
        ->get();

    $batchTotal = 0;
    foreach ($locationBatches as $lb) {
        $location = DB::table('locations')->where('id', $lb->location_id)->first();
        $locationName = $location ? $location->name : 'Unknown';

        echo "  Location {$lb->location_id} ({$locationName}): {$lb->qty} units\n";
        $batchTotal += $lb->qty;
    }

    echo "  Batch Total: $batchTotal\n";
    $grandTotal += $batchTotal;

    // Check stock history for this batch
    $stockHistories = DB::table('stock_histories as sh')
        ->join('location_batches as lb', 'sh.loc_batch_id', '=', 'lb.id')
        ->where('lb.batch_id', $batch->id)
        ->orderBy('sh.created_at', 'desc')
        ->limit(5)
        ->select('sh.*', 'lb.location_id')
        ->get();

    if (count($stockHistories) > 0) {
        echo "  Recent Stock History:\n";
        foreach ($stockHistories as $history) {
            $type = $history->stock_type;
            $qty = $history->quantity;
            $date = $history->created_at;
            echo "    [{$date}] Loc {$history->location_id}: {$qty} ({$type})\n";
        }
    }

    // Check sales for this batch
    $sales = DB::table('sales_products')
        ->where('batch_id', $batch->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    if (count($sales) > 0) {
        echo "  Recent Sales:\n";
        foreach ($sales as $sale) {
            $saleInfo = DB::table('sales')->where('id', $sale->sale_id)->first();
            $status = $saleInfo ? $saleInfo->status : 'Unknown';
            echo "    Sale ID {$sale->sale_id} ({$status}): -{$sale->quantity} units @ {$sale->created_at}\n";
        }
    }

    echo "\n";
}

echo "=== GRAND TOTAL STOCK: $grandTotal ===\n";
echo "\nNOTE: If this total doesn't match what POS shows, there's a calculation bug!\n";
