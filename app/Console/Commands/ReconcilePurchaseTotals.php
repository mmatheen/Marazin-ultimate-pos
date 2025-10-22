<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcilePurchaseTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchase:reconcile-totals {--fix : Actually fix the mismatches (default is dry-run)} {--purchase-id= : Check specific purchase ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile purchase totals - shows calculation breakdown and optionally fixes mismatches';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = !$this->option('fix');
        $specificPurchaseId = $this->option('purchase-id');

        $this->info('=================================================');
        $this->info('Purchase Total Reconciliation');
        $this->info('=================================================');
        $this->info('Mode: ' . ($isDryRun ? 'DRY-RUN (no changes will be made)' : 'FIX MODE (will update mismatches)'));
        $this->info('');

        // Query purchases
        $query = Purchase::with(['purchaseProducts']);
        
        if ($specificPurchaseId) {
            $query->where('id', $specificPurchaseId);
        }
        
        $purchases = $query->orderBy('id', 'desc')->get();

        if ($purchases->isEmpty()) {
            $this->warn('No purchases found.');
            return 0;
        }

        $this->info("Checking {$purchases->count()} purchase(s)...\n");

        $totalChecked = 0;
        $totalMismatches = 0;
        $totalFixed = 0;
        $mismatches = [];

        foreach ($purchases as $purchase) {
            $totalChecked++;
            
            // Calculate server-side totals
            $calculation = $this->calculatePurchaseTotal($purchase);
            
            $storedFinalTotal = (float) $purchase->final_total;
            $calculatedFinalTotal = $calculation['final_total'];
            
            $difference = abs($storedFinalTotal - $calculatedFinalTotal);
            
            // Consider mismatch if difference > 0.5 (to account for minor rounding)
            if ($difference > 0.5) {
                $totalMismatches++;
                
                $mismatches[] = [
                    'purchase' => $purchase,
                    'calculation' => $calculation,
                    'difference' => $difference,
                ];
                
                // Display detailed breakdown
                $this->displayCalculationBreakdown($purchase, $calculation, $storedFinalTotal, $difference);
                
                // Fix if requested
                if (!$isDryRun) {
                    try {
                        $purchase->update([
                            'total' => $calculation['product_total'],
                            'discount_type' => $purchase->discount_type,
                            'discount_amount' => $purchase->discount_amount,
                            'final_total' => $calculation['final_total'],
                        ]);
                        
                        $totalFixed++;
                        $this->info("  ✓ FIXED: Updated final_total from {$storedFinalTotal} to {$calculatedFinalTotal}\n");
                        
                        Log::info('Purchase total reconciliation - fixed', [
                            'purchase_id' => $purchase->id,
                            'reference_no' => $purchase->reference_no,
                            'old_final_total' => $storedFinalTotal,
                            'new_final_total' => $calculatedFinalTotal,
                            'difference' => $difference,
                        ]);
                    } catch (\Exception $e) {
                        $this->error("  ✗ ERROR fixing purchase {$purchase->id}: " . $e->getMessage());
                    }
                } else {
                    $this->warn("  → Would update final_total from {$storedFinalTotal} to {$calculatedFinalTotal}\n");
                }
            }
        }

        // Summary
        $this->info('=================================================');
        $this->info('Summary');
        $this->info('=================================================');
        $this->info("Total purchases checked: {$totalChecked}");
        $this->info("Mismatches found: {$totalMismatches}");
        
        if ($isDryRun) {
            $this->warn("No changes made (dry-run mode).");
            if ($totalMismatches > 0) {
                $this->info("\nTo fix these mismatches, run:");
                $this->line("  php artisan purchase:reconcile-totals --fix");
            }
        } else {
            $this->info("Purchases fixed: {$totalFixed}");
        }
        
        $this->info('=================================================');

        return 0;
    }

    /**
     * Calculate purchase total using server-side logic
     */
    private function calculatePurchaseTotal(Purchase $purchase)
    {
        // Sum all product totals
        $productTotal = (float) $purchase->purchaseProducts()->sum(DB::raw('COALESCE(total, 0)'));
        
        // Get discount info
        $discountType = $purchase->discount_type;
        $discountValue = (float) ($purchase->discount_amount ?? 0);
        
        // Calculate discount amount
        $discountAmount = 0.0;
        if ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        } elseif ($discountType === 'percent' || $discountType === 'percentage') {
            $discountAmount = ($productTotal * $discountValue) / 100.0;
        }
        
        // Calculate tax (if any tax type is stored - extend this based on your needs)
        // Note: Purchase model doesn't have tax_type field by default, so this is 0 for now
        $taxAmount = 0.0;
        // If you add tax_type field to purchases table, you can calculate it here
        
        // Calculate final total
        $finalTotal = $productTotal - $discountAmount + $taxAmount;
        
        return [
            'product_total' => $productTotal,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'final_total' => $finalTotal,
        ];
    }

    /**
     * Display detailed calculation breakdown
     */
    private function displayCalculationBreakdown(Purchase $purchase, array $calculation, float $storedTotal, float $difference)
    {
        $this->line('');
        $this->info("Purchase ID: {$purchase->id} | Reference: {$purchase->reference_no}");
        $this->line('─────────────────────────────────────────────────');
        
        // Show product-level breakdown
        $products = $purchase->purchaseProducts;
        $this->line("Products ({$products->count()} items):");
        
        foreach ($products as $index => $pp) {
            $productName = $pp->product ? $pp->product->product_name : "Product ID {$pp->product_id}";
            $this->line(sprintf(
                "  %d. %-30s | Qty: %-8s | Unit: %-10s | Total: %s",
                $index + 1,
                substr($productName, 0, 30),
                number_format($pp->quantity, 2),
                number_format($pp->unit_cost, 2),
                number_format($pp->total, 2)
            ));
        }
        
        $this->line('');
        $this->line('Calculation Breakdown:');
        $this->line('─────────────────────────────────────────────────');
        $this->line(sprintf("  Product Total (Sum):       %15s", number_format($calculation['product_total'], 2)));
        
        if ($calculation['discount_amount'] > 0) {
            $discountLabel = $calculation['discount_type'] === 'fixed' 
                ? "Discount (Fixed)" 
                : "Discount ({$calculation['discount_value']}%)";
            $this->line(sprintf("  %s:     -%14s", $discountLabel, number_format($calculation['discount_amount'], 2)));
        }
        
        if ($calculation['tax_amount'] > 0) {
            $this->line(sprintf("  Tax:                       +%14s", number_format($calculation['tax_amount'], 2)));
        }
        
        $this->line('─────────────────────────────────────────────────');
        $this->line(sprintf("  CALCULATED Final Total:    %15s", number_format($calculation['final_total'], 2)));
        $this->line('');
        $this->error(sprintf("  STORED Final Total:        %15s", number_format($storedTotal, 2)));
        $this->error(sprintf("  DIFFERENCE:                %15s", number_format($difference, 2)));
        $this->line('─────────────────────────────────────────────────');
    }
}