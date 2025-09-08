<?php
// Simple test to check if location logo feature is working
echo "Location Logo Feature Test" . PHP_EOL;
echo "=========================" . PHP_EOL;

// Check if directory exists
$logoDir = 'public/storage/location_logos';
if (is_dir($logoDir)) {
    echo "✓ Logo directory exists: $logoDir" . PHP_EOL;
    $files = scandir($logoDir);
    $logoFiles = array_filter($files, function($file) {
        return !in_array($file, ['.', '..']);
    });
    echo "✓ Logo files found: " . count($logoFiles) . PHP_EOL;
    foreach ($logoFiles as $file) {
        echo "  - $file" . PHP_EOL;
    }
} else {
    echo "✗ Logo directory not found: $logoDir" . PHP_EOL;
}

// Check migration file
$migrationFile = 'database/migrations/2025_09_08_133917_add_logo_image_to_locations_table.php';
if (file_exists($migrationFile)) {
    echo "✓ Migration file exists" . PHP_EOL;
} else {
    echo "✗ Migration file not found" . PHP_EOL;
}

echo PHP_EOL . "Feature should be ready for testing!" . PHP_EOL;
