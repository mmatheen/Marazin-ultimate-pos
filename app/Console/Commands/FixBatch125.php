<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixBatch125 extends Command
{
    protected $signature = 'location:fix-batch-125 {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix Batch 125 to have exactly 4 units at Location 6 and 0 at other locations';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('=== BATCH 125 FIX ===');
        $this->info('Date: ' . date('Y-m-d H:i:s'));
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        $this->info('=====================');
        
        try {
            // 1. Current Status
            $this->info('1. 📊 CURRENT BATCH 125 STATUS:');
            $this->line('===============================');
            
            $batch125Records = DB::table('location_batches')
                ->select('id', 'location_id', 'qty')
                ->where('batch_id', 125)
                ->orderBy('location_id')
                ->get();
            
            if ($batch125Records->count() == 0) {
                $this->error('❌ Batch 125 not found!');
                return 1;
            }
            
            foreach ($batch125Records as $record) {
                $status = $record->qty > 0 ? '✅' : ($record->qty < 0 ? '❌' : '⚪');
                $this->line("{$status} Location {$record->location_id}: {$record->qty} units");
            }
            $this->line('');
            
            // 2. Check for duplicates first
            $duplicates = DB::select("
                SELECT location_id, COUNT(*) as count 
                FROM location_batches 
                WHERE batch_id = 125
                GROUP BY location_id 
                HAVING COUNT(*) > 1
            ");
            
            if (count($duplicates) > 0) {
                $this->info('2. 🔧 FIXING DUPLICATES FIRST:');
                $this->line('=============================');
                
                foreach ($duplicates as $dup) {
                    $records = DB::table('location_batches')
                        ->select('id', 'qty')
                        ->where('batch_id', 125)
                        ->where('location_id', $dup->location_id)
                        ->orderBy('id')
                        ->get();
                    
                    $totalQty = $records->sum('qty');
                    $keepId = $records->first()->id;
                    
                    $this->line("Location {$dup->location_id}: Consolidating {$dup->count} records → total qty {$totalQty}");
                    
                    if (!$dryRun) {
                        // Update first record with total quantity
                        DB::table('location_batches')
                            ->where('id', $keepId)
                            ->update(['qty' => $totalQty]);
                        
                        // Delete duplicate records
                        $deleteIds = $records->skip(1)->pluck('id')->toArray();
                        if (!empty($deleteIds)) {
                            DB::table('location_batches')->whereIn('id', $deleteIds)->delete();
                        }
                    }
                }
                $this->line('');
                
                // Refresh records after duplicate fix
                $batch125Records = DB::table('location_batches')
                    ->select('id', 'location_id', 'qty')
                    ->where('batch_id', 125)
                    ->orderBy('location_id')
                    ->get();
            }
            
            // 3. Set correct quantities
            $this->info('3. 🎯 SETTING CORRECT QUANTITIES:');
            $this->line('=================================');
            
            foreach ($batch125Records as $record) {
                if ($record->location_id == 6) {
                    // Location 6 should have exactly 4 units
                    if ($record->qty != 4) {
                        $this->line("Location 6: {$record->qty} → 4.0000 units ✅");
                        if (!$dryRun) {
                            DB::table('location_batches')
                                ->where('id', $record->id)
                                ->update(['qty' => 4.0000]);
                        }
                    } else {
                        $this->line("Location 6: Already has 4.0000 units ✅");
                    }
                } else {
                    // All other locations should have 0 units
                    if ($record->qty != 0) {
                        $this->line("Location {$record->location_id}: {$record->qty} → 0.0000 units");
                        if (!$dryRun) {
                            DB::table('location_batches')
                                ->where('id', $record->id)
                                ->update(['qty' => 0.0000]);
                        }
                    } else {
                        $this->line("Location {$record->location_id}: Already has 0.0000 units ⚪");
                    }
                }
            }
            $this->line('');
            
            // 4. Final Verification
            if (!$dryRun) {
                $this->info('4. ✅ FINAL VERIFICATION:');
                $this->line('========================');
                
                $finalRecords = DB::table('location_batches')
                    ->select('location_id', 'qty')
                    ->where('batch_id', 125)
                    ->orderBy('location_id')
                    ->get();
                
                $totalQty = 0;
                $correct = true;
                
                foreach ($finalRecords as $record) {
                    $status = '✅';
                    if ($record->location_id == 6 && $record->qty != 4) {
                        $status = '❌';
                        $correct = false;
                    } elseif ($record->location_id != 6 && $record->qty != 0) {
                        $status = '⚠️';
                        if ($record->qty < 0) {
                            $status = '❌';
                            $correct = false;
                        }
                    }
                    
                    $this->line("{$status} Location {$record->location_id}: {$record->qty} units");
                    $totalQty += $record->qty;
                }
                
                $this->line('');
                $this->line("📊 FINAL SUMMARY:");
                $this->line("Total Batch 125 quantity: {$totalQty} units");
                
                if ($correct && $totalQty == 4) {
                    $this->info('🎉 SUCCESS: Batch 125 fixed perfectly!');
                    $this->info('✅ Location 6 has exactly 4 units');
                    $this->info('✅ All other locations have 0 units');
                    $this->info('✅ Ready for POS sales!');
                } else {
                    $this->error('⚠️  Something may still need attention');
                }
                
            } else {
                $this->warn('🔍 DRY RUN COMPLETED - No changes were made');
                $this->info('💡 Run without --dry-run to apply fixes:');
                $this->line('   php artisan location:fix-batch-125');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}