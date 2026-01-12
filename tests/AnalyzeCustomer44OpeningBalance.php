<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Opening Balance Entries for Customer 44 ===\n\n";

$entries = DB::table('ledgers')
    ->where('contact_id', 44)
    ->where('transaction_type', 'opening_balance')
    ->orderBy('id', 'desc')
    ->get();

foreach ($entries as $e) {
    echo "ID: {$e->id} | Date: {$e->transaction_date} | Debit: {$e->debit} | Status: {$e->status}\n";
}

echo "\n=== Current Customer Record ===\n";
$customer = App\Models\Customer::withoutGlobalScopes()->find(44);
echo "Opening Balance in customers table: {$customer->opening_balance}\n";

echo "\n=== Analysis ===\n";
echo "The correct opening balance should be: 350,085 (from customers table)\n";
echo "Current active opening_balance ledger: 373,885\n";
echo "Difference: " . (373885 - 350085) . "\n";
echo "\nThe old opening_balance of 373,885 should be reversed and replaced with 350,085\n";
