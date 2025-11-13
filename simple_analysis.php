<?php
/**
 * Simple Production Ledger Analysis - No Validation
 * Works directly with database without connection checks
 */

require_once 'simple_database_manager.php';

echo "=== SIMPLE LEDGER ANALYSIS ===\n\n";

try {
    // Get database manager
    $dbManager = SimpleDatabaseManager::getInstance();
    $conn = $dbManager->getConnection();
    
    echo "✅ Database connected successfully\n\n";
    
    // Customer Analysis
    echo "=== CUSTOMER ANALYSIS ===\n";
    
    $customerQuery = "
        SELECT 
            c.id,
            c.first_name,
            c.mobile,
            c.opening_balance,
            COALESCE(SUM(CASE WHEN s.transaction_type = 'sale' THEN s.grand_total ELSE 0 END), 0) as total_sales,
            COALESCE(SUM(CASE WHEN s.transaction_type = 'sale_return' THEN s.grand_total ELSE 0 END), 0) as total_returns,
            COALESCE(SUM(tp.amount), 0) as total_payments,
            COALESCE(SUM(CASE WHEN l.debit > 0 THEN l.debit ELSE 0 END), 0) as ledger_debits,
            COALESCE(SUM(CASE WHEN l.credit > 0 THEN l.credit ELSE 0 END), 0) as ledger_credits
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id
        LEFT JOIN transaction_payments tp ON s.id = tp.transaction_id AND tp.transaction_type = 'sale'
        LEFT JOIN ledgers l ON c.id = l.customer_id
        GROUP BY c.id, c.first_name, c.mobile, c.opening_balance
        ORDER BY c.first_name
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
        
        if (!$isBalanceCorrect) {
            $customerIssues++;
            echo "❌ {$customer['first_name']} ({$customer['mobile']}): Balance mismatch\n";
            echo "   Expected: {$expectedBalance}, Ledger: {$ledgerBalance}\n";
        } else {
            echo "✅ {$customer['first_name']}: Balance OK ({$expectedBalance})\n";
        }
    }
    
    echo "\nCustomer Summary: " . count($customers) . " customers, {$customerIssues} issues\n\n";
    
    // Supplier Analysis
    echo "=== SUPPLIER ANALYSIS ===\n";
    
    $supplierQuery = "
        SELECT 
            s.id,
            s.first_name,
            s.mobile,
            s.opening_balance,
            COALESCE(SUM(CASE WHEN p.transaction_type = 'purchase' THEN p.grand_total ELSE 0 END), 0) as total_purchases,
            COALESCE(SUM(CASE WHEN p.transaction_type = 'purchase_return' THEN p.grand_total ELSE 0 END), 0) as total_returns,
            COALESCE(SUM(tp.amount), 0) as total_payments,
            COALESCE(SUM(CASE WHEN l.debit > 0 THEN l.debit ELSE 0 END), 0) as ledger_debits,
            COALESCE(SUM(CASE WHEN l.credit > 0 THEN l.credit ELSE 0 END), 0) as ledger_credits
        FROM suppliers s
        LEFT JOIN purchases p ON s.id = p.supplier_id
        LEFT JOIN transaction_payments tp ON p.id = tp.transaction_id AND tp.transaction_type = 'purchase'
        LEFT JOIN ledgers l ON s.id = l.supplier_id
        GROUP BY s.id, s.first_name, s.mobile, s.opening_balance
        ORDER BY s.first_name
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
        
        if (!$isBalanceCorrect) {
            $supplierIssues++;
            echo "❌ {$supplier['first_name']} ({$supplier['mobile']}): Balance mismatch\n";
            echo "   Expected: {$expectedBalance}, Ledger: {$ledgerBalance}\n";
        } else {
            echo "✅ {$supplier['first_name']}: Balance OK ({$expectedBalance})\n";
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
    } else {
        echo "\n⚠️  Issues found - run fixing script to resolve\n";
    }
    
    // Save simple report
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'customers_analyzed' => count($customers),
        'customer_issues' => $customerIssues,
        'suppliers_analyzed' => count($suppliers),
        'supplier_issues' => $supplierIssues,
        'total_issues' => $customerIssues + $supplierIssues,
        'total_receivables' => $totalReceivables,
        'total_payables' => $totalPayables
    ];
    
    $reportFile = 'simple_analysis_' . date('Ymd_His') . '.json';
    file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
    echo "📁 Report saved to: {$reportFile}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Please check your database configuration and try again.\n";
    exit(1);
}

echo "\n=== ANALYSIS COMPLETE ===\n";
?>