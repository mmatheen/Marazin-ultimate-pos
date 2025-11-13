<?php
/**
 * Production-Ready Ledger Analysis - Adapted for Your Database Schema
 * Uses the actual 'payments' table and correct column names
 */

require_once 'simple_database_manager.php';

echo "=== PRODUCTION LEDGER ANALYSIS ===\n\n";

try {
    // Get database manager
    $dbManager = SimpleDatabaseManager::getInstance();
    $conn = $dbManager->getConnection();
    
    echo "✅ Database connected successfully\n\n";
    
    // First, let's check the actual structure of customers table
    echo "=== CHECKING TABLE STRUCTURES ===\n";
    
    // Check customers table structure
    $customerColumns = $conn->query("DESCRIBE customers")->fetchAll();
    $customerFields = array_column($customerColumns, 'Field');
    echo "📋 Customer fields: " . implode(', ', $customerFields) . "\n";
    
    // Check suppliers table structure  
    $supplierColumns = $conn->query("DESCRIBE suppliers")->fetchAll();
    $supplierFields = array_column($supplierColumns, 'Field');
    echo "📋 Supplier fields: " . implode(', ', $supplierFields) . "\n\n";
    
    // Customer Analysis using actual table structure
    echo "=== CUSTOMER ANALYSIS ===\n";
    
    // Build customer query with available fields
    $nameField = in_array('first_name', $customerFields) ? 'first_name' : 
                (in_array('name', $customerFields) ? 'name' : 'id');
    $phoneField = in_array('mobile', $customerFields) ? 'mobile' : 
                  (in_array('phone', $customerFields) ? 'phone' : 
                  (in_array('contact_number', $customerFields) ? 'contact_number' : 'NULL'));
    
    $customerQuery = "
        SELECT 
            c.id,
            c.{$nameField} as customer_name,
            " . ($phoneField !== 'NULL' ? "c.{$phoneField}" : 'NULL') . " as phone,
            COALESCE(c.opening_balance, 0) as opening_balance,
            COALESCE(SUM(CASE WHEN s.type = 'sale' OR s.type IS NULL THEN s.grand_total ELSE 0 END), 0) as total_sales,
            COALESCE(SUM(CASE WHEN sr.grand_total IS NOT NULL THEN sr.grand_total ELSE 0 END), 0) as total_returns,
            COALESCE(SUM(CASE WHEN p.payment_type = 'sale' THEN p.amount ELSE 0 END), 0) as total_payments,
            COALESCE(SUM(CASE WHEN l.debit > 0 THEN l.debit ELSE 0 END), 0) as ledger_debits,
            COALESCE(SUM(CASE WHEN l.credit > 0 THEN l.credit ELSE 0 END), 0) as ledger_credits
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id
        LEFT JOIN sales_returns sr ON c.id = sr.customer_id
        LEFT JOIN payments p ON c.id = p.customer_id AND p.payment_type = 'sale'
        LEFT JOIN ledgers l ON c.id = l.customer_id
        GROUP BY c.id, c.{$nameField}, " . ($phoneField !== 'NULL' ? "c.{$phoneField}" : 'NULL') . ", c.opening_balance
        ORDER BY c.{$nameField}
    ";
    
    $customerStmt = $conn->query($customerQuery);
    $customers = $customerStmt->fetchAll();
    
    $customerIssues = 0;
    $totalReceivables = 0;
    
    foreach ($customers as $customer) {
        $expectedBalance = $customer['opening_balance'] + $customer['total_sales'] - $customer['total_returns'] - $customer['total_payments'];
        $ledgerBalance = $customer['ledger_debits'] - $customer['ledger_credits'];
        
        $isBalanceCorrect = abs($expectedBalance - $ledgerBalance) < 0.01;
        $totalReceivables += $expectedBalance;
        
        $phone = $customer['phone'] ? " ({$customer['phone']})" : "";
        
        if (!$isBalanceCorrect) {
            $customerIssues++;
            echo "❌ {$customer['customer_name']}{$phone}: Balance mismatch\n";
            echo "   Expected: " . number_format($expectedBalance, 2) . ", Ledger: " . number_format($ledgerBalance, 2) . "\n";
        } else {
            echo "✅ {$customer['customer_name']}: Balance OK (" . number_format($expectedBalance, 2) . ")\n";
        }
    }
    
    echo "\nCustomer Summary: " . count($customers) . " customers, {$customerIssues} issues\n\n";
    
    // Supplier Analysis
    echo "=== SUPPLIER ANALYSIS ===\n";
    
    $supplierNameField = in_array('first_name', $supplierFields) ? 'first_name' : 
                        (in_array('name', $supplierFields) ? 'name' : 'id');
    $supplierPhoneField = in_array('mobile', $supplierFields) ? 'mobile' : 
                         (in_array('phone', $supplierFields) ? 'phone' : 
                         (in_array('contact_number', $supplierFields) ? 'contact_number' : 'NULL'));
    
    $supplierQuery = "
        SELECT 
            s.id,
            s.{$supplierNameField} as supplier_name,
            " . ($supplierPhoneField !== 'NULL' ? "s.{$supplierPhoneField}" : 'NULL') . " as phone,
            COALESCE(s.opening_balance, 0) as opening_balance,
            COALESCE(SUM(CASE WHEN p.type = 'purchase' OR p.type IS NULL THEN p.grand_total ELSE 0 END), 0) as total_purchases,
            COALESCE(SUM(CASE WHEN pr.grand_total IS NOT NULL THEN pr.grand_total ELSE 0 END), 0) as total_returns,
            COALESCE(SUM(CASE WHEN pay.payment_type = 'purchase' THEN pay.amount ELSE 0 END), 0) as total_payments,
            COALESCE(SUM(CASE WHEN l.debit > 0 THEN l.debit ELSE 0 END), 0) as ledger_debits,
            COALESCE(SUM(CASE WHEN l.credit > 0 THEN l.credit ELSE 0 END), 0) as ledger_credits
        FROM suppliers s
        LEFT JOIN purchases p ON s.id = p.supplier_id
        LEFT JOIN purchase_returns pr ON s.id = pr.supplier_id
        LEFT JOIN payments pay ON s.id = pay.supplier_id AND pay.payment_type = 'purchase'
        LEFT JOIN ledgers l ON s.id = l.supplier_id
        GROUP BY s.id, s.{$supplierNameField}, " . ($supplierPhoneField !== 'NULL' ? "s.{$supplierPhoneField}" : 'NULL') . ", s.opening_balance
        ORDER BY s.{$supplierNameField}
    ";
    
    $supplierStmt = $conn->query($supplierQuery);
    $suppliers = $supplierStmt->fetchAll();
    
    $supplierIssues = 0;
    $totalPayables = 0;
    
    foreach ($suppliers as $supplier) {
        $expectedBalance = $supplier['opening_balance'] + $supplier['total_purchases'] - $supplier['total_returns'] - $supplier['total_payments'];
        $ledgerBalance = $supplier['ledger_credits'] - $supplier['ledger_debits'];
        
        $isBalanceCorrect = abs($expectedBalance - $ledgerBalance) < 0.01;
        $totalPayables += $expectedBalance;
        
        $phone = $supplier['phone'] ? " ({$supplier['phone']})" : "";
        
        if (!$isBalanceCorrect) {
            $supplierIssues++;
            echo "❌ {$supplier['supplier_name']}{$phone}: Balance mismatch\n";
            echo "   Expected: " . number_format($expectedBalance, 2) . ", Ledger: " . number_format($ledgerBalance, 2) . "\n";
        } else {
            echo "✅ {$supplier['supplier_name']}: Balance OK (" . number_format($expectedBalance, 2) . ")\n";
        }
    }
    
    echo "\nSupplier Summary: " . count($suppliers) . " suppliers, {$supplierIssues} issues\n\n";
    
    // Overall Summary
    echo "=== SUMMARY ===\n";
    echo "Total Issues Found: " . ($customerIssues + $supplierIssues) . "\n";
    echo "Total Receivables: " . number_format($totalReceivables, 2) . "\n";
    echo "Total Payables: " . number_format($totalPayables, 2) . "\n";
    
    if (($customerIssues + $supplierIssues) === 0) {
        echo "\n🎉 ALL LEDGER RECORDS ARE CONSISTENT!\n";
        echo "✅ Customer ledgers: 100% accurate\n";
        echo "✅ Supplier ledgers: 100% accurate\n";
        echo "✅ Financial records: Fully synchronized\n";
    } else {
        echo "\n⚠️  Issues found - run fixing script to resolve\n";
        echo "🔧 Use: php production_ready_fix.php --dry-run (to test)\n";
        echo "🔧 Use: php production_ready_fix.php (to apply fixes)\n";
    }
    
    // Save detailed report
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'database_schema' => [
            'customer_name_field' => $nameField,
            'customer_phone_field' => $phoneField,
            'supplier_name_field' => $supplierNameField,
            'supplier_phone_field' => $supplierPhoneField
        ],
        'analysis_results' => [
            'customers_analyzed' => count($customers),
            'customer_issues' => $customerIssues,
            'suppliers_analyzed' => count($suppliers),
            'supplier_issues' => $supplierIssues,
            'total_issues' => $customerIssues + $supplierIssues,
            'total_receivables' => $totalReceivables,
            'total_payables' => $totalPayables
        ],
        'customer_details' => $customers,
        'supplier_details' => $suppliers
    ];
    
    $reportFile = 'production_analysis_' . date('Ymd_His') . '.json';
    file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
    echo "📁 Detailed report saved to: {$reportFile}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Debug information:\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== ANALYSIS COMPLETE ===\n";
?>