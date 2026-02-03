<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 90) . "\n";
echo "SUPPLIER 9 LEDGER STATUS CHECK\n";
echo str_repeat("=", 90) . "\n\n";

$supplierId = 9;

// Check if supplier exists
$supplier = DB::table('suppliers')->where('id', $supplierId)->first();
if (!$supplier) {
    echo "⚠ Supplier 9 not found!\n\n";
    exit;
}

echo "Supplier: {$supplier->first_name} {$supplier->last_name}\n";
echo "Mobile: {$supplier->mobile_no}\n";
echo "Opening Balance: " . number_format($supplier->opening_balance ?? 0, 2) . "\n\n";

// Check "Show Full History" = OFF (Active only)
echo "VIEW 1: Normal View (Active Entries Only)\n";
echo str_repeat("-", 90) . "\n";

$activeLedgers = DB::table('ledgers')
    ->where('contact_id', $supplierId)
    ->where('contact_type', 'supplier')
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

echo sprintf("%-6s %-20s %-20s %12s %12s %12s %s\n", 
    "ID", "Date", "Type", "Debit", "Credit", "Balance", "Ref");
echo str_repeat("-", 90) . "\n";

$balance = 0;
foreach ($activeLedgers as $ledger) {
    $balance += ($ledger->credit - $ledger->debit);
    echo sprintf("%-6d %-20s %-20s %12.2f %12.2f %12.2f %s\n",
        $ledger->id,
        $ledger->transaction_date,
        $ledger->transaction_type,
        $ledger->debit,
        $ledger->credit,
        $balance,
        $ledger->reference_no
    );
}

$totalDebit = $activeLedgers->sum('debit');
$totalCredit = $activeLedgers->sum('credit');
$finalBalance = $totalCredit - $totalDebit;

echo str_repeat("-", 90) . "\n";
echo sprintf("TOTALS: Debit: %12.2f | Credit: %12.2f | Balance: %12.2f\n",
    $totalDebit, $totalCredit, $finalBalance);

if (abs($finalBalance) < 0.01) {
    echo "\n✓ Ledger is BALANCED (Fully Paid)\n\n";
} else if ($finalBalance > 0) {
    echo "\n⚠ Balance DUE: " . number_format($finalBalance, 2) . "\n\n";
} else {
    echo "\n✓ ADVANCE/CREDIT: " . number_format(abs($finalBalance), 2) . "\n\n";
}

// Check "Show Full History" = ON (All entries including reversed)
echo str_repeat("=", 90) . "\n";
echo "VIEW 2: Full History View (All Entries Including Reversed)\n";
echo str_repeat("-", 90) . "\n";

$allLedgers = DB::table('ledgers')
    ->where('contact_id', $supplierId)
    ->where('contact_type', 'supplier')
    ->orderBy('id')
    ->get();

echo sprintf("%-6s %-20s %-20s %12s %12s %12s %-10s %s\n", 
    "ID", "Date", "Type", "Debit", "Credit", "Balance", "Status", "Ref");
echo str_repeat("-", 90) . "\n";

$balance = 0;
foreach ($allLedgers as $ledger) {
    $balance += ($ledger->credit - $ledger->debit);
    echo sprintf("%-6d %-20s %-20s %12.2f %12.2f %12.2f %-10s %s\n",
        $ledger->id,
        $ledger->transaction_date,
        $ledger->transaction_type,
        $ledger->debit,
        $ledger->credit,
        $balance,
        $ledger->status,
        $ledger->reference_no
    );
}

$totalDebit = $allLedgers->sum('debit');
$totalCredit = $allLedgers->sum('credit');
$finalBalance = $totalCredit - $totalDebit;

echo str_repeat("-", 90) . "\n";
echo sprintf("TOTALS: Debit: %12.2f | Credit: %12.2f | Balance: %12.2f\n",
    $totalDebit, $totalCredit, $finalBalance);
echo "\n";

// Purchases check
echo str_repeat("=", 90) . "\n";
echo "PURCHASES STATUS\n";
echo str_repeat("-", 90) . "\n";

$purchases = DB::table('purchases')
    ->where('supplier_id', $supplierId)
    ->orderBy('id')
    ->get();

if ($purchases->isEmpty()) {
    echo "No purchases found for supplier 9\n";
} else {
    echo sprintf("%-6s %-12s %12s %12s %12s %s\n",
        "ID", "Ref", "Total", "Paid", "Due", "Status");
    echo str_repeat("-", 90) . "\n";
    
    foreach ($purchases as $purchase) {
        echo sprintf("%-6d %-12s %12.2f %12.2f %12.2f %s\n",
            $purchase->id,
            $purchase->reference_no,
            $purchase->final_total,
            $purchase->total_paid,
            $purchase->total_due,
            $purchase->payment_status
        );
    }
    
    echo str_repeat("-", 90) . "\n";
    echo sprintf("TOTALS: %12s %12.2f %12.2f %12.2f\n",
        "",
        $purchases->sum('final_total'),
        $purchases->sum('total_paid'),
        $purchases->sum('total_due')
    );
}

// Active payments
echo "\n" . str_repeat("=", 90) . "\n";
echo "ACTIVE PAYMENTS\n";
echo str_repeat("-", 90) . "\n";

$activePayments = DB::table('payments')
    ->where('supplier_id', $supplierId)
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

if ($activePayments->isEmpty()) {
    echo "No active payments found for supplier 9\n";
} else {
    echo sprintf("%-6s %-12s %-12s %12s %s\n",
        "ID", "Date", "Ref", "Amount", "Method");
    echo str_repeat("-", 90) . "\n";
    
    foreach ($activePayments as $payment) {
        echo sprintf("%-6d %-12s %-12s %12.2f %s\n",
            $payment->id,
            $payment->payment_date,
            $payment->reference_no,
            $payment->amount,
            $payment->payment_method
        );
    }
    
    echo str_repeat("-", 90) . "\n";
    echo sprintf("TOTAL PAYMENTS: %12.2f\n", $activePayments->sum('amount'));
}

echo "\n" . str_repeat("=", 90) . "\n";
echo "CONCLUSION:\n";
echo str_repeat("=", 90) . "\n";
echo "• Active ledger balance: " . number_format($activeLedgers->sum('credit') - $activeLedgers->sum('debit'), 2) . "\n";
echo "• All ledger balance: " . number_format($allLedgers->sum('credit') - $allLedgers->sum('debit'), 2) . "\n";
echo "• Purchases total due: " . number_format($purchases->sum('total_due'), 2) . "\n";
echo "• Active payments total: " . number_format($activePayments->sum('amount'), 2) . "\n";

// Check if needs fixing
$activeBalance = $activeLedgers->sum('credit') - $activeLedgers->sum('debit');
$purchasesDue = $purchases->sum('total_due');

if (abs($activeBalance) < 0.01 && abs($purchasesDue) < 0.01) {
    echo "\n✓✓✓ SUPPLIER 9 IS FULLY PAID - NO ISSUES! ✓✓✓\n";
} else if (abs($activeBalance - $purchasesDue) < 0.01) {
    echo "\n⚠ Supplier 9 has outstanding balance of " . number_format($activeBalance, 2) . "\n";
} else {
    echo "\n⚠⚠⚠ MISMATCH DETECTED! ⚠⚠⚠\n";
    echo "  Ledger shows: " . number_format($activeBalance, 2) . "\n";
    echo "  Purchases show: " . number_format($purchasesDue, 2) . "\n";
    echo "  Difference: " . number_format($activeBalance - $purchasesDue, 2) . "\n";
}

echo str_repeat("=", 90) . "\n\n";
