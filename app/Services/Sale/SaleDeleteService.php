<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Models\StockHistory;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;

class SaleDeleteService
{
    public function __construct(
        protected SaleProductProcessor  $saleProductProcessor,
        protected UnifiedLedgerService  $unifiedLedgerService
    ) {}

    /**
     * Delete a sale inside a transaction.
     * When $restoreStock is true, reverses stock and clears the customer ledger.
     */
    public function delete(Sale $sale, bool $restoreStock): void
    {
        DB::transaction(function () use ($sale, $restoreStock) {
            foreach ($sale->products as $product) {
                if ($restoreStock) {
                    $this->saleProductProcessor->restoreStock(
                        $product,
                        StockHistory::STOCK_TYPE_SALE_REVERSAL
                    );
                }
                $product->delete();
            }

            if ($restoreStock && $sale->customer_id && $sale->customer_id != 1) {
                $this->unifiedLedgerService->deleteSaleLedger($sale);
            }

            $sale->delete();
        });
    }
}
