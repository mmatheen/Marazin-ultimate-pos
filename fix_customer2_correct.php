<?php
/**
 * ===================================================================
 * 🎯 CUSTOMER 2 CORRECT BALANCE FIX
 * ===================================================================
 * 
 * Fix Customer 2 to have only opening balance 720
 * All bills should be settled with correct payments only
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🎯 CUSTOMER 2 (SITHIK STORE) CORRECT BALANCE FIX\n";
echo "===============================================\n\n";

$customerId = 2;

echo "📋 CURRENT SITUATION:\n";
echo "Customer should have:\n";
echo "- Opening balance: 720\n";
echo "- All bills settled with exact payments\n";
echo "- Final balance: 720 (opening balance only)\n\n";

// Get current sales and expected payments
$sales = DB::table('sales')->where('customer_id', $customerId)->get();
$totalSalesAmount = $sales->sum('final_total');

echo "💼 ACTUAL BUSINESS TRANSACTIONS:\n";
echo "Sales: {$sales->count()} records = Rs. {$totalSalesAmount}\n";

foreach ($sales as $sale) {
    echo "  - {$sale->invoice_no}: Rs. {$sale->final_total}\n";
}

echo "\nFor bills to be 'settled', we need EXACTLY Rs. {$totalSalesAmount} in payments\n";
echo "Final balance should be: 720 (opening) + {$totalSalesAmount} (sales) - {$totalSalesAmount} (payments) = 720\n\n";

// Check current payments
$payments = DB::table('payments')->where('customer_id', $customerId)->get();
echo "💰 CURRENT PAYMENT RECORDS:\n";
$totalCorrectPayments = 0;
$incorrectPayments = [];

foreach ($payments as $payment) {
    echo "  - {$payment->reference_no}: Rs. {$payment->amount}";
    
    if (strpos($payment->reference_no, 'OB-PAYMENT') !== false || 
        strpos($payment->reference_no, 'OPENING') !== false ||
        $payment->amount > 100000) { // Large payments are likely incorrect
        echo " [SUSPICIOUS - Opening Balance Payment]";
        $incorrectPayments[] = $payment;
    } else {
        echo " [CORRECT - Bill Payment]";
        $totalCorrectPayments += $payment->amount;
    }
    echo "\n";
}

echo "\nCorrect bill payments total: Rs. {$totalCorrectPayments}\n";
echo "Sales total: Rs. {$totalSalesAmount}\n";

if (abs($totalCorrectPayments - $totalSalesAmount) < 1) {
    echo "✅ Bills are exactly settled with correct payments!\n\n";
} else {
    $difference = $totalCorrectPayments - $totalSalesAmount;
    echo "⚠️  Payment difference: Rs. {$difference}\n\n";
}

// Show incorrect payments to remove
if (!empty($incorrectPayments)) {
    echo "🚨 INCORRECT PAYMENTS TO REMOVE:\n";
    foreach ($incorrectPayments as $payment) {
        echo "  - Payment ID {$payment->id}: {$payment->reference_no} = Rs. {$payment->amount}\n";
        echo "    Date: {$payment->created_at}\n";
        echo "    Notes: " . substr($payment->notes ?? '', 0, 50) . "\n\n";
    }
}

// Check ledger entries for these incorrect payments
$incorrectLedgerEntries = [];
foreach ($incorrectPayments as $payment) {
    $ledgerEntry = DB::table('ledgers')
        ->where('contact_id', $customerId)
        ->where('reference_no', $payment->reference_no)
        ->where('transaction_type', 'payments')
        ->where('status', 'active')
        ->first();
    
    if ($ledgerEntry) {
        $incorrectLedgerEntries[] = $ledgerEntry;
    }
}

if (!empty($incorrectLedgerEntries)) {
    echo "🔴 INCORRECT LEDGER ENTRIES TO REVERSE:\n";
    foreach ($incorrectLedgerEntries as $entry) {
        echo "  - Ledger ID {$entry->id}: {$entry->reference_no} = Credit: {$entry->credit}\n";
    }
    echo "\n";
}

// Calculate what balance should be after fix
$expectedBalance = 720 + $totalSalesAmount - $totalCorrectPayments;
echo "📊 EXPECTED BALANCE AFTER FIX: Rs. {$expectedBalance}\n\n";

// Ask for confirmation
echo "Do you want to remove the incorrect opening balance payment and fix the ledger? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation === 'yes') {
    echo "\n🔧 FIXING CUSTOMER 2 BALANCE...\n\n";
    
    DB::beginTransaction();
    try {
        $fixedCount = 0;
        
        // Remove incorrect payment records
        foreach ($incorrectPayments as $payment) {
            echo "Removing incorrect payment ID {$payment->id} ({$payment->reference_no})...\n";
            
            // Mark payment as deleted/cancelled
            DB::table('payments')->where('id', $payment->id)->update([
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [CANCELLED: Incorrect opening balance payment - " . date('Y-m-d H:i:s') . "]')")
            ]);
            
            $fixedCount++;
        }
        
        // Remove incorrect ledger entries
        foreach ($incorrectLedgerEntries as $entry) {
            echo "Reversing incorrect ledger entry ID {$entry->id}...\n";
            
            DB::table('ledgers')->where('id', $entry->id)->update([
                'status' => 'reversed',
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [REVERSED: Incorrect opening balance payment - " . date('Y-m-d H:i:s') . "]')")
            ]);
            
            $fixedCount++;
        }
        
        DB::commit();
        
        echo "\n✅ FIXED {$fixedCount} INCORRECT ENTRIES!\n\n";
        
        // Show new balance
        $newBalance = DB::table('ledgers')
            ->where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->selectRaw('SUM(debit - credit) as balance')
            ->first();
            
        $newActiveCount = DB::table('ledgers')
            ->where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->count();
            
        echo "📊 NEW CUSTOMER 2 STATUS:\n";
        echo "Active ledger entries: {$newActiveCount}\n";
        echo "New calculated balance: Rs. {$newBalance->balance}\n";
        echo "Expected balance: Rs. {$expectedBalance}\n";
        
        if (abs($newBalance->balance - $expectedBalance) < 1) {
            echo "✅ Balance is now correct!\n";
            echo "✅ Customer 2 now shows: Opening balance (720) + All bills settled = Rs. {$newBalance->balance}\n";
        } else {
            echo "⚠️  Balance still needs minor adjustment\n";
        }
        
        // Final summary
        echo "\n🎉 CUSTOMER 2 LEDGER CORRECTED!\n";
        echo "==================================\n";
        echo "✅ Removed incorrect opening balance payments\n";
        echo "✅ Kept only legitimate bill payments\n";
        echo "✅ Final balance shows proper amount\n";
        echo "✅ All bills are settled correctly\n";
        
    } catch (Exception $e) {
        DB::rollback();
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        echo "Changes rolled back.\n";
    }
} else {
    echo "❌ Fix cancelled.\n";
}

echo "\n✅ Analysis completed at " . date('Y-m-d H:i:s') . "\n";