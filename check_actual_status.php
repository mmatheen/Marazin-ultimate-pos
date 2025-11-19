<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING ACTUAL STATUS OF ALL LEDGER ENTRIES ===\n\n";

$customerId = 2;
$entries = DB::select('
    SELECT id, transaction_date, transaction_type, debit, credit, status, reference_no, notes 
    FROM ledgers 
    WHERE contact_id = ? AND contact_type = "customer" 
    ORDER BY created_at ASC, id ASC
', [$customerId]);

echo "All entries with their ACTUAL status:\n";
echo str_repeat("-", 130) . "\n";

$runningActive = 0;
foreach ($entries as $entry) {
    if ($entry->status === 'active') {
        $runningActive += ($entry->debit - $entry->credit);
    }
    
    $statusIcon = match($entry->status) {
        'active' => '✅',
        'reversed' => '❌',
        default => '❓'
    };
    
    echo sprintf("%s ID:%2d | %-15s | D:%8.2f | C:%8.2f | Status:%-8s | Running:%8.2f | Ref: %s\n",
        $statusIcon, $entry->id, $entry->transaction_type, $entry->debit, $entry->credit, 
        $entry->status, $runningActive, $entry->reference_no);
}

echo str_repeat("-", 130) . "\n";
echo "Final running balance (active only): $runningActive\n";
echo "This should match the business balance and effective due.\n";