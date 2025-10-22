<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;

class CheckPurchaseTotals extends Command
{
    protected $signature = 'purchase:check {id : Purchase ID to check}';
    protected $description = 'Check a specific purchase total calculation';

    public function handle()
    {
        $purchaseId = $this->argument('id');
        $purchase = Purchase::with('purchaseProducts.product')->find($purchaseId);
        
        if (!$purchase) {
            $this->error("Purchase #{$purchaseId} not found!");
            return 1;
        }
        
        $this->info("=== Purchase #{$purchase->id} - {$purchase->reference_no} ===");
        $this->info("Date: {$purchase->purchase_date}");
        $this->newLine();
        
        // Products breakdown
        $this->info('PRODUCTS:');
        $productsTotal = 0;
        
        $productData = [];
        foreach ($purchase->purchaseProducts as $product) {
            $productName = $product->product->product_name ?? "Product {$product->product_id}";
            
            if ($product->price > 0 && $product->discount_percent > 0) {
                $discountAmount = $product->price * ($product->discount_percent / 100);
                $unitCost = $product->price - $discountAmount;
                $total = $unitCost * $product->quantity;
                
                $productData[] = [
                    $productName,
                    number_format($product->quantity, 2),
                    number_format($product->price, 2),
                    $product->discount_percent . '%',
                    number_format($unitCost, 2),
                    number_format($total, 2),
                ];
            } else {
                $total = $product->unit_cost * $product->quantity;
                $productData[] = [
                    $productName,
                    number_format($product->quantity, 2),
                    '-',
                    '-',
                    number_format($product->unit_cost, 2),
                    number_format($total, 2),
                ];
            }
            
            $productsTotal += $total;
        }
        
        $this->table(
            ['Product', 'Qty', 'Price', 'Disc%', 'Unit Cost', 'Total'],
            $productData
        );
        
        $this->newLine();
        $this->info('Products Total: ' . number_format($productsTotal, 2));
        
        // Purchase-level discount
        $discountAmount = 0;
        if ($purchase->discount_type === 'fixed') {
            $discountAmount = $purchase->discount_amount ?? 0;
            $this->info('Discount (Fixed): -' . number_format($discountAmount, 2));
        } elseif ($purchase->discount_type === 'percent') {
            $discountAmount = ($productsTotal * ($purchase->discount_amount ?? 0)) / 100;
            $this->info("Discount ({$purchase->discount_amount}%): -" . number_format($discountAmount, 2));
        }
        
        // Tax
        $taxAmount = 0;
        if ($purchase->tax_type === 'vat10' || $purchase->tax_type === 'cgst10') {
            $taxAmount = ($productsTotal - $discountAmount) * 0.10;
            $this->info('Tax (10%): +' . number_format($taxAmount, 2));
        }
        
        $calculatedFinalTotal = $productsTotal - $discountAmount + $taxAmount;
        
        $this->newLine();
        $this->info('=== CALCULATED ===');
        $this->info('Total: ' . number_format($productsTotal, 2));
        $this->info('Final Total: ' . number_format($calculatedFinalTotal, 2));
        
        $this->newLine();
        $this->info('=== IN DATABASE ===');
        $this->info('Total: ' . number_format($purchase->total, 2));
        $this->info('Final Total: ' . number_format($purchase->final_total, 2));
        
        $this->newLine();
        
        // Check for mismatches
        $totalMatch = abs($purchase->total - $productsTotal) < 0.01;
        $finalMatch = abs($purchase->final_total - $calculatedFinalTotal) < 0.01;
        
        if ($totalMatch && $finalMatch) {
            $this->info('✅ Totals are CORRECT!');
        } else {
            $this->warn('⚠️  Totals are INCORRECT!');
            if (!$totalMatch) {
                $diff = $purchase->total - $productsTotal;
                $this->error('  Total difference: ' . number_format($diff, 2));
            }
            if (!$finalMatch) {
                $diff = $purchase->final_total - $calculatedFinalTotal;
                $this->error('  Final Total difference: ' . number_format($diff, 2));
            }
            $this->newLine();
            $this->info('To fix this purchase, run:');
            $this->info('php artisan purchase:reconcile-all --fix');
        }
        
        return 0;
    }
}
