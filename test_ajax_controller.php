<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Web\ProductController;
use Illuminate\Http\Request;

echo "=== TESTING AJAX REQUEST TO WEB CONTROLLER ===\n\n";

try {
    // Create a mock AJAX request
    $request = new Request();
    
    // Set request properties to simulate AJAX call
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $request->headers->set('Accept', 'application/json');
    
    // Replace the global request instance
    app()->instance('request', $request);
    
    // Create controller instance
    $controller = new ProductController();
    
    // Call the getStockHistory method with AJAX simulation
    $response = $controller->getStockHistory(1);
    
    // Get the response content
    $jsonContent = $response->getContent();
    $statusCode = $response->getStatusCode();
    
    echo "HTTP Status: {$statusCode}\n";
    echo "Response type: " . get_class($response) . "\n";
    echo "Content length: " . strlen($jsonContent) . " characters\n";
    
    // Try to decode JSON
    $data = json_decode($jsonContent, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ Valid JSON response received\n\n";
        
        if (isset($data['stock_histories']) && is_array($data['stock_histories'])) {
            echo "Stock histories count: " . count($data['stock_histories']) . "\n";
            
            // Check for adjustments in the response
            $adjustments = array_filter($data['stock_histories'], function($history) {
                return isset($history['stock_type']) && $history['stock_type'] === 'adjustment';
            });
            
            echo "Adjustment entries found: " . count($adjustments) . "\n";
            
            if (count($adjustments) > 0) {
                echo "✅ Adjustments found in controller response!\n";
                foreach ($adjustments as $adj) {
                    echo "  - Adjustment: " . ($adj['quantity'] ?? 'N/A') . "\n";
                }
            } else {
                echo "❌ No adjustment entries in response\n";
            }
            
            // Check current stock
            if (isset($data['current_stock'])) {
                $currentStock = floatval($data['current_stock']);
                echo "\nFinal current stock: {$currentStock}\n";
                echo "Expected: 8\n";
                echo "Match: " . ($currentStock == 8 ? "✅ YES" : "❌ NO") . "\n";
            }
            
            // Show stock type sums for debugging
            if (isset($data['stock_type_sums'])) {
                echo "\nStock type sums:\n";
                foreach ($data['stock_type_sums'] as $type => $sum) {
                    echo "  {$type}: {$sum}\n";
                }
            }
            
        } else {
            echo "❌ No stock_histories array in response\n";
        }
    } else {
        echo "❌ Invalid JSON response\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
        echo "First 500 characters:\n" . substr($jsonContent, 0, 500) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>