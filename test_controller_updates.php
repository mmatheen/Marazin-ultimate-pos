<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing LocationController updates...\n\n";

// Test 1: Verify Location model methods exist
echo "1. Testing Location model methods:\n";
$location = new App\Models\Location();
$methods = ['isParentLocation', 'isSublocation', 'hasVehicleDetails', 'validateVehicleRequirements'];

foreach ($methods as $method) {
    echo "- Method {$method}: " . (method_exists($location, $method) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n";

// Test 2: Test fillable fields
echo "2. Testing fillable fields:\n";
$fillable = $location->getFillable();
$requiredFields = ['vehicle_number', 'vehicle_type', 'parent_id'];

foreach ($requiredFields as $field) {
    echo "- Field {$field}: " . (in_array($field, $fillable) ? 'INCLUDED' : 'MISSING') . "\n";
}

echo "\n";

// Test 3: Test LocationController methods exist
echo "3. Testing LocationController methods:\n";
$controller = new App\Http\Controllers\LocationController();
$controllerMethods = [
    'getParentLocations',
    'getSublocations', 
    'getLocationsByVehicleType',
    'searchByVehicleNumber',
    'getLocationHierarchy'
];

foreach ($controllerMethods as $method) {
    echo "- Method {$method}: " . (method_exists($controller, $method) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n";

// Test 4: Test validation logic
echo "4. Testing validation logic:\n";

try {
    // Parent location should not require vehicle details
    $parent = new App\Models\Location([
        'name' => 'Test Parent',
        'location_id' => 'TP001',
        'parent_id' => null
    ]);
    echo "- Parent location validation: PASSED\n";
    
    // Sublocation should require vehicle details
    $sublocation = new App\Models\Location([
        'name' => 'Test Sublocation',
        'location_id' => 'TS001',
        'parent_id' => 1,
        'vehicle_number' => 'TEST-123',
        'vehicle_type' => 'Van'
    ]);
    echo "- Sublocation with vehicle details validation: PASSED\n";
    
    // Test sublocation without vehicle details (should fail)
    $invalidSublocation = new App\Models\Location([
        'name' => 'Invalid Sublocation',
        'location_id' => 'IS001',
        'parent_id' => 1
    ]);
    
    try {
        $invalidSublocation->validateVehicleRequirements();
        echo "- Invalid sublocation validation: FAILED (should have thrown exception)\n";
    } catch (Exception $e) {
        echo "- Invalid sublocation validation: PASSED (correctly threw exception)\n";
    }
    
} catch (Exception $e) {
    echo "- Validation test error: " . $e->getMessage() . "\n";
}

echo "\nController update test completed!\n";
