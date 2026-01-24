<?php

/**
 * Quick Fix for CSX-1758 Missing Sale Ledger Entry
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== Fix CSX-1758 Missing Sale Ledger Entry ===\n\n";

try {
    $invoiceNo = 'CSX-1758';

    // Get the sale
    $sale = DB::table('sales')->where('invoice_no', $invoiceNo)->first();

    if (!$sale) {
        echo "❌ Sale not found: $invoiceNo\n";
        exit(1);
    }

    echo "Sale found:\n";
    echo "  ID: {$sale->id}\n";
    echo "  Invoice: {$sale->invoice_no}\n";
    echo "  Customer ID: {$sale->customer_id}\n";
    echo "  Status: {$sale->status}\n";
    echo "  Final Total: Rs. " . number_format($sale->final_total, 2) . "\n";
    echo "  Created: {$sale->created_at}\n";
    echo "  Updated: {$sale->updated_at}\n\n";

    // Check existing ledger entries
    $ledgers = DB::table('ledgers')
        ->where('reference_no', $invoiceNo)
        ->get();

    echo "Existing ledger entries:\n";
    foreach ($ledgers as $ledger) {
        echo "  - ID {$ledger->id}: Type={$ledger->transaction_type}, Debit={$ledger->debit}, Credit={$ledger->credit}, Status={$ledger->status}\n";
    }

    // Check if sale entry exists
    $saleEntry = DB::table('ledgers')
        ->where('reference_no', $invoiceNo)
        ->where('transaction_type', 'sale')
        ->where('status', 'active')
        ->first();

    if ($saleEntry) {
        echo "\n✅ Sale ledger entry already exists (ID: {$saleEntry->id})\n";
        exit(0);
    }

    echo "\n⚠️  Sale ledger entry is MISSING!\n";
    echo "\nCreating missing sale ledger entry...\n";

    $insertedId = DB::table('ledgers')->insertGetId([
        'contact_id' => $sale->customer_id,
        'contact_type' => 'customer',
        'transaction_date' => $sale->created_at,
        'reference_no' => $sale->invoice_no,
        'transaction_type' => 'sale',
        'debit' => $sale->final_total,
        'credit' => 0,
        'status' => 'active',
        'notes' => "Sale invoice #{$sale->invoice_no} [RECOVERED: Missing ledger entry created on " . Carbon::now()->format('Y-m-d H:i:s') . "]",
        'created_by' => $sale->user_id ?? 1,
        'created_at' => $sale->created_at,
        'updated_at' => Carbon::now()
    ]);

    echo "✅ Successfully created sale ledger entry (ID: $insertedId)\n";
    echo "   Debit: Rs. " . number_format($sale->final_total, 2) . "\n";

    // Verify the fix
    echo "\nVerifying ledger balance for customer {$sale->customer_id}...\n";

    $ledgerBalance = DB::table('ledgers')
        ->where('contact_id', $sale->customer_id)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
        ->first();

    $balance = $ledgerBalance->total_debit - $ledgerBalance->total_credit;

    echo "  Total Debit: Rs. " . number_format($ledgerBalance->total_debit, 2) . "\n";
    echo "  Total Credit: Rs. " . number_format($ledgerBalance->total_credit, 2) . "\n";
    echo "  Balance: Rs. " . number_format($balance, 2) . "\n";
    echo "\n✅ Fix completed successfully!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
