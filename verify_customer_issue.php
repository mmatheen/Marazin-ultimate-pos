<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Sale;

echo "🔍 CHECKING SPECIFIC CUSTOMER LEDGER ISSUE\n";
echo "==========================================\n\n";

// Find the customer from the screenshot - likely Customer ID 3 based on our analysis
$customer = Customer::find(3);
if (!$customer) {
    echo "❌ Customer ID 3 not found. Let me search by name...\n";
    $customer = Customer::where('first_name', 'LIKE', '%2Star%')
        ->orWhere('last_name', 'LIKE', '%STR%')
        ->orWhere('first_name', 'LIKE', '%Star%')
        ->first();
}

if ($customer) {
    echo "✅ FOUND CUSTOMER:\n";
    echo "ID: {$customer->id}\n";
    echo "Name: {$customer->first_name} {$customer->last_name}\n";
    echo "Mobile: {$customer->mobile_no}\n";
    echo "Current Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
    echo "Opening Balance: Rs " . number_format($customer->opening_balance, 2) . "\n\n";
    
    echo "🔍 CHECKING PROBLEMATIC INVOICES:\n";
    $problematicRefs = ['ATF-017', 'ATF-020', 'ATF-027'];
    $totalOrphanedAmount = 0;
    
    foreach ($problematicRefs as $ref) {
        echo "Invoice: $ref\n";
        echo str_repeat("-", 20) . "\n";
        
        // Check if sale exists
        $sale = Sale::where('invoice_no', $ref)->first();
        echo "Sale exists: " . ($sale ? "YES (Customer: {$sale->customer_id})" : "NO - DELETED!") . "\n";
        
        // Check ledger entries for this customer
        $ledgerEntry = Ledger::where('reference_no', $ref)
            ->where('user_id', $customer->id)
            ->where('contact_type', 'customer')
            ->first();
            
        if ($ledgerEntry) {
            echo "Ledger Entry Found:\n";
            echo "  Debit: Rs " . number_format($ledgerEntry->debit, 2) . "\n";
            echo "  Credit: Rs " . number_format($ledgerEntry->credit, 2) . "\n";
            echo "  Balance Impact: Rs " . number_format($ledgerEntry->debit - $ledgerEntry->credit, 2) . "\n";
            echo "  Date: " . $ledgerEntry->created_at->format('Y-m-d H:i:s') . "\n";
            
            $totalOrphanedAmount += ($ledgerEntry->debit - $ledgerEntry->credit);
            
            if (!$sale) {
                echo "  ❌ PROBLEM: Ledger exists but sale is deleted!\n";
            } elseif ($sale->customer_id != $customer->id) {
                echo "  ❌ PROBLEM: Sale transferred to Customer {$sale->customer_id} but ledger remains!\n";
            }
        } else {
            echo "No ledger entry found for this customer\n";
        }
        echo "\n";
    }
    
    echo "💰 TOTAL ORPHANED AMOUNT: Rs " . number_format($totalOrphanedAmount, 2) . "\n";
    echo "📊 CUSTOMER'S CORRECT BALANCE SHOULD BE: Rs " . number_format($customer->current_balance - $totalOrphanedAmount, 2) . "\n\n";
    
    if ($totalOrphanedAmount > 0) {
        echo "🚨 CONFIRMATION: This customer has orphaned ledger entries!\n";
        echo "The cleanup SQL will fix this by creating reversal entries.\n";
    }
    
} else {
    echo "❌ Customer not found\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "READY TO EXECUTE CLEANUP? The safe_ledger_cleanup.sql is ready.\n";