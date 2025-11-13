<?php
/**
 * PRODUCTION-SAFE LEDGER ANALYSIS SCRIPT
 * 
 * Features:
 * - Secure database connection via .env
 * - Read-only analysis (no modifications)
 * - Comprehensive logging
 * - JSON export for review
 * - Backup recommendations
 */

require_once 'secure_database_manager.php';

echo "=== PRODUCTION-SAFE LEDGER ANALYSIS ===\n\n";

try {
    // Initialize secure connection
    $dbManager = SecureDatabaseManager::getInstance();
    
    // Test connection
    if (!$dbManager->testConnection()) {
        throw new Exception("Failed to connect to database. Please check your .env configuration.");
    }
    
    $db = $dbManager->getConnection();
    $dbInfo = $dbManager->getDatabaseInfo();
    
    echo "✅ Database Connection Successful\n";
    echo "   Host: {$dbInfo['host']}\n";
    echo "   Database: {$dbInfo['database']}\n";
    echo "   User: {$dbInfo['username']}\n\n";
    
    SecurityManager::logAction("ANALYSIS_STARTED", $dbInfo);
    
    // Analysis results storage
    $analysisResults = [
        'timestamp' => date('Y-m-d H:i:s'),
        'database' => $dbInfo['database'],
        'customer_issues' => [],
        'supplier_issues' => [],
        'summary' => []
    ];
    
    echo "=== ANALYZING CUSTOMERS ===\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM customers");
    $customerCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT * FROM customers ORDER BY id");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $customerIssues = [];
    $customersWithIssues = 0;
    
    foreach ($customers as $customer) {
        $issues = [];
        $customerName = trim($customer['first_name'] . ' ' . $customer['last_name']);
        
        // Get ledger entries
        $stmt = $db->prepare("SELECT * FROM ledgers WHERE user_id = ? AND contact_type = 'customer' ORDER BY transaction_date");
        $stmt->execute([$customer['id']]);
        $ledgers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get sales records
        $stmt = $db->prepare("SELECT * FROM sales WHERE customer_id = ?");
        $stmt->execute([$customer['id']]);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate ledger balance
        $ledgerBalance = 0;
        $ledgerSales = 0;
        $ledgerPayments = 0;
        $ledgerReturns = 0;
        
        foreach ($ledgers as $ledger) {
            $ledgerBalance += $ledger['debit'] - $ledger['credit'];
            
            if ($ledger['transaction_type'] === 'sale') {
                $ledgerSales += $ledger['debit'];
            } elseif (strpos($ledger['transaction_type'], 'payment') !== false) {
                $ledgerPayments += $ledger['credit'];
            } elseif (strpos($ledger['transaction_type'], 'return') !== false) {
                $ledgerReturns += $ledger['credit'];
            }
        }
        
        // Calculate sales table totals
        $salesTotal = array_sum(array_column($sales, 'final_total'));
        $salesPaid = array_sum(array_column($sales, 'total_paid'));
        
        // Check for issues
        if (abs($ledgerBalance - $customer['current_balance']) > 0.01) {
            $issues[] = [
                'type' => 'balance_mismatch',
                'severity' => 'high',
                'description' => 'Ledger calculated balance does not match customer current_balance',
                'ledger_balance' => $ledgerBalance,
                'database_balance' => $customer['current_balance'],
                'difference' => $ledgerBalance - $customer['current_balance']
            ];
        }
        
        if (abs($ledgerSales - $salesTotal) > 0.01) {
            $issues[] = [
                'type' => 'sales_mismatch',
                'severity' => 'medium',
                'description' => 'Ledger sales total does not match sales table total',
                'ledger_sales' => $ledgerSales,
                'sales_table_total' => $salesTotal,
                'difference' => $ledgerSales - $salesTotal
            ];
        }
        
        if (abs($ledgerPayments - $salesPaid) > 0.01) {
            $issues[] = [
                'type' => 'payment_mismatch',
                'severity' => 'medium',
                'description' => 'Ledger payments do not match sales table paid amounts',
                'ledger_payments' => $ledgerPayments,
                'sales_paid' => $salesPaid,
                'difference' => $ledgerPayments - $salesPaid
            ];
        }
        
        // Check for payment reversals
        $hasReversals = false;
        foreach ($ledgers as $ledger) {
            if (strpos($ledger['notes'], 'REVERSAL') !== false) {
                $hasReversals = true;
                break;
            }
        }
        
        if ($hasReversals) {
            $issues[] = [
                'type' => 'payment_reversals',
                'severity' => 'high',
                'description' => 'Payment reversal entries found - indicates incorrect payment recording'
            ];
        }
        
        if (!empty($issues)) {
            $customersWithIssues++;
            $customerIssues[] = [
                'id' => $customer['id'],
                'name' => $customerName,
                'mobile' => $customer['mobile_no'],
                'current_balance' => $customer['current_balance'],
                'calculated_balance' => $ledgerBalance,
                'issues' => $issues
            ];
            
            echo "❌ {$customerName} (ID: {$customer['id']}) - " . count($issues) . " issues\n";
        } else {
            echo "✅ {$customerName} (ID: {$customer['id']}) - No issues\n";
        }
    }
    
    echo "\n=== ANALYZING SUPPLIERS ===\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM suppliers");
    $supplierCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT * FROM suppliers ORDER BY id");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $supplierIssues = [];
    $suppliersWithIssues = 0;
    
    foreach ($suppliers as $supplier) {
        $issues = [];
        $supplierName = trim($supplier['first_name'] . ' ' . $supplier['last_name']);
        
        // Get ledger entries
        $stmt = $db->prepare("SELECT * FROM ledgers WHERE user_id = ? AND contact_type = 'supplier' ORDER BY transaction_date");
        $stmt->execute([$supplier['id']]);
        $ledgers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get purchase records
        $stmt = $db->prepare("SELECT * FROM purchases WHERE supplier_id = ?");
        $stmt->execute([$supplier['id']]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate ledger balance (for suppliers: credits increase balance, debits decrease)
        $ledgerBalance = 0;
        $ledgerPurchases = 0;
        $ledgerPayments = 0;
        $ledgerReturns = 0;
        
        foreach ($ledgers as $ledger) {
            if ($ledger['transaction_type'] === 'opening_balance') {
                $ledgerBalance += $ledger['credit'] - $ledger['debit'];
            } elseif ($ledger['transaction_type'] === 'purchase') {
                $ledgerPurchases += $ledger['credit'];
                $ledgerBalance += $ledger['credit'];
            } elseif (strpos($ledger['transaction_type'], 'payment') !== false) {
                $ledgerPayments += $ledger['debit'];
                $ledgerBalance -= $ledger['debit'];
            } elseif (strpos($ledger['transaction_type'], 'return') !== false) {
                $ledgerReturns += $ledger['debit'];
                $ledgerBalance -= $ledger['debit'];
            }
        }
        
        // Calculate purchase table totals
        $purchaseTotal = array_sum(array_column($purchases, 'final_total'));
        $purchasePaid = array_sum(array_column($purchases, 'total_paid'));
        
        // Check for issues
        if (abs($ledgerBalance - $supplier['current_balance']) > 0.01) {
            $issues[] = [
                'type' => 'balance_mismatch',
                'severity' => 'high',
                'description' => 'Ledger calculated balance does not match supplier current_balance',
                'ledger_balance' => $ledgerBalance,
                'database_balance' => $supplier['current_balance'],
                'difference' => $ledgerBalance - $supplier['current_balance']
            ];
        }
        
        if (abs($ledgerPurchases - $purchaseTotal) > 0.01) {
            $issues[] = [
                'type' => 'purchase_mismatch',
                'severity' => 'medium',
                'description' => 'Ledger purchase total does not match purchase table total',
                'ledger_purchases' => $ledgerPurchases,
                'purchase_table_total' => $purchaseTotal,
                'difference' => $ledgerPurchases - $purchaseTotal
            ];
        }
        
        if (!empty($issues)) {
            $suppliersWithIssues++;
            $supplierIssues[] = [
                'id' => $supplier['id'],
                'name' => $supplierName,
                'mobile' => $supplier['mobile_no'],
                'current_balance' => $supplier['current_balance'],
                'calculated_balance' => $ledgerBalance,
                'issues' => $issues
            ];
            
            echo "❌ {$supplierName} (ID: {$supplier['id']}) - " . count($issues) . " issues\n";
        } else {
            echo "✅ {$supplierName} (ID: {$supplier['id']}) - No issues\n";
        }
    }
    
    // Prepare analysis results
    $analysisResults['customer_issues'] = $customerIssues;
    $analysisResults['supplier_issues'] = $supplierIssues;
    $analysisResults['summary'] = [
        'total_customers' => $customerCount,
        'customers_with_issues' => $customersWithIssues,
        'customer_success_rate' => round(($customerCount - $customersWithIssues) / $customerCount * 100, 2),
        'total_suppliers' => $supplierCount,
        'suppliers_with_issues' => $suppliersWithIssues,
        'supplier_success_rate' => round(($supplierCount - $suppliersWithIssues) / $supplierCount * 100, 2),
        'total_issues_found' => array_sum([
            array_sum(array_map(function($c) { return count($c['issues']); }, $customerIssues)),
            array_sum(array_map(function($s) { return count($s['issues']); }, $supplierIssues))
        ])
    ];
    
    // Save results
    $filename = 'ledger_analysis_' . date('Ymd_His') . '.json';
    file_put_contents($filename, json_encode($analysisResults, JSON_PRETTY_PRINT));
    
    echo "\n=== ANALYSIS SUMMARY ===\n";
    echo "Total Customers: {$analysisResults['summary']['total_customers']}\n";
    echo "Customers with Issues: {$analysisResults['summary']['customers_with_issues']}\n";
    echo "Customer Success Rate: {$analysisResults['summary']['customer_success_rate']}%\n\n";
    
    echo "Total Suppliers: {$analysisResults['summary']['total_suppliers']}\n";
    echo "Suppliers with Issues: {$analysisResults['summary']['suppliers_with_issues']}\n";
    echo "Supplier Success Rate: {$analysisResults['summary']['supplier_success_rate']}%\n\n";
    
    echo "Total Issues Found: {$analysisResults['summary']['total_issues_found']}\n";
    
    echo "\n✅ Analysis saved to: {$filename}\n";
    
    SecurityManager::logAction("ANALYSIS_COMPLETED", [
        'issues_found' => $analysisResults['summary']['total_issues_found'],
        'customers_affected' => $customersWithIssues,
        'suppliers_affected' => $suppliersWithIssues,
        'report_file' => $filename
    ]);
    
    if ($analysisResults['summary']['total_issues_found'] > 0) {
        echo "\n⚠️  ISSUES FOUND: Run the production-safe fix script to resolve them.\n";
        echo "📁 Review the detailed analysis in: {$filename}\n";
    } else {
        echo "\n🎉 NO ISSUES FOUND: All ledger records are consistent!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    SecurityManager::logAction("ANALYSIS_ERROR", ['error' => $e->getMessage()]);
    exit(1);
}

echo "\n=== ANALYSIS COMPLETE ===\n";
echo "Check ledger_operations.log for detailed operation logs.\n";
?>