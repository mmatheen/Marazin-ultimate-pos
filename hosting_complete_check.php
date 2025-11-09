<?php

// Use Laravel's database configuration instead of hardcoded credentials
require_once __DIR__ . '/bootstrap/app.php';

$app = app();

// Get database configuration from Laravel
$config = $app['config']['database.connections.mysql'];

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== HOSTING SERVER - COMPLETE LOCATION_BATCHES CHECK ===\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    echo "Database: {$config['host']}:{$config['port']}/{$config['database']}\n";
    echo "========================================================\n\n";
    
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
        $negativeDetails = $pdo->query("SELECT batch_id, location_id, qty FROM location_batches WHERE qty < 0 ORDER BY qty ASC LIMIT 10")->fetchAll();
        foreach ($negativeDetails as $record) {
            echo "- Batch {$record['batch_id']}, Location {$record['location_id']}: {$record['qty']} units\n";
        }
        if ($negativeQty > 10) {
            echo "... and " . ($negativeQty - 10) . " more negative quantities\n";
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
            ORDER BY count DESC
            LIMIT 10
        ")->fetchAll();
        
        foreach ($duplicates as $dup) {
            echo "- Batch {$dup['batch_id']} at Location {$dup['location_id']}: {$dup['count']} records\n";
        }
        if ($duplicatesCount > 10) {
            echo "... and " . ($duplicatesCount - 10) . " more duplicates\n";
        }
    } else {
        echo "✅ PERFECT: No duplicate batch-location combinations found!\n";
    }
    echo "\n";
    
    // 4. Business Impact Assessment
    echo "4. 💼 BUSINESS IMPACT ASSESSMENT:\n";
    echo "================================\n";
    
    $availableStock = $pdo->query("SELECT COUNT(*) FROM location_batches WHERE qty > 0")->fetchColumn();
    $totalStockValue = $pdo->query("SELECT SUM(qty) FROM location_batches WHERE qty > 0")->fetchColumn();
    $zeroStockCount = $pdo->query("SELECT COUNT(*) FROM location_batches WHERE qty = 0")->fetchColumn();
    
    echo "Batches available for sale: {$availableStock}\n";
    echo "Total units available: {$totalStockValue}\n";
    echo "Zero stock batches: {$zeroStockCount}\n\n";
    
    // 5. Top problematic batches
    if ($negativeQty > 0) {
        echo "5. 🚨 MOST PROBLEMATIC BATCHES:\n";
        echo "==============================\n";
        
        $worstBatches = $pdo->query("
            SELECT batch_id, location_id, qty 
            FROM location_batches 
            WHERE qty < 0 
            ORDER BY qty ASC 
            LIMIT 5
        ")->fetchAll();
        
        foreach ($worstBatches as $batch) {
            echo "🚨 Batch {$batch['batch_id']} at Location {$batch['location_id']}: {$batch['qty']} units (CRITICAL)\n";
        }
        echo "\n";
    }
    
    // 6. Final Health Score
    echo "6. 🏆 HOSTING SERVER HEALTH SCORE:\n";
    echo "=================================\n";
    
    $healthScore = 100;
    $criticalIssues = [];
    
    if ($negativeQty > 0) {
        $healthScore -= 40;
        $criticalIssues[] = "{$negativeQty} negative stock quantities (CRITICAL)";
    }
    
    if ($duplicatesCount > 0) {
        $healthScore -= 30;
        $criticalIssues[] = "{$duplicatesCount} duplicate combinations (HIGH)";
    }
    
    $zeroPercentage = round(($zeroStockCount / $totalRecords) * 100, 1);
    if ($zeroPercentage > 70) {
        $healthScore -= 10;
        $criticalIssues[] = "High zero stock percentage ({$zeroPercentage}%)";
    }
    
    echo "HOSTING SERVER HEALTH SCORE: {$healthScore}/100\n\n";
    
    if ($healthScore >= 95) {
        echo "🎉 EXCELLENT: Your hosting server is in PERFECT condition!\n";
        echo "✅ Ready for full production use\n";
        echo "✅ No critical issues detected\n";
        echo "✅ POS system fully operational\n";
    } elseif ($healthScore >= 80) {
        echo "✅ GOOD: Your hosting server is in good condition\n";
        echo "⚠️  Minor issues exist but system is operational\n";
    } elseif ($healthScore >= 60) {
        echo "⚠️  FAIR: Your hosting server has issues\n";
        echo "❌ Fixes recommended before heavy usage\n";
    } else {
        echo "❌ CRITICAL: Your hosting server has serious issues\n";
        echo "🚨 IMMEDIATE FIXES REQUIRED!\n";
    }
    
    if (!empty($criticalIssues)) {
        echo "\n🚨 CRITICAL ISSUES ON HOSTING SERVER:\n";
        foreach ($criticalIssues as $issue) {
            echo "- {$issue}\n";
        }
        
        echo "\n💡 RECOMMENDED ACTION:\n";
        echo "Run: php hosting_location_batch_fix.php\n";
    } else {
        echo "\n🎊 NO ISSUES DETECTED - HOSTING SERVER IS PERFECT! 🎊\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📋 HOSTING SERVER SUMMARY:\n";
    echo str_repeat("=", 50) . "\n";
    echo "✅ Total records: {$totalRecords}\n";
    echo "✅ Negative quantities: {$negativeQty}\n";
    echo "✅ Duplicate combinations: {$duplicatesCount}\n";
    echo "✅ Available stock batches: {$availableStock}\n";
    echo "✅ Total available units: {$totalStockValue}\n";
    echo "✅ Health Score: {$healthScore}/100\n";
    
    if ($healthScore >= 95) {
        echo "\n🚀 HOSTING SERVER IS 100% READY FOR PRODUCTION! 🚀\n";
    } else {
        echo "\n⚠️  HOSTING SERVER NEEDS ATTENTION BEFORE PRODUCTION! ⚠️\n";
    }

} catch (PDOException $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in .env file\n";
} catch (Exception $e) {
    echo "❌ Error during check: " . $e->getMessage() . "\n";
}

echo "\nHosting server check completed at: " . date('Y-m-d H:i:s') . "\n";