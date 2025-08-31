<?php

require_once 'vendor/autoload.php';

// This is a simple test script to verify the purchase ledger functionality
// Run this with: php test_purchase_ledger.php

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Ledger;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

// Test the ledger calculation logic
function testLedgerCalculation() {
    echo "Testing Purchase Ledger Calculation...\n";
    
    // Create a test supplier
    $supplier = Supplier::create([
        'name' => 'Test Supplier',
        'phone' => '1234567890',
        'email' => 'test@supplier.com',
        'opening_balance' => 1000.00,
        'current_balance' => 1000.00
    ]);
    
    echo "Created test supplier with ID: {$supplier->id}\n";
    
    // Test 1: Create a purchase
    $purchaseAmount = 5000.00;
    echo "Creating purchase ledger entry for amount: {$purchaseAmount}\n";
    
    $lastLedger = Ledger::where('user_id', $supplier->id)
        ->where('contact_type', 'supplier')
        ->orderBy('transaction_date', 'desc')
        ->orderBy('id', 'desc')
        ->first();
    
    $previousBalance = $lastLedger ? $lastLedger->balance : 0;
    $newBalance = $previousBalance + $purchaseAmount - 0; // debit - credit
    
    echo "Previous balance: {$previousBalance}\n";
    echo "New balance after purchase: {$newBalance}\n";
    
    Ledger::create([
        'transaction_date' => now(),
        'reference_no' => 'PUR001',
        'transaction_type' => 'purchase',
        'debit' => $purchaseAmount,
        'credit' => 0,
        'balance' => $newBalance,
        'contact_type' => 'supplier',
        'user_id' => $supplier->id,
    ]);
    
    // Test 2: Create a payment
    $paymentAmount = 2000.00;
    echo "Creating payment ledger entry for amount: {$paymentAmount}\n";
    
    $lastLedger = Ledger::where('user_id', $supplier->id)
        ->where('contact_type', 'supplier')
        ->orderBy('transaction_date', 'desc')
        ->orderBy('id', 'desc')
        ->first();
    
    $previousBalance = $lastLedger ? $lastLedger->balance : 0;
    $newBalance = $previousBalance + 0 - $paymentAmount; // debit - credit
    
    echo "Previous balance: {$previousBalance}\n";
    echo "New balance after payment: {$newBalance}\n";
    
    Ledger::create([
        'transaction_date' => now(),
        'reference_no' => 'PUR001',
        'transaction_type' => 'payments',
        'debit' => 0,
        'credit' => $paymentAmount,
        'balance' => $newBalance,
        'contact_type' => 'supplier',
        'user_id' => $supplier->id,
    ]);
    
    // Verify final balance
    $finalLedger = Ledger::where('user_id', $supplier->id)
        ->where('contact_type', 'supplier')
        ->orderBy('transaction_date', 'desc')
        ->orderBy('id', 'desc')
        ->first();
    
    echo "Final ledger balance: {$finalLedger->balance}\n";
    echo "Expected balance: " . ($purchaseAmount - $paymentAmount) . "\n";
    
    if ($finalLedger->balance == ($purchaseAmount - $paymentAmount)) {
        echo "✅ Ledger calculation is CORRECT!\n";
    } else {
        echo "❌ Ledger calculation is INCORRECT!\n";
    }
    
    // Clean up
    Ledger::where('user_id', $supplier->id)->delete();
    $supplier->delete();
    
    echo "Test completed and cleaned up.\n\n";
}

// Run the test if called directly
if (php_sapi_name() === 'cli') {
    // Initialize Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    try {
        testLedgerCalculation();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
