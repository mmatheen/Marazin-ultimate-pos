<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairSalesSubtotals extends Command
{
    protected $signature = 'sales:repair-subtotals {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Recalculate and fix sales subtotals and final_totals from sales_products';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('🔍 Scanning for sales with incorrect subtotals...');

        $sales = DB::table('sales')->get();
        $fixedCount = 0;
        $errors = [];

        foreach ($sales as $sale) {
            // Calculate correct subtotal from sales_products
            $correctSubtotal = DB::table('sales_products')
                ->where('sale_id', $sale->id)
                ->sum(DB::raw('quantity * price'));

            // Calculate correct final_total
            $discount = $sale->discount_amount ?? 0;
            if ($sale->discount_type === 'percentage') {
                $correctFinalTotal = $correctSubtotal - ($correctSubtotal * $discount / 100);
            } else {
                $correctFinalTotal = $correctSubtotal - $discount;
            }

            // Add shipping
            $correctFinalTotal += ($sale->shipping_charges ?? 0);

            // Calculate correct total_due
            $correctTotalDue = $correctFinalTotal - $sale->total_paid;

            // Check if needs fixing
            $needsFix = (abs($sale->subtotal - $correctSubtotal) > 0.01) ||
                       (abs($sale->final_total - $correctFinalTotal) > 0.01) ||
                       (abs($sale->total_due - $correctTotalDue) > 0.01);

            if ($needsFix) {
                $this->warn("❌ Sale ID {$sale->id} (Invoice: {$sale->invoice_no}):");
                $this->line("   Subtotal: {$sale->subtotal} → {$correctSubtotal}");
                $this->line("   Final Total: {$sale->final_total} → {$correctFinalTotal}");
                $this->line("   Total Due: {$sale->total_due} → {$correctTotalDue}");

                if (!$isDryRun) {
                    try {
                        DB::table('sales')
                            ->where('id', $sale->id)
                            ->update([
                                'subtotal' => $correctSubtotal,
                                'final_total' => $correctFinalTotal,
                                'total_due' => $correctTotalDue,
                                'updated_at' => now(),
                            ]);
                        $this->info("   ✅ Fixed!");
                        $fixedCount++;
                    } catch (\Exception $e) {
                        $this->error("   ❌ Error: " . $e->getMessage());
                        $errors[] = "Sale {$sale->id}: " . $e->getMessage();
                    }
                }
            }
        }

        if ($isDryRun) {
            $this->info("\n🔍 Dry run completed. Found issues in {$fixedCount} sales.");
            $this->info("Run without --dry-run to fix these sales.");
        } else {
            $this->info("\n✅ Repair completed!");
            $this->info("Fixed {$fixedCount} sales records.");

            if (count($errors) > 0) {
                $this->error("\n⚠️  Errors encountered:");
                foreach ($errors as $error) {
                    $this->error("  - {$error}");
                }
            }
        }

        return 0;
    }
}
