<?php

try {
    $pdo = new PDO("mysql:host=localhost;dbname=retailarb", 'root', '');
    
    echo "=== FORCE REFRESH ADJUSTMENT ENTRIES ===\n\n";
    
    // Step 1: Delete existing adjustment entries for Product ID 1
    echo "Step 1: Removing existing adjustment entries...\n";
    $stmt = $pdo->prepare("
        DELETE sh FROM stock_histories sh
        JOIN location_batches lb ON sh.loc_batch_id = lb.id
        JOIN batches b ON lb.batch_id = b.id
        WHERE b.product_id = 1 AND sh.stock_type = 'adjustment'
    ");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    echo "Deleted {$deletedCount} adjustment entries.\n\n";
    
    // Step 2: Recalculate discrepancies
    echo "Step 2: Recalculating discrepancies...\n";
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
    
    $adjustmentsNeeded = [];
    
    foreach ($locationBatches as $batch) {
        $calculatedStock = $batch['total_in'] - $batch['total_out'];
        $actualStock = floatval($batch['actual_qty']);
        $discrepancy = $actualStock - $calculatedStock;
        
        echo "Location Batch {$batch['loc_batch_id']} ({$batch['batch_no']}):\n";
        echo "  Actual: {$actualStock}, Calculated: {$calculatedStock}, Discrepancy: {$discrepancy}\n";
        
        if ($discrepancy != 0) {
            $adjustmentsNeeded[] = [
                'loc_batch_id' => $batch['loc_batch_id'],
                'adjustment' => $discrepancy
            ];
        }
    }
    
    // Step 3: Create new adjustment entries
    echo "\nStep 3: Creating new adjustment entries...\n";
    
    foreach ($adjustmentsNeeded as $adjustment) {
        $stmt = $pdo->prepare("
            INSERT INTO stock_histories (loc_batch_id, quantity, stock_type, created_at, updated_at)
            VALUES (?, ?, 'adjustment', NOW(), NOW())
        ");
        
        if ($stmt->execute([$adjustment['loc_batch_id'], $adjustment['adjustment']])) {
            echo "Created adjustment: {$adjustment['adjustment']} for loc_batch_id {$adjustment['loc_batch_id']}\n";
        }
    }
    
    // Step 4: Verify the fix
    echo "\nStep 4: Verifying the fix...\n";
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
    echo "Expected: 8\n";
    echo "Match: " . ($newCalculatedStock == 8 ? "✅ YES" : "❌ NO") . "\n\n";
    
    echo "🔄 Refresh the Product Stock History page now!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>