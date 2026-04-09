<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Services\PaymentService;
use App\Services\Sale\SalePaymentStatusService;
use Illuminate\Console\Command;

class SyncSalePaymentStatuses extends Command
{
    protected $signature = 'sales:sync-payment-status
        {--dry-run : List mismatch count without saving}
        {--recalc-total-paid : Recompute total_paid from payments table (bounced cheques still count toward invoice)}
        {--strict-recalc-total-paid : Recompute total_paid from active payment rows only and preserve only live return credit}
        {--sale-id=* : Limit recalculation to one or more sale IDs}';

    protected $description = 'Align payment_status with final_total/total_paid on final and suspend sales';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $saleIds = collect($this->option('sale-id'))->filter()->map(fn ($value) => (int) $value)->all();

        $salesQuery = Sale::withoutGlobalScopes()->whereIn('status', ['final', 'suspend']);
        if (! empty($saleIds)) {
            $salesQuery->whereIn('id', $saleIds);
        }

        if ($this->option('recalc-total-paid')) {
            if ($dry) {
                $this->warn('Use --recalc-total-paid without --dry-run (it writes total_paid from payments).');

                return Command::FAILURE;
            }

            $paymentService = app(PaymentService::class);
            $count = 0;

            $salesToProcess = clone $salesQuery;
            $salesToProcess->orderBy('id')->chunkById(200, function ($sales) use ($paymentService, &$count) {
                foreach ($sales as $sale) {
                    $paymentService->updateSalePaymentStatus($sale);
                    $count++;
                }
            });

            $this->info("Recalculated total_paid from payment rows: {$count} sale(s).");
        }

        if ($this->option('strict-recalc-total-paid')) {
            if ($dry) {
                $this->warn('Use --strict-recalc-total-paid without --dry-run (it writes total_paid from active payment rows).');

                return Command::FAILURE;
            }

            $count = 0;
            $salesToProcess = clone $salesQuery;
            $salesToProcess->orderBy('id')->chunkById(200, function ($sales) use (&$count) {
                foreach ($sales as $sale) {
                    $this->recalculateSaleFromLiveState($sale);
                    $count++;
                }
            });

            $this->info("Strictly recalculated total_paid from live payment rows: {$count} sale(s).");

            return Command::SUCCESS;
        }

        $mismatch = 0;
        $fixed = 0;

        $paymentStatusService = app(SalePaymentStatusService::class);

        $salesToProcess = clone $salesQuery;
        $salesToProcess->orderBy('id')->chunkById(500, function ($sales) use ($dry, &$mismatch, &$fixed, $paymentStatusService) {
            foreach ($sales as $sale) {
                $expected = $paymentStatusService->deriveForInvoice(
                    (float) ($sale->final_total ?? 0),
                    $sale->total_paid !== null ? (float) $sale->total_paid : null
                );

                if ($sale->payment_status === $expected) {
                    continue;
                }

                $mismatch++;

                if (! $dry) {
                    $sale->save();
                    $fixed++;
                }
            }
        });

        $this->info("Rows where payment_status did not match amounts: {$mismatch}");

        if ($dry) {
            $this->warn('Dry run — no changes. Run without --dry-run to fix.');
        } else {
            $this->info("Updated {$fixed} sale(s).");
        }

        return self::SUCCESS;
    }

    private function recalculateSaleFromLiveState(Sale $sale): void
    {
        $activeCashTotal = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where('status', '!=', 'deleted')
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['cancelled']);
            })
            ->sum('amount');

        $liveReturnCredit = $this->getAppliedReturnCreditForSale((int) $sale->id);

        $sale->total_paid = $activeCashTotal + $liveReturnCredit;
        $sale->save();
        $sale->refresh();

        $this->line(sprintf(
            'Sale %d (%s): total_paid=%s total_due=%s payment_status=%s',
            $sale->id,
            $sale->invoice_no,
            $sale->total_paid,
            $sale->total_due,
            $sale->payment_status
        ));
    }

    private function getAppliedReturnCreditForSale(int $saleId): float
    {
        $returns = SalesReturn::where('sale_id', $saleId)->get(['id', 'total_paid']);
        if ($returns->isEmpty()) {
            return 0.0;
        }

        $totalReturnPaid = (float) $returns->sum(function ($return) {
            return (float) $return->total_paid;
        });

        $cashRefunded = Payment::whereIn('reference_id', $returns->pluck('id')->all())
            ->where('payment_type', 'sale_return_with_bill')
            ->sum('amount');

        return max(0.0, $totalReturnPaid - (float) $cashRefunded);
    }
}
