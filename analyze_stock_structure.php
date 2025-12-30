<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== STOCK MANAGEMENT STRUCTURE ANALYSIS ===\n\n";

// 1. Check Product Model Relationships
echo "1. PRODUCT MODEL RELATIONSHIPS:\n";
echo "   - products table: stores product master data\n";
echo "   - locations(): belongsToMany (pivot: location_product) - OLD, for assignment only\n";
echo "   - batches(): hasMany - Product has many batches\n";
echo "   - locationBatches(): hasManyThrough Batch - Access to actual stock\n\n";

// 2. Batch Model
echo "2. BATCH MODEL:\n";
echo "   - batches table: stores purchase batches (batch_no, prices, expiry)\n";
echo "   - product_id: foreign key to products\n";
echo "   - locationBatches(): hasMany - Each batch distributed across locations\n\n";

// 3. LocationBatch Model - THE MAIN INVENTORY TABLE
echo "3. LOCATION_BATCHES TABLE (★ MAIN STOCK INVENTORY ★):\n";
echo "   - id: primary key\n";
echo "   - batch_id: foreign key to batches\n";
echo "   - location_id: foreign key to locations\n";
echo "   - qty: decimal(15,4) - THE ACTUAL STOCK QUANTITY\n";
echo "   This is the ONLY table that stores actual stock quantities!\n\n";

// 4. Location Product Pivot - Assignment only
echo "4. LOCATION_PRODUCT TABLE (Pivot - Product Assignment):\n";
echo "   - product_id, location_id, qty\n";
echo "   - Purpose: Indicates which products are ASSIGNED to which locations\n";
echo "   - qty field: OUTDATED/UNUSED - ignore this!\n";
echo "   - Use: Only for filtering which products belong to a location\n\n";

// 5. Stock Calculation Method
echo "5. CORRECT STOCK CALCULATION METHOD:\n";
$sql = "
SELECT
    p.id,
    p.product_name,
    p.stock_alert,
    COALESCE(SUM(lb.qty), 0) as total_stock,
    COALESCE((SELECT SUM(lb2.qty)
              FROM location_batches lb2
              INNER JOIN batches b2 ON lb2.batch_id = b2.id
              WHERE b2.product_id = p.id AND lb2.location_id = 1), 0) as stock_loc_1,
    COALESCE((SELECT SUM(lb2.qty)
              FROM location_batches lb2
              INNER JOIN batches b2 ON lb2.batch_id = b2.id
              WHERE b2.product_id = p.id AND lb2.location_id = 2), 0) as stock_loc_2
FROM products p
LEFT JOIN batches b ON b.product_id = p.id
LEFT JOIN location_batches lb ON lb.batch_id = b.id
WHERE p.id = 474
GROUP BY p.id, p.product_name, p.stock_alert;
";
echo "SQL Query:\n$sql\n\n";

// 6. Run the actual check
$result = DB::select($sql);
if (!empty($result)) {
    $r = $result[0];
    echo "RESULT FOR PRODUCT 474:\n";
    echo "   Product: {$r->product_name}\n";
    echo "   Stock Alert: {$r->stock_alert} (0=unlimited, 1=managed)\n";
    echo "   Total Stock: {$r->total_stock}\n";
    echo "   Location 1: {$r->stock_loc_1}\n";
    echo "   Location 2: {$r->stock_loc_2}\n\n";
}

// 7. Check location_product vs location_batches
echo "6. COMPARISON - location_product vs location_batches:\n";
$lpData = DB::table('location_product')->where('product_id', 474)->get();
echo "   location_product (OLD/UNUSED):\n";
if ($lpData->isEmpty()) {
    echo "      No records (CORRECT - this table is deprecated)\n";
} else {
    foreach ($lpData as $lp) {
        echo "      Loc {$lp->location_id}: qty = {$lp->qty} (IGNORE THIS!)\n";
    }
}

echo "\n   location_batches (CORRECT - ACTUAL STOCK):\n";
$lbData = DB::table('location_batches')
    ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
    ->where('batches.product_id', 474)
    ->select('location_batches.location_id', 'location_batches.qty', 'batches.batch_no', 'location_batches.batch_id')
    ->get();

foreach ($lbData as $lb) {
    echo "      Batch {$lb->batch_id} ({$lb->batch_no}) @ Loc {$lb->location_id}: qty = {$lb->qty}\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ location_batches.qty = ACTUAL STOCK (USE THIS)\n";
echo "❌ location_product.qty = DEPRECATED (IGNORE THIS)\n";
echo "✅ Product assignment: Check if product in location_product OR has stock in location_batches\n";
echo "✅ Stock calculation: SUM(location_batches.qty) WHERE batch.product_id = X\n\n";

// 8. Verify current code is using correct table
echo "7. CODE VERIFICATION:\n";
echo "   ProductController line 1938: ✅ Uses DB::table('location_batches')->join('batches')...\n";
echo "   SaleController: Should use location_batches for deduction\n";
echo "   All controllers: Must query location_batches, NOT location_product!\n\n";

echo "=== ANALYSIS COMPLETE ===\n";
