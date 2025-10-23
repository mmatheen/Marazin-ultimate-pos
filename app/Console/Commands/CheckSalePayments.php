<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CheckSalePayments extends Command
{
    protected $signature = 'check:sale-payments {invoice_no?}';
    protected $description = 'Check sale and payment data';

    public function handle()
    {
        $invoiceNo = $this->argument('invoice_no');

        if ($invoiceNo) {
            // Check specific invoice
            $sale = Sale::with('payments')->where('invoice_no', $invoiceNo)->first();
            
            if (!$sale) {
                $this->error("Sale with invoice {$invoiceNo} not found!");
                return Command::FAILURE;
            }

            $this->displaySaleInfo($sale);
        } else {
            // Show recent sales with discounts
            $this->info('Recent sales with discounts:');
            $this->newLine();

            $sales = Sale::where('discount_amount', '>', 0)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->with('payments')
                ->get();

            if ($sales->isEmpty()) {
                $this->warn('No sales with discounts found.');
                
                // Show last 5 sales
                $this->newLine();
                $this->info('Last 5 sales:');
                $recentSales = Sale::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->with('payments')
                    ->get();
                    
                foreach ($recentSales as $sale) {
                    $this->displaySaleInfo($sale);
                    $this->newLine();
                }
            } else {
                foreach ($sales as $sale) {
                    $this->displaySaleInfo($sale);
                    $this->newLine();
                }
            }
        }

        return Command::SUCCESS;
    }

    private function displaySaleInfo($sale)
    {
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Invoice: {$sale->invoice_no} (ID: {$sale->id})");
        $this->info("Date: {$sale->sales_date}");
        $this->info("Subtotal: " . number_format($sale->subtotal, 2));
        $this->info("Discount: " . number_format($sale->discount_amount, 2) . " ({$sale->discount_type})");
        $this->info("Final Total: " . number_format($sale->final_total, 2));
        
        if (isset($sale->amount_given)) {
            $this->info("Amount Given: " . number_format($sale->amount_given, 2));
        }
        
        if (isset($sale->balance_amount)) {
            $this->info("Balance: " . number_format($sale->balance_amount, 2));
        }

        if ($sale->payments->isNotEmpty()) {
            $this->info("Payments ({$sale->payments->count()}):");
            foreach ($sale->payments as $payment) {
                $amount = number_format($payment->amount, 2);
                $this->info("  - ID: {$payment->id} | Method: {$payment->payment_method} | Amount: {$amount}");
                
                // Check if payment amount matches subtotal (the bug)
                if (abs($payment->amount - $sale->subtotal) < 0.01 && abs($sale->subtotal - $sale->final_total) > 0.01) {
                    $this->error("    ⚠️  BUG FOUND: Payment amount ({$amount}) equals subtotal, should be final_total (" . number_format($sale->final_total, 2) . ")");
                }
            }
        } else {
            $this->warn("  No payments found");
        }
    }
}
