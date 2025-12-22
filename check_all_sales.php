<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sale;

echo "ALL Sale Records:\n";
echo "=================\n\n";

$allSales = Sale::orderBy('id', 'desc')->limit(20)->get();

echo "Total records found: " . Sale::count() . "\n";
echo "Showing last 20 records:\n\n";

foreach ($allSales as $sale) {
    echo "ID: {$sale->id} | Type: {$sale->transaction_type} | ";
    echo "Order#: " . ($sale->order_number ?? 'N/A') . " | ";
    echo "Invoice#: " . ($sale->invoice_no ?? 'N/A') . " | ";
    echo "Status: {$sale->order_status}\n";
}
