<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckLocationBatches extends Command
{
    protected $signature = 'location:check-batches';
    protected $description = 'Check location_batches table for duplicates and negative quantities';

    public function handle()
    {
        $this->info('=== LOCATION BATCHES CHECK ===');
        $this->info('Date: ' . date('Y-m-d H:i:s'));
        $this->info('==============================');
        
        try {
            // 1. Basic Statistics
            $this->info('1. 📊 BASIC STATISTICS:');
            $this->line('=======================');
            
            $totalRecords = DB::table('location_batches')->count();
            $uniqueBatches = DB::table('location_batches')->distinct('batch_id')->count();
            $uniqueLocations = DB::table('location_batches')->distinct('location_id')->count();
            
            $this->line("Total records: {$totalRecords}");
            $this->line("Unique batches: {$uniqueBatches}");
            $this->line("Unique locations: {$uniqueLocations}");
            $this->line('');
            
            // 2. Check Negative Quantities
            $this->info('2. ❌ NEGATIVE QUANTITIES:');
            $this->line('=========================');
            
            $negativeCount = DB::table('location_batches')->where('qty', '<', 0)->count();
            
            if ($negativeCount > 0) {
                $this->error("❌ FOUND {$negativeCount} NEGATIVE QUANTITIES:");
                
                $negatives = DB::table('location_batches')
                    ->select('batch_id', 'location_id', 'qty')
                    ->where('qty', '<', 0)
                    ->orderBy('qty', 'asc')
                    ->limit(10)
                    ->get();
                
                foreach ($negatives as $negative) {
                    $this->line("- Batch {$negative->batch_id}, Location {$negative->location_id}: {$negative->qty} units");
                }
                
                if ($negativeCount > 10) {
                    $this->line("... and " . ($negativeCount - 10) . " more");
                }
            } else {
                $this->info('✅ No negative quantities found!');
            }
            $this->line('');
            
            // 3. Check Duplicates
            $this->info('3. 🔄 DUPLICATE COMBINATIONS:');
            $this->line('============================');
            
            $duplicates = DB::select("
                SELECT batch_id, location_id, COUNT(*) as count 
                FROM location_batches 
                GROUP BY batch_id, location_id 
                HAVING COUNT(*) > 1
            ");
            
            if (count($duplicates) > 0) {
                $this->error("❌ FOUND " . count($duplicates) . " DUPLICATE COMBINATIONS:");
                
                foreach (array_slice($duplicates, 0, 10) as $dup) {
                    $this->line("- Batch {$dup->batch_id} at Location {$dup->location_id}: {$dup->count} records");
                }
                
                if (count($duplicates) > 10) {
                    $this->line("... and " . (count($duplicates) - 10) . " more");
                }
            } else {
                $this->info('✅ No duplicate combinations found!');
            }
            $this->line('');
            
            // 4. Summary
            $this->info('4. 📋 SUMMARY:');
            $this->line('==============');
            $this->line("Total records: {$totalRecords}");
            $this->line("Negative quantities: {$negativeCount}");
            $this->line("Duplicate combinations: " . count($duplicates));
            
            if ($negativeCount == 0 && count($duplicates) == 0) {
                $this->info('🎉 PERFECT: No issues found!');
                $this->info('✅ Your location_batches table is healthy!');
            } else {
                $this->warn('⚠️  Issues found that need fixing!');
                $this->info('💡 Run: php artisan location:fix-batches');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}