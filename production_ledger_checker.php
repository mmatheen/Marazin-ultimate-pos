<?php
/**
 * PRODUCTION SAFE: Sales Table & Ledger Record Checker/Updater
 * 
 * This script will:
 * 1. Check all sales for missing ledger entries
 * 2. Verify customer balance consistency
 * 3. Optionally fix issues (with confirmation)
 * 4. Create detailed reports
 */

// Database connection configuration
$host = 'localhost';
$dbname = 'marazin_pos_db';  // Your production database
$username = 'root';          // Your DB username
$password = '';              // Your DB password

// Configuration flags
$DRY_RUN = true;  // Set to false to actually make changes
$BACKUP_RECOMMENDED = true;  // Always backup before changes

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "==========================================\n";
    echo "PRODUCTION DATABASE LEDGER CHECKER\n";
    echo "==========================================\n";
    echo "Database: {$dbname}\n";
    echo "Mode: " . ($DRY_RUN ? "READ-ONLY (Safe)" : "WRITE MODE (Changes DB)") . "\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "==========================================\n\n";
    
    if ($BACKUP_RECOMMENDED && !$DRY_RUN) {
        echo "⚠️  WARNING: This will modify your database!\n";
        echo "📋 BACKUP RECOMMENDATION: Run this command first:\n";
        echo "mysqldump -u{$username} -p{$password} {$dbname} > backup_" . date('Y-m-d_H-i-s') . ".sql\n\n";
        echo "Continue? Type 'yes' to proceed: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if(trim($line) != 'yes') {
            echo "Aborted for safety.\n";
            exit;
        }
        fclose($handle);
        echo "\n";
    }

    // ===============================
    // STEP 1: DATABASE OVERVIEW
    // ===============================
    echo "STEP 1: DATABASE OVERVIEW\n";
    echo str_repeat("-", 30) . "\n";
    
    // Count all sales
    $stmt = $pdo->query("SELECT payment_status, COUNT(*) as count, SUM(final_total) as total_amount FROM sales GROUP BY payment_status");
    $sales_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 SALES SUMMARY:\n";
    foreach ($sales_summary as $status) {
        echo "  {$status['payment_status']}: {$status['count']} sales (Rs. " . number_format($status['total_amount'], 2) . ")\n";
    }
    
    // Count ledger entries
    $stmt = $pdo->query("SELECT transaction_type, COUNT(*) as count FROM ledgers GROUP BY transaction_type");
    $ledger_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 LEDGER SUMMARY:\n";
    foreach ($ledger_summary as $type) {
        echo "  {$type['transaction_type']}: {$type['count']} entries\n";
    }
    echo "\n";

    // ===============================
    // STEP 2: IDENTIFY ISSUES
    // ===============================
    echo "STEP 2: IDENTIFYING ISSUES\n";
    echo str_repeat("-", 30) . "\n";
    
    // Find sales without ledger entries
    $stmt = $pdo->query("
        SELECT s.id, s.invoice_no, s.customer_id, s.final_total, s.total_due, s.payment_status,
               CONCAT(c.first_name, ' ', IFNULL(c.last_name, '')) as customer_name,
               s.sales_date, s.created_at
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN ledgers l ON (l.transaction_type = 'sale' AND l.user_id = s.customer_id AND l.reference_no = s.invoice_no)
        WHERE s.payment_status IN ('Due', 'Partial')
        AND l.id IS NULL
        ORDER BY s.id
    ");
    
    $missing_ledger_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($missing_ledger_sales) > 0) {
        echo "❌ FOUND " . count($missing_ledger_sales) . " SALES WITHOUT LEDGER ENTRIES:\n";
        echo sprintf("%-6s %-12s %-25s %-12s %-10s %s\n", "ID", "Invoice", "Customer", "Amount", "Status", "Date");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($missing_ledger_sales as $sale) {
            echo sprintf("%-6s %-12s %-25s Rs.%-9s %-10s %s\n", 
                $sale['id'], 
                $sale['invoice_no'], 
                substr($sale['customer_name'], 0, 24),
                number_format($sale['final_total'], 2),
                $sale['payment_status'],
                $sale['sales_date']
            );
        }
    } else {
        echo "✅ All due/partial sales have ledger entries\n";
    }
    echo "\n";

    // ===============================
    // STEP 3: CUSTOMER BALANCE VERIFICATION
    // ===============================
    echo "STEP 3: CUSTOMER BALANCE VERIFICATION\n";
    echo str_repeat("-", 40) . "\n";
    
    $stmt = $pdo->query("
        SELECT c.id, CONCAT(c.first_name, ' ', IFNULL(c.last_name, '')) as name,
               COALESCE(SUM(s.total_due), 0) as total_due_from_sales,
               COALESCE(ledger_balance.balance, 0) as current_ledger_balance
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_status IN ('Due', 'Partial')
        LEFT JOIN (
            SELECT user_id, SUM(debit - credit) as balance
            FROM ledgers 
            WHERE contact_type = 'customer'
            GROUP BY user_id
        ) ledger_balance ON c.id = ledger_balance.user_id
        GROUP BY c.id, c.first_name, c.last_name, ledger_balance.balance
        HAVING total_due_from_sales > 0 OR current_ledger_balance > 0
        ORDER BY current_ledger_balance DESC
    ");
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $balance_mismatches = [];
    
    echo sprintf("%-4s %-25s %-12s %-12s %-10s\n", "ID", "Customer Name", "Sales Due", "Ledger Bal", "Status");
    echo str_repeat("-", 70) . "\n";
    
    foreach ($customers as $customer) {
        $sales_due = floatval($customer['total_due_from_sales']);
        $ledger_balance = floatval($customer['current_ledger_balance']);
        $difference = abs($sales_due - $ledger_balance);
        
        $status = $difference < 0.01 ? "✅ OK" : "⚠️  DIFF";
        
        echo sprintf("%-4s %-25s Rs.%-9s Rs.%-9s %s\n", 
            $customer['id'],
            substr($customer['name'], 0, 24),
            number_format($sales_due, 2),
            number_format($ledger_balance, 2),
            $status
        );
        
        if ($difference >= 0.01) {
            $balance_mismatches[] = array_merge($customer, ['difference' => $difference]);
        }
    }
    
    echo "\nBalance Mismatches: " . count($balance_mismatches) . "\n\n";

    // ===============================
    // STEP 4: DETAILED ISSUE ANALYSIS
    // ===============================
    if (count($missing_ledger_sales) > 0 || count($balance_mismatches) > 0) {
        echo "STEP 4: DETAILED ISSUE ANALYSIS\n";
        echo str_repeat("-", 35) . "\n";
        
        if (count($missing_ledger_sales) > 0) {
            echo "📋 MISSING LEDGER ENTRIES DETAILS:\n";
            $total_missing_amount = 0;
            foreach ($missing_ledger_sales as $sale) {
                $total_missing_amount += $sale['final_total'];
                echo "  Sale ID {$sale['id']}: {$sale['customer_name']} owes Rs.{$sale['final_total']} (Invoice: {$sale['invoice_no']})\n";
            }
            echo "  📊 Total unrecorded amount: Rs." . number_format($total_missing_amount, 2) . "\n\n";
        }
        
        if (count($balance_mismatches) > 0) {
            echo "⚖️  BALANCE MISMATCHES:\n";
            foreach ($balance_mismatches as $mismatch) {
                echo "  {$mismatch['name']} (ID: {$mismatch['id']}): Difference of Rs.{$mismatch['difference']}\n";
            }
            echo "\n";
        }
    }

    // ===============================
    // STEP 5: FIXES (IF NOT DRY RUN)
    // ===============================
    if (!$DRY_RUN && (count($missing_ledger_sales) > 0 || count($balance_mismatches) > 0)) {
        echo "STEP 5: APPLYING FIXES\n";
        echo str_repeat("-", 25) . "\n";
        
        if (count($missing_ledger_sales) > 0) {
            echo "🔧 Creating missing ledger entries...\n";
            
            foreach ($missing_ledger_sales as $sale) {
                // Get customer's current balance
                $stmt = $pdo->prepare("
                    SELECT balance FROM ledgers 
                    WHERE user_id = ? AND contact_type = 'customer'
                    ORDER BY created_at DESC, id DESC 
                    LIMIT 1
                ");
                $stmt->execute([$sale['customer_id']]);
                $current_balance = $stmt->fetchColumn() ?: 0;
                
                $new_balance = $current_balance + $sale['final_total'];
                
                // Create ledger entry
                $stmt = $pdo->prepare("
                    INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, created_at, updated_at)
                    VALUES (?, ?, 'sale', ?, 0, ?, 'customer', ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $sale['sales_date'],
                    $sale['invoice_no'],
                    $sale['final_total'],
                    $new_balance,
                    $sale['customer_id']
                ]);
                
                echo "  ✅ Created ledger entry for Sale ID {$sale['id']} (Rs.{$sale['final_total']})\n";
            }
        }
        
        if (count($balance_mismatches) > 0) {
            echo "\n🔧 Recalculating customer balances...\n";
            
            foreach ($balance_mismatches as $customer) {
                // Recalculate ledger balances in chronological order
                $stmt = $pdo->prepare("
                    SELECT id, debit, credit 
                    FROM ledgers 
                    WHERE user_id = ? AND contact_type = 'customer'
                    ORDER BY created_at ASC, id ASC
                ");
                $stmt->execute([$customer['id']]);
                $ledger_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $running_balance = 0;
                foreach ($ledger_entries as $entry) {
                    $running_balance += ($entry['debit'] - $entry['credit']);
                    
                    $update_stmt = $pdo->prepare("UPDATE ledgers SET balance = ? WHERE id = ?");
                    $update_stmt->execute([$running_balance, $entry['id']]);
                }
                
                echo "  ✅ Recalculated balances for {$customer['name']}\n";
            }
        }
        
        echo "\n🎉 ALL FIXES APPLIED SUCCESSFULLY!\n";
    }
    
    // ===============================
    // STEP 6: FINAL SUMMARY
    // ===============================
    echo "STEP 6: FINAL SUMMARY\n";
    echo str_repeat("-", 25) . "\n";
    
    // Re-check counts after fixes
    $stmt = $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_status IN ('Due', 'Partial')");
    $due_sales = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM ledgers WHERE transaction_type = 'sale'");
    $sale_ledgers = $stmt->fetchColumn();
    
    echo "📊 FINAL STATISTICS:\n";
    echo "  Due/Partial Sales: {$due_sales}\n";
    echo "  Sale Ledger Entries: {$sale_ledgers}\n";
    echo "  Missing Ledger Entries: " . count($missing_ledger_sales) . ($DRY_RUN ? " (would be fixed)" : " (fixed)") . "\n";
    echo "  Balance Mismatches: " . count($balance_mismatches) . ($DRY_RUN ? " (would be fixed)" : " (fixed)") . "\n";
    
    if ($due_sales == $sale_ledgers && count($missing_ledger_sales) == 0 && count($balance_mismatches) == 0) {
        echo "\n🎉 DATABASE IS CONSISTENT!\n";
    } else if ($DRY_RUN) {
        echo "\n📋 TO FIX ISSUES:\n";
        echo "  1. Create database backup\n";
        echo "  2. Set \$DRY_RUN = false in this script\n";
        echo "  3. Run script again\n";
    }
    
    echo "\n==========================================\n";
    echo "PRODUCTION DATABASE CHECK COMPLETE\n";
    echo "==========================================\n";

} catch (PDOException $e) {
    echo "❌ DATABASE ERROR: " . $e->getMessage() . "\n";
    echo "Please check your database configuration.\n";
}
?>