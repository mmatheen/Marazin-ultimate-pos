<?php
/**
 * Fixed Simple Ledger Fix - Works with actual database structure
 * Uses sales/purchases paid_amount instead of transaction_payments table
 */

require_once 'simple_database_manager.php';

echo "=== FIXED SIMPLE LEDGER FIX ===\n\n";

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
                c.first_name,
                c.opening_balance,
                COALESCE(SUM(CASE WHEN s.transaction_type = 'sale' THEN s.grand_total ELSE 0 END), 0) as total_sales,
                COALESCE(SUM(CASE WHEN s.transaction_type = 'sale_return' THEN s.grand_total ELSE 0 END), 0) as total_returns,
                COALESCE(SUM(CASE WHEN s.transaction_type = 'sale' THEN COALESCE(s.paid_amount, 0) ELSE 0 END), 0) as total_payments,
                COALESCE(SUM(CASE WHEN l.debit > 0 THEN l.debit ELSE 0 END), 0) as ledger_debits,
                COALESCE(SUM(CASE WHEN l.credit > 0 THEN l.credit ELSE 0 END), 0) as ledger_credits
            FROM customers c
            LEFT JOIN sales s ON c.id = s.customer_id
            LEFT JOIN ledgers l ON c.id = l.customer_id
            GROUP BY c.id, c.first_name, c.opening_balance
        ";
        
        $customers = $conn->query($customerQuery)->fetchAll();
        
        foreach ($customers as $customer) {
            $expectedBalance = $customer['opening_balance'] + $customer['total_sales'] - $customer['total_returns'] - $customer['total_payments'];
            $ledgerBalance = $customer['ledger_debits'] - $customer['ledger_credits'];
            
            if (abs($expectedBalance - $ledgerBalance) > 0.01) {
                $difference = $expectedBalance - $ledgerBalance;
                
                if ($isDryRun) {
                    echo "🔍 Would fix {$customer['first_name']}: Difference = " . number_format($difference, 2) . "\n";
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
                    
                    echo "✅ Fixed {$customer['first_name']}: Balance = " . number_format($expectedBalance, 2) . "\n";
                }
                
                $totalFixed++;
            }
        }
        
        // Fix Supplier Ledgers
        echo "\n=== FIXING SUPPLIER LEDGERS ===\n";
        
        $supplierQuery = "
            SELECT 
                s.id,
                s.first_name,
                s.opening_balance,
                COALESCE(SUM(CASE WHEN p.transaction_type = 'purchase' THEN p.grand_total ELSE 0 END), 0) as total_purchases,
                COALESCE(SUM(CASE WHEN p.transaction_type = 'purchase_return' THEN p.grand_total ELSE 0 END), 0) as total_returns,
                COALESCE(SUM(CASE WHEN p.transaction_type = 'purchase' THEN COALESCE(p.paid_amount, 0) ELSE 0 END), 0) as total_payments,
                COALESCE(SUM(CASE WHEN l.debit > 0 THEN l.debit ELSE 0 END), 0) as ledger_debits,
                COALESCE(SUM(CASE WHEN l.credit > 0 THEN l.credit ELSE 0 END), 0) as ledger_credits
            FROM suppliers s
            LEFT JOIN purchases p ON s.id = p.supplier_id
            LEFT JOIN ledgers l ON s.id = l.supplier_id
            GROUP BY s.id, s.first_name, s.opening_balance
        ";
        
        $suppliers = $conn->query($supplierQuery)->fetchAll();
        
        foreach ($suppliers as $supplier) {
            $expectedBalance = $supplier['opening_balance'] + $supplier['total_purchases'] - $supplier['total_returns'] - $supplier['total_payments'];
            $ledgerBalance = $supplier['ledger_credits'] - $supplier['ledger_debits'];
            
            if (abs($expectedBalance - $ledgerBalance) > 0.01) {
                $difference = $expectedBalance - $ledgerBalance;
                
                if ($isDryRun) {
                    echo "🔍 Would fix {$supplier['first_name']}: Difference = " . number_format($difference, 2) . "\n";
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
                    
                    echo "✅ Fixed {$supplier['first_name']}: Balance = " . number_format($expectedBalance, 2) . "\n";
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
        echo "\n🧪 This was a dry run. Use 'php fixed_simple_fix.php' to apply changes.\n";
    } else {
        echo "\n🎉 Ledger fixes completed successfully!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'transaction_payments') !== false) {
        echo "\n💡 SOLUTION: Using sales/purchases 'paid_amount' instead of 'transaction_payments' table.\n";
    }
    
    exit(1);
}

echo "\n=== FIX COMPLETE ===\n";
?>