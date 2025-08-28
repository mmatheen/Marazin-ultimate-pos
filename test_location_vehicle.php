<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Location model with vehicle details...\n\n";

// Test 1: Check fillable fields
echo "1. Location model fillable fields:\n";
$location = new App\Models\Location();
$fillable = $location->getFillable();
echo "Fillable fields: " . implode(', ', $fillable) . "\n";
echo "Has vehicle_number: " . (in_array('vehicle_number', $fillable) ? 'YES' : 'NO') . "\n";
echo "Has vehicle_type: " . (in_array('vehicle_type', $fillable) ? 'YES' : 'NO') . "\n\n";

// Test 2: Test parent location creation (should work without vehicle details)
echo "2. Testing parent location creation (no vehicle required):\n";
try {
    $parent = new App\Models\Location([
        'name' => 'Test Main Warehouse',
        'location_id' => 'TMW001',
        'address' => '123 Test St'
    ]);
    
    echo "Parent location object created successfully\n";
    echo "Is parent location: " . ($parent->isParentLocation() ? 'YES' : 'NO') . "\n";
    echo "Has vehicle details: " . ($parent->hasVehicleDetails() ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "Error creating parent location: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test sublocation validation without vehicle details (should fail)
echo "3. Testing sublocation without vehicle details (should fail validation):\n";
try {
    $sub_no_vehicle = new App\Models\Location([
        'name' => 'Test Delivery Point',
        'location_id' => 'TDP001',
        'parent_id' => 1
    ]);
    
    echo "Sublocation object created\n";
    echo "Is sublocation: " . ($sub_no_vehicle->isSublocation() ? 'YES' : 'NO') . "\n";
    echo "Has vehicle details: " . ($sub_no_vehicle->hasVehicleDetails() ? 'YES' : 'NO') . "\n";
    
    $sub_no_vehicle->validateVehicleRequirements();
    echo "Validation passed (this should not happen!)\n";
    
} catch (Exception $e) {
    echo "Validation failed as expected: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test sublocation with vehicle details (should pass)
echo "4. Testing sublocation with vehicle details (should pass validation):\n";
try {
    $sub_with_vehicle = new App\Models\Location([
        'name' => 'Test Delivery Van',
        'location_id' => 'TDV001',
        'parent_id' => 1,
        'vehicle_number' => 'TEST-1234',
        'vehicle_type' => 'Van'
    ]);
    
    echo "Sublocation object created\n";
    echo "Is sublocation: " . ($sub_with_vehicle->isSublocation() ? 'YES' : 'NO') . "\n";
    echo "Has vehicle details: " . ($sub_with_vehicle->hasVehicleDetails() ? 'YES' : 'NO') . "\n";
    echo "Vehicle number: " . $sub_with_vehicle->vehicle_number . "\n";
    echo "Vehicle type: " . $sub_with_vehicle->vehicle_type . "\n";
    
    $sub_with_vehicle->validateVehicleRequirements();
    echo "Validation passed as expected\n";
    
} catch (Exception $e) {
    echo "Validation failed: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n";
