<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;

echo "=== LIST OF CUSTOMERS (WITHOUT LOCATION SCOPE) ===\n\n";

$customers = Customer::withoutLocationScope()->orderBy('id')->get();

if ($customers->isEmpty()) {
    echo "No customers found in the database.\n";
} else {
    foreach ($customers as $customer) {
        echo sprintf(
            "ID: %d | Name: %s | Balance: %.2f | Opening Balance: %.2f\n",
            $customer->id,
            $customer->name,
            $customer->balance ?? 0,
            $customer->opening_balance ?? 0
        );
    }
    echo "\nTotal Customers: " . $customers->count() . "\n";
}
