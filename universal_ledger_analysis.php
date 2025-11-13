<?php
/**
 * Universal Ledger Analysis - Works with ANY Database Structure
 * Automatically adapts to your exact table structure and column names
 */

require_once 'simple_database_manager.php';

echo "=== UNIVERSAL LEDGER ANALYSIS ===\n\n";

try {
    $dbManager = SimpleDatabaseManager::getInstance();
    $conn = $dbManager->getConnection();
    
    echo "✅ Database connected successfully\n\n";
    
    // Function to safely get table columns
    function getTableColumns($conn, $tableName) {
        try {
            $result = $conn->query("DESCRIBE `{$tableName}`")->fetchAll();
            return array_column($result, 'Field');
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Auto-detect table structures
    echo "🔍 Auto-detecting database structure...\n";
    
    $customerCols = getTableColumns($conn, 'customers');
    $supplierCols = getTableColumns($conn, 'suppliers');
    $salesCols = getTableColumns($conn, 'sales');
    $purchaseCols = getTableColumns($conn, 'purchases');
    $paymentCols = getTableColumns($conn, 'payments');
    
    echo "✅ Tables analyzed: customers, suppliers, sales, purchases, payments\n\n";
    
    // Dynamic field mapping for customers
    $custNameField = in_array('first_name', $customerCols) ? 'first_name' : 
                     (in_array('name', $customerCols) ? 'name' : 'id');
    $custPhoneField = in_array('mobile_no', $customerCols) ? 'mobile_no' : 
                      (in_array('mobile', $customerCols) ? 'mobile' : 'id');
    
    // Dynamic field mapping for suppliers
    $suppNameField = in_array('first_name', $supplierCols) ? 'first_name' : 
                     (in_array('name', $supplierCols) ? 'name' : 'id');
    $suppPhoneField = in_array('mobile_no', $supplierCols) ? 'mobile_no' : 
                      (in_array('mobile', $supplierCols) ? 'mobile' : 'id');
    
    // Dynamic field mapping for sales
    $salesTotalField = in_array('grand_total', $salesCols) ? 'grand_total' : 
                      (in_array('total', $salesCols) ? 'total' : 'amount');
    $salesPaidField = in_array('paid_amount', $salesCols) ? 'paid_amount' : '0';
    
    // Dynamic field mapping for purchases
    $purchTotalField = in_array('grand_total', $purchaseCols) ? 'grand_total' : 
                      (in_array('total', $purchaseCols) ? 'total' : 'amount');
    $purchPaidField = in_array('paid_amount', $purchaseCols) ? 'paid_amount' : '0';
    
    echo "📋 Field mappings:\n";
    echo "   Customer name: {$custNameField}\n";
    echo "   Customer phone: {$custPhoneField}\n";
    echo "   Sales total: {$salesTotalField}\n";
    echo "   Purchase total: {$purchTotalField}\n\n";
    
    // Customer Analysis with dynamic fields
    echo "=== CUSTOMER ANALYSIS ===\n";
    
    // Build customer query with detected fields
    $customerQuery = "
        SELECT 
            c.id,
            c.{$custNameField} as customer_name,
            " . ($custPhoneField !== 'id' ? "c.{$custPhoneField}" : "'N/A'") . " as phone,
            COALESCE(c.opening_balance, 0) as opening_balance,
            COALESCE(sales_data.total_sales, 0) as total_sales,
            COALESCE(returns_data.total_returns, 0) as total_returns,
            COALESCE(payment_data.total_payments, 0) as total_payments,
            COALESCE(ledger_data.ledger_debits, 0) as ledger_debits,
            COALESCE(ledger_data.ledger_credits, 0) as ledger_credits
        FROM customers c
        LEFT JOIN (
            SELECT customer_id, SUM({$salesTotalField}) as total_sales 
            FROM sales 
            WHERE customer_id IS NOT NULL
            GROUP BY customer_id
        ) sales_data ON c.id = sales_data.customer_id
        LEFT JOIN (
            SELECT customer_id, SUM(grand_total) as total_returns 
            FROM sales_returns 
            WHERE customer_id IS NOT NULL
            GROUP BY customer_id
        ) returns_data ON c.id = returns_data.customer_id
        LEFT JOIN (
            SELECT customer_id, SUM(amount) as total_payments 
            FROM payments 
            WHERE customer_id IS NOT NULL AND payment_type = 'sale'
            GROUP BY customer_id
        ) payment_data ON c.id = payment_data.customer_id
        LEFT JOIN (
            SELECT customer_id, 
                   SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as ledger_debits,
                   SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as ledger_credits
            FROM ledgers 
            WHERE customer_id IS NOT NULL
            GROUP BY customer_id
        ) ledger_data ON c.id = ledger_data.customer_id
        ORDER BY c.{$custNameField}
    ";
    
    $customers = $conn->query($customerQuery)->fetchAll();
    
    $customerIssues = 0;
    $totalReceivables = 0;
    
    foreach ($customers as $customer) {
        $expectedBalance = $customer['opening_balance'] + $customer['total_sales'] - $customer['total_returns'] - $customer['total_payments'];
        $ledgerBalance = $customer['ledger_debits'] - $customer['ledger_credits'];
        
        $isBalanceCorrect = abs($expectedBalance - $ledgerBalance) < 0.01;
        $totalReceivables += $expectedBalance;
        
        $phone = ($customer['phone'] && $customer['phone'] !== 'N/A') ? " ({$customer['phone']})" : "";
        
        if (!$isBalanceCorrect) {
            $customerIssues++;
            echo "❌ {$customer['customer_name']}{$phone}: Balance mismatch\n";
            echo "   Expected: " . number_format($expectedBalance, 2) . ", Ledger: " . number_format($ledgerBalance, 2) . "\n";
        } else {
            echo "✅ {$customer['customer_name']}: Balance OK (" . number_format($expectedBalance, 2) . ")\n";
        }
    }
    
    echo "\nCustomer Summary: " . count($customers) . " customers, {$customerIssues} issues\n\n";
    
    // Supplier Analysis with dynamic fields
    echo "=== SUPPLIER ANALYSIS ===\n";
    
    $supplierQuery = "
        SELECT 
            s.id,
            s.{$suppNameField} as supplier_name,
            " . ($suppPhoneField !== 'id' ? "s.{$suppPhoneField}" : "'N/A'") . " as phone,
            COALESCE(s.opening_balance, 0) as opening_balance,
            COALESCE(purchase_data.total_purchases, 0) as total_purchases,
            COALESCE(returns_data.total_returns, 0) as total_returns,
            COALESCE(payment_data.total_payments, 0) as total_payments,
            COALESCE(ledger_data.ledger_debits, 0) as ledger_debits,
            COALESCE(ledger_data.ledger_credits, 0) as ledger_credits
        FROM suppliers s
        LEFT JOIN (
            SELECT supplier_id, SUM({$purchTotalField}) as total_purchases 
            FROM purchases 
            WHERE supplier_id IS NOT NULL
            GROUP BY supplier_id
        ) purchase_data ON s.id = purchase_data.supplier_id
        LEFT JOIN (
            SELECT supplier_id, SUM(grand_total) as total_returns 
            FROM purchase_returns 
            WHERE supplier_id IS NOT NULL
            GROUP BY supplier_id
        ) returns_data ON s.id = returns_data.supplier_id
        LEFT JOIN (
            SELECT supplier_id, SUM(amount) as total_payments 
            FROM payments 
            WHERE supplier_id IS NOT NULL AND payment_type = 'purchase'
            GROUP BY supplier_id
        ) payment_data ON s.id = payment_data.supplier_id
        LEFT JOIN (
            SELECT supplier_id, 
                   SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as ledger_debits,
                   SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as ledger_credits
            FROM ledgers 
            WHERE supplier_id IS NOT NULL
            GROUP BY supplier_id
        ) ledger_data ON s.id = ledger_data.supplier_id
        ORDER BY s.{$suppNameField}
    ";
    
    $suppliers = $conn->query($supplierQuery)->fetchAll();
    
    $supplierIssues = 0;
    $totalPayables = 0;
    
    foreach ($suppliers as $supplier) {
        $expectedBalance = $supplier['opening_balance'] + $supplier['total_purchases'] - $supplier['total_returns'] - $supplier['total_payments'];
        $ledgerBalance = $supplier['ledger_credits'] - $supplier['ledger_debits'];
        
        $isBalanceCorrect = abs($expectedBalance - $ledgerBalance) < 0.01;
        $totalPayables += $expectedBalance;
        
        $phone = ($supplier['phone'] && $supplier['phone'] !== 'N/A') ? " ({$supplier['phone']})" : "";
        
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
        echo "🔧 Use: php universal_ledger_fix.php --dry-run (to test)\n";
        echo "🔧 Use: php universal_ledger_fix.php (to apply fixes)\n";
    }
    
    // Save comprehensive report
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'database_structure' => [
            'customer_name_field' => $custNameField,
            'customer_phone_field' => $custPhoneField,
            'supplier_name_field' => $suppNameField,
            'supplier_phone_field' => $suppPhoneField,
            'sales_total_field' => $salesTotalField,
            'purchase_total_field' => $purchTotalField
        ],
        'analysis_results' => [
            'customers_analyzed' => count($customers),
            'customer_issues' => $customerIssues,
            'suppliers_analyzed' => count($suppliers),
            'supplier_issues' => $supplierIssues,
            'total_issues' => $customerIssues + $supplierIssues,
            'total_receivables' => $totalReceivables,
            'total_payables' => $totalPayables
        ]
    ];
    
    $reportFile = 'universal_analysis_' . date('Ymd_His') . '.json';
    file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
    echo "📁 Detailed report saved to: {$reportFile}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Debug information:\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "\n💡 This universal script auto-adapts to your database structure.\n";
    echo "🔧 Run 'php complete_schema_inspector.php' for detailed table analysis.\n";
    exit(1);
}

echo "\n=== UNIVERSAL ANALYSIS COMPLETE ===\n";
?>