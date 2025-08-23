<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;

// Create a customer for location 2 (ARB FASHION)
$customer = Customer::withoutGlobalScopes()->create([
    'first_name' => 'Test Customer',
    'last_name' => 'Location 2',
    'mobile_no' => '1234567890',
    'location_id' => 2,
    'opening_balance' => 0,
    'current_balance' => 0,
    'credit_limit' => 10000
]);

echo "Created customer ID: {$customer->id} for location 2 (ARB FASHION)\n";

// Also create one for location 4 (Ninthavur)
$customer4 = Customer::withoutGlobalScopes()->create([
    'first_name' => 'Test Customer',
    'last_name' => 'Location 4',
    'mobile_no' => '1234567891',
    'location_id' => 4,
    'opening_balance' => 0,
    'current_balance' => 0,
    'credit_limit' => 10000
]);

echo "Created customer ID: {$customer4->id} for location 4 (Ninthavur)\n";

echo "\nNow you can test with:\n";
echo "- Customer ID {$customer->id} when logged in as Suraif (location 2)\n";
echo "- Customer ID {$customer4->id} when logged in as Riskan (location 4)\n";
echo "- Customer ID 1 (Walk-in) works for everyone\n";
echo "- Customer ID 2 only works for users with access to location 1 (Sammanthurai)\n";
