<?php
// Simple test to verify the location selection logic

// Mock user with multiple locations (using array instead of collection)
$userLocations = [
    ['id' => 1, 'name' => 'Main Location'],
    ['id' => 2, 'name' => 'Branch A'],
    ['id' => 3, 'name' => 'Branch B'],
];

// Test case 1: User has selected location in session
echo "Test Case 1: User has selected location in session\n";
$selectedLocationId = 2; // User selected Branch A
$userLocationIds = array_column($userLocations, 'id');

if (in_array($selectedLocationId, $userLocationIds)) {
    $authLocationId = $selectedLocationId;
    echo "✓ Using selected location: Branch A (ID: $authLocationId)\n";
} else {
    echo "✗ Selected location not assigned to user\n";
}

// Test case 2: No selected location, use first assigned location
echo "\nTest Case 2: No selected location, use first assigned location\n";
$selectedLocationId = null;

if ($selectedLocationId) {
    echo "Using selected location\n";
} else {
    if (empty($userLocations)) {
        echo "✗ User has no assigned locations\n";
    } else {
        $authLocationId = $userLocations[0]['id'];
        echo "✓ Using first assigned location: Main Location (ID: $authLocationId)\n";
    }
}

// Test case 3: User selects location not assigned to them
echo "\nTest Case 3: User selects location not assigned to them\n";
$selectedLocationId = 99; // Invalid location ID

if ($selectedLocationId) {
    if (in_array($selectedLocationId, $userLocationIds)) {
        echo "Using selected location\n";
    } else {
        echo "✗ Selected location (ID: $selectedLocationId) is not assigned to the current user\n";
        echo "   User's assigned locations: " . implode(', ', $userLocationIds) . "\n";
    }
}

echo "\nLocation selection logic test completed!\n";
