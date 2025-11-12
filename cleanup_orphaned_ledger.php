<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\UnifiedLedgerService;

echo "=== Analyzing Orphaned Ledger Entries ===\n\n";

// Step 1: Find all orphaned/mismatched entries
$orphanedEntries = DB::select('
    SELECT l.*, s.customer_id as sale_customer_id, s.id as sale_id 
    FROM ledgers l 
    LEFT JOIN sales s ON l.reference_no = s.invoice_no 
    WHERE l.contact_type = "customer" 
    AND l.transaction_type != "payments" 
    AND (s.id IS NULL OR s.customer_id != l.user_id)
    ORDER BY l.user_id, l.created_at
');

echo "Found " . count($orphanedEntries) . " orphaned/mismatched entries:\n\n";

$affectedCustomers = [];

foreach($orphanedEntries as $entry) {
    echo "ID: {$entry->id} | Customer: {$entry->user_id} | Ref: {$entry->reference_no} | Type: {$entry->transaction_type} | Debit: {$entry->debit} | Credit: {$entry->credit} | Sale Customer: " . ($entry->sale_customer_id ?? 'NULL') . "\n";
    
    if (!in_array($entry->user_id, $affectedCustomers)) {
        $affectedCustomers[] = $entry->user_id;
    }
}

echo "\n=== Affected Customers ===\n";
foreach($affectedCustomers as $customerId) {
    $customer = Customer::find($customerId);
    if ($customer) {
        $currentBalance = Ledger::getLatestBalance($customerId, 'customer');
        echo "Customer {$customerId}: {$customer->first_name} {$customer->last_name} | Current Balance: {$currentBalance}\n";
    } else {
        echo "Customer {$customerId}: NOT FOUND in customers table\n";
    }
}

echo "\n=== Specific Analysis for Customer ID 3 ===\n";
$customer3Entries = DB::select('
    SELECT * FROM ledgers 
    WHERE user_id = 3 AND contact_type = "customer" 
    ORDER BY created_at
');

foreach($customer3Entries as $entry) {
    echo "ID: {$entry->id} | Date: {$entry->transaction_date} | Ref: {$entry->reference_no} | Type: {$entry->transaction_type} | Debit: {$entry->debit} | Credit: {$entry->credit} | Balance: {$entry->balance}\n";
}

echo "\n=== Specific Analysis for Customer ID 871 ===\n";
$customer871Entries = DB::select('
    SELECT * FROM ledgers 
    WHERE user_id = 871 AND contact_type = "customer" 
    ORDER BY created_at
');

foreach($customer871Entries as $entry) {
    echo "ID: {$entry->id} | Date: {$entry->transaction_date} | Ref: {$entry->reference_no} | Type: {$entry->transaction_type} | Debit: {$entry->debit} | Credit: {$entry->credit} | Balance: {$entry->balance}\n";
}

echo "\n=== Check if sales exist for these references ===\n";
$checkSales = [
    'MLX-050' => 871,
    'ATF-017' => 3,
    'ATF-020' => 3,
    'ATF-027' => 3
];

foreach($checkSales as $invoiceNo => $expectedCustomer) {
    $sale = Sale::where('invoice_no', $invoiceNo)->first();
    if ($sale) {
        echo "Sale {$invoiceNo}: EXISTS | Customer: {$sale->customer_id} | Expected: {$expectedCustomer} | Match: " . ($sale->customer_id == $expectedCustomer ? 'YES' : 'NO') . "\n";
    } else {
        echo "Sale {$invoiceNo}: NOT FOUND\n";
    }
}

echo "\nAnalysis complete.\n";