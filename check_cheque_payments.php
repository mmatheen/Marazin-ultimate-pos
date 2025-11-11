<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 COMPREHENSIVE CHEQUE PAYMENT ANALYSIS\n";
echo "=========================================\n\n";

// 1. Check cheque payments and their impact
echo "1. ANALYZING CHEQUE PAYMENTS:\n";
echo str_repeat("-", 60) . "\n";

$chequePayments = DB::select("
    SELECT 
        p.id as payment_id,
        p.reference_id as sale_id,
        p.amount,
        p.cheque_status,
        p.payment_status,
        p.cheque_number,
        p.cheque_received_date,
        p.cheque_clearance_date,
        p.customer_id,
        c.first_name,
        c.last_name,
        c.current_balance,
        s.invoice_no,
        s.final_total,
        s.total_paid as sale_total_paid,
        s.total_due as sale_total_due,
        s.payment_status as sale_payment_status
    FROM payments p
    LEFT JOIN customers c ON p.customer_id = c.id
    LEFT JOIN sales s ON p.reference_id = s.id AND p.payment_type = 'sale'
    WHERE p.payment_method = 'cheque' 
    AND p.payment_type = 'sale'
    ORDER BY p.created_at DESC
    LIMIT 20
");

if (count($chequePayments) > 0) {
    echo "Found " . count($chequePayments) . " cheque payments:\n\n";
    
    $pendingCheques = [];
    $clearedCheques = [];
    $bouncedCheques = [];
    
    foreach ($chequePayments as $payment) {
        echo "Payment ID: {$payment->payment_id} | Cheque: {$payment->cheque_number}\n";
        echo "  Customer: {$payment->customer_id} ({$payment->first_name} {$payment->last_name})\n";
        echo "  Sale: {$payment->sale_id} | Invoice: {$payment->invoice_no}\n";
        echo "  Amount: Rs " . number_format($payment->amount, 2) . "\n";
        echo "  Cheque Status: {$payment->cheque_status}\n";
        echo "  Payment Status: {$payment->payment_status}\n";
        echo "  Sale Total: Rs " . number_format($payment->final_total, 2) . "\n";
        echo "  Sale Paid: Rs " . number_format($payment->sale_total_paid, 2) . "\n";
        echo "  Sale Due: Rs " . number_format($payment->sale_total_due, 2) . "\n";
        echo "  Sale Status: {$payment->sale_payment_status}\n";
        echo "  Customer Balance: Rs " . number_format($payment->current_balance, 2) . "\n";
        
        // Categorize based on cheque status
        if ($payment->cheque_status == 'pending') {
            $pendingCheques[] = $payment;
        } elseif (in_array($payment->cheque_status, ['cleared', 'deposited'])) {
            $clearedCheques[] = $payment;
        } elseif ($payment->cheque_status == 'bounced') {
            $bouncedCheques[] = $payment;
        }
        
        echo str_repeat("-", 60) . "\n";
    }
    
    echo "\nSUMMARY:\n";
    echo "- Pending cheques: " . count($pendingCheques) . "\n";
    echo "- Cleared/Deposited cheques: " . count($clearedCheques) . "\n";
    echo "- Bounced cheques: " . count($bouncedCheques) . "\n";
    
} else {
    echo "No cheque payments found.\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 2. Check for inconsistencies in cheque payment handling
echo "2. CHECKING CHEQUE PAYMENT INCONSISTENCIES:\n";
echo str_repeat("-", 60) . "\n";

$chequeInconsistencies = DB::select("
    SELECT 
        p.id as payment_id,
        p.reference_id as sale_id,
        p.amount,
        p.cheque_status,
        p.payment_status,
        s.invoice_no,
        s.final_total,
        s.total_paid,
        s.total_due,
        s.payment_status as sale_status,
        c.first_name,
        c.last_name,
        c.current_balance
    FROM payments p
    LEFT JOIN sales s ON p.reference_id = s.id AND p.payment_type = 'sale'
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.payment_method = 'cheque' 
    AND p.payment_type = 'sale'
    AND (
        -- Case 1: Pending cheque but sale shows as paid
        (p.cheque_status = 'pending' AND s.payment_status = 'Paid')
        OR
        -- Case 2: Cleared cheque but sale still shows due
        (p.cheque_status IN ('cleared', 'deposited') AND s.total_due > 0)
        OR  
        -- Case 3: Bounced cheque but sale still shows paid
        (p.cheque_status = 'bounced' AND s.payment_status = 'Paid')
    )
    ORDER BY p.created_at DESC
");

if (count($chequeInconsistencies) > 0) {
    echo "⚠️  FOUND " . count($chequeInconsistencies) . " CHEQUE PAYMENT INCONSISTENCIES:\n\n";
    
    foreach ($chequeInconsistencies as $issue) {
        echo "🚨 INCONSISTENCY DETECTED:\n";
        echo "  Payment ID: {$issue->payment_id} | Sale ID: {$issue->sale_id}\n";
        echo "  Invoice: {$issue->invoice_no}\n";
        echo "  Customer: {$issue->first_name} {$issue->last_name}\n";
        echo "  Cheque Amount: Rs " . number_format($issue->amount, 2) . "\n";
        echo "  Cheque Status: {$issue->cheque_status}\n";
        echo "  Payment Status: {$issue->payment_status}\n";
        echo "  Sale Total: Rs " . number_format($issue->final_total, 2) . "\n";
        echo "  Sale Paid: Rs " . number_format($issue->total_paid, 2) . "\n";
        echo "  Sale Due: Rs " . number_format($issue->total_due, 2) . "\n";
        echo "  Sale Status: {$issue->sale_status}\n";
        
        // Identify the specific problem
        if ($issue->cheque_status == 'pending' && $issue->sale_status == 'Paid') {
            echo "  ❌ PROBLEM: Pending cheque but sale marked as PAID!\n";
        } elseif (in_array($issue->cheque_status, ['cleared', 'deposited']) && $issue->total_due > 0) {
            echo "  ❌ PROBLEM: Cleared cheque but sale still shows DUE!\n";
        } elseif ($issue->cheque_status == 'bounced' && $issue->sale_status == 'Paid') {
            echo "  ❌ PROBLEM: Bounced cheque but sale still marked as PAID!\n";
        }
        
        echo str_repeat("-", 60) . "\n";
    }
} else {
    echo "✅ No cheque payment inconsistencies found!\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

// 3. Check ledger entries for cheque payments
echo "3. CHECKING LEDGER ENTRIES FOR CHEQUE PAYMENTS:\n";
echo str_repeat("-", 60) . "\n";

$chequeLedgerCheck = DB::select("
    SELECT 
        p.id as payment_id,
        p.reference_id as sale_id,
        p.amount as payment_amount,
        p.cheque_status,
        p.cheque_number,
        s.invoice_no,
        c.first_name,
        c.last_name,
        -- Check for corresponding ledger entries
        (SELECT COUNT(*) FROM ledgers l 
         WHERE l.reference_no = s.invoice_no 
         AND l.contact_type = 'customer' 
         AND l.user_id = c.id 
         AND l.transaction_type = 'sale') as sale_ledger_count,
        (SELECT COUNT(*) FROM ledgers l 
         WHERE l.reference_no LIKE CONCAT('PAY-%', p.id)
         AND l.contact_type = 'customer' 
         AND l.user_id = c.id 
         AND l.transaction_type = 'payment') as payment_ledger_count
    FROM payments p
    LEFT JOIN sales s ON p.reference_id = s.id AND p.payment_type = 'sale'
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.payment_method = 'cheque' 
    AND p.payment_type = 'sale'
    ORDER BY p.created_at DESC
    LIMIT 10
");

if (count($chequeLedgerCheck) > 0) {
    echo "Cheque payment ledger verification:\n\n";
    
    foreach ($chequeLedgerCheck as $check) {
        echo "Payment ID: {$check->payment_id} | Cheque: {$check->cheque_number}\n";
        echo "  Customer: {$check->first_name} {$check->last_name}\n";
        echo "  Invoice: {$check->invoice_no} | Amount: Rs " . number_format($check->payment_amount, 2) . "\n";
        echo "  Cheque Status: {$check->cheque_status}\n";
        echo "  Sale Ledger Entries: {$check->sale_ledger_count}\n";
        echo "  Payment Ledger Entries: {$check->payment_ledger_count}\n";
        
        // Check for issues
        if ($check->sale_ledger_count == 0) {
            echo "  ❌ Missing sale ledger entry!\n";
        }
        
        if ($check->cheque_status == 'pending' && $check->payment_ledger_count > 0) {
            echo "  ⚠️  Payment ledger created for pending cheque!\n";
        } elseif (in_array($check->cheque_status, ['cleared', 'deposited']) && $check->payment_ledger_count == 0) {
            echo "  ❌ Missing payment ledger for cleared cheque!\n";
        }
        
        echo str_repeat("-", 40) . "\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";

// 4. Proposed solution summary
echo "4. CHEQUE PAYMENT HANDLING RECOMMENDATIONS:\n";
echo str_repeat("-", 60) . "\n";

echo "📋 CORRECT CHEQUE PAYMENT FLOW:\n\n";

echo "1. WHEN CHEQUE IS RECEIVED:\n";
echo "   - Payment record created with cheque_status = 'pending'\n";
echo "   - Sale total_paid should NOT be updated\n";
echo "   - Sale status remains 'Due' or 'Partial'\n";
echo "   - NO ledger payment entry created yet\n";
echo "   - Customer balance remains unchanged\n\n";

echo "2. WHEN CHEQUE IS CLEARED:\n";
echo "   - Update cheque_status = 'cleared'\n";
echo "   - Update payment_status = 'completed'\n";
echo "   - Update sale total_paid and recalculate total_due\n";
echo "   - Create ledger payment entry (credit)\n";
echo "   - Update customer balance\n";
echo "   - Update sale payment_status if fully paid\n\n";

echo "3. WHEN CHEQUE BOUNCES:\n";
echo "   - Update cheque_status = 'bounced'\n";
echo "   - Update payment_status = 'failed'\n";
echo "   - Ensure sale remains as 'Due'\n";
echo "   - NO ledger payment entry\n";
echo "   - Customer balance unchanged\n";
echo "   - Create bounce charges if applicable\n\n";

$totalInconsistencies = count($chequeInconsistencies);
if ($totalInconsistencies > 0) {
    echo "🚨 URGENT: Found $totalInconsistencies cheque payment inconsistencies!\n";
    echo "   These need to be fixed to ensure accurate customer balances.\n\n";
} else {
    echo "✅ Cheque payment handling appears consistent!\n\n";
}

echo "✅ CHEQUE PAYMENT ANALYSIS COMPLETE!\n";