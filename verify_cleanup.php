<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ledger;
use App\Models\Customer;

echo "🔍 VERIFYING CLEANUP RESULTS\n";
echo "============================\n\n";

// Check the affected customers
$affectedCustomers = [3, 146, 871, 916, 921, 935];

foreach ($affectedCustomers as $customerId) {
    echo "Customer ID: $customerId\n";
    echo str_repeat("-", 20) . "\n";
    
    $customer = Customer::find($customerId);
    if ($customer) {
        echo "Name: {$customer->first_name} {$customer->last_name}\n";
        
        // Get the latest ledger balance
        $latestLedgerBalance = Ledger::getLatestBalance($customerId, 'customer');
        
        // Update the customer model if needed
        if (abs($customer->current_balance - $latestLedgerBalance) > 0.01) {
            echo "Old Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
            echo "New Balance: Rs " . number_format($latestLedgerBalance, 2) . "\n";
            
            $customer->current_balance = $latestLedgerBalance;
            $customer->save();
            echo "✅ Customer balance updated!\n";
        } else {
            echo "Balance: Rs " . number_format($customer->current_balance, 2) . " (already correct)\n";
        }
        
        // Check if this customer had reversal entries created
        $reversalEntries = Ledger::where('user_id', $customerId)
            ->where('reference_no', 'LIKE', 'CLEANUP-REV-%')
            ->count();
        
        if ($reversalEntries > 0) {
            echo "Reversal entries created: $reversalEntries\n";
        }
    } else {
        echo "❌ Customer not found\n";
    }
    echo "\n";
}

echo "🎯 SPECIAL CHECK FOR CUSTOMER 3 (2Star - STR):\n";
echo "================================================\n";
$customer3 = Customer::find(3);
if ($customer3) {
    echo "Customer: {$customer3->first_name} {$customer3->last_name}\n";
    echo "Current Balance: Rs " . number_format($customer3->current_balance, 2) . "\n";
    echo "Opening Balance: Rs " . number_format($customer3->opening_balance, 2) . "\n\n";
    
    echo "Recent ledger entries:\n";
    $recentEntries = Ledger::where('user_id', 3)
        ->where('contact_type', 'customer')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($recentEntries as $entry) {
        $type = $entry->reference_no;
        if (strpos($type, 'CLEANUP-REV') !== false) {
            $type .= " (CLEANUP)";
        }
        echo "  {$entry->created_at->format('Y-m-d H:i')} | $type | ";
        echo "D: Rs " . number_format($entry->debit, 2) . " | ";
        echo "C: Rs " . number_format($entry->credit, 2) . " | ";
        echo "Bal: Rs " . number_format($entry->balance, 2) . "\n";
    }
} else {
    echo "❌ Customer 3 not found\n";
}

echo "\n✅ CLEANUP VERIFICATION COMPLETE!\n";
echo "The customer '2Star - STR' should now have the correct balance.\n";
echo "Check the ledger report again to confirm the fix.\n";