<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SalesProduct;
use App\Models\Batch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "=== FIXING SALE PRODUCT ID 1004 ===\n\n";

$saleProduct = SalesProduct::find(1004);

if (!$saleProduct) {
    echo "❌ Sale Product ID 1004 not found\n";
    exit;
}

echo "Product ID: {$saleProduct->product_id}\n";
echo "Location ID: {$saleProduct->location_id}\n";

$product = Product::find($saleProduct->product_id);
echo "Product Name: {$product->product_name}\n";
echo "Product Stock Alert: {$product->stock_alert}\n\n";

// Find all batches for this product
$batches = Batch::where('product_id', $saleProduct->product_id)->get();

echo "Batches found for this product: " . $batches->count() . "\n";

foreach ($batches as $batch) {
    echo "  - Batch ID: {$batch->id}, Batch No: {$batch->batch_no}, Created: {$batch->created_at}\n";
    
    // Check location_batches
    $locationBatches = DB::table('location_batches')
        ->where('batch_id', $batch->id)
        ->get();
    
    foreach ($locationBatches as $lb) {
        echo "    Location Batch: Location ID {$lb->location_id}, Qty: {$lb->qty}\n";
    }
}

// Use the first batch (FIFO)
if ($batches->count() > 0) {
    $firstBatch = $batches->sortBy('created_at')->first();
    
    echo "\nUpdating with Batch ID: {$firstBatch->id} (Batch No: {$firstBatch->batch_no})\n";
    
    $saleProduct->batch_id = $firstBatch->id;
    $saleProduct->save();
    
    echo "✅ Updated successfully!\n";
} else {
    echo "\n❌ No batches available to assign\n";
}
