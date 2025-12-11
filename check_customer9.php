<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking ledger entries for customer 9...\n\n";

// Get all ledger entries for customer 9
$ledgers = DB::table('ledgers')
    ->where('contact_id', 9)
    ->where('contact_type', 'customer')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'transaction_date', 'reference_no', 'transaction_type', 'debit', 'credit', 'status', 'notes']);

foreach ($ledgers as $ledger) {
    echo "ID: {$ledger->id}\n";
    echo "Reference: {$ledger->reference_no}\n";
    echo "Type: {$ledger->transaction_type}\n";
    echo "Debit: {$ledger->debit}\n";
    echo "Credit: {$ledger->credit}\n";
    echo "Status: {$ledger->status}\n";
    echo "Notes: {$ledger->notes}\n";
    echo "Date: {$ledger->transaction_date}\n";
    echo str_repeat("-", 50) . "\n";
}

echo "\n\nChecking sale CSX-538...\n\n";

$sale = DB::table('sales')
    ->where('invoice_no', 'CSX-538')
    ->first(['id', 'customer_id', 'invoice_no', 'final_total', 'status']);

if ($sale) {
    echo "Sale ID: {$sale->id}\n";
    echo "Customer ID: {$sale->customer_id}\n";
    echo "Invoice No: {$sale->invoice_no}\n";
    echo "Final Total: {$sale->final_total}\n";
    echo "Status: {$sale->status}\n";
} else {
    echo "Sale CSX-538 not found!\n";
}
