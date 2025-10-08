<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Web\ProductController;
use Illuminate\Http\Request;

echo "=== TESTING WEB PRODUCT CONTROLLER DIRECTLY ===\n\n";

try {
    // Create a mock request
    $request = new Request();
    
    // Create controller instance
    $controller = new ProductController();
    
    // Call the getStockHistory method directly
    $response = $controller->getStockHistory(1);
    
    // Get the response data
    if (method_exists($response, 'getData')) {
        $data = $response->getData(true); // true for array instead of object
    } else {
        $data = json_decode($response->getContent(), true);
    }
    
    echo "Response received: " . (empty($data) ? "No" : "Yes") . "\n";
    
    if (!empty($data) && isset($data['data'])) {
        $stockHistories = $data['data'];
        echo "Stock histories count: " . count($stockHistories) . "\n";
        
        // Check for adjustments
        $adjustments = array_filter($stockHistories, function($history) {
            return isset($history['stock_type']) && $history['stock_type'] === 'adjustment';
        });
        
        echo "Adjustment entries: " . count($adjustments) . "\n";
        
        if (count($adjustments) > 0) {
            echo "✅ Adjustments found in controller response!\n";
            foreach ($adjustments as $adj) {
                echo "  - Adjustment: " . ($adj['quantity'] ?? 'N/A') . "\n";
            }
        } else {
            echo "❌ No adjustments in controller response\n";
        }
        
        // Find the final running stock
        $lastRunningStock = null;
        
        foreach ($stockHistories as $history) {
            if (isset($history['running_stock'])) {
                $lastRunningStock = floatval($history['running_stock']);
            }
        }
        
        if ($lastRunningStock !== null) {
            echo "Final running stock from controller: {$lastRunningStock}\n";
            echo "Expected: 8\n";
            echo "Match: " . ($lastRunningStock == 8 ? "✅ YES" : "❌ NO") . "\n";
        } else {
            echo "❌ No running stock found in response\n";
        }
        
        // Show a few sample entries to verify data structure
        echo "\nSample entries (last 3):\n";
        $sampleEntries = array_slice($stockHistories, -3);
        foreach ($sampleEntries as $entry) {
            echo "  - Type: " . ($entry['stock_type'] ?? 'N/A') . 
                 ", Qty: " . ($entry['quantity'] ?? 'N/A') . 
                 ", Running: " . ($entry['running_stock'] ?? 'N/A') . "\n";
        }
        
    } else {
        echo "❌ No data in response or unexpected structure\n";
        var_dump($data);
    }
    
} catch (Exception $e) {
    echo "❌ Error calling controller: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>