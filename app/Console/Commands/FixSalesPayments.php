<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;

class FixSalesPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:sales-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix total_paid, total_due, and payment_status for all sales based on actual payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting to fix sales payment data...');

        $sales = Sale::all();
        $count = 0;

        foreach ($sales as $sale) {
            $totalPaid = $sale->payments()->sum('amount');
            $totalDue = max(0, $sale->final_total - $totalPaid);
            $status = $totalDue <= 0 ? 'Paid' : ($totalPaid > 0 ? 'Partial' : 'Due');

            $sale->update([
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
                'payment_status' => $status,
            ]);

            $count++;
        }

        $this->info("✅ Fixed payment data for $count sales.");
    }
}