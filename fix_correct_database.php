<?php

try {
    // Connect to the CORRECT database that Laravel uses
    $pdo = new PDO("mysql:host=localhost;dbname=marazin_pos_db", 'root', '');
    
    echo "=== ADDING ADJUSTMENTS TO CORRECT DATABASE (marazin_pos_db) ===\n\n";
    
    // Step 1: Check if Product ID 1 exists in this database
    $stmt = $pdo->prepare("SELECT id, product_name FROM products WHERE id = 1");
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo "❌ Product ID 1 not found in marazin_pos_db\n";
        exit;
    }
    
    echo "✅ Found product: {$product['product_name']}\n\n";
    
    // Step 2: Check current stock discrepancy
    echo "Step 1: Checking current stock discrepancy...\n";
    $stmt = $pdo->prepare("
        SELECT 
            lb.id as loc_batch_id,
            lb.qty as actual_qty,
            b.batch_no,
            COALESCE(SUM(CASE 
                WHEN sh.stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'sale_reversal', 'transfer_in') 
                THEN sh.quantity 
                ELSE 0 
            END), 0) as total_in,
            COALESCE(SUM(CASE 
                WHEN sh.stock_type IN ('sale', 'purchase_return', 'purchase_return_reversal', 'transfer_out') 
                THEN ABS(sh.quantity) 
                ELSE 0 
            END), 0) as total_out
        FROM location_batches lb
        JOIN batches b ON lb.batch_id = b.id
        LEFT JOIN stock_histories sh ON sh.loc_batch_id = lb.id
        WHERE b.product_id = 1
        GROUP BY lb.id
        ORDER BY lb.id
    ");
    $stmt->execute();
    $locationBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($locationBatches)) {
        echo "❌ No location batches found for Product ID 1\n";
        exit;
    }
    
    $adjustmentsNeeded = [];
    $totalCurrentStock = 0;
    $totalActualStock = 0;
    
    foreach ($locationBatches as $batch) {
        $calculatedStock = $batch['total_in'] - $batch['total_out'];
        $actualStock = floatval($batch['actual_qty']);
        $discrepancy = $actualStock - $calculatedStock;
        
        $totalCurrentStock += $calculatedStock;
        $totalActualStock += $actualStock;
        
        echo "Location Batch {$batch['loc_batch_id']} ({$batch['batch_no']}):\n";
        echo "  Actual: {$actualStock}, Calculated: {$calculatedStock}, Discrepancy: {$discrepancy}\n";
        
        if ($discrepancy != 0) {
            $adjustmentsNeeded[] = [
                'loc_batch_id' => $batch['loc_batch_id'],
                'adjustment' => $discrepancy
            ];
        }
    }
    
    echo "\nTotals:\n";
    echo "Current calculated stock: {$totalCurrentStock}\n";
    echo "Actual stock should be: {$totalActualStock}\n";
    echo "Total adjustment needed: " . ($totalActualStock - $totalCurrentStock) . "\n\n";
    
    if (empty($adjustmentsNeeded)) {
        echo "✅ No adjustments needed - stock is already correct!\n";
        exit;
    }
    
    // Step 3: Create adjustment entries
    echo "Step 2: Creating adjustment entries in marazin_pos_db...\n";
    
    foreach ($adjustmentsNeeded as $adjustment) {
        $stmt = $pdo->prepare("
            INSERT INTO stock_histories (loc_batch_id, quantity, stock_type, created_at, updated_at)
            VALUES (?, ?, 'adjustment', NOW(), NOW())
        ");
        
        if ($stmt->execute([$adjustment['loc_batch_id'], $adjustment['adjustment']])) {
            echo "✅ Created adjustment: {$adjustment['adjustment']} for loc_batch_id {$adjustment['loc_batch_id']}\n";
        } else {
            echo "❌ Failed to create adjustment for loc_batch_id {$adjustment['loc_batch_id']}\n";
        }
    }
    
    // Step 4: Verify the fix
    echo "\nStep 3: Verifying the fix...\n";
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE 
                WHEN sh.stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'sale_reversal', 'transfer_in', 'adjustment') 
                THEN sh.quantity 
                ELSE 0 
            END), 0) as total_in,
            COALESCE(SUM(CASE 
                WHEN sh.stock_type IN ('sale', 'purchase_return', 'purchase_return_reversal', 'transfer_out') 
                THEN ABS(sh.quantity) 
                ELSE 0 
            END), 0) as total_out
        FROM stock_histories sh
        JOIN location_batches lb ON sh.loc_batch_id = lb.id
        JOIN batches b ON lb.batch_id = b.id
        WHERE b.product_id = 1
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $newCalculatedStock = $result['total_in'] - $result['total_out'];
    
    echo "New calculated stock: {$newCalculatedStock}\n";
    echo "Expected: {$totalActualStock}\n";
    echo "Match: " . ($newCalculatedStock == $totalActualStock ? "✅ YES" : "❌ NO") . "\n\n";
    
    echo "🎉 Adjustments added to the correct database!\n";
    echo "🔄 Please refresh the Product Stock History page now!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>