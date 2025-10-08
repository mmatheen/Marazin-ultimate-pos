<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING STOCK_HISTORIES TABLE STRUCTURE ===\n\n";

try {
    $columns = DB::select('SHOW COLUMNS FROM stock_histories');
    
    echo "Columns in stock_histories table:\n";
    foreach ($columns as $col) {
        echo "- {$col->Field} ({$col->Type})\n";
    }
    
    echo "\n=== SAMPLE DATA ===\n";
    $sampleData = DB::select('SELECT * FROM stock_histories LIMIT 5');
    
    if (empty($sampleData)) {
        echo "No data found in stock_histories table\n";
    } else {
        echo "Sample records:\n";
        foreach ($sampleData as $record) {
            echo "ID: {$record->id}, ";
            foreach ($record as $key => $value) {
                if ($key !== 'id') {
                    echo "{$key}: {$value}, ";
                }
            }
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>