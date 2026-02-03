<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 90) . "\n";
echo "=== SUPPLIER 5 COMPLETE FIX SCRIPT ===\n";
echo "=== Date: " . date('Y-m-d H:i:s') . " ===\n";
echo str_repeat("=", 90) . "\n\n";

$supplierId = 5;

try {
    DB::beginTransaction();
    
    echo "STEP 1: Current State Analysis\n";
    echo str_repeat("-", 90) . "\n";
    
    // Get purchases
    $purchases = DB::table('purchases')
        ->where('supplier_id', $supplierId)
        ->orderBy('id')
        ->get();
    
    echo "\nPurchases:\n";
    $totalPurchases = 0;
    foreach ($purchases as $purchase) {
        echo sprintf(
            "  [%d] %s | Total: %10.2f | Paid: %10.2f | Due: %10.2f | %s\n",
            $purchase->id,
            $purchase->reference_no,
            $purchase->final_total,
            $purchase->total_paid,
            $purchase->total_due,
            $purchase->payment_status
        );
        $totalPurchases += $purchase->final_total;
    }
    echo "  " . str_repeat("-", 86) . "\n";
    echo sprintf("  TOTAL PURCHASES: %10.2f\n", $totalPurchases);
    
    // Get ALL payments (including deleted)
    $allPayments = DB::table('payments')
        ->where('supplier_id', $supplierId)
        ->orderBy('id')
        ->get();
    
    echo "\nAll Payments:\n";
    echo sprintf("  %-6s %-12s %-15s %12s %-10s %s\n", "ID", "Date", "Ref", "Amount", "Status", "Notes");
    echo "  " . str_repeat("-", 86) . "\n";
    foreach ($allPayments as $payment) {
        echo sprintf(
            "  %-6d %-12s %-15s %12.2f %-10s %s\n",
            $payment->id,
            $payment->payment_date,
            $payment->reference_no,
            $payment->amount,
            $payment->status,
            substr($payment->notes ?? '', 0, 30)
        );
    }
    
    // Get active payments
    $activePayments = DB::table('payments')
        ->where('supplier_id', $supplierId)
        ->where('status', 'active')
        ->orderBy('id')
        ->get();
    
    echo "\nActive Payments Summary:\n";
    $totalActivePayments = 0;
    foreach ($activePayments as $payment) {
        echo sprintf(
            "  [%d] %s | Amount: %10.2f | Ref: %s\n",
            $payment->id,
            $payment->payment_date,
            $payment->amount,
            $payment->reference_no
        );
        $totalActivePayments += $payment->amount;
    }
    echo "  " . str_repeat("-", 86) . "\n";
    echo sprintf("  TOTAL ACTIVE PAYMENTS: %10.2f\n", $totalActivePayments);
    
    // Get ALL ledger entries
    $allLedgers = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->orderBy('id')
        ->get();
    
    echo "\nAll Ledger Entries:\n";
    echo sprintf("  %-6s %-20s %-20s %12s %12s %-10s\n", "ID", "Date", "Type", "Debit", "Credit", "Status");
    echo "  " . str_repeat("-", 86) . "\n";
    foreach ($allLedgers as $ledger) {
        echo sprintf(
            "  %-6d %-20s %-20s %12.2f %12.2f %-10s %s\n",
            $ledger->id,
            $ledger->transaction_date,
            $ledger->transaction_type,
            $ledger->debit,
            $ledger->credit,
            $ledger->status,
            $ledger->reference_no
        );
    }
    
    // Calculate active ledger balance
    $activeLedgers = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('status', 'active')
        ->get();
    
    echo "\nActive Ledger Summary:\n";
    $totalDebit = 0;
    $totalCredit = 0;
    foreach ($activeLedgers as $ledger) {
        $totalDebit += $ledger->debit;
        $totalCredit += $ledger->credit;
    }
    $balance = $totalCredit - $totalDebit;
    echo sprintf("  Total Debit:  %10.2f (Payments)\n", $totalDebit);
    echo sprintf("  Total Credit: %10.2f (Purchases)\n", $totalCredit);
    echo sprintf("  Balance:      %10.2f (Amount Due)\n", $balance);
    
    echo "\n" . str_repeat("=", 90) . "\n";
    echo "STEP 2: Fix Ledger Entry 389 (PUR021 discrepancy)\n";
    echo str_repeat("-", 90) . "\n";
    
    $ledger389 = DB::table('ledgers')->where('id', 389)->first();
    if ($ledger389 && $ledger389->credit == 416100) {
        DB::table('ledgers')->where('id', 389)->update(['credit' => 417100]);
        echo "  ✓ Fixed ledger #389: Credit 416,100 → 417,100\n";
        $totalCredit += 1000;
        $balance += 1000;
    } else {
        echo "  ℹ Ledger #389 already correct or not found\n";
    }
    
    echo "\n" . str_repeat("=", 90) . "\n";
    echo "STEP 3: Find Missing Ledger Entries for Active Payments\n";
    echo str_repeat("-", 90) . "\n";
    
    $missingCount = 0;
    foreach ($activePayments as $payment) {
        // Check if there's an active ledger entry for this payment
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
                "  ⚠ Missing ledger for Payment [%d]: %s, Amount: %.2f, Ref: %s\n",
                $payment->id,
                $payment->payment_date,
                $payment->amount,
                $payment->reference_no
            );
            
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
            echo sprintf("  ✓ Created ledger entry #%d for payment #%d\n", $newLedgerId, $payment->id);
            $missingCount++;
        }
    }
    
    if ($missingCount == 0) {
        echo "  ✓ All active payments have corresponding ledger entries\n";
    }
    
    echo "\n" . str_repeat("=", 90) . "\n";
    echo "STEP 4: Calculate Outstanding Balance and Create Final Payment\n";
    echo str_repeat("-", 90) . "\n";
    
    // Recalculate ledger balance after fixes
    $activeLedgers = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('status', 'active')
        ->get();
    
    $totalDebit = 0;
    $totalCredit = 0;
    foreach ($activeLedgers as $ledger) {
        $totalDebit += $ledger->debit;
        $totalCredit += $ledger->credit;
    }
    $ledgerBalance = $totalCredit - $totalDebit;
    
    echo sprintf("  Updated Ledger - Debit: %.2f, Credit: %.2f, Balance: %.2f\n", 
        $totalDebit, $totalCredit, $ledgerBalance);
    
    if ($ledgerBalance > 0) {
        echo sprintf("\n  Outstanding amount: %.2f needs to be paid\n", $ledgerBalance);
        
        // Find which purchase has outstanding due
        $unpaidPurchases = DB::table('purchases')
            ->where('supplier_id', $supplierId)
            ->where('total_due', '>', 0)
            ->get();
        
        foreach ($unpaidPurchases as $purchase) {
            echo sprintf(
                "  Creating payment for %s (ID: %d) - Due: %.2f\n",
                $purchase->reference_no,
                $purchase->id,
                $purchase->total_due
            );
            
            $paymentAmount = $purchase->total_due;
            $paymentDate = date('Y-m-d');
            
            // Create payment
            $paymentData = [
                'payment_date' => $paymentDate,
                'amount' => $paymentAmount,
                'payment_method' => 'cash',
                'reference_no' => $purchase->reference_no,
                'notes' => 'Final payment - Ledger correction',
                'payment_type' => 'purchase',
                'reference_id' => $purchase->id,
                'supplier_id' => $supplierId,
                'status' => 'active',
                'payment_status' => 'completed',
                'bank_charges' => 0.00,
                'created_by' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            $paymentId = DB::table('payments')->insertGetId($paymentData);
            echo sprintf("  ✓ Created payment #%d for %.2f\n", $paymentId, $paymentAmount);
            
            // Update purchase
            DB::table('purchases')
                ->where('id', $purchase->id)
                ->update([
                    'total_paid' => DB::raw('total_paid + ' . $paymentAmount),
                    'payment_status' => 'Paid',
                    'updated_at' => now()
                ]);
            echo sprintf("  ✓ Updated purchase #%d to Paid status\n", $purchase->id);
            
            // Create ledger entry
            $ledgerData = [
                'contact_id' => $supplierId,
                'transaction_date' => $paymentDate,
                'reference_no' => $purchase->reference_no,
                'transaction_type' => 'payments',
                'debit' => $paymentAmount,
                'credit' => 0,
                'status' => 'active',
                'contact_type' => 'supplier',
                'notes' => 'Final payment - Ledger correction',
                'created_by' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $ledgerId = DB::table('ledgers')->insertGetId($ledgerData);
            echo sprintf("  ✓ Created ledger entry #%d (debit: %.2f)\n", $ledgerId, $paymentAmount);
        }
    } else {
        echo "  ℹ No outstanding balance - supplier fully paid\n";
    }
    
    echo "\n" . str_repeat("=", 90) . "\n";
    echo "STEP 5: FINAL VERIFICATION\n";
    echo str_repeat("=", 90) . "\n";
    
    // Final purchases
    $finalPurchases = DB::table('purchases')
        ->where('supplier_id', $supplierId)
        ->orderBy('id')
        ->get();
    
    echo "\nFinal Purchases:\n";
    $finalTotalDue = 0;
    $finalTotalPaid = 0;
    foreach ($finalPurchases as $purchase) {
        echo sprintf(
            "  [%d] %s | Total: %10.2f | Paid: %10.2f | Due: %10.2f | %s\n",
            $purchase->id,
            $purchase->reference_no,
            $purchase->final_total,
            $purchase->total_paid,
            $purchase->total_due,
            $purchase->payment_status
        );
        $finalTotalDue += $purchase->total_due;
        $finalTotalPaid += $purchase->total_paid;
    }
    echo "  " . str_repeat("-", 86) . "\n";
    echo sprintf("  TOTAL PAID: %10.2f | TOTAL DUE: %10.2f\n", $finalTotalPaid, $finalTotalDue);
    
    // Final ledger
    $finalLedgers = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('status', 'active')
        ->orderBy('id')
        ->get();
    
    echo "\nFinal Active Ledger:\n";
    echo sprintf("  %-6s %-20s %-20s %12s %12s %s\n", "ID", "Date", "Type", "Debit", "Credit", "Ref");
    echo "  " . str_repeat("-", 86) . "\n";
    
    $finalDebit = 0;
    $finalCredit = 0;
    foreach ($finalLedgers as $ledger) {
        echo sprintf(
            "  %-6d %-20s %-20s %12.2f %12.2f %s\n",
            $ledger->id,
            $ledger->transaction_date,
            $ledger->transaction_type,
            $ledger->debit,
            $ledger->credit,
            $ledger->reference_no
        );
        $finalDebit += $ledger->debit;
        $finalCredit += $ledger->credit;
    }
    $finalBalance = $finalCredit - $finalDebit;
    
    echo "  " . str_repeat("-", 86) . "\n";
    echo sprintf("  TOTALS: Debit: %10.2f | Credit: %10.2f | Balance: %10.2f\n", 
        $finalDebit, $finalCredit, $finalBalance);
    
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
        echo "✓✓✓ SUCCESS! SUPPLIER 5 IS FULLY PAID AND LEDGER IS BALANCED! ✓✓✓\n";
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
    echo "✗ ERROR OCCURRED: " . $e->getMessage() . "\n";
    echo str_repeat("=", 90) . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}
