<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📋 ISSUES SUMMARY FOR USER EXPLANATION\n";
echo "======================================\n\n";

echo "🔍 CURRENT ISSUES FOUND:\n";
echo str_repeat("-", 40) . "\n";

// 1. Payment Issues
echo "1. PAYMENT LEDGER ISSUES:\n";
$missingPaymentLedgers = DB::select("
    SELECT COUNT(*) as count, 
           SUM(amount) as total_amount,
           payment_method
    FROM payments p
    WHERE p.payment_type = 'sale'
    AND p.payment_status = 'completed'
    AND NOT EXISTS (
        SELECT 1 FROM ledgers l 
        WHERE l.reference_no LIKE CONCAT('PAY-%', p.id)
        AND l.contact_type = 'customer' 
        AND l.transaction_type = 'payment'
    )
    GROUP BY payment_method
");

$totalMissingPayments = 0;
$totalMissingAmount = 0;
foreach ($missingPaymentLedgers as $missing) {
    echo "   - {$missing->payment_method}: {$missing->count} payments, Rs " . number_format($missing->total_amount, 2) . "\n";
    $totalMissingPayments += $missing->count;
    $totalMissingAmount += $missing->total_amount;
}
echo "   TOTAL: $totalMissingPayments payments worth Rs " . number_format($totalMissingAmount, 2) . "\n\n";

// 2. Customer Balance Issues
echo "2. CUSTOMER BALANCE ISSUES:\n";
$balanceIssues = DB::select("
    SELECT COUNT(*) as count
    FROM customers c
    LEFT JOIN (
        SELECT 
            user_id,
            balance,
            ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC, id DESC) as rn
        FROM ledgers 
        WHERE contact_type = 'customer'
    ) l ON c.id = l.user_id AND l.rn = 1
    WHERE ABS(c.current_balance - COALESCE(l.balance, c.opening_balance, 0)) > 0.01
")[0]->count;

echo "   - $balanceIssues customers have incorrect balances\n\n";

// 3. Sales Ledger Issues
echo "3. SALES LEDGER ISSUES:\n";
$salesIssues = DB::select("
    SELECT COUNT(*) as count
    FROM sales s
    WHERE s.customer_id != 1 
    AND s.invoice_no IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM ledgers l 
        WHERE l.reference_no = s.invoice_no 
        AND l.contact_type = 'customer' 
        AND l.transaction_type = 'sale'
    )
")[0]->count;

$duplicateLedgers = DB::select("
    SELECT COUNT(*) as count
    FROM (
        SELECT reference_no
        FROM ledgers 
        WHERE contact_type = 'customer' 
        AND transaction_type = 'sale'
        GROUP BY reference_no
        HAVING COUNT(*) > 1
    ) dup
")[0]->count;

echo "   - $salesIssues sales without ledger entries\n";
echo "   - $duplicateLedgers invoices with duplicate ledger entries\n\n";

// 4. Cheque Payment Issues
echo "4. CHEQUE PAYMENT ISSUES:\n";
$pendingCheques = DB::select("
    SELECT COUNT(*) as count, SUM(amount) as total_amount
    FROM payments p
    WHERE p.payment_method = 'cheque'
    AND p.cheque_status = 'pending'
    AND p.payment_type = 'sale'
")[0];

echo "   - {$pendingCheques->count} pending cheques worth Rs " . number_format($pendingCheques->total_amount, 2) . "\n";
echo "   - These should NOT affect customer balances until cleared\n\n";

echo "🎯 WHAT NEEDS TO BE FIXED:\n";
echo str_repeat("-", 40) . "\n";
echo "1. Create missing ledger entries for completed payments\n";
echo "2. Fix customer balance calculations\n";
echo "3. Remove duplicate ledger entries\n";
echo "4. Handle pending cheques correctly\n";
echo "5. Ensure all sales have proper ledger entries\n\n";

echo "💡 WHY THESE ISSUES OCCURRED:\n";
echo str_repeat("-", 40) . "\n";
echo "- Payment ledger entries were not created when payments were recorded\n";
echo "- Customer balances were not updated after transactions\n";
echo "- Some sales created duplicate ledger entries\n";
echo "- Pending cheques were treated as completed payments\n\n";

// Get specific examples for explanation
echo "📝 EXAMPLES OF ISSUES:\n";
echo str_repeat("-", 40) . "\n";

// Example payment issue
$examplePayment = DB::select("
    SELECT 
        p.id, p.amount, p.payment_method, 
        s.invoice_no, 
        c.first_name, c.last_name
    FROM payments p
    LEFT JOIN sales s ON p.reference_id = s.id
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.payment_type = 'sale'
    AND p.payment_status = 'completed'
    AND NOT EXISTS (
        SELECT 1 FROM ledgers l 
        WHERE l.reference_no LIKE CONCAT('PAY-%', p.id)
    )
    LIMIT 1
")[0] ?? null;

if ($examplePayment) {
    echo "EXAMPLE - Missing Payment Ledger:\n";
    echo "  Payment ID: {$examplePayment->id}\n";
    echo "  Customer: {$examplePayment->first_name} {$examplePayment->last_name}\n";
    echo "  Invoice: {$examplePayment->invoice_no}\n";
    echo "  Amount: Rs " . number_format($examplePayment->amount, 2) . "\n";
    echo "  Method: {$examplePayment->payment_method}\n";
    echo "  Problem: Payment was recorded but no ledger entry created\n";
    echo "  Impact: Customer balance is wrong\n\n";
}

// Example balance issue
$exampleBalance = DB::select("
    SELECT 
        c.id, c.first_name, c.last_name, c.current_balance,
        COALESCE(l.balance, 0) as ledger_balance
    FROM customers c
    LEFT JOIN (
        SELECT 
            user_id, balance,
            ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC, id DESC) as rn
        FROM ledgers 
        WHERE contact_type = 'customer'
    ) l ON c.id = l.user_id AND l.rn = 1
    WHERE ABS(c.current_balance - COALESCE(l.balance, c.opening_balance, 0)) > 100
    LIMIT 1
")[0] ?? null;

if ($exampleBalance) {
    echo "EXAMPLE - Balance Mismatch:\n";
    echo "  Customer: {$exampleBalance->first_name} {$exampleBalance->last_name}\n";
    echo "  Database Balance: Rs " . number_format($exampleBalance->current_balance, 2) . "\n";
    echo "  Ledger Balance: Rs " . number_format($exampleBalance->ledger_balance, 2) . "\n";
    echo "  Difference: Rs " . number_format($exampleBalance->current_balance - $exampleBalance->ledger_balance, 2) . "\n";
    echo "  Problem: Customer table balance doesn't match ledger calculation\n\n";
}

echo "✅ AFTER FIXING, THE SYSTEM WILL:\n";
echo str_repeat("-", 40) . "\n";
echo "1. ✅ All payments will have proper ledger entries\n";
echo "2. ✅ Customer balances will be accurate\n";
echo "3. ✅ Pending cheques won't affect balances until cleared\n";
echo "4. ✅ All sales will have correct ledger records\n";
echo "5. ✅ No duplicate or missing entries\n";
echo "6. ✅ Complete audit trail for all transactions\n\n";

echo "🎯 RECOMMENDATION: Run the comprehensive fix to resolve all issues safely.\n";