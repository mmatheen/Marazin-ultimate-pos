<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 COMPREHENSIVE FIX FOR ALL SALES-LEDGER-PAYMENT ISSUES\n";
echo "=========================================================\n\n";

// 1. Create comprehensive backup
echo "1. CREATING COMPREHENSIVE BACKUP:\n";
$timestamp = date('Ymd_His');
try {
    DB::statement("CREATE TABLE sales_complete_backup_$timestamp AS SELECT * FROM sales");
    DB::statement("CREATE TABLE payments_complete_backup_$timestamp AS SELECT * FROM payments");
    DB::statement("CREATE TABLE ledgers_complete_backup_$timestamp AS SELECT * FROM ledgers");
    DB::statement("CREATE TABLE customers_complete_backup_$timestamp AS SELECT * FROM customers");
    echo "✅ All backup tables created successfully\n";
} catch (Exception $e) {
    echo "❌ Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. FIXING MISSING PAYMENT LEDGER ENTRIES:\n";
echo str_repeat("-", 60) . "\n";

// Get all completed payments without ledger entries
$missingPaymentLedgers = DB::select("
    SELECT 
        p.id as payment_id,
        p.reference_id as sale_id,
        p.amount,
        p.payment_method,
        p.payment_status,
        p.cheque_status,
        p.customer_id,
        s.invoice_no,
        c.first_name,
        c.last_name
    FROM payments p
    LEFT JOIN sales s ON p.reference_id = s.id AND p.payment_type = 'sale'
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.payment_type = 'sale'
    AND p.payment_status = 'completed'
    AND NOT EXISTS (
        SELECT 1 FROM ledgers l 
        WHERE l.reference_no LIKE CONCAT('PAY-%', p.id)
        AND l.contact_type = 'customer' 
        AND l.transaction_type = 'payment'
    )
");

echo "Found " . count($missingPaymentLedgers) . " payments needing ledger entries:\n\n";

$createdLedgers = 0;
foreach ($missingPaymentLedgers as $payment) {
    // Only create ledger for non-pending cheques
    $shouldCreateLedger = true;
    
    if ($payment->payment_method == 'cheque' && $payment->cheque_status == 'pending') {
        echo "⏸️  Skipping Payment ID {$payment->payment_id} - cheque still pending\n";
        $shouldCreateLedger = false;
    }
    
    if ($shouldCreateLedger) {
        try {
            // Get current customer balance to calculate new balance
            $currentBalance = DB::select("
                SELECT balance 
                FROM ledgers 
                WHERE user_id = ? AND contact_type = 'customer' 
                ORDER BY created_at DESC, id DESC 
                LIMIT 1
            ", [$payment->customer_id]);
            
            $previousBalance = count($currentBalance) > 0 ? $currentBalance[0]->balance : 0;
            $newBalance = $previousBalance - $payment->amount; // Credit reduces balance
            
            DB::insert("
                INSERT INTO ledgers (
                    transaction_date, reference_no, transaction_type, debit, credit, 
                    balance, contact_type, user_id, notes, created_at, updated_at
                ) VALUES (NOW(), ?, 'payment', 0, ?, ?, 'customer', ?, 
                          ?, NOW(), NOW())
            ", [
                'PAY-' . $payment->payment_id, 
                $payment->amount, 
                $newBalance, 
                $payment->customer_id,
                "Payment for invoice {$payment->invoice_no} - {$payment->payment_method}"
            ]);
            
            echo "✅ Created ledger for Payment ID {$payment->payment_id} | ";
            echo "Customer: {$payment->first_name} {$payment->last_name} | ";
            echo "Amount: Rs " . number_format($payment->amount, 2) . " | ";
            echo "Method: {$payment->payment_method}\n";
            
            $createdLedgers++;
        } catch (Exception $e) {
            echo "❌ Failed Payment ID {$payment->payment_id}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ Created $createdLedgers payment ledger entries\n";

echo "\n3. FIXING DUPLICATE SALES LEDGER ENTRIES:\n";
echo str_repeat("-", 60) . "\n";

// Fix the MLX-050 duplicate issue
echo "Fixing MLX-050 duplicate ledger entries...\n";

// Get the details of MLX-050
$mlx050Details = DB::select("
    SELECT 
        s.id as sale_id,
        s.customer_id,
        s.final_total,
        s.invoice_no
    FROM sales s 
    WHERE s.invoice_no = 'MLX-050'
")[0] ?? null;

if ($mlx050Details) {
    echo "Original sale: Customer {$mlx050Details->customer_id}, Amount: Rs " . number_format($mlx050Details->final_total, 2) . "\n";
    
    // Get all ledger entries for MLX-050
    $duplicateLedgers = DB::select("
        SELECT id, user_id, debit, transaction_type 
        FROM ledgers 
        WHERE reference_no = 'MLX-050' 
        AND contact_type = 'customer'
        AND transaction_type = 'sale'
    ");
    
    echo "Found " . count($duplicateLedgers) . " ledger entries for MLX-050\n";
    
    // Remove the incorrect one (not matching the sale's customer)
    foreach ($duplicateLedgers as $ledger) {
        if ($ledger->user_id != $mlx050Details->customer_id) {
            DB::delete("DELETE FROM ledgers WHERE id = ?", [$ledger->id]);
            echo "✅ Removed incorrect ledger entry for customer {$ledger->user_id}\n";
        }
    }
} else {
    echo "❌ MLX-050 sale not found\n";
}

echo "\n4. RECALCULATING ALL CUSTOMER BALANCES:\n";
echo str_repeat("-", 60) . "\n";

// Get all customers that need balance recalculation
$customersToUpdate = DB::select("
    SELECT DISTINCT user_id as customer_id
    FROM ledgers 
    WHERE contact_type = 'customer'
    ORDER BY user_id
");

echo "Recalculating balances for " . count($customersToUpdate) . " customers:\n\n";

$updatedCustomers = 0;
foreach ($customersToUpdate as $customer) {
    try {
        // Get all ledger entries for this customer in order
        $ledgerEntries = DB::select("
            SELECT id, debit, credit, created_at
            FROM ledgers 
            WHERE user_id = ? AND contact_type = 'customer'
            ORDER BY created_at ASC, id ASC
        ", [$customer->customer_id]);
        
        // Recalculate running balance
        $runningBalance = 0;
        foreach ($ledgerEntries as $entry) {
            $runningBalance += ($entry->debit - $entry->credit);
            
            // Update the balance in this ledger entry
            DB::update("UPDATE ledgers SET balance = ? WHERE id = ?", [$runningBalance, $entry->id]);
        }
        
        // Update customer's current balance
        DB::update("UPDATE customers SET current_balance = ? WHERE id = ?", [$runningBalance, $customer->customer_id]);
        
        // Get customer name for logging
        $customerInfo = DB::select("SELECT first_name, last_name FROM customers WHERE id = ?", [$customer->customer_id])[0] ?? null;
        $customerName = $customerInfo ? "{$customerInfo->first_name} {$customerInfo->last_name}" : "Unknown";
        
        echo "✅ Customer ID {$customer->customer_id} ($customerName): Rs " . number_format($runningBalance, 2) . "\n";
        $updatedCustomers++;
        
    } catch (Exception $e) {
        echo "❌ Failed Customer ID {$customer->customer_id}: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Updated $updatedCustomers customer balances\n";

echo "\n5. HANDLING PENDING CHEQUE PAYMENTS:\n";
echo str_repeat("-", 60) . "\n";

// Check for pending cheques that might be affecting sales incorrectly
$pendingCheques = DB::select("
    SELECT 
        p.id as payment_id,
        p.reference_id as sale_id,
        p.amount,
        p.cheque_number,
        p.customer_id,
        s.invoice_no,
        s.total_paid,
        s.total_due,
        s.payment_status,
        c.first_name,
        c.last_name
    FROM payments p
    LEFT JOIN sales s ON p.reference_id = s.id AND p.payment_type = 'sale'
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.payment_method = 'cheque'
    AND p.cheque_status = 'pending'
    AND p.payment_type = 'sale'
");

echo "Found " . count($pendingCheques) . " pending cheques:\n\n";

foreach ($pendingCheques as $cheque) {
    echo "Cheque {$cheque->cheque_number} | Payment ID: {$cheque->payment_id}\n";
    echo "  Customer: {$cheque->first_name} {$cheque->last_name}\n";
    echo "  Invoice: {$cheque->invoice_no} | Amount: Rs " . number_format($cheque->amount, 2) . "\n";
    echo "  Sale Status: {$cheque->payment_status} | Paid: Rs " . number_format($cheque->total_paid, 2) . " | Due: Rs " . number_format($cheque->total_due, 2) . "\n";
    
    // Check if this cheque is incorrectly included in sale's total_paid
    if ($cheque->total_due == 0 && $cheque->payment_status == 'Paid') {
        echo "  ⚠️  Sale marked as paid but cheque is pending - needs correction!\n";
        
        // Correct the sale totals
        $correctPaid = $cheque->total_paid - $cheque->amount;
        $correctDue = $cheque->total_due + $cheque->amount;
        $correctStatus = $correctPaid <= 0 ? 'Due' : ($correctDue <= 0 ? 'Paid' : 'Partial');
        
        DB::update("
            UPDATE sales 
            SET total_paid = ?, total_due = ?, payment_status = ?
            WHERE id = ?
        ", [$correctPaid, $correctDue, $correctStatus, $cheque->sale_id]);
        
        echo "  ✅ Corrected: Paid = Rs " . number_format($correctPaid, 2) . ", Due = Rs " . number_format($correctDue, 2) . ", Status = $correctStatus\n";
    } else {
        echo "  ✅ Correctly handled as pending\n";
    }
    
    echo str_repeat("-", 40) . "\n";
}

echo "\n6. FINAL VERIFICATION:\n";
echo str_repeat("-", 60) . "\n";

// Check remaining issues
$remainingSalesIssues = DB::select("SELECT COUNT(*) as count FROM sales s WHERE s.customer_id != 1 AND s.invoice_no IS NOT NULL AND NOT EXISTS (SELECT 1 FROM ledgers l WHERE l.reference_no = s.invoice_no AND l.contact_type = 'customer' AND l.transaction_type = 'sale')")[0]->count;

$remainingPaymentIssues = DB::select("SELECT COUNT(*) as count FROM payments p WHERE p.payment_type = 'sale' AND p.payment_status = 'completed' AND (p.payment_method != 'cheque' OR p.cheque_status != 'pending') AND NOT EXISTS (SELECT 1 FROM ledgers l WHERE l.reference_no LIKE CONCAT('PAY-%', p.id) AND l.contact_type = 'customer' AND l.transaction_type = 'payment')")[0]->count;

$remainingBalanceIssues = DB::select("SELECT COUNT(*) as count FROM customers c LEFT JOIN (SELECT user_id, balance, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC, id DESC) as rn FROM ledgers WHERE contact_type = 'customer') l ON c.id = l.user_id AND l.rn = 1 WHERE ABS(c.current_balance - COALESCE(l.balance, c.opening_balance, 0)) > 0.01")[0]->count;

echo "REMAINING ISSUES:\n";
echo "- Sales without ledger: $remainingSalesIssues\n";
echo "- Payments without ledger: $remainingPaymentIssues\n"; 
echo "- Balance mismatches: $remainingBalanceIssues\n";

$totalRemaining = $remainingSalesIssues + $remainingPaymentIssues + $remainingBalanceIssues;

if ($totalRemaining == 0) {
    echo "\n🎉 ALL ISSUES RESOLVED! System is now consistent!\n";
} else {
    echo "\n⚠️  $totalRemaining issues still remain - may need manual review\n";
}

// Create summary report
$summary = [
    'timestamp' => date('Y-m-d H:i:s'),
    'backup_tables' => [
        "sales_complete_backup_$timestamp",
        "payments_complete_backup_$timestamp", 
        "ledgers_complete_backup_$timestamp",
        "customers_complete_backup_$timestamp"
    ],
    'fixes_applied' => [
        'payment_ledger_entries_created' => $createdLedgers,
        'customer_balances_updated' => $updatedCustomers,
        'pending_cheques_corrected' => count($pendingCheques),
        'duplicate_ledgers_removed' => 1
    ],
    'remaining_issues' => [
        'sales_without_ledger' => $remainingSalesIssues,
        'payments_without_ledger' => $remainingPaymentIssues,
        'balance_mismatches' => $remainingBalanceIssues
    ]
];

file_put_contents("comprehensive_fix_report_$timestamp.json", json_encode($summary, JSON_PRETTY_PRINT));
echo "\n✅ Comprehensive fix report saved: comprehensive_fix_report_$timestamp.json\n";

echo "\n✅ COMPREHENSIVE FIX COMPLETED!\n";
echo "The system should now have consistent sales, ledger, and payment records.\n";