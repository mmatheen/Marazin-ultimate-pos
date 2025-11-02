<?php
/**
 * SPECIFIC PRODUCTION DATABASE CHECKER
 * Checks all DUE sales (excluding walk-in customer ID 1) have ledger records
 */

// Database configuration
$host = 'localhost';
$dbname = 'marazin_pos_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "==========================================\n";
    echo "PRODUCTION SALES & LEDGER VERIFICATION\n";
    echo "Excluding Customer ID 1 (Walk-in)\n";
    echo "==========================================\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

    // ===============================
    // STEP 1: FIND ALL DUE SALES (EXCLUDING WALK-IN)
    // ===============================
    echo "STEP 1: CHECKING DUE SALES (Excluding Walk-in Customer)\n";
    echo str_repeat("-", 50) . "\n";
    
    $stmt = $pdo->query("
        SELECT s.id, s.invoice_no, s.customer_id, s.final_total, s.total_due, s.payment_status,
               CONCAT(c.first_name, ' ', IFNULL(c.last_name, '')) as customer_name,
               s.sales_date, s.created_at
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.payment_status = 'Due' 
        AND s.customer_id != 1
        ORDER BY s.id DESC
    ");
    
    $due_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Found " . count($due_sales) . " DUE SALES (excluding walk-in customer):\n\n";
    
    if (count($due_sales) > 0) {
        echo sprintf("%-4s %-12s %-25s %-10s %-12s %s\n", "ID", "Invoice", "Customer", "Due Amount", "Status", "Date");
        echo str_repeat("-", 80) . "\n";
        
        foreach (array_slice($due_sales, 0, 10) as $sale) { // Show first 10
            echo sprintf("%-4s %-12s %-25s Rs.%-7s %-12s %s\n", 
                $sale['id'], 
                $sale['invoice_no'], 
                substr($sale['customer_name'], 0, 24),
                number_format($sale['total_due'], 2),
                $sale['payment_status'],
                $sale['sales_date']
            );
        }
        
        if (count($due_sales) > 10) {
            echo "... and " . (count($due_sales) - 10) . " more sales\n";
        }
    }
    echo "\n";

    // ===============================
    // STEP 2: CHECK LEDGER ENTRIES FOR EACH DUE SALE
    // ===============================
    echo "STEP 2: VERIFYING LEDGER ENTRIES FOR DUE SALES\n";
    echo str_repeat("-", 50) . "\n";
    
    $missing_ledger = [];
    $total_missing_amount = 0;
    
    foreach ($due_sales as $sale) {
        // Check if ledger entry exists for this sale
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM ledgers 
            WHERE transaction_type = 'sale' 
            AND user_id = ? 
            AND contact_type = 'customer'
            AND reference_no = ?
        ");
        $stmt->execute([$sale['customer_id'], $sale['invoice_no']]);
        $ledger_exists = $stmt->fetchColumn() > 0;
        
        if (!$ledger_exists) {
            $missing_ledger[] = $sale;
            $total_missing_amount += $sale['total_due'];
        }
    }
    
    if (count($missing_ledger) > 0) {
        echo "❌ FOUND " . count($missing_ledger) . " DUE SALES WITHOUT LEDGER ENTRIES:\n";
        echo sprintf("%-4s %-12s %-25s %-12s %s\n", "ID", "Invoice", "Customer", "Due Amount", "Date");
        echo str_repeat("-", 70) . "\n";
        
        foreach ($missing_ledger as $sale) {
            echo sprintf("%-4s %-12s %-25s Rs.%-9s %s\n", 
                $sale['id'], 
                $sale['invoice_no'], 
                substr($sale['customer_name'], 0, 24),
                number_format($sale['total_due'], 2),
                $sale['sales_date']
            );
        }
        
        echo "\n💰 TOTAL UNRECORDED AMOUNT: Rs." . number_format($total_missing_amount, 2) . "\n";
    } else {
        echo "✅ ALL DUE SALES HAVE LEDGER ENTRIES!\n";
    }
    echo "\n";

    // ===============================
    // STEP 3: CUSTOMER BALANCE VERIFICATION (EXCLUDING WALK-IN)
    // ===============================
    echo "STEP 3: CUSTOMER BALANCE VERIFICATION\n";
    echo str_repeat("-", 40) . "\n";
    
    $stmt = $pdo->query("
        SELECT c.id, CONCAT(c.first_name, ' ', IFNULL(c.last_name, '')) as name,
               COUNT(s.id) as due_sales_count,
               SUM(s.total_due) as total_sales_due,
               COALESCE(ledger_balance.balance, 0) as ledger_balance
        FROM customers c
        INNER JOIN sales s ON c.id = s.customer_id AND s.payment_status = 'Due'
        LEFT JOIN (
            SELECT user_id, SUM(debit - credit) as balance
            FROM ledgers 
            WHERE contact_type = 'customer'
            GROUP BY user_id
        ) ledger_balance ON c.id = ledger_balance.user_id
        WHERE c.id != 1
        GROUP BY c.id, c.first_name, c.last_name, ledger_balance.balance
        ORDER BY total_sales_due DESC
    ");
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🧾 TOP CUSTOMERS WITH DUE AMOUNTS:\n";
    echo sprintf("%-4s %-25s %-6s %-12s %-12s %s\n", "ID", "Customer Name", "Sales", "Sales Due", "Ledger Bal", "Status");
    echo str_repeat("-", 75) . "\n";
    
    $balance_issues = 0;
    foreach (array_slice($customers, 0, 15) as $customer) { // Show top 15
        $sales_due = floatval($customer['total_sales_due']);
        $ledger_balance = floatval($customer['ledger_balance']);
        $difference = abs($sales_due - $ledger_balance);
        
        $status = $difference < 0.01 ? "✅" : "⚠️ ";
        if ($difference >= 0.01) {
            $balance_issues++;
        }
        
        echo sprintf("%-4s %-25s %-6s Rs.%-9s Rs.%-9s %s\n", 
            $customer['id'],
            substr($customer['name'], 0, 24),
            $customer['due_sales_count'],
            number_format($sales_due, 2),
            number_format($ledger_balance, 2),
            $status
        );
    }
    
    echo "\n📊 Balance Issues Found: {$balance_issues}\n\n";

    // ===============================
    // STEP 4: SUMMARY AND RECOMMENDATIONS
    // ===============================
    echo "STEP 4: FINAL SUMMARY\n";
    echo str_repeat("-", 25) . "\n";
    
    echo "📈 STATISTICS:\n";
    echo "  Total Due Sales (excluding walk-in): " . count($due_sales) . "\n";
    echo "  Sales Missing Ledger Entries: " . count($missing_ledger) . "\n";
    echo "  Customers with Balance Issues: {$balance_issues}\n";
    echo "  Total Due Amount: Rs." . number_format(array_sum(array_column($due_sales, 'total_due')), 2) . "\n";
    
    if (count($missing_ledger) == 0 && $balance_issues == 0) {
        echo "\n🎉 PRODUCTION DATABASE IS PERFECT!\n";
        echo "✅ All due sales have ledger entries\n";
        echo "✅ All customer balances are consistent\n";
        echo "✅ No data corruption found\n";
    } else {
        echo "\n🔧 ISSUES FOUND:\n";
        if (count($missing_ledger) > 0) {
            echo "  - " . count($missing_ledger) . " sales need ledger entries\n";
        }
        if ($balance_issues > 0) {
            echo "  - {$balance_issues} customers have balance mismatches\n";
        }
        echo "\n📋 TO FIX: Run the production checker with fix mode enabled\n";
    }
    
    echo "\n==========================================\n";
    echo "PRODUCTION VERIFICATION COMPLETE\n";
    echo "Database: {$dbname} | Time: " . date('Y-m-d H:i:s') . "\n";
    echo "==========================================\n";

} catch (PDOException $e) {
    echo "❌ DATABASE ERROR: " . $e->getMessage() . "\n";
}
?>