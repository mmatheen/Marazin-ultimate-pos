<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking MLX-230 Ledger Entries ===\n\n";

// Check sale record
echo "Sale Record (ID: 636):\n";
$sale = DB::table('sales')->where('id', 636)->first();
if ($sale) {
    echo "Invoice: {$sale->invoice_no}\n";
    echo "Customer ID: {$sale->customer_id}\n";
    echo "Final Total: {$sale->final_total}\n";
    echo "Status: {$sale->status}\n";
    echo "Created: {$sale->created_at}\n";
    echo "Updated: {$sale->updated_at}\n\n";
} else {
    echo "Sale not found!\n\n";
}

// Check ledger entries
echo "Ledger Entries for MLX-230:\n";
$ledgers = DB::table('ledgers')
    ->where('reference_no', 'LIKE', '%MLX-230%')
    ->orWhere('reference_no', 'LIKE', '%636%')
    ->orderBy('created_at', 'asc')
    ->get();

if ($ledgers->count() > 0) {
    foreach ($ledgers as $ledger) {
        echo "---\n";
        echo "ID: {$ledger->id}\n";
        echo "Contact ID: {$ledger->contact_id} ({$ledger->contact_type})\n";
        echo "Date: {$ledger->transaction_date}\n";
        echo "Reference: {$ledger->reference_no}\n";
        echo "Type: {$ledger->transaction_type}\n";
        echo "Debit: {$ledger->debit}\n";
        echo "Credit: {$ledger->credit}\n";
        echo "Status: {$ledger->status}\n";
        echo "Notes: {$ledger->notes}\n";
    }
} else {
    echo "NO LEDGER ENTRIES FOUND! This is the problem.\n";
}

echo "\n=== Customer 582 Balance Check ===\n";
$customer = DB::table('customers')->where('id', 582)->first();
if ($customer) {
    $customerName = $customer->customer_name ?? 'Unknown';
    echo "Customer: {$customerName}\n";

    // Calculate balance from ledger
    $result = DB::selectOne("
        SELECT
            COALESCE(SUM(debit), 0) as total_debits,
            COALESCE(SUM(credit), 0) as total_credits,
            COALESCE(SUM(debit) - SUM(credit), 0) as balance
        FROM ledgers
        WHERE contact_id = 582
            AND contact_type = 'customer'
            AND status = 'active'
    ");

    $ledgerBalance = $result ? (float) $result->balance : 0.0;
    echo "Ledger Balance: Rs. " . number_format($ledgerBalance, 2) . "\n";
    echo "Opening Balance (from customer): Rs. " . number_format($customer->opening_balance ?? 0, 2) . "\n";
} else {
    echo "Customer 582 not found!\n";
}

echo "\n=== All Sales for Customer 582 ===\n";
$sales = DB::table('sales')
    ->where('customer_id', 582)
    ->where('status', '!=', 'deleted')
    ->orderBy('created_at', 'asc')
    ->get(['id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'status', 'created_at', 'updated_at']);

foreach ($sales as $s) {
    echo "ID: {$s->id} | Invoice: {$s->invoice_no} | Total: {$s->final_total} | Paid: {$s->total_paid} | Due: {$s->total_due} | Status: {$s->status} | Created: {$s->created_at} | Updated: {$s->updated_at}\n";
}
