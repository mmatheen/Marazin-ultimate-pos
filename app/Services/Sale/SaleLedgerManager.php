<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\Log;

/**
 * SaleLedgerManager
 *
 * Encapsulates all ledger-entry decisions for a sale create/update.
 * Called AFTER the Sale record has been saved (inside the DB transaction),
 * and BEFORE payments are processed.
 *
 * Rules:
 *  - Walk-In customer (id = 1) → never write ledger entries.
 *  - New sale (not update) → recordSale() unless draft / quotation / sale_order.
 *  - Update path → one of five sub-cases:
 *      1. Sale-order → invoice conversion  → recordNewSaleEntry()
 *      2. Draft / quotation → final         → recordNewSaleEntry()
 *      3. Customer changed                  → editSaleWithCustomerChange()
 *      4. Financial data changed            → updateSale()
 *      5. Non-financial change only         → no ledger action
 */
class SaleLedgerManager
{
    public function __construct(
        protected UnifiedLedgerService $ledger
    ) {}

    // -------------------------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------------------------

    /**
     * Decide which ledger operation (if any) is required and execute it.
     *
     * @param  Sale        $sale
     * @param  bool        $isUpdate
     * @param  string|null $oldStatus          Status before this edit (null for new sales)
     * @param  string      $newStatus
     * @param  string      $transactionType    'invoice' | 'sale_order' | etc.
     * @param  int         $customerId         From the request (may differ from $sale->customer_id during edit)
     * @param  int|null    $oldCustomerId      Customer before this edit
     * @param  float|null  $oldFinalTotal      final_total before this edit
     * @param  bool        $customerChanged
     * @param  bool        $financialDataChanged
     * @param  string      $referenceNo
     */
    public function record(
        Sale    $sale,
        bool    $isUpdate,
        ?string $oldStatus,
        string  $newStatus,
        string  $transactionType,
        int     $customerId,
        ?int    $oldCustomerId,
        ?float  $oldFinalTotal,
        bool    $customerChanged,
        bool    $financialDataChanged,
        ?string $referenceNo
    ): void {
        // Walk-In customers have no credit ledger — skip entirely
        if ($customerId == 1) {
            return;
        }

        if (!$isUpdate) {
            $this->handleNewSale($sale, $transactionType);
        } else {
            $this->handleUpdate(
                $sale,
                $oldStatus,
                $newStatus,
                $transactionType,
                $oldCustomerId,
                $oldFinalTotal,
                $customerChanged,
                $financialDataChanged,
                $referenceNo
            );
        }
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * New-sale path.
     * Skip ledger for drafts, quotations, and sale orders; record everything else.
     */
    private function handleNewSale(Sale $sale, string $transactionType): void
    {
        if (in_array($sale->status, ['draft', 'quotation']) || $transactionType === 'sale_order') {
            // No ledger entry for non-committed documents
            return;
        }

        // ✅ CRITICAL: Record sale in unified ledger INSIDE DB transaction.
        // If ledger creation fails the entire transaction rolls back (sale won't be saved).
        // This ensures accounting integrity — no sale without a ledger entry.
        $this->ledger->recordSale($sale);
    }

    /**
     * Update path — choose one of five sub-cases.
     * Skip entirely for sale orders and when target status is draft / quotation.
     */
    private function handleUpdate(
        Sale    $sale,
        ?string $oldStatus,
        string  $newStatus,
        string  $transactionType,
        ?int    $oldCustomerId,
        ?float  $oldFinalTotal,
        bool    $customerChanged,
        bool    $financialDataChanged,
        ?string $referenceNo
    ): void {
        // Skip ledger updates for sale orders and non-final target statuses
        if ($transactionType === 'sale_order' || in_array($newStatus, ['draft', 'quotation'])) {
            return;
        }

        // ── Case 1: Sale-order → Invoice conversion ─────────────────────────
        $isSaleOrderToInvoiceConversion =
            $sale->getOriginal('transaction_type') === 'sale_order' &&
            $transactionType === 'invoice' &&
            $newStatus === 'final';

        if ($isSaleOrderToInvoiceConversion) {
            // Sale orders have no existing ledger entry, so just create a new one
            $this->ledger->recordNewSaleEntry($sale);
            return;
        }

        // ── Case 2: Draft / Quotation → Final conversion ────────────────────
        $isDraftToFinalConversion =
            in_array($oldStatus, ['draft', 'quotation']) &&
            in_array($newStatus, ['final', 'suspend']);

        if ($isDraftToFinalConversion) {
            // No old ledger entry exists for drafts — force-create a new one
            $this->ledger->recordNewSaleEntry($sale);
            return;
        }

        // ── Case 3: Customer changed ─────────────────────────────────────────
        if ($customerChanged) {
            $this->ledger->editSaleWithCustomerChange(
                $sale,
                $oldCustomerId,
                $sale->customer_id,
                $oldFinalTotal,
                'Customer changed during sale edit'
            );
            return;
        }

        // ── Case 4: Financial data changed ───────────────────────────────────
        if ($financialDataChanged) {
            // Guard: ensure customer_id survived the save
            if (empty($sale->customer_id)) {
                Log::error('CRITICAL: Sale customer_id is empty before ledger operations', [
                    'sale_id'              => $sale->id,
                    'sale_customer_id'     => $sale->customer_id,
                    'sale_attributes'      => $sale->getAttributes(),
                ]);

                $sale->refresh();

                if (empty($sale->customer_id)) {
                    throw new \Exception(
                        "CRITICAL ERROR: Sale customer_id is missing after database refresh. " .
                        "Sale ID: {$sale->id}. This indicates a database constraint violation or data corruption."
                    );
                }
            }

            // UnifiedLedgerService handles both reversal of old entry and creation of new entry
            $this->ledger->updateSale($sale, $referenceNo ?? '');
            return;
        }

        // ── Case 5: Non-financial change only ────────────────────────────────
        // e.g. sale_notes, reference, etc. — no ledger action needed
    }
}
