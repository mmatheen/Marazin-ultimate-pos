<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\Product;
use App\Models\StockHistory;

// Set up database connection (Laravel Capsule)
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'retailarb',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== DEBUGGING ELOQUENT RELATIONSHIP ===\n\n";

// First, let's check if adjustment records exist directly
echo "1. Direct query for adjustment records:\n";
$adjustments = StockHistory::where('stock_type', 'adjustment')
    ->whereHas('locationBatch.batch', function($query) {
        $query->where('product_id', 1);
    })
    ->get();

echo "Found " . $adjustments->count() . " adjustment records directly\n";
foreach ($adjustments as $adj) {
    echo "  - ID: {$adj->id}, Quantity: {$adj->quantity}, Loc Batch: {$adj->loc_batch_id}\n";
}

// Now let's test the relationship chain step by step
echo "\n2. Step-by-step relationship test:\n";

$product = Product::find(1);
echo "Product found: " . ($product ? "Yes" : "No") . "\n";

if ($product) {
    $locationBatches = $product->locationBatches;
    echo "Location batches count: " . $locationBatches->count() . "\n";
    
    foreach ($locationBatches as $batch) {
        echo "  Location Batch {$batch->id}:\n";
        
        // Load stock histories without eager loading to test fresh
        $stockHistories = $batch->stockHistories()->get();
        echo "    Stock histories: " . $stockHistories->count() . "\n";
        
        $adjustmentCount = $stockHistories->where('stock_type', 'adjustment')->count();
        echo "    Adjustments: {$adjustmentCount}\n";
        
        if ($adjustmentCount > 0) {
            foreach ($stockHistories->where('stock_type', 'adjustment') as $adj) {
                echo "      - Adjustment: {$adj->quantity}\n";
            }
        }
    }
}

// Test the exact same query used in the controller but with fresh loading
echo "\n3. Fresh eager loading test:\n";
$product2 = Product::with(['locationBatches' => function($query) {
    $query->with(['stockHistories' => function($subQuery) {
        $subQuery->orderBy('created_at', 'asc');
    }]);
}])->find(1);

if ($product2) {
    $allHistories = [];
    foreach ($product2->locationBatches as $batch) {
        foreach ($batch->stockHistories as $history) {
            $allHistories[] = $history;
        }
    }
    
    echo "Total histories with fresh eager loading: " . count($allHistories) . "\n";
    
    $adjustmentHistories = array_filter($allHistories, function($h) {
        return $h->stock_type === 'adjustment';
    });
    
    echo "Adjustment histories found: " . count($adjustmentHistories) . "\n";
    
    if (count($adjustmentHistories) > 0) {
        echo "✅ Fresh eager loading found adjustments!\n";
        foreach ($adjustmentHistories as $adj) {
            echo "  - Adjustment: {$adj->quantity}\n";
        }
    } else {
        echo "❌ Fresh eager loading still missing adjustments!\n";
        
        // Show all stock types found
        $stockTypes = array_unique(array_map(function($h) {
            return $h->stock_type;
        }, $allHistories));
        echo "Stock types found: " . implode(', ', $stockTypes) . "\n";
    }
}

?>