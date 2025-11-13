<?php
/**
 * Production-Ready Ledger Fix - Adapted for Your Database Schema
 * Uses the actual 'payments' table and handles all column variations
 */

require_once 'simple_database_manager.php';

echo "=== PRODUCTION LEDGER FIX ===\n\n";

// Check for dry-run mode
$isDryRun = in_array('--dry-run', $argv);
$noConfirm = in_array('--no-confirm', $argv);

if ($isDryRun) {
    echo "🧪 DRY RUN MODE - No database changes will be made\n\n";
}

try {
    $dbManager = SimpleDatabaseManager::getInstance();
    $securityManager = new SimpleSecurityManager($dbManager);
    $conn = $dbManager->getConnection();
    
    echo "✅ Database connected\n\n";
    
    // Detect table structure
    $customerColumns = $conn->query("DESCRIBE customers")->fetchAll();
    $customerFields = array_column($customerColumns, 'Field');
    
    $supplierColumns = $conn->query("DESCRIBE suppliers")->fetchAll();
    $supplierFields = array_column($supplierColumns, 'Field');
    
    $nameField = in_array('first_name', $customerFields) ? 'first_name' : 
                (in_array('name', $customerFields) ? 'name' : 'id');
    $supplierNameField = in_array('first_name', $supplierFields) ? 'first_name' : 
                        (in_array('name', $supplierFields) ? 'name' : 'id');
    
    echo "📋 Using customer name field: {$nameField}\n";
    echo "📋 Using supplier name field: {$supplierNameField}\n\n";
    
    if (!$isDryRun && !$noConfirm) {
        if (!$securityManager->confirmAction("Create database backups before fixing?")) {
            echo "❌ Operation cancelled\n";
            exit(0);
        }
    }
    
    // Create backups (unless dry run)
    $backupTables = [];
    if (!$isDryRun) {
        echo "🔄 Creating backups...\n";
        $backupTables['customers'] = $securityManager->createBackup('customers');
        $backupTables['suppliers'] = $securityManager->createBackup('suppliers');
        $backupTables['ledgers'] = $securityManager->createBackup('ledgers');
        echo "✅ Backups created\n\n";
    }
    
    $totalFixed = 0;
    
    if (!$isDryRun) {
        $conn->beginTransaction();
    }
    
    try {
        // Fix Customer Ledgers
        echo "=== FIXING CUSTOMER LEDGERS ===\n";
        
        $customerQuery = "
            SELECT 
                c.id,
                c.{$nameField} as customer_name,
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
            GROUP BY c.id, c.{$nameField}, c.opening_balance
        ";
        
        $customers = $conn->query($customerQuery)->fetchAll();
        
        foreach ($customers as $customer) {
            $expectedBalance = $customer['opening_balance'] + $customer['total_sales'] - $customer['total_returns'] - $customer['total_payments'];
            $ledgerBalance = $customer['ledger_debits'] - $customer['ledger_credits'];
            
            if (abs($expectedBalance - $ledgerBalance) > 0.01) {
                $difference = $expectedBalance - $ledgerBalance;
                
                if ($isDryRun) {
                    echo "🔍 Would fix {$customer['customer_name']}: Difference = " . number_format($difference, 2) . "\n";
                } else {
                    // Clear existing ledgers for this customer
                    $conn->prepare("DELETE FROM ledgers WHERE customer_id = ?")->execute([$customer['id']]);
                    
                    // Create correct ledger entry
                    if ($expectedBalance != 0) {
                        $insertLedger = $conn->prepare("
                            INSERT INTO ledgers (customer_id, transaction_type, debit, credit, balance, created_at, updated_at) 
                            VALUES (?, 'balance_correction', ?, 0, ?, NOW(), NOW())
                        ");
                        $insertLedger->execute([$customer['id'], $expectedBalance, $expectedBalance]);
                    }
                    
                    echo "✅ Fixed {$customer['customer_name']}: Balance = " . number_format($expectedBalance, 2) . "\n";
                }
                
                $totalFixed++;
            }
        }
        
        // Fix Supplier Ledgers
        echo "\n=== FIXING SUPPLIER LEDGERS ===\n";
        
        $supplierQuery = "
            SELECT 
                s.id,
                s.{$supplierNameField} as supplier_name,
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
            GROUP BY s.id, s.{$supplierNameField}, s.opening_balance
        ";
        
        $suppliers = $conn->query($supplierQuery)->fetchAll();
        
        foreach ($suppliers as $supplier) {
            $expectedBalance = $supplier['opening_balance'] + $supplier['total_purchases'] - $supplier['total_returns'] - $supplier['total_payments'];
            $ledgerBalance = $supplier['ledger_credits'] - $supplier['ledger_debits'];
            
            if (abs($expectedBalance - $ledgerBalance) > 0.01) {
                $difference = $expectedBalance - $ledgerBalance;
                
                if ($isDryRun) {
                    echo "🔍 Would fix {$supplier['supplier_name']}: Difference = " . number_format($difference, 2) . "\n";
                } else {
                    // Clear existing ledgers for this supplier
                    $conn->prepare("DELETE FROM ledgers WHERE supplier_id = ?")->execute([$supplier['id']]);
                    
                    // Create correct ledger entry
                    if ($expectedBalance != 0) {
                        $insertLedger = $conn->prepare("
                            INSERT INTO ledgers (supplier_id, transaction_type, debit, credit, balance, created_at, updated_at) 
                            VALUES (?, 'balance_correction', 0, ?, ?, NOW(), NOW())
                        ");
                        $insertLedger->execute([$supplier['id'], $expectedBalance, $expectedBalance]);
                    }
                    
                    echo "✅ Fixed {$supplier['supplier_name']}: Balance = " . number_format($expectedBalance, 2) . "\n";
                }
                
                $totalFixed++;
            }
        }
        
        if (!$isDryRun) {
            $conn->commit();
            echo "\n✅ All changes committed successfully!\n";
        }
        
    } catch (Exception $e) {
        if (!$isDryRun) {
            $conn->rollback();
            echo "\n❌ Error occurred, rolled back changes: " . $e->getMessage() . "\n";
        }
        throw $e;
    }
    
    echo "\n=== FIX SUMMARY ===\n";
    echo "Total Issues " . ($isDryRun ? "Found" : "Fixed") . ": {$totalFixed}\n";
    
    if (!$isDryRun && !empty($backupTables)) {
        echo "\n📦 Backup Tables Created:\n";
        foreach ($backupTables as $original => $backup) {
            echo "   {$original} -> {$backup}\n";
        }
    }
    
    if ($isDryRun) {
        echo "\n🧪 This was a dry run. Use 'php production_ready_fix.php' to apply changes.\n";
    } else {
        echo "\n🎉 Ledger fixes completed successfully!\n";
        echo "🔍 Run 'php production_ready_analysis.php' to verify fixes.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Debug information:\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== FIX COMPLETE ===\n";
?>