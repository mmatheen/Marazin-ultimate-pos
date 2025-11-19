<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Helpers\BalanceHelper;

echo "=== CUSTOMER ID 2 (Aasath) LEDGER ANALYSIS ===\n";

// Get all ledger entries
$entries = DB::select('
    SELECT id, transaction_date, transaction_type, debit, credit, status, reference_no, notes 
    FROM ledgers 
    WHERE contact_id = 2 AND contact_type = "customer" 
    ORDER BY id ASC
');

echo "Total entries found: " . count($entries) . "\n\n";

$totalDebits = 0;
$totalCredits = 0;
$activeDebits = 0;
$activeCredits = 0;

foreach($entries as $entry) {
    $status_indicator = $entry->status === 'active' ? '✅' : '❌';
    echo sprintf("%s ID: %d | Date: %s | Type: %s | Debit: %.2f | Credit: %.2f | Status: %s | Ref: %s\n", 
        $status_indicator,
        $entry->id, 
        $entry->transaction_date, 
        $entry->transaction_type, 
        $entry->debit, 
        $entry->credit, 
        $entry->status, 
        $entry->reference_no
    );
    
    $totalDebits += $entry->debit;
    $totalCredits += $entry->credit;
    
    if ($entry->status === 'active') {
        $activeDebits += $entry->debit;
        $activeCredits += $entry->credit;
    }
}

echo "\n=== BALANCE CALCULATION ===\n";
echo "ALL ENTRIES:\n";
echo "  Total Debits: $totalDebits\n";
echo "  Total Credits: $totalCredits\n";
echo "  Balance: " . ($totalDebits - $totalCredits) . "\n\n";

echo "ACTIVE ENTRIES ONLY:\n";
echo "  Active Debits: $activeDebits\n";
echo "  Active Credits: $activeCredits\n";
echo "  Balance: " . ($activeDebits - $activeCredits) . "\n\n";

// Use BalanceHelper
$balanceFromHelper = BalanceHelper::getCustomerBalance(2);
echo "BalanceHelper result: $balanceFromHelper\n";

// Debug using BalanceHelper
echo "\n=== USING BALANCE HELPER DEBUG ===\n";
BalanceHelper::debugCustomerBalance(2);