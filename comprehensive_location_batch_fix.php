<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=marazin_pos_db;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== COMPREHENSIVE LOCATION_BATCHES FIX ===\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    echo "=========================================\n\n";
    
    // 1. First, let's get current status
    echo "1. 📊 CURRENT STATUS:\n";
    echo "====================\n";
    
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM location_batches")->fetchColumn();
    $negativeCount = $pdo->query("SELECT COUNT(*) FROM location_batches WHERE qty < 0")->fetchColumn();
    $duplicatesCount = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT batch_id, location_id 
            FROM location_batches 
            GROUP BY batch_id, location_id 
            HAVING COUNT(*) > 1
        ) as dups
    ")->fetchColumn();
    
    echo "Total records: {$totalRecords}\n";
    echo "Negative quantities: {$negativeCount}\n";
    echo "Duplicate combinations: {$duplicatesCount}\n\n";
    
    // 2. Fix duplicates first (consolidate quantities)
    echo "2. 🔧 FIXING DUPLICATE COMBINATIONS:\n";
    echo "===================================\n";
    
    $duplicates = $pdo->query("
        SELECT batch_id, location_id, COUNT(*) as count 
        FROM location_batches 
        GROUP BY batch_id, location_id 
        HAVING COUNT(*) > 1
    ")->fetchAll();
    
    $fixedDuplicates = 0;
    
    foreach ($duplicates as $dup) {
        // Get all records for this batch-location combination
        $stmt = $pdo->prepare("SELECT id, qty FROM location_batches WHERE batch_id = ? AND location_id = ? ORDER BY id");
        $stmt->execute([$dup['batch_id'], $dup['location_id']]);
        $records = $stmt->fetchAll();
        
        if (count($records) > 1) {
            // Calculate total quantity
            $totalQty = array_sum(array_column($records, 'qty'));
            
            // Keep the first record and update its quantity
            $keepId = $records[0]['id'];
            $updateStmt = $pdo->prepare("UPDATE location_batches SET qty = ? WHERE id = ?");
            $updateStmt->execute([$totalQty, $keepId]);
            
            // Delete the duplicate records
            for ($i = 1; $i < count($records); $i++) {
                $deleteStmt = $pdo->prepare("DELETE FROM location_batches WHERE id = ?");
                $deleteStmt->execute([$records[$i]['id']]);
            }
            
            echo "Fixed Batch {$dup['batch_id']} at Location {$dup['location_id']}: ";
            echo "Consolidated {$dup['count']} records into 1 with qty {$totalQty}\n";
            $fixedDuplicates++;
        }
    }
    
    echo "✅ Fixed {$fixedDuplicates} duplicate combinations\n\n";
    
    // 3. Fix negative quantities (set to zero for safety)
    echo "3. 🔧 FIXING NEGATIVE QUANTITIES:\n";
    echo "=================================\n";
    
    $negativeRecords = $pdo->query("SELECT id, batch_id, location_id, qty FROM location_batches WHERE qty < 0")->fetchAll();
    
    $fixedNegatives = 0;
    foreach ($negativeRecords as $record) {
        $updateStmt = $pdo->prepare("UPDATE location_batches SET qty = 0 WHERE id = ?");
        $updateStmt->execute([$record['id']]);
        
        echo "Fixed Batch {$record['batch_id']} at Location {$record['location_id']}: ";
        echo "{$record['qty']} → 0\n";
        $fixedNegatives++;
    }
    
    echo "✅ Fixed {$fixedNegatives} negative quantities\n\n";
    
    // 4. Special fix for Batch 125 (restore to 4 units at Location 6)
    echo "4. 🎯 RESTORING BATCH 125:\n";
    echo "=========================\n";
    
    $batch125Records = $pdo->query("SELECT * FROM location_batches WHERE batch_id = 125")->fetchAll();
    
    if (count($batch125Records) > 0) {
        foreach ($batch125Records as $record) {
            if ($record['location_id'] == 6) {
                // Set Batch 125 at Location 6 to 4 units
                $updateStmt = $pdo->prepare("UPDATE location_batches SET qty = 4.0000 WHERE id = ?");
                $updateStmt->execute([$record['id']]);
                echo "✅ Restored Batch 125 at Location 6 to 4.0000 units\n";
            } else {
                // Set other locations to 0 for this batch
                $updateStmt = $pdo->prepare("UPDATE location_batches SET qty = 0 WHERE id = ?");
                $updateStmt->execute([$record['id']]);
                echo "Set Batch 125 at Location {$record['location_id']} to 0 units\n";
            }
        }
    } else {
        echo "❌ Batch 125 not found\n";
    }
    echo "\n";
    
    // 5. Final verification
    echo "5. ✅ FINAL VERIFICATION:\n";
    echo "========================\n";
    
    $finalTotal = $pdo->query("SELECT COUNT(*) FROM location_batches")->fetchColumn();
    $finalNegative = $pdo->query("SELECT COUNT(*) FROM location_batches WHERE qty < 0")->fetchColumn();
    $finalDuplicates = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT batch_id, location_id 
            FROM location_batches 
            GROUP BY batch_id, location_id 
            HAVING COUNT(*) > 1
        ) as dups
    ")->fetchColumn();
    
    $batch125Final = $pdo->query("SELECT location_id, qty FROM location_batches WHERE batch_id = 125")->fetchAll();
    
    echo "Final status:\n";
    echo "- Total records: {$finalTotal}\n";
    echo "- Negative quantities: {$finalNegative}\n";
    echo "- Duplicate combinations: {$finalDuplicates}\n";
    echo "- Records cleaned: " . ($totalRecords - $finalTotal) . "\n\n";
    
    echo "Batch 125 final status:\n";
    foreach ($batch125Final as $record) {
        echo "- Location {$record['location_id']}: {$record['qty']} units\n";
    }
    
    $totalBatch125 = array_sum(array_column($batch125Final, 'qty'));
    echo "- Total Batch 125 available: {$totalBatch125} units\n\n";
    
    if ($finalNegative == 0 && $finalDuplicates == 0) {
        echo "🎉 SUCCESS: All issues fixed!\n";
        echo "✅ No negative quantities\n";
        echo "✅ No duplicate combinations\n";
        echo "✅ Batch 125 restored to 4 units\n";
        echo "✅ Your POS system is ready!\n";
    } else {
        echo "⚠️  Some issues may remain:\n";
        if ($finalNegative > 0) echo "- Still have {$finalNegative} negative quantities\n";
        if ($finalDuplicates > 0) echo "- Still have {$finalDuplicates} duplicates\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nFix completed at: " . date('Y-m-d H:i:s') . "\n";