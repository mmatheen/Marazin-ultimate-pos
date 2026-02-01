<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\Ledger;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════\n";
echo "  REVERTING WRONG FIX & APPLYING CORRECT FIX\n";
echo "  Sale ID 923\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$sale = Sale::withoutGlobalScopes()->find(923);

if (!$sale) {
    echo "❌ Sale 923 not found!\n";
    exit;
}

echo "Current Status:\n";
echo "  Sale Subtotal: Rs " . number_format($sale->subtotal, 2) . "\n";
echo "  Sale Final Total: Rs " . number_format($sale->final_total, 2) . "\n\n";

$salesProducts = SalesProduct::where('sale_id', 923)->get();

echo "Sales Products:\n";
echo "─────────────────────────────────────────────────────────\n";

$correctSubtotal = 0;

foreach ($salesProducts as $sp) {
    $product = \App\Models\Product::find($sp->product_id);
    $batch = \App\Models\Batch::find($sp->batch_id);
    
    $subtotalForProduct = $sp->price * $sp->quantity;
    $correctSubtotal += $subtotalForProduct;
    
    $mrpPerUnit = $batch ? $batch->max_retail_price : 0;
    $discountPerUnit = $mrpPerUnit - $sp->price;
    
    echo "Product: " . ($product ? $product->product_name : "ID {$sp->product_id}") . "\n";
    echo "  Qty: {$sp->quantity} × Price: Rs {$sp->price} = Rs " . number_format($subtotalForProduct, 2) . "\n";
    echo "  Current discount_amount: Rs {$sp->discount_amount}\n";
    echo "  Correct discount_amount (per unit): Rs " . number_format($discountPerUnit, 2) . "\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "CORRECT VALUES:\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "Correct Subtotal (sum of price × qty): Rs " . number_format($correctSubtotal, 2) . "\n";
echo "Current Sale Subtotal in DB: Rs " . number_format($sale->subtotal, 2) . "\n";
echo "─────────────────────────────────────────────────────────\n";

if (abs($sale->subtotal - $correctSubtotal) < 0.01) {
    echo "✅ Sale subtotal is already correct!\n";
    exit;
}

echo "⚠️ Sale subtotal needs fixing!\n";
echo "Difference: Rs " . number_format($sale->subtotal - $correctSubtotal, 2) . "\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "FIXES TO APPLY:\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "1. Revert discount_amount to PER-UNIT values\n";
echo "2. Fix sale.subtotal and sale.final_total\n";
echo "3. Fix ledger entry\n";
echo "4. Recalculate customer balance\n\n";

echo "Type 'yes' to proceed: ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n❌ Fix cancelled.\n";
    exit;
}

echo "\n🔧 APPLYING CORRECT FIX...\n\n";

DB::beginTransaction();

try {
    // Step 1: Revert discount_amount to per-unit values
    echo "Step 1: Reverting discount_amount to PER-UNIT values...\n";
    
    foreach ($salesProducts as $sp) {
        $batch = \App\Models\Batch::find($sp->batch_id);
        if ($batch) {
            $discountPerUnit = $batch->max_retail_price - $sp->price;
            
            SalesProduct::where('id', $sp->id)->update([
                'discount_amount' => $discountPerUnit
            ]);
            
            echo "  ✅ Product ID {$sp->id}: discount_amount set to Rs " . number_format($discountPerUnit, 2) . " (per unit)\n";
        }
    }
    
    // Step 2: Fix sale totals (only subtotal and final_total)
    echo "\nStep 2: Fixing sale totals...\n";
    echo "  Old Final Total: Rs " . number_format($sale->final_total, 2) . "\n";
    echo "  New Final Total: Rs " . number_format($correctSubtotal, 2) . "\n";
    
    $newTotalDue = max(0, $correctSubtotal - $sale->total_paid);
    
    $sale->update([
        'subtotal' => $correctSubtotal,
        'final_total' => $correctSubtotal,
        'total_due' => $newTotalDue,
        'payment_status' => $newTotalDue > 0 ? ($sale->total_paid > 0 ? 'Partial' : 'Due') : 'Paid'
    ]);
    
    echo "  ✅ Sale totals updated\n";
    
    // Step 3: Fix ledger
    if ($sale->customer_id != 1) {
        echo "\nStep 3: Fixing ledger entry...\n";
        
        $ledgerEntry = Ledger::where('transaction_type', 'sale')
            ->where('contact_id', $sale->customer_id)
            ->where('contact_type', 'customer')
            ->where('reference_no', $sale->invoice_no)
            ->where('status', 'active')
            ->first();
        
        if ($ledgerEntry) {
            $ledgerEntry->update([
                'debit' => $correctSubtotal,
                'notes' => ($ledgerEntry->notes ?? '') . " [CORRECTED " . now()->format('Y-m-d H:i:s') . "]"
            ]);
            echo "  ✅ Ledger entry updated\n";
        } else {
            echo "  ⚠️ No ledger entry found\n";
        }
        
        // Step 4: Recalculate customer balance
        echo "\nStep 4: Recalculating customer balance...\n";
        $customer = Customer::withoutGlobalScopes()->find($sale->customer_id);
        
        if ($customer && method_exists($customer, 'calculateBalanceFromLedger')) {
            $newBalance = $customer->calculateBalanceFromLedger();
            $customer->update(['opening_balance' => $newBalance]);
            echo "  ✅ Customer balance: Rs " . number_format($newBalance, 2) . "\n";
        }
    } else {
        echo "\nStep 3-4: Skipped (Walk-In customer)\n";
    }
    
    DB::commit();
    
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "✅ CORRECT FIX APPLIED SUCCESSFULLY!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    echo "FINAL CORRECTED VALUES:\n";
    echo "  Sale ID: 923\n";
    echo "  Subtotal: Rs " . number_format($correctSubtotal, 2) . "\n";
    echo "  Final Total: Rs " . number_format($correctSubtotal, 2) . "\n";
    echo "  Discount amounts: Stored as PER-UNIT values (for receipt)\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "All changes rolled back.\n";
}
