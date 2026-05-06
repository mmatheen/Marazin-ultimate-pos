<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Services\UnifiedLedgerService;

class PurchaseObserver
{
    protected $unifiedLedgerService;

    public function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
    }

    /**
     * Handle the Purchase "deleting" event.
     * This fires BEFORE the purchase is actually deleted
     */
    public function deleting(Purchase $purchase)
    {
        // ✅ CRITICAL: Reverse all ledger entries BEFORE deleting purchase
        // This ensures supplier account is properly credited back and audit trail is maintained
        $this->unifiedLedgerService->deletePurchaseLedger($purchase);
    }
}
