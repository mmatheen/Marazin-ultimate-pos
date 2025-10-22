<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class ReconcileAllPurchases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchase:reconcile-all {--fix : Actually fix the purchases (default is dry-run)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile all purchase totals and final_totals based on products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fix = $this->option('fix');
        
        $this->info('=== PURCHASE RECONCILIATION ===');
        $this->info('Mode: ' . ($fix ? 'LIVE UPDATE' : 'DRY RUN (no changes)'));
        $this->newLine();
        
        $purchases = Purchase::with('purchaseProducts')->get();
        $totalPurchases = $purchases->count();
        $fixedCount = 0;
        
        $this->info("Found {$totalPurchases} purchases to check");
        $this->newLine();
        
        $bar = $this->output->createProgressBar($totalPurchases);
        $bar->start();
        
        foreach ($purchases as $purchase) {
            // Calculate correct total from products
            $productsTotal = 0;
            
            foreach ($purchase->purchaseProducts as $product) {
                // If discount_percent exists and price is set, recalculate
                if ($product->price > 0 && $product->discount_percent > 0) {
                    $discountAmount = $product->price * ($product->discount_percent / 100);
                    $unitCostAfterDiscount = $product->price - $discountAmount;
                    $productTotal = $unitCostAfterDiscount * $product->quantity;
                } else {
                    // No discount or old data, use existing unit_cost
                    $productTotal = $product->unit_cost * $product->quantity;
                }
                
                $productsTotal += $productTotal;
            }
            
            // Apply purchase-level discount
            $discountAmount = 0;
            if ($purchase->discount_type === 'fixed') {
                $discountAmount = $purchase->discount_amount ?? 0;
            } elseif ($purchase->discount_type === 'percent') {
                $discountAmount = ($productsTotal * ($purchase->discount_amount ?? 0)) / 100;
            }
            
            // Apply tax
            $taxAmount = 0;
            if ($purchase->tax_type === 'vat10' || $purchase->tax_type === 'cgst10') {
                $taxAmount = ($productsTotal - $discountAmount) * 0.10;
            }
            
            $calculatedFinalTotal = $productsTotal - $discountAmount + $taxAmount;
            
            // Check if needs fixing
            $needsFixing = false;
            if (abs($purchase->total - $productsTotal) > 0.01) {
                $needsFixing = true;
            }
            
            if (abs($purchase->final_total - $calculatedFinalTotal) > 0.01) {
                $needsFixing = true;
            }
            
            if ($needsFixing) {
                if ($fix) {
                    $purchase->update([
                        'total' => $productsTotal,
                        'final_total' => $calculatedFinalTotal,
                    ]);
                }
                $fixedCount++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info('=== SUMMARY ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total purchases checked', $totalPurchases],
                ['Purchases needing fix', $fixedCount],
                ['Mode', $fix ? 'LIVE UPDATE' : 'DRY RUN'],
            ]
        );
        
        if (!$fix && $fixedCount > 0) {
            $this->newLine();
            $this->warn('⚠️  To actually apply these fixes, run:');
            $this->info('php artisan purchase:reconcile-all --fix');
        } elseif ($fix && $fixedCount > 0) {
            $this->newLine();
            $this->info("✅ Fixed {$fixedCount} purchases!");
        } else {
            $this->newLine();
            $this->info('✅ All purchases are already correct!');
        }
        
        return 0;
    }
}
