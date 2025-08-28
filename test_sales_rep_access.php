<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing User-Location relationship...\n\n";

// Test 1: Check if User model has locations method
echo "1. User model methods:\n";
$user = new App\Models\User();
$userMethods = ['locations', 'vehicle', 'salesRep'];

foreach ($userMethods as $method) {
    echo "- Method {$method}: " . (method_exists($user, $method) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n";

// Test 2: Check fillable fields
echo "2. User fillable fields:\n";
$fillable = $user->getFillable();
echo "Fillable: " . implode(', ', $fillable) . "\n";
echo "Has is_admin: " . (in_array('is_admin', $fillable) ? 'YES' : 'NO') . "\n";

echo "\n";

// Test 3: Check Location model relationship
echo "3. Location model methods:\n";
$location = new App\Models\Location();
$locationMethods = ['users', 'parent', 'children'];

foreach ($locationMethods as $method) {
    echo "- Method {$method}: " . (method_exists($location, $method) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n";

// Test 4: Check User casts
echo "4. User model casts:\n";
$casts = $user->getCasts();
echo "Casts: " . implode(', ', array_keys($casts)) . "\n";
echo "Has is_admin cast: " . (isset($casts['is_admin']) ? 'YES' : 'NO') . "\n";

echo "\nRelationship test completed!\n";
