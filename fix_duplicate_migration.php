<?php
// Fix duplicate audit migration
// Run this on production server: php fix_duplicate_migration.php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Checking duplicate audit migration...\n";
    
    // Check if the migration already exists in migrations table
    $exists = DB::table('migrations')
        ->where('migration', '2025_11_12_144434_create_audits_table')
        ->exists();
        
    if ($exists) {
        echo "✅ Migration already marked as completed\n";
    } else {
        echo "❌ Migration not found in migrations table\n";
        echo "Adding migration to migrations table...\n";
        
        // Get the latest batch number
        $latestBatch = DB::table('migrations')->max('batch') ?: 0;
        
        // Insert the migration record
        DB::table('migrations')->insert([
            'migration' => '2025_11_12_144434_create_audits_table',
            'batch' => $latestBatch + 1
        ]);
        
        echo "✅ Successfully marked migration as completed\n";
    }
    
    echo "\nVerifying audits table exists...\n";
    if (DB::select("SHOW TABLES LIKE 'audits'")) {
        echo "✅ Audits table exists\n";
    } else {
        echo "❌ Audits table missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nDone! You can now run 'php artisan migrate:status' to verify.\n";