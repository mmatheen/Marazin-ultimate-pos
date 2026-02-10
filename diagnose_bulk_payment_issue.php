<?php

/**
 * Diagnose Bulk Payment Ledger Issue
 * 
 * This script shows exactly what the problem is:
 * - Multiple payments created with same reference_no (e.g., BLK-S0075)
 * - Only first payment gets ledger entry
 * - Subsequent payments are skipped due to duplicate detection
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Ledger;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║         BULK PAYMENT LEDGER ISSUE DIAGNOSIS - Customer ID: 1058              ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Example 1: BLK-S0075 (18 payments)
echo "📋 EXAMPLE 1: Bulk Payment BLK-S0075\n";
echo str_repeat('=', 80) . "\n\n";

$bulkRef1 = 'BLK-S0075';
$payments1 = Payment::where('reference_no', $bulkRef1)
    ->where('customer_id', 1058)
    ->orderBy('id')
    ->get();

echo "Payments created: " . count($payments1) . "\n\n";

echo "Payment Records:\n";
echo str_repeat('-', 80) . "\n";
printf("%-8s %-12s %-12s %-15s %s\n", "Pay ID", "Amount", "Sale ID", "Cheque No", "Has Ledger?");
echo str_repeat('-', 80) . "\n";

foreach ($payments1 as $payment) {
    // Check if ledger entry exists with either old format or new format
    $ledgerOld = Ledger::where('contact_id', $payment->customer_id)
        ->where('reference_no', $bulkRef1)
        ->where('status', 'active')
        ->first();
    
    $ledgerNew = Ledger::where('contact_id', $payment->customer_id)
        ->where('reference_no', $bulkRef1 . '-PAY' . $payment->id)
        ->where('status', 'active')
        ->first();
    
    $hasLedger = $ledgerOld || $ledgerNew ? '✅ YES' : '❌ NO';
    
    printf("%-8s %-12s %-12s %-15s %s\n",
        $payment->id,
        number_format($payment->amount, 2),
        $payment->reference_id ?: 'N/A',
        $payment->cheque_number ?: 'N/A',
        $hasLedger
    );
}

echo str_repeat('-', 80) . "\n\n";

// Count ledger entries for this bulk reference
$ledgerCount = Ledger::where('contact_id', 1058)
    ->where(function($q) use ($bulkRef1) {
        $q->where('reference_no', $bulkRef1)
          ->orWhere('reference_no', 'LIKE', $bulkRef1 . '-PAY%');
    })
    ->where('status', 'active')
    ->count();

echo "📊 Summary:\n";
echo "   Total Payments: " . count($payments1) . "\n";
echo "   Total Ledger Entries: " . $ledgerCount . "\n";
echo "   Missing Ledger Entries: " . (count($payments1) - $ledgerCount) . "\n\n";

if ($ledgerCount < count($payments1)) {
    echo "❌ PROBLEM DETECTED: Not all payments have ledger entries!\n\n";
    
    echo "🔍 WHY THIS HAPPENS:\n";
    echo "   1. All payments created with same reference_no: '{$bulkRef1}'\n";
    echo "   2. When creating ledger entries, duplicate detection checks:\n";
    echo "      - Same contact_id (1058)\n";
    echo "      - Same reference_no ('{$bulkRef1}')\n";
    echo "      - Same transaction_type ('payments')\n";
    echo "      - Within 10 seconds\n";
    echo "   3. First payment creates ledger entry successfully\n";
    echo "   4. Subsequent payments are flagged as duplicates and SKIPPED!\n\n";
} else {
    echo "✅ All payments have ledger entries\n\n";
}

// Example 2: BLK-S0076 (1 payment)
echo "\n";
echo "📋 EXAMPLE 2: Bulk Payment BLK-S0076\n";
echo str_repeat('=', 80) . "\n\n";

$bulkRef2 = 'BLK-S0076';
$payments2 = Payment::where('reference_no', $bulkRef2)
    ->where('customer_id', 1058)
    ->orderBy('id')
    ->get();

echo "Payments created: " . count($payments2) . "\n\n";

foreach ($payments2 as $payment) {
    $ledgerOld = Ledger::where('contact_id', $payment->customer_id)
        ->where('reference_no', $bulkRef2)
        ->where('status', 'active')
        ->first();
    
    $ledgerNew = Ledger::where('contact_id', $payment->customer_id)
        ->where('reference_no', $bulkRef2 . '-PAY' . $payment->id)
        ->where('status', 'active')
        ->first();
    
    $hasLedger = $ledgerOld || $ledgerNew;
    
    echo "Payment ID: {$payment->id}, Amount: " . number_format($payment->amount, 2);
    echo ", Has Ledger: " . ($hasLedger ? '✅ YES' : '❌ NO') . "\n";
}

// Show the actual ledger entries
echo "\n\n";
echo "📖 ACTUAL LEDGER ENTRIES (for customer 1058):\n";
echo str_repeat('=', 80) . "\n\n";

$ledgers = Ledger::where('contact_id', 1058)
    ->where('contact_type', 'customer')
    ->where(function($q) {
        $q->where('reference_no', 'LIKE', 'BLK-S%')
          ->where('transaction_type', 'payments');
    })
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

printf("%-8s %-20s %-12s %-12s %s\n", "Ledger ID", "Reference No", "Debit", "Credit", "Notes");
echo str_repeat('-', 80) . "\n";

foreach ($ledgers as $ledger) {
    printf("%-8s %-20s %-12s %-12s %s\n",
        $ledger->id,
        substr($ledger->reference_no, 0, 20),
        number_format($ledger->debit, 2),
        number_format($ledger->credit, 2),
        substr($ledger->notes, 0, 30)
    );
}

echo str_repeat('-', 80) . "\n";
echo "Total ledger entries shown: " . count($ledgers) . "\n\n";

// Show the balance impact
echo "\n";
echo "💰 BALANCE IMPACT:\n";
echo str_repeat('=', 80) . "\n\n";

$totalPayments = Payment::where('customer_id', 1058)
    ->where('reference_no', 'LIKE', 'BLK-S%')
    ->where('status', 'active')
    ->sum('amount');

$totalLedgerCredits = Ledger::where('contact_id', 1058)
    ->where('reference_no', 'LIKE', 'BLK-S%')
    ->where('transaction_type', 'payments')
    ->where('status', 'active')
    ->sum('credit');

echo "Total Payments Amount: Rs. " . number_format($totalPayments, 2) . "\n";
echo "Total Ledger Credits:  Rs. " . number_format($totalLedgerCredits, 2) . "\n";
echo "Missing from Ledger:   Rs. " . number_format($totalPayments - $totalLedgerCredits, 2) . "\n\n";

if ($totalPayments != $totalLedgerCredits) {
    echo "❌ PROBLEM: Customer's ledger is MISSING Rs. " . number_format($totalPayments - $totalLedgerCredits, 2) . " in payment credits!\n";
    echo "   This means the customer's balance calculation is WRONG!\n";
    echo "   Customer appears to owe MORE than they actually do.\n\n";
}

// Show the fix
echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                              THE FIX                                          ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "🔧 What the fix does:\n";
echo "   1. Change reference_no format for bulk payments:\n";
echo "      OLD: All payments use 'BLK-S0075'\n";
echo "      NEW: Each payment gets unique reference 'BLK-S0075-PAY638', 'BLK-S0075-PAY639', etc.\n\n";
echo "   2. This makes each payment unique in ledger duplicate detection\n\n";
echo "   3. Repair existing data: Create missing ledger entries with new unique references\n\n";

echo "📝 Files changed:\n";
echo "   - app/Services/UnifiedLedgerService.php (recordSalePayment, recordPurchasePayment)\n";
echo "   - app/Models/Ledger.php (duplicate detection logic)\n\n";

echo "🎯 To fix the existing data, run:\n";
echo "   php fix_missing_bulk_payment_ledgers.php\n\n";

echo "Done!\n\n";
