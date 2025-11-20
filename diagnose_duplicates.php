<?php
/**
 * ===================================================================
 * 🩺 IMMEDIATE DUPLICATE DIAGNOSIS
 * ===================================================================
 * 
 * Quick diagnosis of the specific duplicates found in your system
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🩺 IMMEDIATE DUPLICATE DIAGNOSIS\n";
echo "===============================\n\n";

// 1. Check the specific duplicates found
echo "1. Analyzing specific duplicates from your output...\n\n";

$specificDuplicates = [
    'PUR007' => ['contact_id' => 4, 'type' => 'purchase'],
    'CSX-190' => ['contact_id' => 7, 'type' => 'payments'],
    'CSX-190' => ['contact_id' => 7, 'type' => 'sale'],
    'PUR007' => ['contact_id' => 4, 'type' => 'payments'],
    'PUR006' => ['contact_id' => 4, 'type' => 'purchase'],
    'CSX-295' => ['contact_id' => 43, 'type' => 'sale'],
    'CSX-109' => ['contact_id' => 2, 'type' => 'sale'],
];

foreach ($specificDuplicates as $ref => $info) {
    echo "🔍 Checking: {$ref} (Contact: {$info['contact_id']}, Type: {$info['type']})\n";
    
    $entries = DB::select("
        SELECT 
            id, 
            reference_no, 
            contact_id, 
            contact_type, 
            transaction_type,
            debit,
            credit,
            status,
            created_at,
            SUBSTRING(notes, 1, 50) as short_notes
        FROM ledgers 
        WHERE reference_no = ? 
            AND contact_id = ? 
            AND transaction_type = ?
        ORDER BY created_at ASC
    ", [$ref, $info['contact_id'], $info['type']]);
    
    if (count($entries) > 1) {
        echo "   ❌ FOUND " . count($entries) . " DUPLICATES:\n";
        foreach ($entries as $i => $entry) {
            $status = $entry->status === 'active' ? '✅' : '❌';
            $entryNum = $i + 1;
            echo "      {$status} #{$entryNum} ID: {$entry->id} | D:{$entry->debit} C:{$entry->credit} | {$entry->created_at}\n";
            echo "         Status: {$entry->status} | Notes: {$entry->short_notes}\n";
        }
        echo "\n";
        
        // Suggest fix
        $activeEntries = array_filter($entries, fn($e) => $e->status === 'active');
        if (count($activeEntries) > 1) {
            echo "   💡 SUGGESTED FIX: Keep the latest entry and mark others as reversed\n";
            echo "      Keep: ID " . end($activeEntries)->id . " (latest)\n";
            echo "      Mark as reversed: ";
            $toReverse = array_slice($activeEntries, 0, -1);
            echo implode(', ', array_map(fn($e) => "ID {$e->id}", $toReverse)) . "\n";
        }
        echo "\n";
    } else {
        echo "   ✅ Only " . count($entries) . " entry found\n\n";
    }
}

echo "2. Checking contact existence...\n\n";

// Check if the contacts exist
$contactsToCheck = [2, 4, 7, 43];
foreach ($contactsToCheck as $contactId) {
    $customer = DB::table('customers')->where('id', $contactId)->first();
    $supplier = DB::table('suppliers')->where('id', $contactId)->first();
    
    echo "Contact ID {$contactId}: ";
    if ($customer) {
        echo "✅ Customer - {$customer->first_name} {$customer->last_name}";
        if (isset($customer->deleted_at) && $customer->deleted_at) {
            echo " (SOFT DELETED)";
        }
    } elseif ($supplier) {
        echo "✅ Supplier - {$supplier->name}";
        if (isset($supplier->deleted_at) && $supplier->deleted_at) {
            echo " (SOFT DELETED)";
        }
    } else {
        echo "❌ NOT FOUND";
    }
    echo "\n";
}

echo "\n3. Quick fix suggestions...\n\n";

echo "💡 IMMEDIATE ACTIONS:\n";
echo "1. For PUR007 (5 duplicates): Keep latest purchase entry, reverse others\n";
echo "2. For CSX-190 (6 total duplicates): Keep latest sale and payment entries\n";
echo "3. For other references: Apply same logic - keep latest, reverse others\n\n";

echo "🔧 SAFE FIX COMMANDS:\n";
echo "php fix_duplicate_ledger.php --check    # See what will be fixed\n";
echo "php fix_duplicate_ledger.php --fix      # Dry run (safe)\n";
echo "php fix_duplicate_ledger.php --fix --confirm  # Actually fix\n\n";

// Create a quick fix script for the specific duplicates
echo "4. Creating targeted fix for your specific duplicates...\n\n";

$targetedFixes = [];
foreach ($specificDuplicates as $ref => $info) {
    $entries = DB::select("
        SELECT id, created_at, status
        FROM ledgers 
        WHERE reference_no = ? 
            AND contact_id = ? 
            AND transaction_type = ?
            AND status = 'active'
        ORDER BY created_at ASC
    ", [$ref, $info['contact_id'], $info['type']]);
    
    if (count($entries) > 1) {
        $keepId = end($entries)->id; // Keep the latest
        $reverseIds = array_slice(array_map(fn($e) => $e->id, $entries), 0, -1);
        
        $targetedFixes[] = [
            'reference' => $ref,
            'contact_id' => $info['contact_id'],
            'type' => $info['type'],
            'keep' => $keepId,
            'reverse' => $reverseIds
        ];
    }
}

if (!empty($targetedFixes)) {
    echo "📋 TARGETED FIX PLAN:\n";
    foreach ($targetedFixes as $fix) {
        echo "   {$fix['reference']}: Keep ID {$fix['keep']}, Reverse IDs: " . implode(', ', $fix['reverse']) . "\n";
    }
    echo "\n";
    
    // Generate SQL for manual fix if needed
    echo "🛠️  MANUAL FIX SQL (if needed):\n";
    echo "-- Mark duplicates as reversed\n";
    foreach ($targetedFixes as $fix) {
        foreach ($fix['reverse'] as $reverseId) {
            echo "UPDATE ledgers SET status='reversed', notes=CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]') WHERE id={$reverseId};\n";
        }
    }
    echo "\n";
}

echo "✅ Diagnosis completed!\n";
echo "Next step: Run the fix script or apply manual SQL if you prefer.\n";