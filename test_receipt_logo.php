<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get a sale and test the receipt
$sale = App\Models\Sale::with(['location', 'customer', 'products'])->first();

if (!$sale) {
    echo "No sales found to test with.\n";
    exit;
}

echo "Testing Receipt Generation:\n";
echo "===========================\n";
echo sprintf("Sale ID: %d\n", $sale->id);
echo sprintf("Location ID from Sale: %d\n", $sale->location_id);

if ($sale->location) {
    echo sprintf("Location Name: %s\n", $sale->location->name);
    echo sprintf("Location Logo Path: %s\n", $sale->location->logo_image ?? 'No logo set');
    echo sprintf("Full Logo URL: %s\n", $sale->location->logo_image ? asset($sale->location->logo_image) : 'No logo URL');
} else {
    echo "WARNING: Sale has no location relationship!\n";
}

echo "\nReceipt Logo Logic Test:\n";
echo "========================\n";

$location = $sale->location;
if ($location && $location->logo_image) {
    echo "✓ Location logo would be displayed: " . asset($location->logo_image) . "\n";
} else {
    echo "✓ Default logo would be displayed: " . asset('assets/img/prany-stores.png') . "\n";
}

echo "\nAll locations and their logos:\n";
echo "==============================\n";
$locations = App\Models\Location::all();
foreach ($locations as $loc) {
    echo sprintf("ID: %d, Name: %s, Logo: %s\n", 
        $loc->id, 
        $loc->name, 
        $loc->logo_image ? "✓ " . $loc->logo_image : "✗ No logo"
    );
}
