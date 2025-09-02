<?php
// Simple test for date conversion functionality

function convertDateFormat($date, $rowNumber)
{
    if (empty($date)) {
        return null;
    }

    // Remove any extra whitespace
    $date = trim($date);
    
    // If already in correct format, return as is
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }
    
    // Try to parse various date formats with exact matching
    $formats = [
        'Y/m/d',    // 2026/05/26
        'Y.m.d',    // 2026.05.09
        'Y-m-d',    // 2026-05-26
        'Y/n/j',    // 2026/5/9 (single digit month/day)
        'Y.n.j',    // 2026.5.9
        'Y-n-j',    // 2026-5-9
    ];

    foreach ($formats as $format) {
        $dateObj = DateTime::createFromFormat($format, $date);
        if ($dateObj !== false) {
            // Additional validation to ensure the parsed date makes sense
            $reformatted = $dateObj->format($format);
            if ($reformatted === $date) {
                return $dateObj->format('Y-m-d');
            }
        }
    }

    // Try regex-based parsing for common patterns
    if (preg_match('/^(\d{4})[\/\.-](\d{1,2})[\/\.-](\d{1,2})$/', $date, $matches)) {
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
        
        // Validate the date components
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    echo "Could not parse date '{$date}' in row {$rowNumber}\n";
    return false;
}

// Test dates from your Excel file
$testDates = [
    '2026/05/26',
    '2025/11/03', 
    '2026.05.09',
    '2026/03/13',
    '2026/01/27',
    '2024/04/28',
    '2025/03/06'
];

echo "Testing date conversion:\n";
echo "========================\n\n";

foreach ($testDates as $index => $testDate) {
    $converted = convertDateFormat($testDate, $index + 1);
    $status = $converted !== false ? "✓ SUCCESS" : "✗ FAILED";
    echo "Row " . ($index + 1) . ": '{$testDate}' -> '{$converted}' [{$status}]\n";
}

echo "\n";
?>
