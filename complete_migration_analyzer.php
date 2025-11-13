<?php
/**
 * Complete Migration and Database Relationship Analyzer
 * Analyzes all tables, columns, relationships, and data flow for ledger system
 */

require_once 'simple_database_manager.php';

echo "=== COMPLETE MIGRATION & RELATIONSHIP ANALYZER ===\n\n";

try {
    $dbManager = SimpleDatabaseManager::getInstance();
    $conn = $dbManager->getConnection();
    
    echo "✅ Database connected successfully\n\n";
    
    // Function to get detailed table info
    function getTableDetails($conn, $tableName) {
        try {
            $columns = $conn->query("DESCRIBE `{$tableName}`")->fetchAll();
            return $columns;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Function to get table relationships
    function getTableRelationships($conn, $tableName) {
        try {
            $query = "
                SELECT 
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '{$tableName}'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ";
            return $conn->query($query)->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Function to get sample data
    function getSampleData($conn, $tableName, $limit = 3) {
        try {
            return $conn->query("SELECT * FROM `{$tableName}` LIMIT {$limit}")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // All related tables
    $allTables = [
        'customers', 'suppliers', 'sales', 'purchases', 
        'sales_returns', 'purchase_returns', 'payments', 
        'ledgers', 'sales_products', 'purchase_products',
        'transaction_payments', 'customer_groups', 'locations'
    ];
    
    $tableAnalysis = [];
    
    foreach ($allTables as $table) {
        echo "=== ANALYZING TABLE: {$table} ===\n";
        
        $details = getTableDetails($conn, $table);
        if (empty($details)) {
            echo "❌ Table '{$table}' does not exist\n\n";
            continue;
        }
        
        $tableAnalysis[$table] = [
            'exists' => true,
            'columns' => $details,
            'relationships' => getTableRelationships($conn, $table),
            'sample_data' => getSampleData($conn, $table, 2)
        ];
        
        echo "✅ Table exists with " . count($details) . " columns\n";
        
        // Show columns with types
        echo "📋 COLUMNS:\n";
        foreach ($details as $col) {
            $nullable = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $col['Default'] !== null ? " DEFAULT '{$col['Default']}'" : '';
            $key = $col['Key'] ? " [{$col['Key']}]" : '';
            echo "   {$col['Field']} - {$col['Type']} {$nullable}{$default}{$key}\n";
        }
        
        // Show relationships
        $relationships = $tableAnalysis[$table]['relationships'];
        if (!empty($relationships)) {
            echo "🔗 RELATIONSHIPS:\n";
            foreach ($relationships as $rel) {
                echo "   {$rel['COLUMN_NAME']} -> {$rel['REFERENCED_TABLE_NAME']}.{$rel['REFERENCED_COLUMN_NAME']}\n";
            }
        }
        
        // Show sample data
        $sampleData = $tableAnalysis[$table]['sample_data'];
        if (!empty($sampleData)) {
            echo "📊 SAMPLE DATA (first record):\n";
            $firstRecord = $sampleData[0];
            foreach ($firstRecord as $key => $value) {
                if (!is_numeric($key)) {
                    $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                    echo "   {$key}: {$displayValue}\n";
                }
            }
        }
        
        echo "\n";
    }
    
    // Analyze data relationships and flows
    echo "=== DATA FLOW ANALYSIS ===\n\n";
    
    // Customer flow analysis
    echo "👥 CUSTOMER DATA FLOW:\n";
    if (isset($tableAnalysis['customers'])) {
        $customerCols = array_column($tableAnalysis['customers']['columns'], 'Field');
        echo "   Fields: " . implode(', ', $customerCols) . "\n";
        
        // Check customer connections
        $customerConnections = [];
        foreach ($allTables as $table) {
            if (isset($tableAnalysis[$table]['columns'])) {
                $cols = array_column($tableAnalysis[$table]['columns'], 'Field');
                if (in_array('customer_id', $cols)) {
                    $customerConnections[] = $table;
                }
            }
        }
        echo "   Connected tables: " . implode(', ', $customerConnections) . "\n\n";
    }
    
    // Supplier flow analysis
    echo "🏪 SUPPLIER DATA FLOW:\n";
    if (isset($tableAnalysis['suppliers'])) {
        $supplierCols = array_column($tableAnalysis['suppliers']['columns'], 'Field');
        echo "   Fields: " . implode(', ', $supplierCols) . "\n";
        
        // Check supplier connections
        $supplierConnections = [];
        foreach ($allTables as $table) {
            if (isset($tableAnalysis[$table]['columns'])) {
                $cols = array_column($tableAnalysis[$table']['columns'], 'Field');
                if (in_array('supplier_id', $cols)) {
                    $supplierConnections[] = $table;
                }
            }
        }
        echo "   Connected tables: " . implode(', ', $supplierConnections) . "\n\n";
    }
    
    // Payment flow analysis
    echo "💳 PAYMENT DATA FLOW:\n";
    if (isset($tableAnalysis['payments'])) {
        $paymentCols = array_column($tableAnalysis['payments']['columns'], 'Field');
        echo "   Payment table fields: " . implode(', ', $paymentCols) . "\n";
        
        // Check payment types
        try {
            $paymentTypes = $conn->query("SELECT DISTINCT payment_type FROM payments LIMIT 10")->fetchAll();
            echo "   Payment types: " . implode(', ', array_column($paymentTypes, 'payment_type')) . "\n";
        } catch (Exception $e) {
            echo "   Payment types: Could not determine\n";
        }
    }
    
    // Check for transaction_payments table
    if (isset($tableAnalysis['transaction_payments'])) {
        echo "   Transaction payments table: EXISTS\n";
    } else {
        echo "   Transaction payments table: DOES NOT EXIST (payments are likely in main payments table)\n";
    }
    echo "\n";
    
    // Ledger flow analysis
    echo "📊 LEDGER DATA FLOW:\n";
    if (isset($tableAnalysis['ledgers'])) {
        $ledgerCols = array_column($tableAnalysis['ledgers']['columns'], 'Field');
        echo "   Ledger fields: " . implode(', ', $ledgerCols) . "\n";
        
        // Check ledger data distribution
        try {
            $ledgerStats = $conn->query("
                SELECT 
                    COUNT(*) as total_entries,
                    COUNT(CASE WHEN customer_id IS NOT NULL THEN 1 END) as customer_entries,
                    COUNT(CASE WHEN supplier_id IS NOT NULL THEN 1 END) as supplier_entries,
                    SUM(debit) as total_debits,
                    SUM(credit) as total_credits
                FROM ledgers
            ")->fetch();
            
            echo "   Total ledger entries: {$ledgerStats['total_entries']}\n";
            echo "   Customer entries: {$ledgerStats['customer_entries']}\n";
            echo "   Supplier entries: {$ledgerStats['supplier_entries']}\n";
            echo "   Total debits: " . number_format($ledgerStats['total_debits'], 2) . "\n";
            echo "   Total credits: " . number_format($ledgerStats['total_credits'], 2) . "\n";
        } catch (Exception $e) {
            echo "   Could not analyze ledger statistics\n";
        }
    }
    echo "\n";
    
    // Sales & Purchase analysis
    echo "💰 SALES & PURCHASE DATA FLOW:\n";
    if (isset($tableAnalysis['sales'])) {
        $salesCols = array_column($tableAnalysis['sales']['columns'], 'Field');
        echo "   Sales fields: " . implode(', ', $salesCols) . "\n";
        
        try {
            $salesStats = $conn->query("
                SELECT 
                    COUNT(*) as total_sales,
                    SUM(grand_total) as total_amount,
                    AVG(grand_total) as avg_amount
                FROM sales
            ")->fetch();
            echo "   Total sales: {$salesStats['total_sales']}\n";
            echo "   Total amount: " . number_format($salesStats['total_amount'], 2) . "\n";
        } catch (Exception $e) {
            echo "   Could not analyze sales statistics\n";
        }
    }
    
    if (isset($tableAnalysis['purchases'])) {
        $purchaseCols = array_column($tableAnalysis['purchases']['columns'], 'Field');
        echo "   Purchase fields: " . implode(', ', $purchaseCols) . "\n";
        
        try {
            $purchaseStats = $conn->query("
                SELECT 
                    COUNT(*) as total_purchases,
                    SUM(grand_total) as total_amount,
                    AVG(grand_total) as avg_amount
                FROM purchases
            ")->fetch();
            echo "   Total purchases: {$purchaseStats['total_purchases']}\n";
            echo "   Total amount: " . number_format($purchaseStats['total_amount'], 2) . "\n";
        } catch (Exception $e) {
            echo "   Could not analyze purchase statistics\n";
        }
    }
    echo "\n";
    
    // Migration recommendations
    echo "=== MIGRATION ANALYSIS & RECOMMENDATIONS ===\n\n";
    
    echo "🔧 LEDGER SYSTEM STRUCTURE:\n";
    
    // Check if proper foreign keys exist
    $fkIssues = [];
    
    if (isset($tableAnalysis['ledgers'])) {
        $ledgerCols = array_column($tableAnalysis['ledgers']['columns'], 'Field');
        
        if (!in_array('customer_id', $ledgerCols)) {
            $fkIssues[] = "ledgers table missing customer_id foreign key";
        }
        if (!in_array('supplier_id', $ledgerCols)) {
            $fkIssues[] = "ledgers table missing supplier_id foreign key";
        }
        if (!in_array('debit', $ledgerCols)) {
            $fkIssues[] = "ledgers table missing debit column";
        }
        if (!in_array('credit', $ledgerCols)) {
            $fkIssues[] = "ledgers table missing credit column";
        }
    }
    
    if (!empty($fkIssues)) {
        echo "❌ ISSUES FOUND:\n";
        foreach ($fkIssues as $issue) {
            echo "   - {$issue}\n";
        }
    } else {
        echo "✅ Ledger table structure looks correct\n";
    }
    
    echo "\n💡 RECOMMENDED QUERY STRUCTURE:\n";
    
    // Generate recommended query based on actual structure
    if (isset($tableAnalysis['customers']) && isset($tableAnalysis['sales']) && isset($tableAnalysis['payments'])) {
        $custCols = array_column($tableAnalysis['customers']['columns'], 'Field');
        $salesCols = array_column($tableAnalysis['sales']['columns'], 'Field');
        $paymentCols = array_column($tableAnalysis['payments']['columns'], 'Field');
        
        $custNameField = in_array('first_name', $custCols) ? 'first_name' : 'name';
        $salesTotalField = in_array('grand_total', $salesCols) ? 'grand_total' : 'total';
        $paymentAmountField = in_array('amount', $paymentCols) ? 'amount' : 'paid_amount';
        
        echo "📝 Customer Analysis Query Structure:\n";
        echo "   Customer name field: {$custNameField}\n";
        echo "   Sales total field: {$salesTotalField}\n";
        echo "   Payment amount field: {$paymentAmountField}\n";
        echo "   Recommended JOIN: customers -> sales -> payments\n";
    }
    
    // Save complete analysis
    $completeAnalysis = [
        'timestamp' => date('Y-m-d H:i:s'),
        'table_analysis' => $tableAnalysis,
        'recommendations' => [
            'customer_name_field' => $custNameField ?? 'first_name',
            'sales_total_field' => $salesTotalField ?? 'grand_total',
            'payment_amount_field' => $paymentAmountField ?? 'amount',
            'issues_found' => $fkIssues
        ]
    ];
    
    $analysisFile = 'complete_migration_analysis_' . date('Ymd_His') . '.json';
    file_put_contents($analysisFile, json_encode($completeAnalysis, JSON_PRETTY_PRINT));
    echo "\n📁 Complete analysis saved to: {$analysisFile}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Debug information:\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== MIGRATION ANALYSIS COMPLETE ===\n";
echo "📋 Use this analysis to create perfectly adapted ledger scripts!\n";
?>