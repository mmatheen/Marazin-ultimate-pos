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
    
    // All related tables for analysis
    $allTables = [
        'customers', 'suppliers', 'sales', 'purchases', 
        'sales_returns', 'purchase_returns', 'payments', 
        'ledgers', 'sales_products', 'purchase_products',
        'transaction_payments', 'customer_groups', 'locations'
    ];
    
    $tableAnalysis = [];
    
    // Analyze each table
    foreach ($allTables as $table) {
        echo "=== ANALYZING TABLE: {$table} ===\n";
        
        try {
            $columns = $conn->query("DESCRIBE `{$table}`")->fetchAll();
            
            if (empty($columns)) {
                echo "❌ Table '{$table}' does not exist or no access\n\n";
                continue;
            }
            
            $tableAnalysis[$table] = [
                'exists' => true,
                'columns' => $columns
            ];
            
            echo "✅ Table exists with " . count($columns) . " columns\n";
            
            // Show columns with types
            echo "📋 COLUMNS:\n";
            foreach ($columns as $col) {
                $nullable = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                $default = $col['Default'] !== null ? " DEFAULT '{$col['Default']}'" : '';
                $key = $col['Key'] ? " [{$col['Key']}]" : '';
                echo "   {$col['Field']} - {$col['Type']} {$nullable}{$default}{$key}\n";
            }
            
            // Get sample data
            try {
                $sampleData = $conn->query("SELECT * FROM `{$table}` LIMIT 2")->fetchAll();
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
            } catch (Exception $e) {
                echo "📊 Could not get sample data\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Table '{$table}' does not exist: {$e->getMessage()}\n";
            $tableAnalysis[$table] = ['exists' => false];
        }
        
        echo "\n";
    }
    
    // Analyze data relationships and flows
    echo "=== DATA FLOW ANALYSIS ===\n\n";
    
    // Customer flow analysis
    echo "👥 CUSTOMER DATA FLOW:\n";
    if (isset($tableAnalysis['customers']) && $tableAnalysis['customers']['exists']) {
        $customerCols = array_column($tableAnalysis['customers']['columns'], 'Field');
        echo "   Customer fields: " . implode(', ', $customerCols) . "\n";
        
        // Check which tables connect to customers
        $customerConnections = [];
        foreach ($allTables as $table) {
            if (isset($tableAnalysis[$table]['columns'])) {
                $cols = array_column($tableAnalysis[$table]['columns'], 'Field');
                if (in_array('customer_id', $cols)) {
                    $customerConnections[] = $table;
                }
            }
        }
        echo "   Tables linked to customers: " . implode(', ', $customerConnections) . "\n\n";
    }
    
    // Supplier flow analysis
    echo "🏪 SUPPLIER DATA FLOW:\n";
    if (isset($tableAnalysis['suppliers']) && $tableAnalysis['suppliers']['exists']) {
        $supplierCols = array_column($tableAnalysis['suppliers']['columns'], 'Field');
        echo "   Supplier fields: " . implode(', ', $supplierCols) . "\n";
        
        // Check which tables connect to suppliers
        $supplierConnections = [];
        foreach ($allTables as $table) {
            if (isset($tableAnalysis[$table]['columns'])) {
                $cols = array_column($tableAnalysis[$table]['columns'], 'Field');
                if (in_array('supplier_id', $cols)) {
                    $supplierConnections[] = $table;
                }
            }
        }
        echo "   Tables linked to suppliers: " . implode(', ', $supplierConnections) . "\n\n";
    }
    
    // Payment flow analysis
    echo "💳 PAYMENT DATA FLOW:\n";
    if (isset($tableAnalysis['payments']) && $tableAnalysis['payments']['exists']) {
        $paymentCols = array_column($tableAnalysis['payments']['columns'], 'Field');
        echo "   Payment table fields: " . implode(', ', $paymentCols) . "\n";
        
        // Check payment types if column exists
        if (in_array('payment_type', $paymentCols)) {
            try {
                $paymentTypes = $conn->query("SELECT DISTINCT payment_type FROM payments LIMIT 10")->fetchAll();
                $types = array_column($paymentTypes, 'payment_type');
                echo "   Payment types found: " . implode(', ', $types) . "\n";
            } catch (Exception $e) {
                echo "   Payment types: Could not determine\n";
            }
        }
        
        // Check customer/supplier payment links
        if (in_array('customer_id', $paymentCols)) {
            echo "   ✅ Payments linked to customers\n";
        }
        if (in_array('supplier_id', $paymentCols)) {
            echo "   ✅ Payments linked to suppliers\n";
        }
    }
    
    // Check for transaction_payments table
    if (isset($tableAnalysis['transaction_payments']) && $tableAnalysis['transaction_payments']['exists']) {
        echo "   ✅ Transaction payments table EXISTS\n";
    } else {
        echo "   ❌ Transaction payments table DOES NOT EXIST\n";
        echo "   💡 Payments are likely stored in main payments table\n";
    }
    echo "\n";
    
    // Ledger flow analysis
    echo "📊 LEDGER DATA FLOW:\n";
    if (isset($tableAnalysis['ledgers']) && $tableAnalysis['ledgers']['exists']) {
        $ledgerCols = array_column($tableAnalysis['ledgers']['columns'], 'Field');
        echo "   Ledger fields: " . implode(', ', $ledgerCols) . "\n";
        
        // Check ledger data distribution
        try {
            $ledgerStats = $conn->query("
                SELECT 
                    COUNT(*) as total_entries,
                    COUNT(CASE WHEN customer_id IS NOT NULL THEN 1 END) as customer_entries,
                    COUNT(CASE WHEN supplier_id IS NOT NULL THEN 1 END) as supplier_entries,
                    SUM(CASE WHEN debit IS NOT NULL THEN debit ELSE 0 END) as total_debits,
                    SUM(CASE WHEN credit IS NOT NULL THEN credit ELSE 0 END) as total_credits
                FROM ledgers
            ")->fetch();
            
            echo "   Total ledger entries: {$ledgerStats['total_entries']}\n";
            echo "   Customer entries: {$ledgerStats['customer_entries']}\n";
            echo "   Supplier entries: {$ledgerStats['supplier_entries']}\n";
            echo "   Total debits: " . number_format($ledgerStats['total_debits'], 2) . "\n";
            echo "   Total credits: " . number_format($ledgerStats['total_credits'], 2) . "\n";
        } catch (Exception $e) {
            echo "   Could not analyze ledger statistics: {$e->getMessage()}\n";
        }
    } else {
        echo "   ❌ Ledgers table not found or not accessible\n";
    }
    echo "\n";
    
    // Sales & Purchase analysis
    echo "💰 SALES & PURCHASE DATA FLOW:\n";
    if (isset($tableAnalysis['sales']) && $tableAnalysis['sales']['exists']) {
        $salesCols = array_column($tableAnalysis['sales']['columns'], 'Field');
        echo "   Sales fields: " . implode(', ', $salesCols) . "\n";
        
        try {
            $salesStats = $conn->query("
                SELECT 
                    COUNT(*) as total_sales,
                    SUM(CASE WHEN grand_total IS NOT NULL THEN grand_total ELSE 0 END) as total_amount,
                    AVG(CASE WHEN grand_total IS NOT NULL THEN grand_total ELSE 0 END) as avg_amount
                FROM sales
            ")->fetch();
            echo "   Total sales: {$salesStats['total_sales']}\n";
            echo "   Total amount: " . number_format($salesStats['total_amount'], 2) . "\n";
            echo "   Average amount: " . number_format($salesStats['avg_amount'], 2) . "\n";
        } catch (Exception $e) {
            echo "   Could not analyze sales statistics\n";
        }
    }
    
    if (isset($tableAnalysis['purchases']) && $tableAnalysis['purchases']['exists']) {
        $purchaseCols = array_column($tableAnalysis['purchases']['columns'], 'Field');
        echo "   Purchase fields: " . implode(', ', $purchaseCols) . "\n";
        
        try {
            $purchaseStats = $conn->query("
                SELECT 
                    COUNT(*) as total_purchases,
                    SUM(CASE WHEN grand_total IS NOT NULL THEN grand_total ELSE 0 END) as total_amount,
                    AVG(CASE WHEN grand_total IS NOT NULL THEN grand_total ELSE 0 END) as avg_amount
                FROM purchases
            ")->fetch();
            echo "   Total purchases: {$purchaseStats['total_purchases']}\n";
            echo "   Total amount: " . number_format($purchaseStats['total_amount'], 2) . "\n";
            echo "   Average amount: " . number_format($purchaseStats['avg_amount'], 2) . "\n";
        } catch (Exception $e) {
            echo "   Could not analyze purchase statistics\n";
        }
    }
    echo "\n";
    
    // Generate field mapping recommendations
    echo "=== FIELD MAPPING RECOMMENDATIONS ===\n\n";
    
    $recommendations = [
        'customer_name_field' => 'first_name',
        'customer_phone_field' => 'mobile_no',
        'supplier_name_field' => 'first_name', 
        'supplier_phone_field' => 'mobile_no',
        'sales_total_field' => 'grand_total',
        'purchase_total_field' => 'grand_total',
        'payment_amount_field' => 'amount'
    ];
    
    // Auto-detect best field mappings
    if (isset($tableAnalysis['customers']['columns'])) {
        $custCols = array_column($tableAnalysis['customers']['columns'], 'Field');
        
        if (in_array('first_name', $custCols)) {
            $recommendations['customer_name_field'] = 'first_name';
        } elseif (in_array('name', $custCols)) {
            $recommendations['customer_name_field'] = 'name';
        }
        
        if (in_array('mobile_no', $custCols)) {
            $recommendations['customer_phone_field'] = 'mobile_no';
        } elseif (in_array('mobile', $custCols)) {
            $recommendations['customer_phone_field'] = 'mobile';
        } elseif (in_array('phone', $custCols)) {
            $recommendations['customer_phone_field'] = 'phone';
        }
    }
    
    if (isset($tableAnalysis['suppliers']['columns'])) {
        $suppCols = array_column($tableAnalysis['suppliers']['columns'], 'Field');
        
        if (in_array('first_name', $suppCols)) {
            $recommendations['supplier_name_field'] = 'first_name';
        } elseif (in_array('name', $suppCols)) {
            $recommendations['supplier_name_field'] = 'name';
        }
        
        if (in_array('mobile_no', $suppCols)) {
            $recommendations['supplier_phone_field'] = 'mobile_no';
        } elseif (in_array('mobile', $suppCols)) {
            $recommendations['supplier_phone_field'] = 'mobile';
        }
    }
    
    echo "📝 RECOMMENDED FIELD MAPPINGS:\n";
    foreach ($recommendations as $purpose => $field) {
        echo "   {$purpose}: {$field}\n";
    }
    
    echo "\n💡 RECOMMENDED LEDGER ANALYSIS QUERY STRUCTURE:\n";
    echo "   Customer Query: customers -> sales/sales_returns -> payments -> ledgers\n";
    echo "   Supplier Query: suppliers -> purchases/purchase_returns -> payments -> ledgers\n";
    echo "   Payment Tracking: Use 'payments' table with payment_type field\n";
    echo "   Balance Calculation: opening_balance + sales - returns - payments\n";
    
    // Save complete analysis
    $completeAnalysis = [
        'timestamp' => date('Y-m-d H:i:s'),
        'tables_found' => array_keys(array_filter($tableAnalysis, function($table) { 
            return isset($table['exists']) && $table['exists']; 
        })),
        'field_mappings' => $recommendations,
        'table_details' => $tableAnalysis
    ];
    
    $analysisFile = 'complete_migration_analysis_' . date('Ymd_His') . '.json';
    file_put_contents($analysisFile, json_encode($completeAnalysis, JSON_PRETTY_PRINT));
    echo "\n📁 Complete migration analysis saved to: {$analysisFile}\n";
    
    echo "\n=== ANALYSIS SUMMARY ===\n";
    $existingTables = array_filter($tableAnalysis, function($table) { 
        return isset($table['exists']) && $table['exists']; 
    });
    echo "✅ Tables found: " . count($existingTables) . " out of " . count($allTables) . "\n";
    echo "📋 Key tables status:\n";
    foreach (['customers', 'suppliers', 'sales', 'purchases', 'payments', 'ledgers'] as $key) {
        $status = (isset($tableAnalysis[$key]['exists']) && $tableAnalysis[$key]['exists']) ? '✅' : '❌';
        echo "   {$status} {$key}\n";
    }
    
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