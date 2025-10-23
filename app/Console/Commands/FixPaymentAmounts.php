<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class FixPaymentAmounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:payment-amounts {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix payment amounts where they were recorded as subtotal instead of final_total';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Starting payment amount fix...');
        $this->newLine();

        // Get all sales with discounts (check both status values)
        $sales = Sale::where('discount_amount', '>', 0)
            ->whereIn('status', ['final', 'Final', 'FINAL'])
            ->with('payments')
            ->get();

        $this->info("Found {$sales->count()} sales with discounts");
        
        if ($sales->count() === 0) {
            // Try without status filter to see if there are any sales with discounts
            $allDiscountedSales = Sale::where('discount_amount', '>', 0)->with('payments')->get();
            if ($allDiscountedSales->count() > 0) {
                $this->warn("Found {$allDiscountedSales->count()} sales with discounts but different status values");
                $sales = $allDiscountedSales;
            }
        }
        
        $this->newLine();

        $fixedCount = 0;
        $skippedCount = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar($sales->count());
        $progressBar->start();

        foreach ($sales as $sale) {
            $progressBar->advance();

            try {
                // Calculate what the payment amount should be
                $subtotal = $sale->subtotal;
                $discountAmount = $sale->discount_amount;
                $discountType = $sale->discount_type;
                $finalTotal = $sale->final_total;

                // Calculate expected discount
                if ($discountType === 'percentage') {
                    $calculatedDiscount = $subtotal * ($discountAmount / 100);
                    $expectedFinalTotal = $subtotal - $calculatedDiscount;
                } else {
                    $expectedFinalTotal = $subtotal - $discountAmount;
                }

                // Round to avoid floating point issues
                $expectedFinalTotal = round($expectedFinalTotal, 2);
                $finalTotal = round($finalTotal, 2);

                // Check if final_total in sale matches expected
                if (abs($expectedFinalTotal - $finalTotal) > 0.01) {
                    $this->newLine();
                    $this->warn("Sale ID {$sale->id}: Final total mismatch. Expected: {$expectedFinalTotal}, Got: {$finalTotal}");
                    $skippedCount++;
                    continue;
                }

                // Get payments for this sale
                $payments = $sale->payments;
                
                if ($payments->isEmpty()) {
                    $skippedCount++;
                    continue;
                }

                $needsUpdate = false;
                $paymentDetails = [];

                foreach ($payments as $payment) {
                    $currentAmount = round($payment->amount, 2);
                    
                    // Check if payment amount equals subtotal (the bug)
                    if (abs($currentAmount - $subtotal) < 0.01 && abs($subtotal - $finalTotal) > 0.01) {
                        // This payment was recorded incorrectly
                        $needsUpdate = true;
                        $paymentDetails[] = [
                            'payment_id' => $payment->id,
                            'old_amount' => $currentAmount,
                            'new_amount' => $finalTotal,
                            'payment_method' => $payment->payment_method,
                        ];
                    }
                }

                if ($needsUpdate) {
                    $this->newLine();
                    $this->info("Sale ID: {$sale->id} | Invoice: {$sale->invoice_no}");
                    $this->info("  Subtotal: {$subtotal} | Discount: {$discountAmount} ({$discountType}) | Final Total: {$finalTotal}");
                    
                    foreach ($paymentDetails as $detail) {
                        $this->info("  Payment ID {$detail['payment_id']} ({$detail['payment_method']}): {$detail['old_amount']} → {$detail['new_amount']}");
                        
                        if (!$dryRun) {
                            // Update the payment amount
                            Payment::where('id', $detail['payment_id'])
                                ->update(['amount' => $detail['new_amount']]);
                        }
                    }

                    // Also update amount_given in sale if needed
                    if ($sale->amount_given == $subtotal && abs($subtotal - $finalTotal) > 0.01) {
                        $this->info("  Updating amount_given: {$sale->amount_given} → {$finalTotal}");
                        
                        if (!$dryRun) {
                            $sale->update(['amount_given' => $finalTotal]);
                        }
                    }

                    $fixedCount++;
                } else {
                    $skippedCount++;
                }

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error processing Sale ID {$sale->id}: " . $e->getMessage());
                $errors[] = "Sale ID {$sale->id}: " . $e->getMessage();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('SUMMARY');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("Total sales processed: {$sales->count()}");
        $this->info("Fixed: {$fixedCount}");
        $this->info("Skipped (no issues): {$skippedCount}");
        $this->info("Errors: " . count($errors));

        if (!empty($errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if ($dryRun && $fixedCount > 0) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        if (!$dryRun && $fixedCount > 0) {
            $this->newLine();
            $this->info('✅ Payment amounts have been fixed!');
        }

        return Command::SUCCESS;
    }
}
