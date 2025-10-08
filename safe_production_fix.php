<?php
/**
 * Safe Production Stock Fix Script
 * 
 * This script safely fixes negative stock calculation issues in production
 * by adding adjustment entries to balance stock_histories with actual stock.
 * 
 * Usage: php safe_production_fix.php
 */

echo "=== SAFE PRODUCTION STOCK FIX ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Validate environment
if (!file_exists('vendor/autoload.php')) {
    die("❌ Error: Laravel vendor directory not found. Run from Laravel root directory.\n");
}

if (!file_exists('.env')) {
    die("❌ Error: .env file not found. Ensure you're in the correct directory.\n");
}

require_once 'vendor/autoload.php';

try {
    // Bootstrap Laravel
    $app = require_once 'bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    // Test database connection
    $dbName = DB::connection()->getDatabaseName();
    echo "✅ Connected to database: {$dbName}\n";
    
} catch (Exception $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Validate table structure
try {
    $tables = ['products', 'batches', 'location_batches', 'stock_histories'];
    foreach ($tables as $table) {
        $exists = DB::select("SHOW TABLES LIKE '{$table}'");
        if (empty($exists)) {
            die("❌ Error: Table '{$table}' not found.\n");
        }
    }
    echo "✅ All required tables found\n";
    
    // Check if stock_type enum includes 'adjustment'
    $columns = DB::select("SHOW COLUMNS FROM stock_histories WHERE Field = 'stock_type'");
    if (empty($columns) || strpos($columns[0]->Type, 'adjustment') === false) {
        die("❌ Error: stock_histories.stock_type enum does not include 'adjustment'\n");
    }
    echo "✅ Stock type 'adjustment' is supported\n\n";
    
} catch (Exception $e) {
    die("❌ Table validation failed: " . $e->getMessage() . "\n");
}

echo "🔍 Analyzing products with stock discrepancies...\n";

$query = "
    SELECT 
        p.id as product_id,
        p.product_name,
        lb.id as location_batch_id,
        lb.qty as actual_qty,
        COALESCE(
            (SELECT SUM(
                CASE 
                    WHEN stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'adjustment') 
                    THEN quantity 
                    WHEN stock_type IN ('sale', 'purchase_return') 
                    THEN -quantity 
                    ELSE 0 
                END
            ) FROM stock_histories WHERE loc_batch_id = lb.id), 0
        ) as calculated_qty
    FROM products p
    JOIN batches b ON p.id = b.product_id
    JOIN location_batches lb ON b.id = lb.batch_id
    WHERE lb.qty > 0
    HAVING actual_qty != calculated_qty
    ORDER BY p.id
    LIMIT 100
";

$discrepancies = DB::select($query);

if (empty($discrepancies)) {
    echo "✅ No stock discrepancies found! All products are correctly balanced.\n";
    exit(0);
}

echo "Found " . count($discrepancies) . " products with discrepancies:\n\n";

$fixCount = 0;
$errorCount = 0;

foreach ($discrepancies as $item) {
    $difference = $item->actual_qty - $item->calculated_qty;
    
    echo "Product: {$item->product_name} (ID: {$item->product_id})\n";
    echo "  Location Batch ID: {$item->location_batch_id}\n";
    echo "  Actual: {$item->actual_qty}, Calculated: {$item->calculated_qty}\n";
    echo "  Need adjustment: {$difference}\n";
    
    try {
        // Check if adjustment already exists
        $existingAdjustment = DB::table('stock_histories')
            ->where('loc_batch_id', $item->location_batch_id)
            ->where('stock_type', 'adjustment')
            ->where('quantity', $difference)
            ->exists();
        
        if ($existingAdjustment) {
            echo "  ⚠️  Exact adjustment already exists\n\n";
            continue;
        }
        
        // Begin transaction
        DB::beginTransaction();
        
        // Insert adjustment record
        DB::table('stock_histories')->insert([
            'loc_batch_id' => $item->location_batch_id,
            'quantity' => $difference,
            'stock_type' => 'adjustment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Verify the fix
        $newCalculated = DB::select("
            SELECT COALESCE(SUM(
                CASE 
                    WHEN stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'adjustment') 
                    THEN quantity 
                    WHEN stock_type IN ('sale', 'purchase_return') 
                    THEN -quantity 
                    ELSE 0 
                END
            ), 0) as total
            FROM stock_histories 
            WHERE loc_batch_id = ?
        ", [$item->location_batch_id])[0]->total;
        
        if (abs($newCalculated - $item->actual_qty) < 0.0001) {
            DB::commit();
            echo "  ✅ Fixed successfully\n\n";
            $fixCount++;
        } else {
            DB::rollBack();
            echo "  ❌ Fix verification failed\n\n";
            $errorCount++;
        }
        
    } catch (Exception $e) {
        DB::rollBack();
        echo "  ❌ Error: " . $e->getMessage() . "\n\n";
        $errorCount++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Products found with discrepancies: " . count($discrepancies) . "\n";
echo "Successfully fixed: {$fixCount}\n";
echo "Errors encountered: {$errorCount}\n";

if ($fixCount > 0) {
    echo "\n✅ Stock discrepancies have been resolved!\n";
    echo "✅ Product Stock History pages should now show correct values.\n";
} else {
    echo "\n⚠️  No new fixes were applied.\n";
}

echo "\nDone.\n";