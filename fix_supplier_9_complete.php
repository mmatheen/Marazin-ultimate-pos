<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 90) . "\n";
echo "=== SUPPLIER 9 LEDGER FIX SCRIPT ===\n";
echo "=== Date: " . date('Y-m-d H:i:s') . " ===\n";
echo str_repeat("=", 90) . "\n\n";

$supplierId = 9;

try {
    DB::beginTransaction();
    
    echo "STEP 1: Analyzing Discrepancies\n";
    echo str_repeat("-", 90) . "\n\n";
    
    // Get all active payments
    $activePayments = DB::table('payments')
        ->where('supplier_id', $supplierId)
        ->where('status', 'active')
        ->orderBy('id')
        ->get();
    
    echo "Checking for missing ledger entries for active payments:\n\n";
    
    $missingCount = 0;
    $missingTotal = 0;
    
    foreach ($activePayments as $payment) {
        // Check if ledger entry exists for this payment
        $ledgerExists = DB::table('ledgers')
            ->where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'payments')
            ->where('reference_no', $payment->reference_no)
            ->where('debit', $payment->amount)
            ->where('status', 'active')
            ->exists();
        
        if (!$ledgerExists) {
            echo sprintf(
                "  ⚠ MISSING: Payment [%d] %s | Ref: %s | Amount: %.2f | Date: %s\n",
                $payment->id,
                $payment->payment_method,
                $payment->reference_no,
                $payment->amount,
                $payment->payment_date
            );
            $missingCount++;
            $missingTotal += $payment->amount;
        }
    }
    
    if ($missingCount == 0) {
        echo "  ✓ No missing payment ledger entries found\n";
    } else {
        echo "\n  Total missing: $missingCount payments, Amount: " . number_format($missingTotal, 2) . "\n";
    }
    
    echo "\n" . str_repeat("=", 90) . "\n";
    echo "STEP 2: Creating Missing Ledger Entries\n";
    echo str_repeat("-", 90) . "\n\n";
    
    $createdCount = 0;
    
    foreach ($activePayments as $payment) {
        // Check again if ledger entry exists
        $ledgerExists = DB::table('ledgers')
            ->where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'payments')
            ->where('reference_no', $payment->reference_no)
            ->where('debit', $payment->amount)
            ->where('status', 'active')
            ->exists();
        
        if (!$ledgerExists) {
            // Create the missing ledger entry
            $ledgerData = [
                'contact_id' => $supplierId,
                'transaction_date' => $payment->payment_date,
                'reference_no' => $payment->reference_no,
                'transaction_type' => 'payments',
                'debit' => $payment->amount,
                'credit' => 0,
                'status' => 'active',
                'contact_type' => 'supplier',
                'notes' => 'Missing ledger entry restored for payment #' . $payment->id,
                'created_by' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $newLedgerId = DB::table('ledgers')->insertGetId($ledgerData);
            echo sprintf(
                "  ✓ Created ledger #%d for payment #%d (Ref: %s, Amount: %.2f)\n",
                $newLedgerId,
                $payment->id,
                $payment->reference_no,
                $payment->amount
            );
            $createdCount++;
        }
    }
    
    if ($createdCount == 0) {
        echo "  ℹ No ledger entries needed to be created\n";
    } else {
        echo "\n  ✓ Created $createdCount ledger entries\n";
    }
    
    echo "\n" . str_repeat("=", 90) . "\n";
    echo "STEP 3: Fixing Purchase PUR067 Payment\n";
    echo str_repeat("-", 90) . "\n\n";
    
    // Get purchase 91 (PUR067)
    $purchase67 = DB::table('purchases')->where('id', 91)->first();
    
    if ($purchase67 && $purchase67->total_due > 0) {
        echo sprintf(
            "  Purchase PUR067 (ID: 91): Total: %.2f | Paid: %.2f | Due: %.2f\n",
            $purchase67->final_total,
            $purchase67->total_paid,
            $purchase67->total_due
        );
        
        $remainingDue = $purchase67->total_due;
        $paymentDate = date('Y-m-d');
        
        // Create payment for remaining due
        $paymentData = [
            'payment_date' => $paymentDate,
            'amount' => $remainingDue,
            'payment_method' => 'cash',
            'reference_no' => 'PUR067',
            'notes' => 'Final payment for PUR067 - Ledger correction',
            'payment_type' => 'purchase',
            'reference_id' => 91,
            'supplier_id' => $supplierId,
            'status' => 'active',
            'payment_status' => 'completed',
            'bank_charges' => 0.00,
            'created_by' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        $paymentId = DB::table('payments')->insertGetId($paymentData);
        echo sprintf("  ✓ Created payment #%d for %.2f\n", $paymentId, $remainingDue);
        
        // Update purchase
        DB::table('purchases')
            ->where('id', 91)
            ->update([
                'total_paid' => DB::raw('total_paid + ' . $remainingDue),
                'payment_status' => 'Paid',
                'updated_at' => now()
            ]);
        echo "  ✓ Updated purchase #91 to Paid status\n";
        
        // Create ledger entry
        $ledgerData = [
            'contact_id' => $supplierId,
            'transaction_date' => $paymentDate,
            'reference_no' => 'PUR067',
            'transaction_type' => 'payments',
            'debit' => $remainingDue,
            'credit' => 0,
            'status' => 'active',
            'contact_type' => 'supplier',
            'notes' => 'Final payment for PUR067 - Ledger correction',
            'created_by' => 2,
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        $ledgerId = DB::table('ledgers')->insertGetId($ledgerData);
        echo sprintf("  ✓ Created ledger entry #%d (debit: %.2f)\n", $ledgerId, $remainingDue);
    } else {
        echo "  ℹ Purchase PUR067 already fully paid or not found\n";
    }
    
    echo "\n" . str_repeat("=", 90) . "\n";
    echo "STEP 4: FINAL VERIFICATION\n";
    echo str_repeat("=", 90) . "\n\n";
    
    // Recalculate final state
    $finalLedgers = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('status', 'active')
        ->get();
    
    $finalDebit = $finalLedgers->sum('debit');
    $finalCredit = $finalLedgers->sum('credit');
    $finalBalance = $finalCredit - $finalDebit;
    
    $finalPurchases = DB::table('purchases')
        ->where('supplier_id', $supplierId)
        ->get();
    
    $finalTotalDue = $finalPurchases->sum('total_due');
    $finalTotalPaid = $finalPurchases->sum('total_paid');
    
    echo "Final Ledger:\n";
    echo sprintf("  Debit: %.2f | Credit: %.2f | Balance: %.2f\n", $finalDebit, $finalCredit, $finalBalance);
    
    echo "\nFinal Purchases:\n";
    echo sprintf("  Total Paid: %.2f | Total Due: %.2f\n", $finalTotalPaid, $finalTotalDue);
    
    echo "\n" . str_repeat("=", 90) . "\n";
    
    // Validation
    $allGood = true;
    $issues = [];
    
    if (abs($finalTotalDue) > 0.01) {
        $allGood = false;
        $issues[] = sprintf("Purchase total_due is %.2f (should be 0)", $finalTotalDue);
    }
    
    if (abs($finalBalance) > 0.01) {
        $allGood = false;
        $issues[] = sprintf("Ledger balance is %.2f (should be 0)", $finalBalance);
    }
    
    if ($allGood) {
        echo "✓✓✓ SUCCESS! SUPPLIER 9 IS FULLY PAID WITH BALANCED LEDGER! ✓✓✓\n";
        echo str_repeat("=", 90) . "\n";
        DB::commit();
        echo "✓ All changes have been committed to the database\n\n";
    } else {
        echo "⚠ WARNING: Issues detected:\n";
        foreach ($issues as $issue) {
            echo "  • $issue\n";
        }
        echo "\nCommit anyway? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        if (strtolower($line) == 'yes' || strtolower($line) == 'y') {
            DB::commit();
            echo "✓ Changes committed\n\n";
        } else {
            DB::rollBack();
            echo "✗ Changes rolled back\n\n";
        }
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n" . str_repeat("=", 90) . "\n";
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo str_repeat("=", 90) . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}
