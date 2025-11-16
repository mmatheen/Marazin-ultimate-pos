<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Batch;
use App\Models\LocationBatch;
use Illuminate\Support\Facades\DB;

echo "🔍 Testing Purchase Edit Functionality\n\n";

// Find the purchase PUR-007 that the user mentioned
$purchase = Purchase::where('reference_no', 'PUR-007')->first();

if (!$purchase) {
    echo "❌ Purchase PUR-007 not found\n";
    
    // List recent purchases for reference
    echo "\n📋 Recent purchases:\n";
    $recentPurchases = Purchase::orderBy('id', 'desc')->take(5)->get(['id', 'reference_no', 'final_total', 'created_at']);
    foreach ($recentPurchases as $p) {
        echo "- {$p->reference_no}: Rs {$p->final_total} (ID: {$p->id})\n";
    }
    exit;
}

echo "✅ Found Purchase: {$purchase->reference_no}\n";
echo "📊 Current Final Total: Rs {$purchase->final_total}\n";
echo "📅 Purchase Date: {$purchase->purchase_date}\n";
echo "🏪 Supplier ID: {$purchase->supplier_id}\n\n";

// Get products in this purchase
$products = PurchaseProduct::where('purchase_id', $purchase->id)->get();
echo "📦 Products in purchase ({$products->count()}):\n";

foreach ($products as $product) {
    $batch = Batch::find($product->batch_id);
    $locationBatches = LocationBatch::where('batch_id', $product->batch_id)->get();
    
    echo "  - Product ID: {$product->product_id}\n";
    echo "    Quantity: {$product->quantity}\n";
    echo "    Unit Cost: Rs {$product->unit_cost}\n";
    echo "    Total: Rs {$product->total}\n";
    echo "    Batch ID: {$product->batch_id}\n";
    
    if ($batch) {
        echo "    Batch Qty: {$batch->qty}\n";
        echo "    Batch Retail Price: Rs {$batch->retail_price}\n";
    }
    
    if ($locationBatches->count() > 0) {
        echo "    Location Stock:\n";
        foreach ($locationBatches as $locBatch) {
            echo "      Location {$locBatch->location_id}: {$locBatch->qty} units\n";
        }
    }
    echo "\n";
}

echo "✨ Test completed. This shows the current state of purchase PUR-007.\n";
echo "To test the edit functionality, you would need to:\n";
echo "1. Change a product quantity in the UI (e.g., from 10 to 20)\n";
echo "2. Submit the form\n";
echo "3. Run this script again to see if stock was correctly updated\n";

?>