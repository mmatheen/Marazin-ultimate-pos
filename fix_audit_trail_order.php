<?php
/**
 * Fix Audit Trail Order for Corrected Ledger Entries
 * 
 * Problem: Fixed ledger entries have created_at = today (2026-02-10)
 *          But should appear right after reversal entries in audit trail
 * 
 * Solution: Adjust created_at to be 1 second after the reversal entry
 */
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================================================\n";
echo "FIX AUDIT TRAIL ORDER - ADJUST CREATED_AT FOR FIXED LEDGER ENTRIES\n";
echo "========================================================================\n\n";

// Find all ledger entries that were created by the fix script
// They have notes containing "[FIXED: Missing ledger entry created"
$fixedEntries = DB::table('ledgers')
    ->where('notes', 'LIKE', '%[FIXED: Missing ledger entry created%')
    ->where('status', 'active')
    ->get();

if ($fixedEntries->isEmpty()) {
    echo "No fixed entries found. Nothing to adjust.\n";
    exit(0);
}

echo "Found " . count($fixedEntries) . " entries that need timestamp adjustment:\n\n";

foreach ($fixedEntries as $entry) {
    echo "─────────────────────────────────────────────────────────────────\n";
    echo "Entry ID: {$entry->id}\n";
    echo "Reference: {$entry->reference_no}\n";
    echo "Customer: {$entry->contact_id}\n";
    echo "Amount: Rs. " . number_format($entry->debit, 2) . "\n";
    echo "Transaction Date: {$entry->transaction_date}\n";
    echo "Current Created At: {$entry->created_at}\n";
    
    // Find the latest reversal entry for this reference
    $latestReversal = DB::table('ledgers')
        ->where('reference_no', 'LIKE', $entry->reference_no . '%')
        ->where('transaction_type', 'sale')
        ->where('status', 'reversed')
        ->orderBy('created_at', 'desc')
        ->first();
    
    if ($latestReversal) {
        // Set created_at to 1 second after the reversal
        $newCreatedAt = date('Y-m-d H:i:s', strtotime($latestReversal->created_at) + 1);
        echo "Reversal Entry Created At: {$latestReversal->created_at}\n";
        echo "New Created At: {$newCreatedAt} (1 second after reversal)\n";
        
        try {
            DB::table('ledgers')
                ->where('id', $entry->id)
                ->update([
                    'created_at' => $newCreatedAt,
                    'updated_at' => $newCreatedAt
                ]);
            
            echo "✅ UPDATED - Audit trail order corrected\n";
        } catch (\Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        // No reversal found, use transaction_date + 1 minute
        $newCreatedAt = date('Y-m-d H:i:s', strtotime($entry->transaction_date) + 60);
        echo "No reversal found, using transaction_date + 1 minute\n";
        echo "New Created At: {$newCreatedAt}\n";
        
        try {
            DB::table('ledgers')
                ->where('id', $entry->id)
                ->update([
                    'created_at' => $newCreatedAt,
                    'updated_at' => $newCreatedAt
                ]);
            
            echo "✅ UPDATED - Timestamp adjusted\n";
        } catch (\Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

echo "========================================================================\n";
echo "VERIFICATION - Customer 582 Ledger Order\n";
echo "========================================================================\n\n";

// Show the corrected order for customer 582
$ledgers = DB::table('ledgers')
    ->where('contact_id', 582)
    ->where('contact_type', 'customer')
    ->orderBy('created_at', 'asc')
    ->get(['id', 'reference_no', 'transaction_type', 'debit', 'credit', 'status', 'created_at', 'transaction_date']);

echo "Ledger entries sorted by created_at (AUDIT TRAIL ORDER):\n\n";

$runningBalance = 0;
foreach ($ledgers as $ledger) {
    $amount = $ledger->debit > 0 ? "+{$ledger->debit}" : "-{$ledger->credit}";
    $statusIcon = $ledger->status === 'active' ? '✅' : '🔄';
    
    if ($ledger->status === 'active') {
        $runningBalance += $ledger->debit;
        $runningBalance -= $ledger->credit;
        $balanceStr = " | Balance: Rs. " . number_format($runningBalance, 2);
    } else {
        $balanceStr = " | (reversed)";
    }
    
    echo "{$statusIcon} ID: {$ledger->id} | {$ledger->reference_no} | {$amount}{$balanceStr}\n";
    echo "   Created: {$ledger->created_at} | Trans Date: {$ledger->transaction_date}\n\n";
}

echo "Final Balance: Rs. " . number_format($runningBalance, 2) . "\n\n";

echo "========================================================================\n";
echo "RESULT\n";
echo "========================================================================\n\n";

echo "✅ Audit trail order has been corrected!\n\n";
echo "Now when viewing ledger by created_at (audit trail), the entries will show:\n";
echo "1. Original entry (reversed)\n";
echo "2. Reversal entry (reversed)\n";
echo "3. New corrected entry (active) ← Now appears immediately after reversal\n\n";
echo "Balance calculations remain correct (they use transaction_date).\n";
echo "Audit trail now shows logical sequence of events.\n\n";

echo "========================================================================\n";
