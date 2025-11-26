<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $maxBatch = DB::table('migrations')->max('batch') ?? 0;
    $exists = DB::table('migrations')
        ->where('migration', '2025_11_19_120000_update_ledgers_table_structure')
        ->exists();
    
    if (!$exists) {
        DB::table('migrations')->insert([
            'migration' => '2025_11_19_120000_update_ledgers_table_structure',
            'batch' => $maxBatch + 1
        ]);
        echo "Migration marked as complete successfully!\n";
    } else {
        echo "Migration already exists in database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
