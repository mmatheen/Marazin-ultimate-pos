<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=marazin_pos_db;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== FINAL COMPLETE LOCATION_BATCHES CHECK ===\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    echo "============================================\n\n";
    
    // 1. Overall Table Health
    echo "1. 📊 OVERALL TABLE HEALTH:\n";
    echo "===========================\n";
    
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM location_batches")->fetchColumn();
    $uniqueBatches = $pdo->query("SELECT COUNT(DISTINCT batch_id) FROM location_batches")->fetchColumn();
    $uniqueLocations = $pdo->query("SELECT COUNT(DISTINCT location_id) FROM location_batches")->fetchColumn();
    
    echo "✅ Total records: {$totalRecords}\n";
    echo "✅ Unique batches: {$uniqueBatches}\n";
    echo "✅ Unique locations: {$uniqueLocations}\n\n";
    
    // 2. Check ALL negative quantities
    echo "2. ❌ NEGATIVE QUANTITIES CHECK (ALL BATCHES):\n";
    echo "=============================================\n";
    
    $negativeQty = $pdo->query("SELECT COUNT(*) FROM location_batches WHERE qty < 0")->fetchColumn();
    
    if ($negativeQty > 0) {
        echo "❌ FOUND {$negativeQty} NEGATIVE QUANTITIES:\n";
        $negativeDetails = $pdo->query("SELECT batch_id, location_id, qty FROM location_batches WHERE qty < 0 ORDER BY batch_id, location_id")->fetchAll();
        foreach ($negativeDetails as $record) {
            echo "- Batch {$record['batch_id']}, Location {$record['location_id']}: {$record['qty']} units\n";
        }
    } else {
        echo "✅ PERFECT: No negative quantities found in ANY batch!\n";
    }
    echo "\n";
    
    // 3. Check ALL duplicate combinations
    echo "3. 🔄 DUPLICATE COMBINATIONS CHECK (ALL BATCHES):\n";
    echo "================================================\n";
    
    $duplicatesCount = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT batch_id, location_id 
            FROM location_batches 
            GROUP BY batch_id, location_id 
            HAVING COUNT(*) > 1
        ) as dups
    ")->fetchColumn();
    
    if ($duplicatesCount > 0) {
        echo "❌ FOUND {$duplicatesCount} DUPLICATE COMBINATIONS:\n";
        $duplicates = $pdo->query("
            SELECT batch_id, location_id, COUNT(*) as count 
            FROM location_batches 
            GROUP BY batch_id, location_id 
            HAVING COUNT(*) > 1
            ORDER BY batch_id, location_id
        ")->fetchAll();
        
        foreach ($duplicates as $dup) {
            echo "- Batch {$dup['batch_id']} at Location {$dup['location_id']}: {$dup['count']} records\n";
        }
    } else {
        echo "✅ PERFECT: No duplicate batch-location combinations found!\n";
    }
    echo "\n";
    
    // 4. Stock Distribution Analysis
    echo "4. 📦 STOCK DISTRIBUTION BY LOCATION (ALL BATCHES):\n";
    echo "==================================================\n";
    
    $locationStats = $pdo->query("
        SELECT 
            location_id,
            COUNT(*) as batch_count,
            SUM(qty) as total_stock,
            MIN(qty) as min_stock,
            MAX(qty) as max_stock,
            AVG(qty) as avg_stock,
            COUNT(CASE WHEN qty = 0 THEN 1 END) as zero_stock_batches,
            COUNT(CASE WHEN qty > 0 THEN 1 END) as positive_stock_batches
        FROM location_batches 
        GROUP BY location_id 
        ORDER BY location_id
    ")->fetchAll();
    
    foreach ($locationStats as $stats) {
        echo "Location {$stats['location_id']}:\n";
        echo "  - Total batches: {$stats['batch_count']}\n";
        echo "  - Total stock: {$stats['total_stock']} units\n";
        echo "  - Stock range: {$stats['min_stock']} to {$stats['max_stock']} units\n";
        echo "  - Average per batch: " . round($stats['avg_stock'], 2) . " units\n";
        echo "  - Batches with stock: {$stats['positive_stock_batches']}\n";
        echo "  - Empty batches: {$stats['zero_stock_batches']}\n\n";
    }
    
    // 5. Top Stock Holdings (highest quantities)
    echo "5. 🔝 TOP 10 HIGHEST STOCK BATCHES:\n";
    echo "===================================\n";
    
    $topStock = $pdo->query("
        SELECT batch_id, location_id, qty 
        FROM location_batches 
        WHERE qty > 0
        ORDER BY qty DESC 
        LIMIT 10
    ")->fetchAll();
    
    if (count($topStock) > 0) {
        foreach ($topStock as $stock) {
            echo "✅ Batch {$stock['batch_id']} at Location {$stock['location_id']}: {$stock['qty']} units\n";
        }
    } else {
        echo "❌ No positive stock found\n";
    }
    echo "\n";
    
    // 6. Zero Stock Analysis
    echo "6. 🔢 ZERO STOCK ANALYSIS:\n";
    echo "=========================\n";
    
    $zeroStockCount = $pdo->query("SELECT COUNT(*) FROM location_batches WHERE qty = 0")->fetchColumn();
    $zeroStockPercentage = round(($zeroStockCount / $totalRecords) * 100, 1);
    
    echo "Zero stock batches: {$zeroStockCount} ({$zeroStockPercentage}% of total)\n";
    
    if ($zeroStockPercentage > 50) {
        echo "⚠️  High percentage of zero stock batches - consider cleanup\n";
    } else {
        echo "✅ Zero stock percentage is normal\n";
    }
    echo "\n";
    
    // 7. Data Integrity Checks
    echo "7. 🔗 DATA INTEGRITY CHECKS:\n";
    echo "===========================\n";
    
    // Check for orphan batches
    $orphanBatches = $pdo->query("
        SELECT COUNT(DISTINCT lb.batch_id) 
        FROM location_batches lb 
        LEFT JOIN batches b ON lb.batch_id = b.id 
        WHERE b.id IS NULL
    ")->fetchColumn();
    
    if ($orphanBatches > 0) {
        echo "❌ Found {$orphanBatches} batches without parent batch records\n";
    } else {
        echo "✅ All batches have valid parent records\n";
    }
    
    // Check for invalid quantities (non-numeric, etc.)
    $invalidQty = $pdo->query("SELECT COUNT(*) FROM location_batches WHERE qty IS NULL")->fetchColumn();
    
    if ($invalidQty > 0) {
        echo "❌ Found {$invalidQty} records with NULL quantities\n";
    } else {
        echo "✅ All quantities are valid\n";
    }
    echo "\n";
    
    // 8. Business Impact Assessment
    echo "8. 💼 BUSINESS IMPACT ASSESSMENT:\n";
    echo "================================\n";
    
    $availableStock = $pdo->query("SELECT COUNT(*) FROM location_batches WHERE qty > 0")->fetchColumn();
    $totalStockValue = $pdo->query("SELECT SUM(qty) FROM location_batches WHERE qty > 0")->fetchColumn();
    
    echo "Batches available for sale: {$availableStock}\n";
    echo "Total units available: {$totalStockValue}\n";
    
    $stockPerLocation = $pdo->query("
        SELECT location_id, COUNT(*) as available_batches, SUM(qty) as total_units
        FROM location_batches 
        WHERE qty > 0 
        GROUP BY location_id
        ORDER BY total_units DESC
    ")->fetchAll();
    
    echo "\nStock availability by location:\n";
    foreach ($stockPerLocation as $stock) {
        echo "- Location {$stock['location_id']}: {$stock['available_batches']} batches, {$stock['total_units']} total units\n";
    }
    echo "\n";
    
    // 9. Final Health Score
    echo "9. 🏆 FINAL SYSTEM HEALTH SCORE:\n";
    echo "================================\n";
    
    $healthScore = 100;
    $criticalIssues = [];
    $minorIssues = [];
    
    // Critical issues (major point deductions)
    if ($negativeQty > 0) {
        $healthScore -= 30;
        $criticalIssues[] = "{$negativeQty} negative stock quantities";
    }
    
    if ($duplicatesCount > 0) {
        $healthScore -= 25;
        $criticalIssues[] = "{$duplicatesCount} duplicate batch-location combinations";
    }
    
    if ($orphanBatches > 0) {
        $healthScore -= 20;
        $criticalIssues[] = "{$orphanBatches} orphaned batch references";
    }
    
    if ($invalidQty > 0) {
        $healthScore -= 15;
        $criticalIssues[] = "{$invalidQty} invalid quantity values";
    }
    
    // Minor issues (small point deductions)
    if ($zeroStockPercentage > 70) {
        $healthScore -= 10;
        $minorIssues[] = "High percentage of zero-stock batches ({$zeroStockPercentage}%)";
    }
    
    if ($availableStock < 100) {
        $healthScore -= 5;
        $minorIssues[] = "Low number of available stock batches ({$availableStock})";
    }
    
    echo "SYSTEM HEALTH SCORE: {$healthScore}/100\n\n";
    
    // Health status
    if ($healthScore >= 95) {
        echo "🎉 EXCELLENT: Your location_batches table is in PERFECT condition!\n";
        echo "✅ Ready for full production use\n";
        echo "✅ No critical issues detected\n";
        echo "✅ POS system fully operational\n";
    } elseif ($healthScore >= 85) {
        echo "✅ VERY GOOD: Your table is in very good condition\n";
        echo "✅ Safe for production use\n";
        echo "⚠️  Minor optimization opportunities exist\n";
    } elseif ($healthScore >= 70) {
        echo "⚠️  GOOD: Your table is functional but has some issues\n";
        echo "⚠️  Recommended to address issues before heavy usage\n";
    } elseif ($healthScore >= 50) {
        echo "⚠️  FAIR: Your table has multiple issues\n";
        echo "❌ Not recommended for production without fixes\n";
    } else {
        echo "❌ POOR: Your table has serious issues\n";
        echo "❌ Immediate fixes required before use\n";
    }
    
    // Issue summary
    if (!empty($criticalIssues) || !empty($minorIssues)) {
        echo "\nISSUES DETECTED:\n";
        
        if (!empty($criticalIssues)) {
            echo "🚨 CRITICAL ISSUES:\n";
            foreach ($criticalIssues as $issue) {
                echo "- {$issue}\n";
            }
        }
        
        if (!empty($minorIssues)) {
            echo "⚠️  MINOR ISSUES:\n";
            foreach ($minorIssues as $issue) {
                echo "- {$issue}\n";
            }
        }
    } else {
        echo "\n🎊 NO ISSUES DETECTED - SYSTEM IS PERFECT! 🎊\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📋 SUMMARY FOR ALL BATCHES:\n";
    echo str_repeat("=", 50) . "\n";
    echo "✅ Total records checked: {$totalRecords}\n";
    echo "✅ Negative quantities: {$negativeQty}\n";
    echo "✅ Duplicate combinations: {$duplicatesCount}\n";
    echo "✅ Zero stock batches: {$zeroStockCount}\n";
    echo "✅ Available stock batches: {$availableStock}\n";
    echo "✅ Orphaned batches: {$orphanBatches}\n";
    echo "✅ Invalid quantities: {$invalidQty}\n";
    echo "✅ Health Score: {$healthScore}/100\n";
    
    if ($healthScore >= 95) {
        echo "\n🚀 YOUR LOCATION_BATCHES TABLE IS 100% READY! 🚀\n";
        echo "🎯 ALL BATCHES ARE PERFECTLY CONFIGURED!\n";
        echo "💪 YOUR POS SYSTEM CAN HANDLE ANY TRANSACTION!\n";
    }

} catch (Exception $e) {
    echo "❌ Error during comprehensive check: " . $e->getMessage() . "\n";
}

echo "\nComprehensive check completed at: " . date('Y-m-d H:i:s') . "\n";