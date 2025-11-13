<?php
/**
 * Complete Database Schema Inspector
 * Checks ALL table structures to create perfectly adapted scripts
 */

require_once 'simple_database_manager.php';

echo "=== COMPLETE DATABASE SCHEMA INSPECTOR ===\n\n";

try {
    $dbManager = SimpleDatabaseManager::getInstance();
    $conn = $dbManager->getConnection();
    
    echo "✅ Database connected successfully\n\n";
    
    // Function to get table structure
    function getTableStructure($conn, $tableName) {
        try {
            $columns = $conn->query("DESCRIBE `{$tableName}`")->fetchAll();
            return array_column($columns, 'Field');
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Check essential tables structure
    $essentialTables = ['customers', 'suppliers', 'sales', 'purchases', 'sales_returns', 'purchase_returns', 'payments', 'ledgers'];
    
    $tableStructures = [];
    
    foreach ($essentialTables as $table) {
        echo "=== {$table} TABLE STRUCTURE ===\n";
        $fields = getTableStructure($conn, $table);
        
        if (!empty($fields)) {
            $tableStructures[$table] = $fields;
            foreach ($fields as $field) {
                echo "  📋 {$field}\n";
            }
        } else {
            echo "  ❌ Table not found or no access\n";
        }
        echo "\n";
    }
    
    // Generate adapted query structure
    echo "=== QUERY ADAPTATION ANALYSIS ===\n\n";
    
    // Customer fields analysis
    echo "🧑 CUSTOMER FIELDS MAPPING:\n";
    $customerFields = $tableStructures['customers'] ?? [];
    
    $customerMapping = [
        'id' => 'id',
        'name' => in_array('first_name', $customerFields) ? 'first_name' : 
                  (in_array('name', $customerFields) ? 'name' : 'id'),
        'phone' => in_array('mobile_no', $customerFields) ? 'mobile_no' : 
                   (in_array('mobile', $customerFields) ? 'mobile' : 
                   (in_array('phone', $customerFields) ? 'phone' : 'NULL')),
        'opening_balance' => in_array('opening_balance', $customerFields) ? 'opening_balance' : '0',
        'current_balance' => in_array('current_balance', $customerFields) ? 'current_balance' : 'NULL'
    ];
    
    foreach ($customerMapping as $purpose => $field) {
        echo "  {$purpose}: {$field}\n";
    }
    
    // Supplier fields analysis
    echo "\n🏪 SUPPLIER FIELDS MAPPING:\n";
    $supplierFields = $tableStructures['suppliers'] ?? [];
    
    $supplierMapping = [
        'id' => 'id',
        'name' => in_array('first_name', $supplierFields) ? 'first_name' : 
                  (in_array('name', $supplierFields) ? 'name' : 'id'),
        'phone' => in_array('mobile_no', $supplierFields) ? 'mobile_no' : 
                   (in_array('mobile', $supplierFields) ? 'mobile' : 
                   (in_array('phone', $supplierFields) ? 'phone' : 'NULL')),
        'opening_balance' => in_array('opening_balance', $supplierFields) ? 'opening_balance' : '0'
    ];
    
    foreach ($supplierMapping as $purpose => $field) {
        echo "  {$purpose}: {$field}\n";
    }
    
    // Sales table analysis
    echo "\n💰 SALES TABLE FIELDS:\n";
    $salesFields = $tableStructures['sales'] ?? [];
    
    $salesMapping = [
        'id' => 'id',
        'customer_id' => in_array('customer_id', $salesFields) ? 'customer_id' : 'NULL',
        'grand_total' => in_array('grand_total', $salesFields) ? 'grand_total' : 
                        (in_array('total', $salesFields) ? 'total' : 'amount'),
        'type_field' => in_array('type', $salesFields) ? 'type' : 
                       (in_array('transaction_type', $salesFields) ? 'transaction_type' : 'NULL'),
        'paid_amount' => in_array('paid_amount', $salesFields) ? 'paid_amount' : '0'
    ];
    
    foreach ($salesMapping as $purpose => $field) {
        echo "  {$purpose}: {$field}\n";
    }
    
    // Purchases table analysis
    echo "\n🛒 PURCHASES TABLE FIELDS:\n";
    $purchaseFields = $tableStructures['purchases'] ?? [];
    
    $purchaseMapping = [
        'id' => 'id',
        'supplier_id' => in_array('supplier_id', $purchaseFields) ? 'supplier_id' : 'NULL',
        'grand_total' => in_array('grand_total', $purchaseFields) ? 'grand_total' : 
                        (in_array('total', $purchaseFields) ? 'total' : 'amount'),
        'type_field' => in_array('type', $purchaseFields) ? 'type' : 
                       (in_array('transaction_type', $purchaseFields) ? 'transaction_type' : 'NULL'),
        'paid_amount' => in_array('paid_amount', $purchaseFields) ? 'paid_amount' : '0'
    ];
    
    foreach ($purchaseMapping as $purpose => $field) {
        echo "  {$purpose}: {$field}\n";
    }
    
    // Returns tables analysis
    echo "\n↩️ SALES RETURNS TABLE FIELDS:\n";
    $salesReturnsFields = $tableStructures['sales_returns'] ?? [];
    foreach ($salesReturnsFields as $field) {
        echo "  📋 {$field}\n";
    }
    
    echo "\n↩️ PURCHASE RETURNS TABLE FIELDS:\n";
    $purchaseReturnsFields = $tableStructures['purchase_returns'] ?? [];
    foreach ($purchaseReturnsFields as $field) {
        echo "  📋 {$field}\n";
    }
    
    // Payments table analysis
    echo "\n💳 PAYMENTS TABLE FIELDS:\n";
    $paymentFields = $tableStructures['payments'] ?? [];
    foreach ($paymentFields as $field) {
        echo "  📋 {$field}\n";
    }
    
    // Ledgers table analysis
    echo "\n📊 LEDGERS TABLE FIELDS:\n";
    $ledgerFields = $tableStructures['ledgers'] ?? [];
    foreach ($ledgerFields as $field) {
        echo "  📋 {$field}\n";
    }
    
    // Sample data check
    echo "\n=== SAMPLE DATA ANALYSIS ===\n";
    
    // Check sales table data samples
    if (!empty($salesFields)) {
        echo "💰 SALES TABLE SAMPLE:\n";
        $sampleSales = $conn->query("SELECT * FROM sales LIMIT 3")->fetchAll();
        if (!empty($sampleSales)) {
            $firstSale = $sampleSales[0];
            foreach ($firstSale as $key => $value) {
                if (!is_numeric($key)) {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
        echo "\n";
    }
    
    // Check payments table data samples
    if (!empty($paymentFields)) {
        echo "💳 PAYMENTS TABLE SAMPLE:\n";
        $samplePayments = $conn->query("SELECT * FROM payments LIMIT 3")->fetchAll();
        if (!empty($samplePayments)) {
            $firstPayment = $samplePayments[0];
            foreach ($firstPayment as $key => $value) {
                if (!is_numeric($key)) {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
    }
    
    // Save complete mapping for script generation
    $mapping = [
        'customer' => $customerMapping,
        'supplier' => $supplierMapping,
        'sales' => $salesMapping,
        'purchases' => $purchaseMapping,
        'table_structures' => $tableStructures
    ];
    
    $mappingFile = 'database_schema_mapping.json';
    file_put_contents($mappingFile, json_encode($mapping, JSON_PRETTY_PRINT));
    echo "\n📁 Complete mapping saved to: {$mappingFile}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Debug information:\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== SCHEMA INSPECTION COMPLETE ===\n";
echo "📋 Use this information to generate perfectly adapted scripts!\n";
?>