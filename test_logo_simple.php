<?php

use App\Models\Location;

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

echo "Testing Logo URL Accessor\n";
echo "========================\n";

$location = new Location();
$location->logo_image = 'storage/location_logos/1757414445_LOC0002.png';

echo "Original logo_image: " . $location->logo_image . "\n";
echo "Generated logo_url: " . $location->logo_url . "\n";

// Test with existing location
$existingLocation = Location::first();
if ($existingLocation) {
    echo "\nExisting location:\n";
    echo "ID: " . $existingLocation->id . "\n";
    echo "Name: " . $existingLocation->name . "\n";
    echo "Logo image: " . ($existingLocation->logo_image ?? 'None') . "\n";
    echo "Logo URL: " . ($existingLocation->logo_url ?? 'None') . "\n";
}
