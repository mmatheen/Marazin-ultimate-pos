<?php
/**
 * Database Table Structure Checker
 * Checks actual table names and structure in your database
 */

require_once 'simple_database_manager.php';

echo "=== DATABASE STRUCTURE CHECKER ===\n\n";

try {
    $dbManager = SimpleDatabaseManager::getInstance();
    $conn = $dbManager->getConnection();
    
    echo "✅ Database connected successfully\n\n";
    
    // Get all table names
    echo "=== AVAILABLE TABLES ===\n";
    $tables = $conn->query("SHOW TABLES")->fetchAll();
    
    $tableNames = [];
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        $tableNames[] = $tableName;
        echo "📄 {$tableName}\n";
    }
    
    echo "\n=== PAYMENT RELATED TABLES ===\n";
    $paymentTables = array_filter($tableNames, function($name) {
        return stripos($name, 'payment') !== false;
    });
    
    if (empty($paymentTables)) {
        echo "❌ No tables with 'payment' in name found\n";
        
        // Check for transaction related tables
        echo "\n=== TRANSACTION RELATED TABLES ===\n";
        $transactionTables = array_filter($tableNames, function($name) {
            return stripos($name, 'transaction') !== false;
        });
        
        if (!empty($transactionTables)) {
            foreach ($transactionTables as $table) {
                echo "📄 {$table}\n";
            }
        } else {
            echo "❌ No transaction tables found either\n";
        }
        
        // Show sales and purchases table structure
        echo "\n=== SALES TABLE STRUCTURE ===\n";
        if (in_array('sales', $tableNames)) {
            $salesColumns = $conn->query("DESCRIBE sales")->fetchAll();
            foreach ($salesColumns as $col) {
                echo "  {$col['Field']} - {$col['Type']}\n";
            }
        } else {
            echo "❌ Sales table not found\n";
        }
        
        echo "\n=== PURCHASES TABLE STRUCTURE ===\n";
        if (in_array('purchases', $tableNames)) {
            $purchaseColumns = $conn->query("DESCRIBE purchases")->fetchAll();
            foreach ($purchaseColumns as $col) {
                echo "  {$col['Field']} - {$col['Type']}\n";
            }
        } else {
            echo "❌ Purchases table not found\n";
        }
        
    } else {
        foreach ($paymentTables as $table) {
            echo "📄 {$table}\n";
            
            // Show table structure
            echo "   Structure:\n";
            $columns = $conn->query("DESCRIBE `{$table}`")->fetchAll();
            foreach ($columns as $col) {
                echo "   - {$col['Field']} ({$col['Type']})\n";
            }
            echo "\n";
        }
    }
    
    // Check essential tables
    echo "=== ESSENTIAL TABLES CHECK ===\n";
    $essentialTables = ['customers', 'suppliers', 'sales', 'purchases', 'ledgers'];
    
    foreach ($essentialTables as $table) {
        if (in_array($table, $tableNames)) {
            echo "✅ {$table} - EXISTS\n";
        } else {
            echo "❌ {$table} - MISSING\n";
        }
    }
    
    // Sample data check
    echo "\n=== SAMPLE DATA CHECK ===\n";
    
    if (in_array('customers', $tableNames)) {
        $customerCount = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch()['count'];
        echo "👥 Customers: {$customerCount}\n";
    }
    
    if (in_array('suppliers', $tableNames)) {
        $supplierCount = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch()['count'];
        echo "🏪 Suppliers: {$supplierCount}\n";
    }
    
    if (in_array('sales', $tableNames)) {
        $salesCount = $conn->query("SELECT COUNT(*) as count FROM sales")->fetch()['count'];
        echo "💰 Sales: {$salesCount}\n";
    }
    
    if (in_array('purchases', $tableNames)) {
        $purchaseCount = $conn->query("SELECT COUNT(*) as count FROM purchases")->fetch()['count'];
        echo "🛒 Purchases: {$purchaseCount}\n";
    }
    
    if (in_array('ledgers', $tableNames)) {
        $ledgerCount = $conn->query("SELECT COUNT(*) as count FROM ledgers")->fetch()['count'];
        echo "📊 Ledgers: {$ledgerCount}\n";
    }
    
    echo "\n=== RECOMMENDATIONS ===\n";
    
    if (empty($paymentTables)) {
        echo "💡 Payment data might be stored in:\n";
        echo "   - 'sales' table (check for 'paid_amount' or 'payment' columns)\n";
        echo "   - 'purchases' table (check for 'paid_amount' or 'payment' columns)\n";
        echo "   - A different table name (check above list)\n";
        
        // Check sales table for payment columns
        if (in_array('sales', $tableNames)) {
            echo "\n=== SALES TABLE PAYMENT COLUMNS ===\n";
            $salesColumns = $conn->query("DESCRIBE sales")->fetchAll();
            $paymentColumns = array_filter($salesColumns, function($col) {
                return stripos($col['Field'], 'pay') !== false || 
                       stripos($col['Field'], 'amount') !== false;
            });
            
            if (!empty($paymentColumns)) {
                foreach ($paymentColumns as $col) {
                    echo "💳 {$col['Field']} - {$col['Type']}\n";
                }
            } else {
                echo "❌ No payment-related columns found in sales table\n";
            }
        }
    } else {
        echo "✅ Use payment tables found above for analysis\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== STRUCTURE CHECK COMPLETE ===\n";
?>