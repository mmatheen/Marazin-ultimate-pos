<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class FixPaymentAmountsSimple extends Command
{
    protected $signature = 'fix:payment-amounts-simple {--dry-run : Run without making changes} {--invoice= : Specific invoice to fix}';
    protected $description = 'Fix payment amounts where they were recorded as subtotal instead of final_total';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificInvoice = $this->option('invoice');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Starting payment amount fix...');
        $this->newLine();

        // Build query
        $query = Sale::query()->with('payments');
        
        if ($specificInvoice) {
            $query->where('invoice_no', $specificInvoice);
            $this->info("Checking invoice: {$specificInvoice}");
        } else {
            $query->where('discount_amount', '>', 0);
            $this->info("Checking all sales with discounts...");
        }
        
        $sales = $query->get();

        $this->info("Found {$sales->count()} sales to check");
        $this->newLine();

        if ($sales->count() === 0) {
            $this->warn('No sales found matching criteria');
            return Command::SUCCESS;
        }

        $fixedCount = 0;
        $skippedCount = 0;

        foreach ($sales as $sale) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Checking Sale ID: {$sale->id} | Invoice: {$sale->invoice_no}");
            
            $subtotal = (float) $sale->subtotal;
            $discountAmount = (float) $sale->discount_amount;
            $finalTotal = (float) $sale->final_total;
            
            $this->info("  Subtotal: {$subtotal}");
            $this->info("  Discount: {$discountAmount}");
            $this->info("  Final Total: {$finalTotal}");
            
            $payments = $sale->payments;
            
            if ($payments->isEmpty()) {
                $this->warn("  No payments found - SKIPPED");
                $skippedCount++;
                continue;
            }
            
            $needsUpdate = false;
            
            foreach ($payments as $payment) {
                $paymentAmount = (float) $payment->amount;
                $this->info("  Payment ID {$payment->id}: {$paymentAmount} ({$payment->payment_method})");
                
                // Check if payment equals subtotal but should equal final_total
                $diffFromSubtotal = abs($paymentAmount - $subtotal);
                $diffFromFinal = abs($paymentAmount - $finalTotal);
                $discountDiff = abs($subtotal - $finalTotal);
                
                // If payment is close to subtotal AND there's a significant discount
                if ($diffFromSubtotal < 0.01 && $discountDiff > 0.01) {
                    $this->error("    ❌ BUG: Payment amount equals subtotal ({$subtotal}), should be final_total ({$finalTotal})");
                    $needsUpdate = true;
                    
                    if (!$dryRun) {
                        $payment->update(['amount' => $finalTotal]);
                        $this->info("    ✅ FIXED: Updated payment to {$finalTotal}");
                    } else {
                        $this->warn("    ⚠️  WOULD UPDATE to: {$finalTotal}");
                    }
                } elseif ($diffFromFinal < 0.01) {
                    $this->info("    ✓ OK: Payment amount is correct");
                } else {
                    $this->warn("    ? Payment amount doesn't match subtotal or final_total");
                }
            }
            
            if ($needsUpdate) {
                $fixedCount++;
            } else {
                $skippedCount++;
            }
            
            $this->newLine();
        }

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('SUMMARY');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("Total sales checked: {$sales->count()}");
        $this->info("Fixed: {$fixedCount}");
        $this->info("Skipped (no issues): {$skippedCount}");

        if ($dryRun && $fixedCount > 0) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        if (!$dryRun && $fixedCount > 0) {
            $this->newLine();
            $this->info('✅ Payment amounts have been fixed!');
            $this->info('Run: php artisan cache:clear');
        }

        return Command::SUCCESS;
    }
}
