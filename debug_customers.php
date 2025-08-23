<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\User;
use App\Models\Location;

echo "=== LOCATIONS ===\n";
$locations = Location::all();
foreach ($locations as $location) {
    echo "ID: {$location->id}, Name: {$location->name}\n";
}

echo "\n=== USERS AND THEIR LOCATIONS ===\n";
$users = User::with('locations')->get();
foreach ($users as $user) {
    $locationNames = $user->locations->pluck('name')->join(', ');
    $locationIds = $user->locations->pluck('id')->join(', ');
    echo "User: {$user->user_name} (ID: {$user->id}) - Locations: {$locationNames} (IDs: {$locationIds})\n";
}

echo "\n=== All customers (bypassing location scope) ===\n";
$customers = Customer::withoutGlobalScopes()->select('id', 'first_name', 'last_name', 'location_id')->get();

if ($customers->count() == 0) {
    echo "No customers found in database.\n";
} else {
    foreach ($customers as $customer) {
        echo "ID: {$customer->id}, Name: {$customer->first_name} {$customer->last_name}, Location: {$customer->location_id}\n";
    }
}

echo "\n=== Checking customer ID 2 specifically ===\n";
$customer2 = Customer::withoutGlobalScopes()->find(2);
if ($customer2) {
    echo "Customer ID 2 found: {$customer2->first_name} {$customer2->last_name}, Location ID: {$customer2->location_id}\n";
} else {
    echo "Customer ID 2 not found\n";
}
