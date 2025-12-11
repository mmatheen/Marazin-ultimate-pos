<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Fixing ledger entries for customer 9 (CSX-538)...\n\n";

// Step 1: Update the reversal entry (ID 426) to have status 'reversed'
echo "Step 1: Updating reversal entry ID 426 to status 'reversed'...\n";
DB::table('ledgers')
    ->where('id', 426)
    ->update(['status' => 'reversed']);
echo "✓ Updated reversal entry to status 'reversed'\n\n";

// Step 2: Check if new sale entry exists
echo "Step 2: Checking for existing new sale entry...\n";
$existingNewEntry = DB::table('ledgers')
    ->where('contact_id', 9)
    ->where('reference_no', 'CSX-538')
    ->where('transaction_type', 'sale')
    ->where('status', 'active')
    ->where('debit', 14400)
    ->first();

if ($existingNewEntry) {
    echo "✓ New sale entry already exists (ID: {$existingNewEntry->id})\n\n";
} else {
    echo "Creating new sale entry with amount 14400...\n";

    // Get the sale details
    $sale = DB::table('sales')->where('invoice_no', 'CSX-538')->first();

    if ($sale && $sale->final_total == 14400) {
        // Create the new sale entry
        $newEntryId = DB::table('ledgers')->insertGetId([
            'contact_id' => 9,
            'contact_type' => 'customer',
            'transaction_date' => now(),
            'reference_no' => 'CSX-538',
            'transaction_type' => 'sale',
            'debit' => 14400,
            'credit' => 0,
            'status' => 'active',
            'notes' => 'Sale Edit - New Amount Rs14400.00 | Decrease: Rs13000.00',
            'created_by' => 2,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        echo "✓ Created new sale entry (ID: {$newEntryId})\n\n";
    } else {
        echo "✗ Sale CSX-538 not found or final_total mismatch\n\n";
    }
}

// Step 3: Verify the fix
echo "Step 3: Verifying ledger entries...\n\n";
$ledgers = DB::table('ledgers')
    ->where('contact_id', 9)
    ->where('reference_no', 'like', 'CSX-538%')
    ->orderBy('id', 'asc')
    ->get(['id', 'reference_no', 'transaction_type', 'debit', 'credit', 'status', 'notes']);

foreach ($ledgers as $ledger) {
    $statusIcon = $ledger->status === 'active' ? '✓' : '○';
    echo "{$statusIcon} ID: {$ledger->id} | {$ledger->reference_no} | ";
    echo "Debit: {$ledger->debit} | Credit: {$ledger->credit} | ";
    echo "Status: {$ledger->status}\n";
}

echo "\n✅ Fix completed!\n";
