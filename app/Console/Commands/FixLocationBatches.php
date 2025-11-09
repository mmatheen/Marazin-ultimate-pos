<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixLocationBatches extends Command
{
    protected $signature = 'location:fix-batches {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix duplicates and negative quantities in location_batches table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('=== LOCATION BATCHES FIX ===');
        $this->info('Date: ' . date('Y-m-d H:i:s'));
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        $this->info('============================');
        
        try {
            // 1. Current Status
            $this->info('1. 📊 CURRENT STATUS:');
            $this->line('=====================');
            
            $totalRecords = DB::table('location_batches')->count();
            $negativeCount = DB::table('location_batches')->where('qty', '<', 0)->count();
            
            $duplicatesCount = DB::select("
                SELECT COUNT(*) as count FROM (
                    SELECT batch_id, location_id 
                    FROM location_batches 
                    GROUP BY batch_id, location_id 
                    HAVING COUNT(*) > 1
                ) as dups
            ")[0]->count;
            
            $this->line("Total records: {$totalRecords}");
            $this->line("Negative quantities: {$negativeCount}");
            $this->line("Duplicate combinations: {$duplicatesCount}");
            $this->line('');
            
            if ($negativeCount == 0 && $duplicatesCount == 0) {
                $this->info('🎉 No issues found! Your database is perfect!');
                return 0;
            }
            
            // 2. Fix Duplicates
            if ($duplicatesCount > 0) {
                $this->info('2. 🔧 FIXING DUPLICATES:');
                $this->line('=======================');
                
                $duplicates = DB::select("
                    SELECT batch_id, location_id, COUNT(*) as count 
                    FROM location_batches 
                    GROUP BY batch_id, location_id 
                    HAVING COUNT(*) > 1
                ");
                
                $fixedDuplicates = 0;
                
                foreach ($duplicates as $dup) {
                    $records = DB::table('location_batches')
                        ->select('id', 'qty')
                        ->where('batch_id', $dup->batch_id)
                        ->where('location_id', $dup->location_id)
                        ->orderBy('id')
                        ->get();
                    
                    if (count($records) > 1) {
                        $totalQty = $records->sum('qty');
                        $keepId = $records->first()->id;
                        
                        $this->line("Batch {$dup->batch_id} at Location {$dup->location_id}: Consolidating {$dup->count} records → qty {$totalQty}");
                        
                        if (!$dryRun) {
                            // Update the first record with total quantity
                            DB::table('location_batches')
                                ->where('id', $keepId)
                                ->update(['qty' => $totalQty]);
                            
                            // Delete duplicate records
                            $deleteIds = $records->skip(1)->pluck('id')->toArray();
                            if (!empty($deleteIds)) {
                                DB::table('location_batches')->whereIn('id', $deleteIds)->delete();
                            }
                        }
                        
                        $fixedDuplicates++;
                    }
                }
                
                $this->info("✅ " . ($dryRun ? 'Would fix' : 'Fixed') . " {$fixedDuplicates} duplicate combinations");
                $this->line('');
            }
            
            // 3. Fix Negative Quantities
            if ($negativeCount > 0) {
                $this->info('3. 🔧 FIXING NEGATIVE QUANTITIES:');
                $this->line('================================');
                
                $negatives = DB::table('location_batches')
                    ->select('id', 'batch_id', 'location_id', 'qty')
                    ->where('qty', '<', 0)
                    ->get();
                
                foreach ($negatives as $negative) {
                    $this->line("Batch {$negative->batch_id} at Location {$negative->location_id}: {$negative->qty} → 0");
                    
                    if (!$dryRun) {
                        DB::table('location_batches')
                            ->where('id', $negative->id)
                            ->update(['qty' => 0]);
                    }
                }
                
                $this->info("✅ " . ($dryRun ? 'Would fix' : 'Fixed') . " {$negativeCount} negative quantities");
                $this->line('');
            }
            
            // 4. Special Batch 125 Fix
            $this->info('4. 🎯 BATCH 125 RESTORATION:');
            $this->line('============================');
            
            $batch125Records = DB::table('location_batches')
                ->where('batch_id', 125)
                ->get();
            
            if ($batch125Records->count() > 0) {
                foreach ($batch125Records as $record) {
                    if ($record->location_id == 6) {
                        $this->line("Setting Batch 125 at Location 6 to 4.0000 units");
                        if (!$dryRun) {
                            DB::table('location_batches')
                                ->where('id', $record->id)
                                ->update(['qty' => 4.0000]);
                        }
                    } else {
                        $this->line("Setting Batch 125 at Location {$record->location_id} to 0 units");
                        if (!$dryRun) {
                            DB::table('location_batches')
                                ->where('id', $record->id)
                                ->update(['qty' => 0]);
                        }
                    }
                }
            } else {
                $this->line('Batch 125 not found');
            }
            $this->line('');
            
            // 5. Final Verification
            if (!$dryRun) {
                $this->info('5. ✅ VERIFICATION:');
                $this->line('==================');
                
                $finalNegative = DB::table('location_batches')->where('qty', '<', 0)->count();
                $finalDuplicates = DB::select("
                    SELECT COUNT(*) as count FROM (
                        SELECT batch_id, location_id 
                        FROM location_batches 
                        GROUP BY batch_id, location_id 
                        HAVING COUNT(*) > 1
                    ) as dups
                ")[0]->count;
                
                $this->line("Final negative quantities: {$finalNegative}");
                $this->line("Final duplicate combinations: {$finalDuplicates}");
                
                if ($finalNegative == 0 && $finalDuplicates == 0) {
                    $this->info('🎉 SUCCESS: All issues fixed!');
                    $this->info('✅ Your POS system is ready!');
                } else {
                    $this->error('⚠️  Some issues remain - please check manually');
                }
            } else {
                $this->warn('🔍 DRY RUN COMPLETED - No changes were made');
                $this->info('💡 Run without --dry-run to apply fixes');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}