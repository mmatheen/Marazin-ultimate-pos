<?php

require 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\LocationBatch;
use App\Models\Batch;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "=== INVESTIGATING PURCHASE EDIT ISSUES ===\n";

// Find PUR-007
$purchase = Purchase::where('purchase_no', 'PUR-007')->first();
if (!$purchase) {
    echo "❌ Purchase PUR-007 not found! Let me check recent purchases...\n";
    
    $recentPurchases = Purchase::orderBy('created_at', 'desc')->limit(10)->get();
    echo "Recent purchases:\n";
    foreach ($recentPurchases as $p) {
        echo "- ID: {$p->id}, No: {$p->purchase_no}, Amount: Rs " . number_format($p->total_amount, 2) . ", Date: {$p->created_at}\n";
    }
    exit;
}

echo "✅ Found Purchase PUR-007\n";
echo "Purchase ID: {$purchase->id}\n";
echo "Purchase No: {$purchase->purchase_no}\n";
echo "Supplier ID: {$purchase->supplier_id}\n";
echo "Total Amount: Rs " . number_format($purchase->total_amount, 2) . "\n";
echo "Status: {$purchase->status}\n";
echo "Created: {$purchase->created_at}\n";
echo "Updated: {$purchase->updated_at}\n";

// Check if purchase was edited (updated != created)
$wasEdited = $purchase->updated_at != $purchase->created_at;
echo "Was edited: " . ($wasEdited ? "YES" : "NO") . "\n";

echo "\n📦 PRODUCTS IN PURCHASE:\n";
$products = PurchaseProduct::where('purchase_id', $purchase->id)->get();

foreach ($products as $purchaseProduct) {
    $product = Product::find($purchaseProduct->product_id);
    echo sprintf(
        "Product ID: %d (%s) | Qty: %d | Unit Cost: Rs %s | Total: Rs %s\n",
        $purchaseProduct->product_id,
        $product->product_name ?? 'Unknown',
        $purchaseProduct->quantity,
        number_format($purchaseProduct->unit_cost, 2),
        number_format($purchaseProduct->total_cost, 2)
    );
    
    // Check stock for this product
    echo "  📊 Current Stock Analysis:\n";
    
    // Get all batches for this product from this purchase
    $batches = Batch::where('product_id', $purchaseProduct->product_id)
        ->where('purchase_id', $purchase->id)
        ->get();
        
    foreach ($batches as $batch) {
        echo "    Batch ID: {$batch->id} | Batch No: {$batch->batch_no} | Initial Qty: {$batch->qty}\n";
        
        // Check location batches
        $locationBatches = LocationBatch::where('batch_id', $batch->id)->get();
        foreach ($locationBatches as $lb) {
            echo "      Location {$lb->location_id}: {$lb->qty} units\n";
        }
    }
    
    // Get total current stock for this product
    $totalStock = DB::table('location_batches')
        ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
        ->where('batches.product_id', $purchaseProduct->product_id)
        ->sum('location_batches.qty');
        
    echo "    Total Current Stock: {$totalStock} units\n";
    
    if ($totalStock == 0 && $purchaseProduct->quantity > 0) {
        echo "    🚨 PROBLEM: Stock is 0 but purchase shows {$purchaseProduct->quantity} units!\n";
    }
}

echo "\n💰 PAYMENTS FOR PURCHASE:\n";
$payments = Payment::where('reference_id', $purchase->id)
    ->where('payment_type', 'purchase')
    ->get();

if ($payments->count() > 0) {
    foreach ($payments as $payment) {
        echo sprintf(
            "Payment ID: %d | Amount: Rs %s | Method: %s | Status: %s | Date: %s\n",
            $payment->id,
            number_format($payment->amount, 2),
            $payment->payment_method,
            $payment->payment_status ?? 'N/A',
            $payment->payment_date
        );
    }
} else {
    echo "No payments found for this purchase\n";
}

// Check for potential issues
echo "\n🔍 ISSUE ANALYSIS:\n";

// Check if total amount matches sum of product costs
$calculatedTotal = $products->sum('total_cost');
$purchaseTotal = $purchase->total_amount;
$amountDiff = abs($calculatedTotal - $purchaseTotal);

if ($amountDiff > 0.01) {
    echo "⚠️ Amount mismatch:\n";
    echo "  Purchase total: Rs " . number_format($purchaseTotal, 2) . "\n";
    echo "  Calculated total: Rs " . number_format($calculatedTotal, 2) . "\n";
    echo "  Difference: Rs " . number_format($amountDiff, 2) . "\n";
}

// Check for duplicate batches (indicates double stock addition)
foreach ($products as $purchaseProduct) {
    $batchCount = Batch::where('product_id', $purchaseProduct->product_id)
        ->where('purchase_id', $purchase->id)
        ->count();
        
    if ($batchCount > 1) {
        echo "⚠️ Multiple batches for product {$purchaseProduct->product_id} (possible duplicate stock)\n";
    }
}

echo "\n=== INVESTIGATION COMPLETE ===\n";
echo "Issues found:\n";
echo "1. Check if stock is correctly reflected\n";
echo "2. Verify no duplicate batches exist\n";
echo "3. Confirm payment amounts match\n";
echo "4. Supplier ledger consistency\n";