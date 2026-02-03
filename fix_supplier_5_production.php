<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Supplier 5 Ledger Fix Script ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$supplierId = 5;

try {
    DB::beginTransaction();
    
    echo "Step 1: Analyzing current state...\n";
    echo str_repeat("-", 80) . "\n";
    
    // Get purchases
    $purchases = DB::table('purchases')
        ->where('supplier_id', $supplierId)
        ->orderBy('id')
        ->get();
    
    echo "\nPurchases:\n";
    foreach ($purchases as $purchase) {
        echo sprintf(
            "  ID: %d | %s | Total: %.2f | Paid: %.2f | Due: %.2f | Status: %s\n",
            $purchase->id,
            $purchase->reference_no,
            $purchase->final_total,
            $purchase->total_paid,
            $purchase->total_due,
            $purchase->payment_status
        );
    }
    
    // Get active payments
    $payments = DB::table('payments')
        ->where('supplier_id', $supplierId)
        ->where('status', 'active')
        ->orderBy('id')
        ->get();
    
    echo "\nActive Payments:\n";
    $totalPayments = 0;
    foreach ($payments as $payment) {
        echo sprintf(
            "  ID: %d | Date: %s | Amount: %.2f | Ref: %s\n",
            $payment->id,
            $payment->payment_date,
            $payment->amount,
            $payment->reference_no
        );
        $totalPayments += $payment->amount;
    }
    echo "  Total Payments: " . number_format($totalPayments, 2) . "\n";
    
    // Get active ledger entries
    $ledgers = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('status', 'active')
        ->orderBy('id')
        ->get();
    
    echo "\nActive Ledger Entries:\n";
    $totalDebit = 0;
    $totalCredit = 0;
    foreach ($ledgers as $ledger) {
        echo sprintf(
            "  ID: %d | Date: %s | Type: %s | Debit: %.2f | Credit: %.2f | Ref: %s\n",
            $ledger->id,
            $ledger->transaction_date,
            $ledger->transaction_type,
            $ledger->debit,
            $ledger->credit,
            $ledger->reference_no
        );
        $totalDebit += $ledger->debit;
        $totalCredit += $ledger->credit;
    }
    $balance = $totalCredit - $totalDebit;
    echo sprintf(
        "  Total: Debit: %.2f | Credit: %.2f | Balance: %.2f\n",
        $totalDebit,
        $totalCredit,
        $balance
    );
    
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "Step 2: Fixing ledger discrepancy for PUR021...\n";
    echo str_repeat("-", 80) . "\n";
    
    // Fix ledger entry 389 - should be 417100 not 416100
    $ledger389 = DB::table('ledgers')->where('id', 389)->first();
    if ($ledger389 && $ledger389->credit == 416100) {
        DB::table('ledgers')
            ->where('id', 389)
            ->update(['credit' => 417100]);
        echo "  ✓ Fixed ledger entry 389: 416100 → 417100\n";
    }
    
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "Step 3: Creating payment for PUR009 to fully pay...\n";
    echo str_repeat("-", 80) . "\n";
    
    // Get purchase 9 details
    $purchase9 = DB::table('purchases')->where('id', 9)->first();
    $remainingDue = $purchase9->total_due;
    
    echo sprintf("  Purchase PUR009 (ID: 9) has due amount: %.2f\n", $remainingDue);
    
    if ($remainingDue > 0) {
        // Create new payment
        $paymentDate = date('Y-m-d');
        $paymentData = [
            'payment_date' => $paymentDate,
            'amount' => $remainingDue,
            'payment_method' => 'cash',
            'reference_no' => 'PUR009',
            'notes' => 'Full payment for PUR009 - Ledger correction',
            'payment_type' => 'purchase',
            'reference_id' => 9,
            'supplier_id' => $supplierId,
            'status' => 'active',
            'payment_status' => 'completed',
            'bank_charges' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        $paymentId = DB::table('payments')->insertGetId($paymentData);
        echo sprintf("  ✓ Created payment ID: %d for amount: %.2f\n", $paymentId, $remainingDue);
        
        // Update purchase (total_due is a generated column, don't set it directly)
        DB::table('purchases')
            ->where('id', 9)
            ->update([
                'total_paid' => DB::raw('total_paid + ' . $remainingDue),
                'payment_status' => 'Paid',
                'updated_at' => now()
            ]);
        echo "  ✓ Updated purchase #9: total_paid increased, status = Paid\n";
        
        // Create ledger entry for payment
        $ledgerData = [
            'contact_id' => $supplierId,
            'transaction_date' => $paymentDate,
            'reference_no' => 'PUR009',
            'transaction_type' => 'payments',
            'debit' => $remainingDue,
            'credit' => 0,
            'status' => 'active',
            'contact_type' => 'supplier',
            'notes' => 'Full payment for PUR009 - Ledger correction',
            'created_by' => 2,
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        $ledgerId = DB::table('ledgers')->insertGetId($ledgerData);
        echo sprintf("  ✓ Created ledger entry ID: %d (debit: %.2f)\n", $ledgerId, $remainingDue);
    } else {
        echo "  ℹ Purchase PUR009 is already fully paid\n";
    }
    
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "Step 4: Verifying final state...\n";
    echo str_repeat("-", 80) . "\n";
    
    // Re-fetch and verify
    $purchases = DB::table('purchases')
        ->where('supplier_id', $supplierId)
        ->orderBy('id')
        ->get();
    
    echo "\nFinal Purchases:\n";
    $totalDue = 0;
    foreach ($purchases as $purchase) {
        echo sprintf(
            "  ID: %d | %s | Total: %.2f | Paid: %.2f | Due: %.2f | Status: %s\n",
            $purchase->id,
            $purchase->reference_no,
            $purchase->final_total,
            $purchase->total_paid,
            $purchase->total_due,
            $purchase->payment_status
        );
        $totalDue += $purchase->total_due;
    }
    echo sprintf("  TOTAL DUE: %.2f\n", $totalDue);
    
    // Final ledger balance
    $ledgers = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->where('status', 'active')
        ->orderBy('id')
        ->get();
    
    echo "\nFinal Ledger Entries:\n";
    $totalDebit = 0;
    $totalCredit = 0;
    foreach ($ledgers as $ledger) {
        echo sprintf(
            "  ID: %d | %s | %s | Dr: %.2f | Cr: %.2f | Ref: %s\n",
            $ledger->id,
            $ledger->transaction_date,
            str_pad($ledger->transaction_type, 20),
            $ledger->debit,
            $ledger->credit,
            $ledger->reference_no
        );
        $totalDebit += $ledger->debit;
        $totalCredit += $ledger->credit;
    }
    $balance = $totalCredit - $totalDebit;
    echo sprintf(
        "\n  TOTALS: Debit: %.2f | Credit: %.2f | Balance: %.2f\n",
        $totalDebit,
        $totalCredit,
        $balance
    );
    
    echo "\n" . str_repeat("=", 80) . "\n";
    
    if ($totalDue == 0 && abs($balance) < 0.01) {
        echo "✓ SUCCESS! Supplier 5 is now FULLY PAID with balanced ledger!\n";
        DB::commit();
        echo "✓ All changes committed to database\n";
    } else {
        echo "⚠ WARNING: There may still be discrepancies:\n";
        echo sprintf("  - Total Due: %.2f (should be 0)\n", $totalDue);
        echo sprintf("  - Ledger Balance: %.2f (should be 0)\n", $balance);
        echo "\nDo you want to commit? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        if ($line == 'yes' || $line == 'y') {
            DB::commit();
            echo "✓ Changes committed\n";
        } else {
            DB::rollBack();
            echo "✗ Changes rolled back\n";
        }
    }
    
    echo str_repeat("=", 80) . "\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
