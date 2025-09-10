<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\Sale;

class FixBouncedChequeSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:bounced-cheque-sales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix sale totals for bounced cheques';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing bounced cheque sale totals...');

        // Find all bounced payments
        $bouncedPayments = Payment::where('cheque_status', 'bounced')->with('sale')->get();

        if ($bouncedPayments->isEmpty()) {
            $this->info('No bounced payments found.');
            return;
        }

        $this->info("Found {$bouncedPayments->count()} bounced payment(s)");

        foreach ($bouncedPayments as $payment) {
            $this->info("Processing Payment ID: {$payment->id}, Amount: Rs. {$payment->amount}");
            
            if ($payment->sale) {
                $sale = $payment->sale;
                $this->info("  Related Sale ID: {$sale->id}");
                
                $this->info("  Before fix:");
                $this->info("    Final Total: Rs. " . number_format($sale->final_total, 2));
                $this->info("    Total Paid: Rs. " . number_format($sale->total_paid, 2));
                $this->info("    Total Due: Rs. " . number_format($sale->total_due, 2));
                $this->info("    Payment Status: {$sale->payment_status}");
                
                // Debug payments
                $this->info("  Payment Details:");
                foreach ($sale->payments as $p) {
                    $this->info("    Payment ID: {$p->id}, Amount: Rs. {$p->amount}, Method: {$p->payment_method}, Cheque Status: " . ($p->cheque_status ?? 'N/A'));
                }
                
                // Debug the calculation
                $totalReceived = $sale->payments()->sum('amount');
                $bouncedCheques = $sale->payments()
                    ->where('payment_method', 'cheque')
                    ->where('cheque_status', 'bounced')
                    ->sum('amount');
                
                $this->info("  Calculation Debug:");
                $this->info("    Total Received: Rs. " . number_format($totalReceived, 2));
                $this->info("    Bounced Cheques: Rs. " . number_format($bouncedCheques, 2));
                $this->info("    Should be Paid: Rs. " . number_format($totalReceived - $bouncedCheques, 2));
                
                // Recalculate payment totals
                $result = $sale->recalculatePaymentTotals();
                
                $this->info("  After fix:");
                $this->info("    Final Total: Rs. " . number_format($sale->final_total, 2));
                $this->info("    Total Paid: Rs. " . number_format($sale->total_paid, 2));
                $this->info("    Total Due: Rs. " . number_format($sale->total_due, 2));
                $this->info("    Payment Status: {$sale->payment_status}");
                
                $this->info("  Payment Analysis:");
                $this->info("    Total Received: Rs. " . number_format($result['total_received'], 2));
                $this->info("    Actual Paid (excluding bounced): Rs. " . number_format($result['actual_paid'], 2));
                $this->info("    Bounced Cheques: Rs. " . number_format($result['bounced_cheques'], 2));
                
                $this->info("  ✅ Sale updated successfully!");
                $this->newLine();
            } else {
                $this->warn("  No related sale found for payment ID: {$payment->id}");
            }
        }

        $this->info('All bounced cheque sales have been fixed!');
    }
}
