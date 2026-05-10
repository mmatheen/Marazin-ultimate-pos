<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Services\Sale\SaleSettlementService;
use Illuminate\Console\Command;

class ReconcileSaleSettlements extends Command
{
    protected $signature = 'sale:reconcile-settlements
                            {--customer= : Reconcile all sales for this customer ID}
                            {--sale= : Reconcile a single sale ID}
                            {--dry-run : Print calculated values without saving}';

    protected $description = 'Resync sale total_paid, total_due, and payment_status from payments (fixes historical drift)';

    public function handle(SaleSettlementService $settlementService): int
    {
        $saleId = $this->option('sale');
        $customerId = $this->option('customer');
        $dryRun = (bool) $this->option('dry-run');

        if ($saleId === null && $customerId === null) {
            $this->error('Specify --sale=ID and/or --customer=ID.');

            return self::FAILURE;
        }

        $query = Sale::withoutGlobalScopes()->orderBy('id');

        if ($saleId !== null) {
            $query->where('id', (int) $saleId);
        }

        if ($customerId !== null) {
            $query->where('customer_id', (int) $customerId);
        }

        $updated = 0;
        $skipped = 0;

        $query->chunkById(100, function ($sales) use ($settlementService, $dryRun, &$updated, &$skipped) {
            foreach ($sales as $sale) {
                $settlement = $settlementService->calculateSettlement($sale);

                if (! $settlement['needs_update']) {
                    $skipped++;

                    continue;
                }

                $this->line(sprintf(
                    'Sale #%d (%s): paid %s → %s | due %s → %s | status %s → %s',
                    $sale->id,
                    $sale->invoice_no ?? '-',
                    number_format($settlement['stored_total_paid'], 2),
                    number_format($settlement['calculated_total_paid'], 2),
                    number_format($settlement['stored_total_due'], 2),
                    number_format($settlement['calculated_total_due'], 2),
                    $settlement['stored_payment_status'],
                    $settlement['calculated_payment_status']
                ));

                if (! $dryRun) {
                    $settlementService->syncSale($sale);
                }

                $updated++;
            }
        });

        $this->info($dryRun
            ? "Dry run: {$updated} sale(s) would be updated, {$skipped} already correct."
            : "Updated {$updated} sale(s), {$skipped} already correct.");

        return self::SUCCESS;
    }
}
