<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check locations and their logo images
$locations = App\Models\Location::select('id', 'name', 'logo_image')->get();

echo "Locations with logo status:\n";
foreach ($locations as $location) {
    echo sprintf("ID: %d, Name: %s, Logo: %s\n", 
        $location->id, 
        $location->name, 
        $location->logo_image ?? 'No logo'
    );
}

// Check a sample sale to see its location
$sale = App\Models\Sale::with('location')->first();
if ($sale) {
    echo "\nSample Sale:\n";
    echo sprintf("Sale ID: %d, Location ID: %d\n", $sale->id, $sale->location_id);
    if ($sale->location) {
        echo sprintf("Location Name: %s, Logo: %s\n", 
            $sale->location->name, 
            $sale->location->logo_image ?? 'No logo'
        );
    }
}
