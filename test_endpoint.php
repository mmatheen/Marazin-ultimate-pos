<?php

// Simple test to call the actual endpoint
$url = "http://localhost/Marazin-ultimate-pos/public/products/stock-history/1";

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "=== TESTING ACTUAL ENDPOINT ===\n\n";
echo "URL: {$url}\n";
echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
} else {
    echo "Response received: " . (strlen($response) > 0 ? "Yes" : "No") . "\n";
    echo "Response length: " . strlen($response) . " characters\n";
    
    if ($httpCode == 200 && $response) {
        // Try to decode JSON response
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ Valid JSON response received\n";
            
            if (isset($data['data']) && is_array($data['data'])) {
                $stockHistories = $data['data'];
                echo "Stock histories count: " . count($stockHistories) . "\n";
                
                // Check for adjustments
                $adjustments = array_filter($stockHistories, function($history) {
                    return isset($history['stock_type']) && $history['stock_type'] === 'adjustment';
                });
                
                echo "Adjustment entries: " . count($adjustments) . "\n";
                
                if (count($adjustments) > 0) {
                    echo "✅ Adjustments found in response!\n";
                    foreach ($adjustments as $adj) {
                        echo "  - Adjustment: " . ($adj['quantity'] ?? 'N/A') . "\n";
                    }
                } else {
                    echo "❌ No adjustments in response\n";
                }
                
                // Calculate running stock to see final result
                $runningStock = 0;
                $lastRunningStock = null;
                
                foreach ($stockHistories as $history) {
                    if (isset($history['running_stock'])) {
                        $lastRunningStock = floatval($history['running_stock']);
                    }
                }
                
                if ($lastRunningStock !== null) {
                    echo "Final running stock from endpoint: {$lastRunningStock}\n";
                    echo "Expected: 8\n";
                    echo "Match: " . ($lastRunningStock == 8 ? "✅ YES" : "❌ NO") . "\n";
                }
                
            } else {
                echo "❌ Unexpected response structure\n";
            }
        } else {
            echo "❌ Invalid JSON response\n";
            echo "First 500 characters:\n" . substr($response, 0, 500) . "\n";
        }
    } else {
        echo "❌ Request failed or empty response\n";
        echo "First 200 characters:\n" . substr($response, 0, 200) . "\n";
    }
}

?>