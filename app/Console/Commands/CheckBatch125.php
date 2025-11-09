<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckBatch125 extends Command
{
    protected $signature = 'location:check-batch-125';
    protected $description = 'Check and show current status of Batch 125 across all locations';

    public function handle()
    {
        $this->info('=== BATCH 125 STATUS CHECK ===');
        $this->info('Date: ' . date('Y-m-d H:i:s'));
        $this->info('==============================');
        
        try {
            // Get all Batch 125 records
            $batch125Records = DB::table('location_batches')
                ->select('id', 'location_id', 'qty', 'created_at', 'updated_at')
                ->where('batch_id', 125)
                ->orderBy('location_id')
                ->get();
            
            if ($batch125Records->count() > 0) {
                $this->info('📦 BATCH 125 CURRENT STATUS:');
                $this->line('============================');
                
                $totalQty = 0;
                foreach ($batch125Records as $record) {
                    $status = $record->qty > 0 ? '✅' : ($record->qty < 0 ? '❌' : '⚪');
                    $this->line("{$status} Location {$record->location_id}: {$record->qty} units (Record ID: {$record->id})");
                    $totalQty += $record->qty;
                }
                
                $this->line('');
                $this->line("📊 SUMMARY:");
                $this->line("Total Batch 125 quantity: {$totalQty} units");
                $this->line("Records found: " . $batch125Records->count());
                
                // Check if it needs fixing
                $needsFix = false;
                $issues = [];
                
                foreach ($batch125Records as $record) {
                    if ($record->location_id == 6 && $record->qty != 4) {
                        $needsFix = true;
                        $issues[] = "Location 6 should have 4 units (currently has {$record->qty})";
                    }
                    if ($record->location_id != 6 && $record->qty < 0) {
                        $needsFix = true;
                        $issues[] = "Location {$record->location_id} has negative quantity ({$record->qty})";
                    }
                }
                
                // Check for duplicates
                $duplicates = DB::select("
                    SELECT location_id, COUNT(*) as count 
                    FROM location_batches 
                    WHERE batch_id = 125
                    GROUP BY location_id 
                    HAVING COUNT(*) > 1
                ");
                
                if (count($duplicates) > 0) {
                    $needsFix = true;
                    foreach ($duplicates as $dup) {
                        $issues[] = "Location {$dup->location_id} has {$dup->count} duplicate records";
                    }
                }
                
                $this->line('');
                if ($needsFix) {
                    $this->warn('⚠️  BATCH 125 NEEDS FIXING:');
                    foreach ($issues as $issue) {
                        $this->line("- {$issue}");
                    }
                    $this->line('');
                    $this->info('💡 RECOMMENDED ACTIONS:');
                    $this->line('1. Run: php artisan location:fix-batch-125 --dry-run (preview)');
                    $this->line('2. Run: php artisan location:fix-batch-125 (apply fix)');
                } else {
                    $this->info('🎉 BATCH 125 IS PERFECT!');
                    $this->info('✅ Location 6 has exactly 4 units');
                    $this->info('✅ No negative quantities');
                    $this->info('✅ No duplicates');
                }
                
            } else {
                $this->error('❌ Batch 125 not found in location_batches table');
                $this->line('This could indicate a data integrity issue.');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}