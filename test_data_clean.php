<?php

// Test the data cleaning logic that matches the current implementation
$testValues = [
    ' ',           // space
    '  ',          // multiple spaces
    '',            // empty string
    'valid_value', // normal value
    null,          // null
    '0',           // zero string
    0              // zero number
];

echo "Testing cleanRowData logic (current implementation):\n";
echo "==================================================\n";

foreach ($testValues as $index => $value) {
    // This is the current logic from cleanRowData method for numeric fields
    if (isset($value)) {
        $trimmed = trim($value);
        $cleaned = ($trimmed === '' || $trimmed === ' ') ? null : $value;
    } else {
        $cleaned = $value;
    }
    
    echo "Test $index:\n";
    echo "  Original: [" . var_export($value, true) . "]\n";
    echo "  Cleaned:  [" . var_export($cleaned, true) . "]\n";
    echo "  Result:   " . ($cleaned === null ? 'NULL (good for database)' : 'Value: ' . $cleaned) . "\n\n";
}
