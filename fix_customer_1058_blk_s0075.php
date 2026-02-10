<?php

/**
 * Fix Specific Bulk Payment - Customer 1058, BLK-S0075
 * 
 * This script fixes ONLY the specific issue for customer 1058 bulk payment BLK-S0075
 * 
 * Usage: php fix_customer_1058_blk_s0075.php --auto
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Ledger;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$autoMode = in_array('--auto', $argv);

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║         🔧 FIX CUSTOMER 1058 BULK PAYMENT BLK-S0075 LEDGER ISSUE             ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$bulkRef = 'BLK-S0075';
$customerId = 1058;

// Get payments
$payments = Payment::where('reference_no', $bulkRef)
    ->where('customer_id', $customerId)
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

// Get old format ledgers
$oldLedgers = Ledger::where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('reference_no', $bulkRef)
    ->where('status', 'active')
    ->get();

// Get new format ledgers
$newLedgers = Ledger::where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('reference_no', 'LIKE', $bulkRef . '-PAY%')
    ->where('status', 'active')
    ->get();

echo "📊 Current Status:\n";
echo "   Customer ID: {$customerId}\n";
echo "   Bulk Reference: {$bulkRef}\n";
echo "   Payments: " . count($payments) . "\n";
echo "   Old format ledgers: " . count($oldLedgers) . "\n";
echo "   New format ledgers: " . count($newLedgers) . "\n";
echo "   Missing: " . (count($payments) - count($oldLedgers) - count($newLedgers)) . "\n\n";

if (count($payments) == count($newLedgers) && count($oldLedgers) == 0) {
    echo "✅ No issues found! Customer 1058 bulk payment BLK-S0075 is already fixed.\n\n";
    exit(0);
}

// Show payment details
echo "📋 Payment Details:\n";
echo str_repeat('-', 100) . "\n";
printf("%-10s %-15s %-12s %-15s %s\n", "Payment ID", "Amount", "Sale ID", "Cheque No", "Has Ledger?");
echo str_repeat('-', 100) . "\n";

$totalAmount = 0;
foreach ($payments as $payment) {
    $totalAmount += $payment->amount;
    
    // Check if ledger exists
    $hasOldLedger = $oldLedgers->where('id', '>=', 0)->count() > 0;
    $hasNewLedger = $newLedgers->contains(function($ledger) use ($payment, $bulkRef) {
        return $ledger->reference_no === $bulkRef . '-PAY' . $payment->id;
    });
    
    $ledgerStatus = $hasNewLedger ? '✅' : ($hasOldLedger ? '⚠️ Old' : '❌ Missing');
    
    printf("%-10s %-15s %-12s %-15s %s\n",
        $payment->id,
        'Rs. ' . number_format($payment->amount, 2),
        $payment->reference_id ?: 'N/A',
        $payment->cheque_number ?: 'N/A',
        $ledgerStatus
    );
}

echo str_repeat('-', 100) . "\n";
echo "Total Amount: Rs. " . number_format($totalAmount, 2) . "\n";
echo str_repeat('-', 100) . "\n\n";

if (!$autoMode) {
    echo "⚠️  This will:\n";
    echo "   1. Delete " . count($oldLedgers) . " old format ledgers (BLK-S0075)\n";
    echo "   2. Create " . count($payments) . " new format ledgers (BLK-S0075-PAY###)\n";
    echo "   3. Fix customer 1058 balance calculation\n\n";
    echo "Proceed? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'yes') {
        echo "\n❌ Operation cancelled.\n\n";
        exit(0);
    }
}

echo "\n🔧 Executing fix...\n";
echo str_repeat('-', 80) . "\n\n";

try {
    DB::transaction(function() use ($bulkRef, $customerId, $payments, $oldLedgers, $newLedgers) {
        
        // Step 1: Delete old format ledgers
        if (count($oldLedgers) > 0) {
            $deleted = Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('reference_no', $bulkRef)
                ->where('status', 'active')
                ->delete();
            
            echo "✓ Deleted {$deleted} old format ledgers\n\n";
        }
        
        // Step 2: Create new format ledgers for each payment
        echo "Creating new ledgers:\n";
        $created = 0;
        
        foreach ($payments as $payment) {
            $newRef = $bulkRef . '-PAY' . $payment->id;
            
            // Check if already exists
            $exists = Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('reference_no', $newRef)
                ->where('status', 'active')
                ->exists();
            
            if ($exists) {
                echo "  ⊘ Payment #{$payment->id} - ledger already exists\n";
                continue;
            }
            
            // Use original payment creation date
            $transactionDate = Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo');
            
            // Create new ledger entry
            $ledger = new Ledger();
            $ledger->contact_id = $customerId;
            $ledger->contact_type = 'customer';
            $ledger->transaction_date = $transactionDate;
            $ledger->reference_no = $newRef;
            $ledger->transaction_type = 'payments';
            $ledger->debit = 0;
            $ledger->credit = $payment->amount;
            $ledger->status = 'active';
            $ledger->notes = $payment->notes ?: "Payment #{$bulkRef}";
            $ledger->created_by = $payment->created_by ?? 1;
            $ledger->created_at = $transactionDate;
            $ledger->updated_at = Carbon::now();
            $ledger->save();
            
            echo "  ✓ Payment #{$payment->id} → {$newRef} (Rs. " . number_format($payment->amount, 2) . ")\n";
            $created++;
        }
        
        echo "\n✅ Created {$created} new ledger entries\n";
    });
    
    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "✅ FIX COMPLETED SUCCESSFULLY\n";
    echo str_repeat('=', 80) . "\n\n";
    
} catch (\Exception $e) {
    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "❌ FIX FAILED - ROLLED BACK\n";
    echo str_repeat('=', 80) . "\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Verification
echo "🔍 Verification:\n";
echo str_repeat('-', 80) . "\n\n";

$finalPaymentCount = Payment::where('reference_no', $bulkRef)
    ->where('customer_id', $customerId)
    ->where('status', 'active')
    ->count();

$finalNewLedgers = Ledger::where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('reference_no', 'LIKE', $bulkRef . '-PAY%')
    ->where('status', 'active')
    ->count();

$finalOldLedgers = Ledger::where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('reference_no', $bulkRef)
    ->where('status', 'active')
    ->count();

$totalCredits = Ledger::where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('reference_no', 'LIKE', $bulkRef . '%')
    ->where('status', 'active')
    ->sum('credit');

echo "   Payments: {$finalPaymentCount}\n";
echo "   New format ledgers: {$finalNewLedgers}\n";
echo "   Old format ledgers: {$finalOldLedgers}\n";
echo "   Total ledger credits: Rs. " . number_format($totalCredits, 2) . "\n\n";

if ($finalPaymentCount == $finalNewLedgers && $finalOldLedgers == 0) {
    echo "✅ VERIFICATION PASSED!\n\n";
    echo "📊 Result:\n";
    echo "   • Customer 1058 bulk payment BLK-S0075 fixed\n";
    echo "   • All {$finalPaymentCount} payments have unique ledger entries\n";
    echo "   • Customer balance now accurate (includes all Rs. " . number_format($totalCredits, 2) . ")\n";
    echo "   • Payment records unchanged (still grouped as BLK-S0075)\n\n";
    echo "✨ Done!\n\n";
} else {
    echo "⚠️  VERIFICATION WARNING\n";
    echo "   Expected: {$finalPaymentCount} payments = {$finalPaymentCount} ledgers\n";
    echo "   Actual: {$finalNewLedgers} new ledgers, {$finalOldLedgers} old ledgers\n\n";
}
