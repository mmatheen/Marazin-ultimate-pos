<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COMPARING LARAVEL PDO VS RAW PDO ===\n\n";

try {
    // Test 1: Laravel's PDO connection
    echo "1. Laravel PDO Connection:\n";
    $laravelPdo = DB::connection()->getPdo();
    
    $query = "
        SELECT sh.id, sh.quantity, lb.location_id
        FROM stock_histories sh
        JOIN location_batches lb ON sh.loc_batch_id = lb.id
        JOIN batches b ON lb.batch_id = b.id
        WHERE b.product_id = ? AND sh.stock_type = 'adjustment'
    ";
    
    $stmt = $laravelPdo->prepare($query);
    $stmt->execute([1]);
    $laravelResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Laravel PDO found: " . count($laravelResults) . " adjustment records\n";
    foreach ($laravelResults as $row) {
        echo "  - ID: {$row['id']}, Quantity: {$row['quantity']}, Location: {$row['location_id']}\n";
    }
    
    // Test 2: Raw PDO connection (same as our working script)
    echo "\n2. Raw PDO Connection:\n";
    $rawPdo = new PDO("mysql:host=localhost;dbname=retailarb", 'root', '');
    
    $stmt2 = $rawPdo->prepare($query);
    $stmt2->execute([1]);
    $rawResults = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Raw PDO found: " . count($rawResults) . " adjustment records\n";
    foreach ($rawResults as $row) {
        echo "  - ID: {$row['id']}, Quantity: {$row['quantity']}, Location: {$row['location_id']}\n";
    }
    
    // Test 3: Check database names
    echo "\n3. Database Connection Info:\n";
    $laravelDbName = $laravelPdo->query("SELECT DATABASE()")->fetchColumn();
    $rawDbName = $rawPdo->query("SELECT DATABASE()")->fetchColumn();
    
    echo "Laravel PDO database: {$laravelDbName}\n";
    echo "Raw PDO database: {$rawDbName}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>