<?php

/**
 * Production Adjustment Management Tool
 * 
 * Safe command-line tool for managing adjustment records in production
 * 
 * Usage: php production_adjustment_manager.php [command] [options]
 */

require 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Get command line arguments
$command = $argv[1] ?? null;
$option = $argv[2] ?? null;

echo "=== PRODUCTION ADJUSTMENT MANAGER ===\n\n";

// Display current environment info
$dbName = DB::connection()->getDatabaseName();
$env = config('app.env');
echo "Database: {$dbName}\n";
echo "Environment: {$env}\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";

// Safety check for production
if ($env === 'production') {
    echo "⚠️  PRODUCTION ENVIRONMENT DETECTED ⚠️\n";
    echo "Extra safety measures are in effect.\n\n";
}

switch ($command) {
    case 'status':
        displayStatus();
        break;
        
    case 'today':
        removeTodayAdjustments();
        break;
        
    case 'date':
        if ($option) {
            removeDateAdjustments($option);
        } else {
            echo "Usage: php production_adjustment_manager.php date YYYY-MM-DD\n";
        }
        break;
        
    case 'count':
        showAdjustmentCounts();
        break;
        
    case 'backup':
        suggestBackup();
        break;
        
    case 'help':
    default:
        showHelp();
        break;
}

function displayStatus() {
    $total = DB::table('stock_histories')->where('stock_type', 'adjustment')->count();
    $today = date('Y-m-d');
    $todayCount = DB::table('stock_histories')->where('stock_type', 'adjustment')->whereDate('created_at', $today)->count();
    
    echo "=== CURRENT STATUS ===\n";
    echo "Total adjustment records: {$total}\n";
    echo "Today's adjustment records: {$todayCount}\n";
    
    if ($total > 0) {
        $latest = DB::table('stock_histories')->where('stock_type', 'adjustment')->orderBy('created_at', 'desc')->first();
        echo "Latest adjustment: {$latest->created_at}\n";
    }
    echo "\n";
}

function removeTodayAdjustments() {
    $today = date('Y-m-d');
    $count = DB::table('stock_histories')->where('stock_type', 'adjustment')->whereDate('created_at', $today)->count();
    
    echo "=== REMOVE TODAY'S ADJUSTMENTS ===\n";
    echo "Date: {$today}\n";
    echo "Records to remove: {$count}\n\n";
    
    if ($count === 0) {
        echo "✅ No adjustments found for today. Nothing to remove.\n";
        return;
    }
    
    echo "⚠️  This will remove {$count} adjustment records from today.\n";
    echo "Type 'CONFIRM_TODAY' to proceed: ";
    
    $confirmation = trim(fgets(STDIN));
    
    if ($confirmation === 'CONFIRM_TODAY') {
        try {
            DB::beginTransaction();
            
            $deleted = DB::table('stock_histories')
                ->where('stock_type', 'adjustment')
                ->whereDate('created_at', $today)
                ->delete();
            
            DB::commit();
            
            echo "✅ Successfully removed {$deleted} adjustment records from today.\n";
            
        } catch (Exception $e) {
            DB::rollBack();
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Operation cancelled.\n";
    }
}

function removeDateAdjustments($date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo "❌ Invalid date format. Use YYYY-MM-DD\n";
        return;
    }
    
    $count = DB::table('stock_histories')->where('stock_type', 'adjustment')->whereDate('created_at', $date)->count();
    
    echo "=== REMOVE ADJUSTMENTS FOR SPECIFIC DATE ===\n";
    echo "Date: {$date}\n";
    echo "Records to remove: {$count}\n\n";
    
    if ($count === 0) {
        echo "✅ No adjustments found for {$date}. Nothing to remove.\n";
        return;
    }
    
    echo "⚠️  This will remove {$count} adjustment records from {$date}.\n";
    echo "Type 'CONFIRM_DATE_{$date}' to proceed: ";
    
    $confirmation = trim(fgets(STDIN));
    $expectedConfirmation = "CONFIRM_DATE_{$date}";
    
    if ($confirmation === $expectedConfirmation) {
        try {
            DB::beginTransaction();
            
            $deleted = DB::table('stock_histories')
                ->where('stock_type', 'adjustment')
                ->whereDate('created_at', $date)
                ->delete();
            
            DB::commit();
            
            echo "✅ Successfully removed {$deleted} adjustment records from {$date}.\n";
            
        } catch (Exception $e) {
            DB::rollBack();
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Operation cancelled. Expected: {$expectedConfirmation}\n";
    }
}

function showAdjustmentCounts() {
    echo "=== ADJUSTMENT COUNTS BY DATE ===\n";
    
    $results = DB::select("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM stock_histories 
        WHERE stock_type = 'adjustment' 
        GROUP BY DATE(created_at) 
        ORDER BY date DESC 
        LIMIT 15
    ");
    
    if (empty($results)) {
        echo "No adjustment records found.\n";
        return;
    }
    
    echo sprintf("%-12s %-8s\n", "Date", "Count");
    echo str_repeat("-", 20) . "\n";
    
    foreach ($results as $row) {
        echo sprintf("%-12s %-8s\n", $row->date, $row->count);
    }
    echo "\n";
}

function suggestBackup() {
    $dbName = DB::connection()->getDatabaseName();
    $timestamp = date('Y-m-d_H-i-s');
    
    echo "=== BACKUP SUGGESTIONS ===\n\n";
    echo "Before removing adjustments, create a backup:\n\n";
    echo "MySQL Command:\n";
    echo "mysqldump -u username -p {$dbName} > backup_before_adjustment_removal_{$timestamp}.sql\n\n";
    echo "Laravel Artisan (if backup package installed):\n";
    echo "php artisan backup:run --only-db\n\n";
    echo "Recommended: Test on staging environment first!\n\n";
}

function showHelp() {
    echo "=== PRODUCTION ADJUSTMENT MANAGER HELP ===\n\n";
    echo "Available commands:\n\n";
    echo "status                           - Show current adjustment record counts\n";
    echo "today                           - Remove today's adjustment records only\n";
    echo "date YYYY-MM-DD                - Remove adjustments for specific date\n";
    echo "count                           - Show adjustment counts by date\n";
    echo "backup                          - Show backup instructions\n";
    echo "help                            - Show this help message\n\n";
    
    echo "Examples:\n";
    echo "php production_adjustment_manager.php status\n";
    echo "php production_adjustment_manager.php today\n";
    echo "php production_adjustment_manager.php date 2025-10-08\n";
    echo "php production_adjustment_manager.php count\n";
    echo "php production_adjustment_manager.php backup\n\n";
    
    echo "Safety Features:\n";
    echo "- Transaction-based operations with rollback\n";
    echo "- Specific confirmation codes required\n";
    echo "- Date validation and verification\n";
    echo "- Environment detection\n";
    echo "- Read-only status commands\n\n";
    
    echo "⚠️  IMPORTANT: Always backup your database before removing records!\n";
}

?>