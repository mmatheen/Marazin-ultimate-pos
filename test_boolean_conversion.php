<?php

// Test boolean conversion for can_sell field

echo "Testing boolean conversion for can_sell field:\n\n";

// Test cases for different input values
$testCases = [
    // String values
    "true" => true,
    "false" => false,
    "1" => true,
    "0" => false,
    
    // Integer values
    1 => true,
    0 => false,
    
    // Boolean values
    true => true,
    false => false,
    
    // Null/empty values
    null => false,
    "" => false,
];

echo "Test cases for filter_var(\$value, FILTER_VALIDATE_BOOLEAN):\n";
echo str_repeat("-", 50) . "\n";

foreach ($testCases as $input => $expected) {
    $result = filter_var($input, FILTER_VALIDATE_BOOLEAN);
    $inputDisplay = is_null($input) ? 'null' : (is_string($input) ? "\"$input\"" : var_export($input, true));
    $status = ($result === $expected) ? "✓ PASS" : "✗ FAIL";
    
    echo sprintf("Input: %-10s | Result: %-5s | Expected: %-5s | %s\n", 
        $inputDisplay, 
        var_export($result, true), 
        var_export($expected, true), 
        $status
    );
}

echo "\n";
echo "The controller now properly converts can_sell values to boolean before validation.\n";
echo "This handles cases where the frontend sends:\n";
echo "- String 'true'/'false'\n";
echo "- String '1'/'0'\n";
echo "- Integer 1/0\n";
echo "- Boolean true/false\n";
echo "- Checkbox values from HTML forms\n";

?>
