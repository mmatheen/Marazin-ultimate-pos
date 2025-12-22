<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sale;

echo "Checking for duplicate records...\n\n";

// Check for records with converted_to_sale_id
$withConverted = Sale::whereNotNull('converted_to_sale_id')->get();
echo "Records with converted_to_sale_id: " . $withConverted->count() . "\n";

foreach ($withConverted as $record) {
    echo "  - ID: {$record->id}, Order: {$record->order_number}, Converted to: {$record->converted_to_sale_id}\n";
}

// Check for sale_order with order_status=completed
$completedOrders = Sale::where('transaction_type', 'sale_order')
    ->where('order_status', 'completed')
    ->get();
echo "\nSale orders with order_status=completed: " . $completedOrders->count() . "\n";

foreach ($completedOrders as $record) {
    echo "  - ID: {$record->id}, Order: {$record->order_number}, Status: {$record->order_status}\n";
}
