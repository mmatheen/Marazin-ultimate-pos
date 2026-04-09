<?php

namespace App\Services\Sale;

use App\Models\Sale;
use Illuminate\Support\Str;

class SaleLifecycleService
{
    public function __construct(
        private readonly SaleCalculationService $saleCalculationService,
        private readonly SalePaymentStatusService $salePaymentStatusService
    )
    {
    }

    public function applyComputedTotalsAndStatus(Sale $sale): void
    {
        // Recalculate totals on every save to keep amount fields consistent.
        $sale->final_total = $this->saleCalculationService->calculateFinalTotal($sale);

        if ($sale->total_paid !== null) {
            $sale->total_due = $sale->final_total - $sale->total_paid;
        } else {
            $sale->total_due = $sale->final_total;
        }

        if (! in_array($sale->status, ['final', 'suspend'], true)) {
            return;
        }

        $final = (float) ($sale->final_total ?? 0);
        $paidAttr = $sale->total_paid;

        $sale->payment_status = $this->salePaymentStatusService->deriveForInvoice(
            $final,
            $paidAttr !== null ? (float) $paidAttr : null
        );
    }

    public function ensureInvoiceTokenForCreation(Sale $sale): void
    {
        if (
            blank($sale->invoice_token)
            && (
                $sale->transaction_type === 'invoice'
                || blank($sale->transaction_type)
            )
        ) {
            $sale->invoice_token = (string) Str::uuid();
        }
    }
}
