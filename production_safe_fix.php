<?php
/**
 * PRODUCTION-SAFE LEDGER FIX SCRIPT
 * 
 * Safety Features:
 * - Automatic database backup before any changes
 * - Transaction-based operations with rollback capability
 * - Step-by-step confirmation prompts
 * - Comprehensive logging and audit trail
 * - Dry-run mode for testing
 * - Rollback functionality if issues occur
 */

require_once 'secure_database_manager.php';

echo "=== PRODUCTION-SAFE LEDGER FIX SCRIPT ===\n\n";

// Configuration
$DRY_RUN = false; // Set to true for testing without making changes
$REQUIRE_CONFIRMATION = true; // Require manual confirmation for each step
$AUTO_BACKUP = true; // Automatically create backups

// Command line arguments
if (isset($argv)) {
    foreach ($argv as $arg) {
        if ($arg === '--dry-run') $DRY_RUN = true;
        if ($arg === '--no-confirm') $REQUIRE_CONFIRMATION = false;
        if ($arg === '--no-backup') $AUTO_BACKUP = false;
    }
}

echo "Mode: " . ($DRY_RUN ? "DRY RUN (no changes will be made)" : "LIVE EXECUTION") . "\n";
echo "Confirmation: " . ($REQUIRE_CONFIRMATION ? "Required" : "Automatic") . "\n";
echo "Backup: " . ($AUTO_BACKUP ? "Enabled" : "Disabled") . "\n\n";

try {
    // Initialize secure connection
    $dbManager = SecureDatabaseManager::getInstance();
    
    if (!$dbManager->testConnection()) {
        throw new Exception("Failed to connect to database. Please check your .env configuration.");
    }
    
    $db = $dbManager->getConnection();
    $dbInfo = $dbManager->getDatabaseInfo();
    
    echo "✅ Database Connection Successful\n";
    echo "   Database: {$dbInfo['database']}\n\n";
    
    // Load analysis results
    $analysisFiles = glob('ledger_analysis_*.json');
    if (empty($analysisFiles)) {
        throw new Exception("No analysis file found. Please run production_safe_analysis.php first.");
    }
    
    $latestAnalysis = end($analysisFiles);
    $analysisData = json_decode(file_get_contents($latestAnalysis), true);
    
    echo "📊 Loading analysis from: {$latestAnalysis}\n";
    echo "   Total issues: {$analysisData['summary']['total_issues_found']}\n\n";
    
    if ($analysisData['summary']['total_issues_found'] === 0) {
        echo "🎉 No issues found to fix!\n";
        exit(0);
    }
    
    SecurityManager::logAction("FIX_STARTED", [
        'mode' => $DRY_RUN ? 'dry_run' : 'live',
        'analysis_file' => $latestAnalysis,
        'total_issues' => $analysisData['summary']['total_issues_found']
    ]);
    
    // Create backups if enabled
    $backupTables = [];
    if ($AUTO_BACKUP && !$DRY_RUN) {
        if ($REQUIRE_CONFIRMATION) {
            if (!SecurityManager::confirmAction("Create backup of customers, suppliers, and ledgers tables?")) {
                echo "❌ Backup declined. Exiting for safety.\n";
                exit(1);
            }
        }
        
        echo "🔄 Creating database backups...\n";
        
        try {
            $backupTables['customers'] = SecurityManager::createBackup('customers');
            $backupTables['suppliers'] = SecurityManager::createBackup('suppliers');
            $backupTables['ledgers'] = SecurityManager::createBackup('ledgers');
            
            echo "✅ Backups created:\n";
            foreach ($backupTables as $table => $backup) {
                echo "   {$table} -> {$backup}\n";
                if (!SecurityManager::verifyBackup($table, $backup)) {
                    throw new Exception("Backup verification failed for {$table}");
                }
            }
            echo "\n";
        } catch (Exception $e) {
            throw new Exception("Backup creation failed: " . $e->getMessage());
        }
    }
    
    // Begin transaction
    if (!$DRY_RUN) {
        $dbManager->beginTransaction();
        echo "🔐 Transaction started\n\n";
    }
    
    $fixedIssues = 0;
    $errors = [];
    
    try {
        // Fix customer issues
        if (!empty($analysisData['customer_issues'])) {
            echo "=== FIXING CUSTOMER ISSUES ===\n";
            
            foreach ($analysisData['customer_issues'] as $customer) {
                echo "Customer: {$customer['name']} (ID: {$customer['id']})\n";
                
                foreach ($customer['issues'] as $issue) {
                    echo "  Issue: {$issue['description']}\n";
                    
                    if ($REQUIRE_CONFIRMATION) {
                        $confirmMsg = "Fix {$issue['type']} for customer {$customer['name']}?";
                        if (!SecurityManager::confirmAction($confirmMsg)) {
                            echo "  ⏭️  Skipped\n";
                            continue;
                        }
                    }
                    
                    if ($DRY_RUN) {
                        echo "  🔍 [DRY RUN] Would fix: {$issue['type']}\n";
                        $fixedIssues++;
                        continue;
                    }
                    
                    // Apply fix based on issue type
                    switch ($issue['type']) {
                        case 'balance_mismatch':
                            $newBalance = $customer['calculated_balance'];
                            $stmt = $db->prepare("UPDATE customers SET current_balance = ? WHERE id = ?");
                            $stmt->execute([$newBalance, $customer['id']]);
                            
                            echo "  ✅ Updated balance to: " . number_format($newBalance, 2) . "\n";
                            $fixedIssues++;
                            break;
                            
                        case 'sales_mismatch':
                            // Handle sales mismatch - might need to create ledger entries
                            echo "  ⚠️  Sales mismatch requires manual review\n";
                            break;
                            
                        case 'payment_reversals':
                            // Remove payment reversal entries
                            $stmt = $db->prepare("DELETE FROM ledgers WHERE user_id = ? AND contact_type = 'customer' AND notes LIKE '%REVERSAL%'");
                            $stmt->execute([$customer['id']]);
                            
                            echo "  ✅ Removed payment reversals\n";
                            $fixedIssues++;
                            break;
                    }
                    
                    SecurityManager::logAction("ISSUE_FIXED", [
                        'type' => $issue['type'],
                        'customer_id' => $customer['id'],
                        'customer_name' => $customer['name']
                    ]);
                }
                echo "\n";
            }
        }
        
        // Fix supplier issues
        if (!empty($analysisData['supplier_issues'])) {
            echo "=== FIXING SUPPLIER ISSUES ===\n";
            
            foreach ($analysisData['supplier_issues'] as $supplier) {
                echo "Supplier: {$supplier['name']} (ID: {$supplier['id']})\n";
                
                foreach ($supplier['issues'] as $issue) {
                    echo "  Issue: {$issue['description']}\n";
                    
                    if ($REQUIRE_CONFIRMATION) {
                        $confirmMsg = "Fix {$issue['type']} for supplier {$supplier['name']}?";
                        if (!SecurityManager::confirmAction($confirmMsg)) {
                            echo "  ⏭️  Skipped\n";
                            continue;
                        }
                    }
                    
                    if ($DRY_RUN) {
                        echo "  🔍 [DRY RUN] Would fix: {$issue['type']}\n";
                        $fixedIssues++;
                        continue;
                    }
                    
                    // Apply fix based on issue type
                    switch ($issue['type']) {
                        case 'balance_mismatch':
                            $newBalance = $supplier['calculated_balance'];
                            $stmt = $db->prepare("UPDATE suppliers SET current_balance = ? WHERE id = ?");
                            $stmt->execute([$newBalance, $supplier['id']]);
                            
                            echo "  ✅ Updated balance to: " . number_format($newBalance, 2) . "\n";
                            $fixedIssues++;
                            break;
                            
                        case 'purchase_mismatch':
                            echo "  ⚠️  Purchase mismatch requires manual review\n";
                            break;
                    }
                    
                    SecurityManager::logAction("ISSUE_FIXED", [
                        'type' => $issue['type'],
                        'supplier_id' => $supplier['id'],
                        'supplier_name' => $supplier['name']
                    ]);
                }
                echo "\n";
            }
        }
        
        // Final balance recalculation
        if (!$DRY_RUN && $fixedIssues > 0) {
            echo "🔄 Performing final balance recalculation...\n";
            
            // Recalculate customer balances
            $stmt = $db->query("SELECT id FROM customers");
            $customerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($customerIds as $customerId) {
                $stmt = $db->prepare("SELECT SUM(debit - credit) as balance FROM ledgers WHERE user_id = ? AND contact_type = 'customer'");
                $stmt->execute([$customerId]);
                $calculatedBalance = $stmt->fetch()['balance'] ?? 0;
                
                $stmt = $db->prepare("UPDATE customers SET current_balance = ? WHERE id = ?");
                $stmt->execute([$calculatedBalance, $customerId]);
            }
            
            // Recalculate supplier balances
            $stmt = $db->query("SELECT id FROM suppliers");
            $supplierIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($supplierIds as $supplierId) {
                $stmt = $db->prepare("
                    SELECT SUM(
                        CASE 
                            WHEN transaction_type = 'opening_balance' THEN credit - debit
                            WHEN transaction_type = 'purchase' THEN credit
                            WHEN transaction_type LIKE '%payment%' THEN -debit
                            WHEN transaction_type LIKE '%return%' THEN -debit
                            ELSE 0
                        END
                    ) as balance 
                    FROM ledgers 
                    WHERE user_id = ? AND contact_type = 'supplier'
                ");
                $stmt->execute([$supplierId]);
                $calculatedBalance = $stmt->fetch()['balance'] ?? 0;
                
                $stmt = $db->prepare("UPDATE suppliers SET current_balance = ? WHERE id = ?");
                $stmt->execute([$calculatedBalance, $supplierId]);
            }
            
            echo "✅ Balance recalculation completed\n\n";
        }
        
        // Commit transaction
        if (!$DRY_RUN) {
            if ($REQUIRE_CONFIRMATION && $fixedIssues > 0) {
                if (!SecurityManager::confirmAction("Commit all changes to database? ({$fixedIssues} issues fixed)")) {
                    $dbManager->rollback();
                    echo "❌ Changes rolled back by user request\n";
                    exit(1);
                }
            }
            
            $dbManager->commit();
            echo "✅ All changes committed successfully\n";
        }
        
    } catch (Exception $e) {
        if (!$DRY_RUN) {
            $dbManager->rollback();
            echo "❌ Error occurred, rolling back changes: " . $e->getMessage() . "\n";
        }
        throw $e;
    }
    
    echo "\n=== FIX SUMMARY ===\n";
    echo "Mode: " . ($DRY_RUN ? "DRY RUN" : "LIVE EXECUTION") . "\n";
    echo "Issues Fixed: {$fixedIssues}\n";
    echo "Errors: " . count($errors) . "\n";
    
    if (!empty($backupTables)) {
        echo "\nBackup Tables Created:\n";
        foreach ($backupTables as $table => $backup) {
            echo "  {$table} -> {$backup}\n";
        }
    }
    
    SecurityManager::logAction("FIX_COMPLETED", [
        'mode' => $DRY_RUN ? 'dry_run' : 'live',
        'issues_fixed' => $fixedIssues,
        'errors' => count($errors),
        'backups' => $backupTables
    ]);
    
    echo "\n🎉 Fix operation completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    SecurityManager::logAction("FIX_ERROR", ['error' => $e->getMessage()]);
    exit(1);
}

echo "\n=== OPERATION COMPLETE ===\n";
echo "Check ledger_operations.log for detailed operation logs.\n";

// Display rollback instructions if needed
if (!$DRY_RUN && !empty($backupTables)) {
    echo "\n📋 ROLLBACK INSTRUCTIONS (if needed):\n";
    foreach ($backupTables as $table => $backup) {
        echo "  To restore {$table}: DROP TABLE {$table}; RENAME TABLE {$backup} TO {$table};\n";
    }
}
?>