<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;

echo "=== Customer Selection Fix Test ===\n\n";

echo "1. Walk-in Customer Verification:\n";
$walkInCustomer = Customer::find(1);
if ($walkInCustomer) {
    echo "   ✓ Walk-in customer exists with ID: {$walkInCustomer->id}\n";
    echo "   ✓ Name: {$walkInCustomer->first_name} {$walkInCustomer->last_name}\n";
    echo "   ✓ Type: {$walkInCustomer->customer_type}\n";
    echo "   ✓ Mobile: {$walkInCustomer->mobile_no}\n";
} else {
    echo "   ✗ Walk-in customer not found!\n";
}

echo "\n2. Total Customers in Database:\n";
$totalCustomers = Customer::count();
echo "   Total customers: {$totalCustomers}\n";

echo "\n3. Customer ID Range Check:\n";
$allCustomers = Customer::select('id', 'first_name', 'last_name')->get();
foreach ($allCustomers as $customer) {
    echo "   ID {$customer->id}: {$customer->first_name} {$customer->last_name}\n";
}

echo "\n4. Check for Customer ID 55:\n";
$customer55 = Customer::find(55);
if ($customer55) {
    echo "   ✗ Customer ID 55 exists: {$customer55->first_name} {$customer55->last_name}\n";
} else {
    echo "   ✓ Customer ID 55 does not exist (good)\n";
}

echo "\n5. Non-Walk-in Customers (for sales reps):\n";
$nonWalkInCustomers = Customer::where('id', '!=', 1)->get();
echo "   Non-walk-in customers count: " . $nonWalkInCustomers->count() . "\n";
foreach ($nonWalkInCustomers as $customer) {
    $cityName = $customer->city ? $customer->city->name : 'No City';
    echo "   ID {$customer->id}: {$customer->first_name} {$customer->last_name} - {$cityName}\n";
}

echo "\n=== Test Complete ===\n";