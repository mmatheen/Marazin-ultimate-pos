<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIX SCRIPT FOR MLX-230 MISSING LEDGER ENTRY ===\n\n";

// Get the sale
$sale = DB::table('sales')->where('id', 636)->first();

if (!$sale) {
    die("ERROR: Sale ID 636 not found!\n");
}

echo "Sale Details:\n";
echo "Invoice: {$sale->invoice_no}\n";
echo "Customer ID: {$sale->customer_id}\n";
echo "Final Total: {$sale->final_total}\n";
echo "Status: {$sale->status}\n\n";

// Check existing ledger entries
$existingActive = DB::table('ledgers')
    ->where('reference_no', 'MLX-230')
    ->where('transaction_type', 'sale')
    ->where('status', 'active')
    ->first();

if ($existingActive) {
    echo "WARNING: Active ledger entry already exists! ID: {$existingActive->id}\n";
    echo "Debit: {$existingActive->debit}\n";
    die("No action needed - active entry exists.\n");
}

echo "CONFIRMED: No active ledger entry exists for MLX-230\n";
echo "Creating new ledger entry...\n\n";

// Create the missing ledger entry
try {
    DB::beginTransaction();

    $ledgerData = [
        'contact_id' => $sale->customer_id,
        'contact_type' => 'customer',
        'transaction_date' => $sale->sales_date,
        'reference_no' => $sale->invoice_no,
        'transaction_type' => 'sale',
        'debit' => $sale->final_total,
        'credit' => 0,
        'status' => 'active',
        'notes' => "Sale invoice #{$sale->invoice_no} [FIXED: Missing ledger entry created on " . date('Y-m-d H:i:s') . "]",
        'created_at' => now(),
        'updated_at' => now()
    ];

    $ledgerId = DB::table('ledgers')->insertGetId($ledgerData);

    echo "✅ SUCCESS: Ledger entry created!\n";
    echo "Ledger ID: {$ledgerId}\n";
    echo "Contact ID: {$sale->customer_id}\n";
    echo "Debit Amount: Rs. {$sale->final_total}\n";
    echo "Reference: {$sale->invoice_no}\n";
    echo "Status: active\n\n";

    DB::commit();

    echo "=== Verification ===\n";
    $newEntry = DB::table('ledgers')->where('id', $ledgerId)->first();
    echo "Created Entry:\n";
    echo "ID: {$newEntry->id}\n";
    echo "Debit: {$newEntry->debit}\n";
    echo "Status: {$newEntry->status}\n";
    echo "Notes: {$newEntry->notes}\n\n";

    // Calculate customer balance
    echo "=== Customer Balance Recalculation ===\n";
    $totalDebit = DB::table('ledgers')
        ->where('contact_id', $sale->customer_id)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->sum('debit');

    $totalCredit = DB::table('ledgers')
        ->where('contact_id', $sale->customer_id)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->sum('credit');

    $balance = $totalDebit - $totalCredit;

    echo "Total Debit: Rs. {$totalDebit}\n";
    echo "Total Credit: Rs. {$totalCredit}\n";
    echo "Balance: Rs. {$balance}\n";

    echo "\n✅ FIX COMPLETED SUCCESSFULLY!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
