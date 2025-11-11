<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\Ledger;

echo "🔍 FINDING THE ACTUAL CUSTOMER DATA\n";
echo "=====================================\n\n";

// First, let's find customers that might match "2Star - STR"
echo "Searching for customers with '2Star' or 'STR' in their names:\n";
$customers = Customer::where(function($query) {
    $query->where('first_name', 'LIKE', '%2Star%')
          ->orWhere('last_name', 'LIKE', '%2Star%')
          ->orWhere('first_name', 'LIKE', '%STR%')
          ->orWhere('last_name', 'LIKE', '%STR%')
          ->orWhere('business_name', 'LIKE', '%2Star%')
          ->orWhere('business_name', 'LIKE', '%STR%');
})->get();

if ($customers->count() > 0) {
    foreach ($customers as $customer) {
        echo "ID: {$customer->id}\n";
        echo "Name: {$customer->first_name} {$customer->last_name}\n";
        echo "Business: {$customer->business_name}\n";
        echo "Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
        echo "Opening Balance: Rs " . number_format($customer->opening_balance, 2) . "\n";
        echo str_repeat("-", 30) . "\n";
    }
} else {
    echo "No customers found with '2Star' or 'STR'.\n\n";
    
    // Let's check customers that had ledger entries created in the cleanup
    echo "Checking customers who had cleanup reversal entries:\n";
    $cleanup_entries = Ledger::where('reference_no', 'LIKE', 'CLEANUP-REV-%')
        ->select('user_id')
        ->distinct()
        ->pluck('user_id');
    
    if ($cleanup_entries->count() > 0) {
        echo "Customer IDs with cleanup entries: " . implode(', ', $cleanup_entries->toArray()) . "\n\n";
        
        foreach ($cleanup_entries as $customerId) {
            $customer = Customer::find($customerId);
            if ($customer) {
                echo "Customer ID: $customerId\n";
                echo "Name: {$customer->first_name} {$customer->last_name}\n";
                echo "Business: {$customer->business_name}\n";
                echo "Current Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
                
                // Check recent ledger entries
                $recent = Ledger::where('user_id', $customerId)
                    ->orderBy('created_at', 'desc')
                    ->limit(3)
                    ->get();
                
                echo "Recent entries:\n";
                foreach ($recent as $entry) {
                    echo "  {$entry->reference_no} | Bal: Rs " . number_format($entry->balance, 2) . "\n";
                }
                echo str_repeat("-", 40) . "\n";
            }
        }
    } else {
        echo "No cleanup entries found.\n";
    }
}

// Also check if we have any customers with high balances
echo "\nCustomers with balance > Rs 5,000:\n";
$highBalanceCustomers = Customer::where('current_balance', '>', 5000)->get();
foreach ($highBalanceCustomers as $customer) {
    echo "ID: {$customer->id} | {$customer->first_name} {$customer->last_name} | ";
    echo "Business: {$customer->business_name} | Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
}

echo "\n✅ CUSTOMER SEARCH COMPLETE\n";