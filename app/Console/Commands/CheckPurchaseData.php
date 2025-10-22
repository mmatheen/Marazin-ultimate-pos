<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPurchaseData extends Command
{
    protected $signature = 'purchase:check-data {--purchase-id= : Specific purchase ID to check}';
    protected $description = 'Check purchase data for calculation issues';

    public function handle()
    {
        $this->info('=================================================');
        $this->info('Purchase Data Diagnostic Check');
        $this->info('=================================================');
        $this->newLine();

        $purchaseId = $this->option('purchase-id');
        
        if ($purchaseId) {
            $purchases = Purchase::with('purchaseProducts.product')->where('id', $purchaseId)->get();
        } else {
            $purchases = Purchase::with('purchaseProducts.product')->orderBy('id', 'desc')->limit(5)->get();
        }

        if ($purchases->isEmpty()) {
            $this->warn('No purchases found in database.');
            return 0;
        }

        $this->info("Checking " . $purchases->count() . " purchase(s)...\n");

        foreach ($purchases as $purchase) {
            $this->line('─────────────────────────────────────────────────');
            $this->info("Purchase ID: {$purchase->id} | Reference: {$purchase->reference_no}");
            $this->line("Date: {$purchase->purchase_date} | Status: {$purchase->purchasing_status}");
            $this->newLine();
            
            // Stored values
            $this->line("STORED VALUES:");
            $this->line("  Total: " . number_format($purchase->total, 2));
            $this->line("  Discount Type: " . ($purchase->discount_type ?: 'none'));
            $this->line("  Discount Amount: " . number_format($purchase->discount_amount ?? 0, 2));
            $this->line("  Final Total: " . number_format($purchase->final_total, 2));
            $this->newLine();
            
            // Products
            $productCount = $purchase->purchaseProducts->count();
            $this->line("PRODUCTS ({$productCount} items):");
            
            $calculatedSum = 0;
            foreach ($purchase->purchaseProducts as $index => $pp) {
                $productName = $pp->product ? $pp->product->product_name : "Product ID {$pp->product_id}";
                $calculatedSum += (float) $pp->total;
                
                $this->line(sprintf(
                    "  %d. %-40s | Qty: %8s | Unit: %10s | Total: %12s",
                    $index + 1,
                    substr($productName, 0, 40),
                    number_format($pp->quantity, 2),
                    number_format($pp->unit_cost, 2),
                    number_format($pp->total, 2)
                ));
            }
            
            $this->newLine();
            $this->line("CALCULATED VALUES:");
            $this->line("  Sum of Product Totals: " . number_format($calculatedSum, 2));
            
            // Apply discount
            $discountAmount = 0;
            if ($purchase->discount_type === 'fixed') {
                $discountAmount = $purchase->discount_amount ?? 0;
            } elseif (in_array($purchase->discount_type, ['percent', 'percentage'])) {
                $discountAmount = ($calculatedSum * ($purchase->discount_amount ?? 0)) / 100;
            }
            
            if ($discountAmount > 0) {
                $this->line("  Minus Discount (" . $purchase->discount_type . "): -" . number_format($discountAmount, 2));
            }
            
            $calculatedFinalTotal = $calculatedSum - $discountAmount;
            $this->line("  Calculated Final Total: " . number_format($calculatedFinalTotal, 2));
            $this->newLine();
            
            // Check for mismatch
            $difference = abs($purchase->final_total - $calculatedFinalTotal);
            
            if ($difference > 0.5) {
                $this->error("⚠️  MISMATCH DETECTED!");
                $this->error("  Stored Final Total:     " . number_format($purchase->final_total, 2));
                $this->error("  Calculated Final Total: " . number_format($calculatedFinalTotal, 2));
                $this->error("  DIFFERENCE:             " . number_format($difference, 2));
                $this->newLine();
                
                // Identify issues
                $this->warn("POSSIBLE CAUSES:");
                
                if (abs($purchase->total - $calculatedSum) > 0.5) {
                    $this->line("  ✗ Stored 'total' (" . number_format($purchase->total, 2) . ") doesn't match product sum (" . number_format($calculatedSum, 2) . ")");
                }
                
                $ratio = $calculatedSum > 0 ? $purchase->final_total / $calculatedSum : 0;
                $this->line("  → Ratio (final_total / product_sum): " . number_format($ratio, 4));
                
                if ($ratio > 1.2) {
                    $this->line("  ✗ Final total is " . number_format(($ratio - 1) * 100, 1) . "% higher than product sum");
                } elseif ($ratio < 0.8) {
                    $this->line("  ✗ Final total is " . number_format((1 - $ratio) * 100, 1) . "% lower than product sum");
                }
                
            } else {
                $this->info("✓ Totals match correctly!");
            }
            
            $this->newLine();
        }

        $this->info('=================================================');
        return 0;
    }
}
