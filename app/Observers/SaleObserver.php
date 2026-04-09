<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\Sale\SaleLifecycleService;
use App\Services\Sale\SaleSmsNotificationService;
use Illuminate\Support\Facades\Log;

class SaleObserver
{
    public function saving(Sale $sale): void
    {
        app(SaleLifecycleService::class)->applyComputedTotalsAndStatus($sale);
    }

    public function creating(Sale $sale): void
    {
        app(SaleLifecycleService::class)->ensureInvoiceTokenForCreation($sale);
    }

    public function created(Sale $sale): void
    {
        try {
            app(SaleSmsNotificationService::class)->dispatchForSale($sale);
        } catch (\Throwable $e) {
            Log::warning('Sale SMS dispatch failed during model created event.', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Sale $sale): void
    {
        $originalTransactionType = $sale->getOriginal('transaction_type');

        if ($originalTransactionType !== 'sale_order' || $sale->transaction_type !== 'invoice') {
            return;
        }

        try {
            app(SaleSmsNotificationService::class)->dispatchForSale($sale);
        } catch (\Throwable $e) {
            Log::warning('Sale SMS dispatch failed during model updated event.', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
