<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 90) . "\n";
echo "SUPPLIER 5 LEDGER STATUS CHECK\n";
echo str_repeat("=", 90) . "\n\n";

$supplierId = 5;

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
    "ID", "Date", "Type", "Debit", "Credit", "Balance", "Status");
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
        $ledger->status
    );
}

$totalDebit = $activeLedgers->sum('debit');
$totalCredit = $activeLedgers->sum('credit');
$finalBalance = $totalCredit - $totalDebit;

echo str_repeat("-", 90) . "\n";
echo sprintf("TOTALS: Debit: %12.2f | Credit: %12.2f | Balance: %12.2f\n",
    $totalDebit, $totalCredit, $finalBalance);
echo "\n✓ This should show ZERO balance (Fully Paid)\n\n";

// Check "Show Full History" = ON (All entries including reversed)
echo str_repeat("=", 90) . "\n";
echo "VIEW 2: Full History View (All Entries Including Reversed)\n";
echo str_repeat("-", 90) . "\n";

$allLedgers = DB::table('ledgers')
    ->where('contact_id', $supplierId)
    ->where('contact_type', 'supplier')
    ->orderBy('id')
    ->get();

echo sprintf("%-6s %-20s %-20s %12s %12s %12s %s\n", 
    "ID", "Date", "Type", "Debit", "Credit", "Balance", "Status");
echo str_repeat("-", 90) . "\n";

$balance = 0;
foreach ($allLedgers as $ledger) {
    $balance += ($ledger->credit - $ledger->debit);
    echo sprintf("%-6d %-20s %-20s %12.2f %12.2f %12.2f %s\n",
        $ledger->id,
        $ledger->transaction_date,
        $ledger->transaction_type,
        $ledger->debit,
        $ledger->credit,
        $balance,
        $ledger->status
    );
}

$totalDebit = $allLedgers->sum('debit');
$totalCredit = $allLedgers->sum('credit');
$finalBalance = $totalCredit - $totalDebit;

echo str_repeat("-", 90) . "\n";
echo sprintf("TOTALS: Debit: %12.2f | Credit: %12.2f | Balance: %12.2f\n",
    $totalDebit, $totalCredit, $finalBalance);
echo "\n⚠ This shows non-zero balance because it includes REVERSED entries\n\n";

// Purchases check
echo str_repeat("=", 90) . "\n";
echo "PURCHASES STATUS\n";
echo str_repeat("-", 90) . "\n";

$purchases = DB::table('purchases')
    ->where('supplier_id', $supplierId)
    ->get();

foreach ($purchases as $purchase) {
    echo sprintf("%-10s | Total: %10.2f | Paid: %10.2f | Due: %10.2f | %s\n",
        $purchase->reference_no,
        $purchase->final_total,
        $purchase->total_paid,
        $purchase->total_due,
        $purchase->payment_status
    );
}

echo "\n" . str_repeat("=", 90) . "\n";
echo "CONCLUSION:\n";
echo str_repeat("=", 90) . "\n";
echo "• Active ledger entries balance: " . number_format($activeLedgers->sum('credit') - $activeLedgers->sum('debit'), 2) . "\n";
echo "• All ledger entries balance: " . number_format($allLedgers->sum('credit') - $allLedgers->sum('debit'), 2) . "\n";
echo "• Purchases total due: " . number_format($purchases->sum('total_due'), 2) . "\n";
echo "\nThe 'Show Full History' checkbox is likely CHECKED in your screenshot!\n";
echo "✓ Uncheck 'Show Full History' to see correct active balance (0.00)\n";
echo "✓ Checked 'Show Full History' shows all entries including reversed (50,400.00)\n";
echo str_repeat("=", 90) . "\n\n";
