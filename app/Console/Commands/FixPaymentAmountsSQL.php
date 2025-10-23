<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPaymentAmountsSQL extends Command
{
    protected $signature = 'fix:payments-sql {--dry-run : Preview changes without applying}';
    protected $description = 'Fix payment amounts using direct SQL queries';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Finding payments that need fixing...');
        $this->newLine();

        // Find all payments where amount doesn't match the sale's final_total
        $sql = "
            SELECT 
                s.id as sale_id,
                s.invoice_no,
                s.subtotal,
                s.discount_amount,
                s.final_total,
                p.id as payment_id,
                p.amount as payment_amount,
                p.payment_method
            FROM sales s
            INNER JOIN payments p ON p.reference_id = s.id 
                AND p.payment_type = 'sale'
            WHERE s.discount_amount > 0
                AND ABS(p.amount - s.subtotal) < 0.01
                AND ABS(s.subtotal - s.final_total) > 0.01
            ORDER BY s.id DESC
        ";

        $records = DB::select($sql);

        if (empty($records)) {
            $this->warn('No payments found that need fixing.');
            return Command::SUCCESS;
        }

        $this->info("Found " . count($records) . " payments to fix:");
        $this->newLine();

        $totalFixed = 0;

        foreach ($records as $record) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Invoice: {$record->invoice_no} (Sale ID: {$record->sale_id})");
            $this->info("  Subtotal: " . number_format($record->subtotal, 2));
            $this->info("  Discount: " . number_format($record->discount_amount, 2));
            $this->info("  Final Total: " . number_format($record->final_total, 2));
            $this->info("  Payment ID: {$record->payment_id} ({$record->payment_method})");
            $this->error("  ❌ Current Amount: " . number_format($record->payment_amount, 2));
            $this->info("  ✅ Should Be: " . number_format($record->final_total, 2));
            
            if (!$dryRun) {
                DB::table('payments')
                    ->where('id', $record->payment_id)
                    ->update(['amount' => $record->final_total]);
                    
                $this->info("  ✓ FIXED!");
                $totalFixed++;
            } else {
                $this->warn("  ⚠️  WOULD FIX (dry-run mode)");
            }
            
            $this->newLine();
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('SUMMARY');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("Total payments found: " . count($records));
        
        if ($dryRun) {
            $this->warn("Total that would be fixed: " . count($records));
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. Run without --dry-run to apply changes:');
            $this->warn('   php artisan fix:payments-sql');
        } else {
            $this->info("Total fixed: {$totalFixed}");
            $this->newLine();
            $this->info('✅ All payment amounts have been corrected!');
            $this->info('💡 Tip: Clear cache with: php artisan cache:clear');
        }

        return Command::SUCCESS;
    }
}
