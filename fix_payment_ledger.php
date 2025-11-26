<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "=== FIXING PAYMENT LEDGER ENTRY ===" . PHP_EOL;

// Create the missing payment ledger entry
try {
    $entry = Ledger::createEntry([
        'contact_id' => 5,
        'contact_type' => 'customer', 
        'transaction_date' => Carbon::now('Asia/Colombo'),
        'reference_no' => 'CSX-384',
        'transaction_type' => 'payments',
        'amount' => -93180, // Negative creates credit entry (payment reduces customer debt)
        'notes' => 'Payment for sale #CSX-384'
    ]);

    echo "✅ Created payment ledger entry ID: " . $entry->id . PHP_EOL;
    
    // Test the balance now
    echo PHP_EOL . "=== CHECKING BALANCE AFTER FIX ===" . PHP_EOL;
    
    $activeEntries = DB::table('ledgers')
        ->where('contact_id', 5)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->get(['id', 'reference_no', 'transaction_type', 'debit', 'credit']);
        
    $totalDebit = 0;
    $totalCredit = 0;
    
    echo "Active entries for customer 5:" . PHP_EOL;
    foreach ($activeEntries as $entry) {
        echo "  ID {$entry->id}: {$entry->reference_no} | {$entry->transaction_type} | D:{$entry->debit} | C:{$entry->credit}" . PHP_EOL;
        $totalDebit += $entry->debit;
        $totalCredit += $entry->credit;
    }
    
    $balance = $totalDebit - $totalCredit;
    echo PHP_EOL . "Total Debit: {$totalDebit}" . PHP_EOL;
    echo "Total Credit: {$totalCredit}" . PHP_EOL;
    echo "Final Balance: {$balance}" . PHP_EOL;
    
    if ($balance == 0) {
        echo "✅ BALANCE IS CORRECT!" . PHP_EOL;
    } else {
        echo "❌ Balance is still incorrect: {$balance}" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== FIX COMPLETE ===" . PHP_EOL;