<?php

// Simulate the cleanRowData method logic to verify the fix
function testCleanRowData($rowArray) {
    $numericFields = [
        'stock_alert_quantity', 
        'alert_quantity',
        'original_price', 
        'retail_price', 
        'whole_sale_price', 
        'special_price', 
        'max_retail_price', 
        'qty', 
        'pax'
    ];

    foreach ($numericFields as $field) {
        if (isset($rowArray[$field])) {
            $value = trim($rowArray[$field]);
            // Only convert empty strings and pure whitespace to null, preserve "0"
            if ($value === '' || $value === ' ' || $value === '  ') {
                $rowArray[$field] = null;
            } else {
                $rowArray[$field] = $value;
            }
        }
    }
    
    return $rowArray;
}

// Test cases that would cause the database error
$testCases = [
    [
        'product_name' => 'Test Product',
        'sku' => 'TEST001',
        'stock_alert_quantity' => ' ',  // This was causing the database error
        'original_price' => '10.00',
        'retail_price' => '15.00'
    ],
    [
        'product_name' => 'Test Product 2',
        'sku' => 'TEST002',
        'stock_alert_quantity' => '',   // Empty string
        'original_price' => '10.00',
        'retail_price' => '15.00'
    ],
    [
        'product_name' => 'Test Product 3',
        'sku' => 'TEST003',
        'stock_alert_quantity' => '0',  // Valid zero value
        'original_price' => '10.00',
        'retail_price' => '15.00'
    ],
    [
        'product_name' => 'Test Product 4',
        'sku' => 'TEST004',
        'stock_alert_quantity' => '5',  // Valid numeric value
        'original_price' => '10.00',
        'retail_price' => '15.00'
    ]
];

echo "Testing cleanRowData fix for alert_quantity database error:\n";
echo "=========================================================\n\n";

foreach ($testCases as $index => $testCase) {
    echo "Test Case " . ($index + 1) . ":\n";
    echo "Before cleaning: stock_alert_quantity = '" . $testCase['stock_alert_quantity'] . "'\n";
    
    $cleaned = testCleanRowData($testCase);
    
    echo "After cleaning:  stock_alert_quantity = ";
    if ($cleaned['stock_alert_quantity'] === null) {
        echo "NULL (safe for database)\n";
    } else {
        echo "'" . $cleaned['stock_alert_quantity'] . "' (valid value)\n";
    }
    echo "Database safety: " . ($cleaned['stock_alert_quantity'] === null || is_numeric($cleaned['stock_alert_quantity']) ? "SAFE" : "UNSAFE") . "\n\n";
}

echo "Summary:\n";
echo "- Empty strings and spaces are now converted to NULL\n";
echo "- Valid numeric values (including '0') are preserved\n";
echo "- This should fix the 'Incorrect integer value' database error\n";
