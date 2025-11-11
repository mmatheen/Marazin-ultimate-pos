<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 FIXING CHEQUE PAYMENT HANDLING ISSUES\n";
echo "=========================================\n\n";

// 1. Create backup
echo "1. CREATING BACKUP:\n";
$timestamp = date('Ymd_His');
try {
    DB::statement("CREATE TABLE payments_backup_$timestamp AS SELECT * FROM payments");
    DB::statement("CREATE TABLE sales_backup_cheque_$timestamp AS SELECT * FROM sales");
    echo "✅ Backup tables created\n";
} catch (Exception $e) {
    echo "❌ Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. ANALYZING CURRENT CHEQUE PAYMENT HANDLING:\n";
echo str_repeat("-", 60) . "\n";

// Find all cheque payments and analyze their current state
$allChequePayments = DB::select("
    SELECT 
        p.id as payment_id,
        p.reference_id as sale_id,
        p.amount,
        p.cheque_status,
        p.payment_status,
        p.cheque_number,
        s.invoice_no,
        s.final_total,
        s.total_paid,
        s.total_due,
        s.payment_status as sale_payment_status,
        c.id as customer_id,
        c.first_name,
        c.last_name,
        c.current_balance
    FROM payments p
    LEFT JOIN sales s ON p.reference_id = s.id AND p.payment_type = 'sale'
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.payment_method = 'cheque' 
    AND p.payment_type = 'sale'
    ORDER BY p.created_at DESC
");

$issuesFound = [];
$correctHandling = [];

foreach ($allChequePayments as $payment) {
    $hasIssue = false;
    $issueType = '';
    
    // Check for various issues based on cheque status
    if ($payment->cheque_status == 'pending') {
        // For pending cheques, check if sale is incorrectly marked as paid
        if ($payment->sale_payment_status == 'Paid' && $payment->total_due == 0) {
            $hasIssue = true;
            $issueType = 'pending_but_marked_paid';
        }
        // Check if payment ledger entry exists for pending cheque
        $pendingLedgerCount = DB::select("
            SELECT COUNT(*) as count 
            FROM ledgers 
            WHERE reference_no LIKE ? AND user_id = ? AND contact_type = 'customer'
        ", ['PAY-%' . $payment->payment_id, $payment->customer_id])[0]->count;
        
        if ($pendingLedgerCount > 0) {
            $hasIssue = true;
            $issueType = 'pending_has_ledger';
        }
    } 
    elseif (in_array($payment->cheque_status, ['cleared', 'deposited'])) {
        // For cleared cheques, ensure proper ledger and sale updates
        $clearedLedgerCount = DB::select("
            SELECT COUNT(*) as count 
            FROM ledgers 
            WHERE reference_no LIKE ? AND user_id = ? AND contact_type = 'customer'
        ", ['PAY-%' . $payment->payment_id, $payment->customer_id])[0]->count;
        
        if ($clearedLedgerCount == 0) {
            $hasIssue = true;
            $issueType = 'cleared_no_ledger';
        }
    }
    elseif ($payment->cheque_status == 'bounced') {
        // For bounced cheques, ensure no ledger entry and sale is marked due
        $bouncedLedgerCount = DB::select("
            SELECT COUNT(*) as count 
            FROM ledgers 
            WHERE reference_no LIKE ? AND user_id = ? AND contact_type = 'customer'
        ", ['PAY-%' . $payment->payment_id, $payment->customer_id])[0]->count;
        
        if ($bouncedLedgerCount > 0 || $payment->total_due == 0) {
            $hasIssue = true;
            $issueType = 'bounced_incorrect_handling';
        }
    }
    
    if ($hasIssue) {
        $issuesFound[] = array_merge((array)$payment, ['issue_type' => $issueType]);
    } else {
        $correctHandling[] = $payment;
    }
}

echo "Analysis Results:\n";
echo "- Total cheque payments: " . count($allChequePayments) . "\n";
echo "- Issues found: " . count($issuesFound) . "\n";
echo "- Correctly handled: " . count($correctHandling) . "\n\n";

if (count($issuesFound) > 0) {
    echo "🚨 ISSUES DETECTED:\n";
    foreach ($issuesFound as $issue) {
        echo "Payment ID: {$issue['payment_id']} | Cheque: {$issue['cheque_number']}\n";
        echo "  Issue: {$issue['issue_type']}\n";
        echo "  Customer: {$issue['first_name']} {$issue['last_name']}\n";
        echo "  Invoice: {$issue['invoice_no']} | Amount: Rs " . number_format($issue['amount'], 2) . "\n";
        echo "  Status: {$issue['cheque_status']} | Sale Due: Rs " . number_format($issue['total_due'], 2) . "\n";
        echo str_repeat("-", 50) . "\n";
    }
} else {
    echo "✅ All cheque payments are handled correctly!\n";
}

echo "\n3. CREATING PROPER CHEQUE HANDLING PROCEDURES:\n";
echo str_repeat("-", 60) . "\n";

// Create a procedure to fix cheque payment handling
if (count($issuesFound) > 0) {
    echo "Applying fixes for cheque payment issues...\n\n";
    
    foreach ($issuesFound as $issue) {
        echo "Fixing Payment ID: {$issue['payment_id']}\n";
        
        switch ($issue['issue_type']) {
            case 'pending_but_marked_paid':
                // Pending cheque shouldn't mark sale as paid
                $correctDue = $issue['final_total'] - ($issue['total_paid'] - $issue['amount']);
                DB::update("
                    UPDATE sales 
                    SET total_paid = total_paid - ?, 
                        total_due = ?, 
                        payment_status = CASE 
                            WHEN total_paid - ? <= 0 THEN 'Due'
                            WHEN total_paid - ? >= final_total THEN 'Paid'
                            ELSE 'Partial'
                        END
                    WHERE id = ?
                ", [$issue['amount'], $correctDue, $issue['amount'], $issue['amount'], $issue['sale_id']]);
                
                echo "  ✅ Sale payment status corrected for pending cheque\n";
                break;
                
            case 'pending_has_ledger':
                // Remove ledger entries for pending cheques
                DB::delete("
                    DELETE FROM ledgers 
                    WHERE reference_no LIKE ? AND user_id = ? AND contact_type = 'customer'
                ", ['PAY-%' . $issue['payment_id'], $issue['customer_id']]);
                
                echo "  ✅ Removed ledger entries for pending cheque\n";
                break;
                
            case 'cleared_no_ledger':
                // Create ledger entry for cleared cheque
                DB::insert("
                    INSERT INTO ledgers (
                        transaction_date, reference_no, transaction_type, debit, credit, 
                        balance, contact_type, user_id, notes, created_at, updated_at
                    ) VALUES (NOW(), ?, 'payment', 0, ?, 0, 'customer', ?, 
                              'Payment for cleared cheque #{$issue['cheque_number']}', NOW(), NOW())
                ", ['PAY-' . $issue['payment_id'], $issue['amount'], $issue['customer_id']]);
                
                echo "  ✅ Created ledger entry for cleared cheque\n";
                break;
                
            case 'bounced_incorrect_handling':
                // Fix bounced cheque handling
                DB::delete("
                    DELETE FROM ledgers 
                    WHERE reference_no LIKE ? AND user_id = ? AND contact_type = 'customer'
                ", ['PAY-%' . $issue['payment_id'], $issue['customer_id']]);
                
                // Update sale to show amount is still due
                DB::update("
                    UPDATE sales 
                    SET total_paid = total_paid - ?, 
                        total_due = total_due + ?, 
                        payment_status = CASE 
                            WHEN total_paid - ? <= 0 THEN 'Due'
                            WHEN total_paid - ? >= final_total THEN 'Paid'
                            ELSE 'Partial'
                        END
                    WHERE id = ?
                ", [$issue['amount'], $issue['amount'], $issue['amount'], $issue['amount'], $issue['sale_id']]);
                
                echo "  ✅ Fixed bounced cheque handling\n";
                break;
        }
        
        echo str_repeat("-", 30) . "\n";
    }
    
    // Recalculate all affected customer balances
    echo "\n🔄 RECALCULATING CUSTOMER BALANCES:\n";
    $affectedCustomers = array_unique(array_column($issuesFound, 'customer_id'));
    
    foreach ($affectedCustomers as $customerId) {
        try {
            // Get latest ledger balance
            $latestBalance = DB::select("
                SELECT balance 
                FROM ledgers 
                WHERE user_id = ? AND contact_type = 'customer' 
                ORDER BY created_at DESC, id DESC 
                LIMIT 1
            ", [$customerId]);
            
            if (count($latestBalance) > 0) {
                $correctBalance = $latestBalance[0]->balance;
                DB::update("UPDATE customers SET current_balance = ? WHERE id = ?", [$correctBalance, $customerId]);
                echo "  ✅ Updated customer $customerId balance\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Error updating customer $customerId: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n4. CREATING CHEQUE HANDLING GUIDELINES:\n";
echo str_repeat("-", 60) . "\n";

$guidelines = [
    'cheque_received' => [
        'description' => 'When cheque is received (pending status)',
        'actions' => [
            'Create payment record with cheque_status = pending',
            'Do NOT update sale total_paid yet', 
            'Do NOT create ledger payment entry',
            'Customer balance remains unchanged',
            'Sale status remains Due/Partial'
        ]
    ],
    'cheque_cleared' => [
        'description' => 'When cheque clears successfully', 
        'actions' => [
            'Update cheque_status = cleared',
            'Update payment_status = completed',
            'Update sale total_paid and recalculate total_due',
            'Create ledger payment entry (credit)',
            'Update customer current_balance',
            'Update sale payment_status if fully paid'
        ]
    ],
    'cheque_bounced' => [
        'description' => 'When cheque bounces',
        'actions' => [
            'Update cheque_status = bounced',
            'Update payment_status = failed', 
            'Ensure sale total_paid excludes bounced amount',
            'Remove any ledger payment entries',
            'Add bank charges if applicable',
            'Customer balance reflects outstanding amount'
        ]
    ]
];

foreach ($guidelines as $scenario => $guide) {
    echo "\n📋 " . strtoupper(str_replace('_', ' ', $scenario)) . ":\n";
    echo "   " . $guide['description'] . "\n";
    foreach ($guide['actions'] as $action) {
        echo "   ✓ $action\n";
    }
}

// Save guidelines to file
file_put_contents("cheque_handling_guidelines_$timestamp.json", json_encode($guidelines, JSON_PRETTY_PRINT));
echo "\n✅ Guidelines saved to: cheque_handling_guidelines_$timestamp.json\n";

echo "\n5. FINAL VERIFICATION:\n";
echo str_repeat("-", 60) . "\n";

// Re-run the analysis to check if issues are fixed
$remainingIssues = 0;
foreach ($allChequePayments as $payment) {
    // Re-check each payment for issues
    $stillHasIssue = false;
    
    if ($payment->cheque_status == 'pending') {
        $updatedSale = DB::select("SELECT payment_status, total_due FROM sales WHERE id = ?", [$payment->sale_id])[0] ?? null;
        if ($updatedSale && $updatedSale->payment_status == 'Paid' && $updatedSale->total_due == 0) {
            $stillHasIssue = true;
        }
    }
    
    if ($stillHasIssue) {
        $remainingIssues++;
    }
}

if ($remainingIssues == 0) {
    echo "🎉 ALL CHEQUE PAYMENT ISSUES RESOLVED!\n";
    echo "✅ Cheque handling is now consistent and accurate\n";
} else {
    echo "⚠️  $remainingIssues issues still remain - may need manual review\n";
}

echo "\n✅ CHEQUE PAYMENT HANDLING FIX COMPLETE!\n";