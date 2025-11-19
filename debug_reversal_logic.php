<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DEBUGGING REVERSAL DETECTION LOGIC ===\n\n";

$customerId = 2;

// Get all entries for this customer
$entries = DB::select('
    SELECT id, transaction_date, transaction_type, debit, credit, status, reference_no, notes 
    FROM ledgers 
    WHERE contact_id = ? AND contact_type = "customer" 
    ORDER BY id ASC
', [$customerId]);

echo "Analyzing each entry to see why running balance is 0:\n\n";

$runningBalance = 0;
foreach ($entries as $entry) {
    echo "ID: {$entry->id} | Ref: {$entry->reference_no} | Status: {$entry->status}\n";
    echo "  Debit: {$entry->debit} | Credit: {$entry->credit}\n";
    
    // Check each condition individually
    $reasons = [];
    
    if ($entry->status !== 'active') {
        $reasons[] = "Status is '{$entry->status}' (not 'active')";
    }
    
    if (strpos(strtolower($entry->reference_no ?? ''), 'rev') !== false) {
        $reasons[] = "Reference contains 'rev'";
    }
    
    if (strpos(strtolower($entry->reference_no ?? ''), 'edit') !== false) {
        $reasons[] = "Reference contains 'edit'";
    }
    
    if (strpos(strtolower($entry->reference_no ?? ''), 'reversal') !== false) {
        $reasons[] = "Reference contains 'reversal'";
    }
    
    if (strpos(strtolower($entry->notes ?? ''), 'reversal') !== false) {
        $reasons[] = "Notes contain 'reversal'";
    }
    
    if (strpos(strtolower($entry->notes ?? ''), '[reversed') !== false) {
        $reasons[] = "Notes contain '[reversed'";
    }
    
    if (strpos(strtolower($entry->notes ?? ''), 'edit') !== false) {
        $reasons[] = "Notes contain 'edit'";
    }
    
    if (strpos(strtolower($entry->notes ?? ''), 'correction') !== false) {
        $reasons[] = "Notes contain 'correction'";
    }
    
    if (strpos(strtolower($entry->notes ?? ''), 'removed') !== false) {
        $reasons[] = "Notes contain 'removed'";
    }
    
    if (empty($reasons)) {
        echo "  ✅ INCLUDED in running balance\n";
        $runningBalance += ($entry->debit - $entry->credit);
        echo "  Running balance: {$runningBalance}\n";
    } else {
        echo "  ❌ EXCLUDED from running balance:\n";
        foreach ($reasons as $reason) {
            echo "    - {$reason}\n";
        }
    }
    
    echo "  Notes: " . ($entry->notes ?? 'NULL') . "\n";
    echo str_repeat("-", 80) . "\n";
}

echo "\nFinal running balance: {$runningBalance}\n";
echo "Expected: 7000 (only opening balance)\n\n";

echo "ANALYSIS:\n";
echo "- If running balance = 7000, logic is correct\n";
echo "- If running balance ≠ 7000, we need to adjust detection patterns\n";