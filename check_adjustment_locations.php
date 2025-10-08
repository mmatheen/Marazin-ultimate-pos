<?php

try {
    $pdo = new PDO("mysql:host=localhost;dbname=retailarb", 'root', '');
    
    echo "=== CHECKING ADJUSTMENT ENTRY LOCATIONS ===\n\n";
    
    $stmt = $pdo->prepare("
        SELECT sh.id, sh.stock_type, sh.quantity, lb.location_id, lb.id as loc_batch_id
        FROM stock_histories sh
        JOIN location_batches lb ON sh.loc_batch_id = lb.id
        JOIN batches b ON lb.batch_id = b.id
        WHERE b.product_id = 1 AND sh.stock_type = 'adjustment'
        ORDER BY sh.id
    ");
    $stmt->execute();
    $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($adjustments as $adj) {
        echo "Adjustment ID: {$adj['id']}, Quantity: {$adj['quantity']}, Location: {$adj['location_id']}, Loc Batch: {$adj['loc_batch_id']}\n";
    }
    
    echo "\nTotal adjustments found: " . count($adjustments) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

?>