<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\Ledger;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "🔧 CUSTOMER LEDGER CLEANUP TOOL\n";
echo "===============================\n\n";

// Get the problematic references
$problematicRefs = ['ATF-017', 'ATF-020', 'ATF-027', 'MLX-050'];

foreach ($problematicRefs as $refNo) {
    echo "🔍 ANALYZING INVOICE: $refNo\n";
    echo str_repeat("-", 40) . "\n";
    
    // Get the current sale record
    $sale = Sale::where('invoice_no', $refNo)->first();
    if (!$sale) {
        echo "❌ Sale not found for invoice $refNo\n\n";
        continue;
    }
    
    // Get all ledger entries for this reference
    $ledgerEntries = Ledger::where('reference_no', $refNo)
        ->where('contact_type', 'customer')
        ->orderBy('created_at', 'asc')
        ->get();
    
    echo "Current Sale Customer: {$sale->customer_id}\n";
    echo "Sale Amount: Rs " . number_format($sale->final_total, 2) . "\n";
    echo "Ledger Entries Found: {$ledgerEntries->count()}\n\n";
    
    // Show all entries
    foreach ($ledgerEntries as $i => $entry) {
        $customer = Customer::find($entry->user_id);
        $customerName = $customer ? $customer->full_name : "Customer {$entry->user_id}";
        
        echo "  Entry " . ($i + 1) . ":\n";
        echo "    Customer: {$entry->user_id} ($customerName)\n";
        echo "    Type: {$entry->transaction_type}\n";
        echo "    Debit: Rs " . number_format($entry->debit, 2) . "\n";
        echo "    Credit: Rs " . number_format($entry->credit, 2) . "\n";
        echo "    Date: " . $entry->created_at->format('Y-m-d H:i:s') . "\n";
        echo "    Notes: {$entry->notes}\n";
        echo "\n";
    }
    
    // Determine which entries to keep and which to remove
    $currentCustomerEntries = $ledgerEntries->where('user_id', $sale->customer_id);
    $oldCustomerEntries = $ledgerEntries->where('user_id', '!=', $sale->customer_id);
    
    echo "✅ ENTRIES TO KEEP (Current Customer {$sale->customer_id}):\n";
    foreach ($currentCustomerEntries as $entry) {
        echo "  - Entry ID: {$entry->id} | Type: {$entry->transaction_type} | Amount: Rs " . number_format($entry->debit + $entry->credit, 2) . "\n";
    }
    
    echo "\n❌ ENTRIES TO REMOVE (Old Customers):\n";
    foreach ($oldCustomerEntries as $entry) {
        echo "  - Entry ID: {$entry->id} | Customer: {$entry->user_id} | Type: {$entry->transaction_type} | Amount: Rs " . number_format($entry->debit + $entry->credit, 2) . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

echo "🎯 CLEANUP SUMMARY:\n";
echo "===================\n";
echo "This script identified the problematic ledger entries.\n";
echo "The solution is to remove the entries for the OLD customers\n";
echo "and keep only the entries for the CURRENT customers.\n\n";

echo "Would you like me to create the CLEANUP SCRIPT? (Type 'yes' to proceed)\n";
echo "This will generate a safe script to fix these issues.\n";

// Don't auto-execute - let user confirm first
echo "\n⚠️  IMPORTANT: Review the analysis above before proceeding with cleanup!\n";
echo "The cleanup script will be generated separately for safety.\n";