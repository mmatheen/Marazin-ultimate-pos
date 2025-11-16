<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SEARCHING FOR ALM RIYATH CUSTOMER ===\n\n";

// Search with different patterns
$customers = DB::table('customers')
              ->where('first_name', 'like', '%ALM%')
              ->orWhere('first_name', 'like', '%RIYATH%')
              ->orWhere('last_name', 'like', '%ALM%')
              ->orWhere('last_name', 'like', '%RIYATH%')
              ->get(['id', 'first_name', 'last_name', 'current_balance']);

if ($customers->count() > 0) {
    echo "Found customers:\n";
    foreach ($customers as $customer) {
        echo "ID: {$customer->id}, Name: '{$customer->first_name} {$customer->last_name}', Balance: {$customer->current_balance}\n";
    }
} else {
    echo "No customers found with ALM or RIYATH in name.\n\n";
    
    // Let's see all customer names to find the right one
    echo "Showing all customers:\n";
    $allCustomers = DB::table('customers')->get(['id', 'first_name', 'last_name']);
    foreach ($allCustomers as $customer) {
        if (stripos($customer->first_name, 'alm') !== false || 
            stripos($customer->first_name, 'riyath') !== false ||
            stripos($customer->last_name, 'alm') !== false || 
            stripos($customer->last_name, 'riyath') !== false) {
            echo "MATCH: ID: {$customer->id}, Name: '{$customer->first_name} {$customer->last_name}'\n";
        } else {
            echo "ID: {$customer->id}, Name: '{$customer->first_name} {$customer->last_name}'\n";
        }
    }
}