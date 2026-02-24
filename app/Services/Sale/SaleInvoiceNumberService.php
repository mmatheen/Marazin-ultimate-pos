<?php

namespace App\Services\Sale;

use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class SaleInvoiceNumberService
{
    /**
     * Generate a unique sale reference number (used before any invoice number is assigned).
     * Format: SALE-YYYYMMDD
     */
    public function generateReferenceNo(): string
    {
        return 'SALE-' . now()->format('Ymd');
    }

    /**
     * Resolve the correct invoice_no, order_number, and order_status for a sale.
     *
     * Returns an array with three keys:
     *   'invoice_no'   => string|null
     *   'order_number' => string|null
     *   'order_status' => string|null
     *
     * @param  Sale        $sale            The existing Sale model (or a fresh unsaved one for new sales)
     * @param  bool        $isUpdate        Whether this is an edit of an existing sale
     * @param  string|null $oldStatus       The original status before this request (null for new sales)
     * @param  string      $newStatus       The incoming status from the request
     * @param  string      $transactionType 'invoice' or 'sale_order'
     * @param  int         $locationId      The location_id from the request
     */
    public function resolve(
        Sale    $sale,
        bool    $isUpdate,
        ?string $oldStatus,
        string  $newStatus,
        string  $transactionType,
        int     $locationId,
        ?string $requestOrderStatus = null
    ): array {
        // ------------------------------------------------------------------
        // PATH 1: Sale Order (new or existing)
        // ------------------------------------------------------------------
        if ($transactionType === 'sale_order') {
            return $this->forSaleOrder($sale, $isUpdate, $locationId, $requestOrderStatus);
        }

        // ------------------------------------------------------------------
        // PATH 2: Sale Order → Invoice conversion
        //         (existing sale_order being finalised as an invoice)
        // ------------------------------------------------------------------
        if (
            $isUpdate &&
            $sale->transaction_type === 'sale_order' &&
            $transactionType === 'invoice' &&
            $newStatus === 'final'
        ) {
            return $this->forSaleOrderToInvoiceConversion($sale, $locationId);
        }

        // ------------------------------------------------------------------
        // PATH 3: Job Ticket → Final/Suspend conversion
        //         (existing jobticket being converted to a normal invoice)
        // ------------------------------------------------------------------
        if (
            $isUpdate &&
            $oldStatus === 'jobticket' &&
            in_array($newStatus, ['final', 'suspend'])
        ) {
            return $this->forJobticketConversion($locationId);
        }

        // ------------------------------------------------------------------
        // PATH 4: New Job Ticket
        // ------------------------------------------------------------------
        if ($newStatus === 'jobticket') {
            return $this->forNewJobticket();
        }

        // ------------------------------------------------------------------
        // PATH 5: New sale (not an update)
        // ------------------------------------------------------------------
        if (!$isUpdate) {
            return $this->forNewSale($newStatus, $locationId);
        }

        // ------------------------------------------------------------------
        // PATH 6: Existing sale edit (draft/quotation → final upgrade, or
        //         plain edit that keeps the same invoice number)
        // ------------------------------------------------------------------
        return $this->forExistingSaleEdit($sale, $oldStatus, $newStatus, $locationId);
    }

    // -----------------------------------------------------------------------
    // Private path handlers
    // -----------------------------------------------------------------------

    /**
     * PATH 1 — Sale Order (new or existing update).
     * No invoice number for sale orders.
     */
    private function forSaleOrder(Sale $sale, bool $isUpdate, int $locationId, ?string $requestOrderStatus): array
    {
        if ($isUpdate && $sale->order_number) {
            // Preserve existing order number during update
            $orderNumber = $sale->order_number;
        } else {
            // Generate a new order number for a new sale order
            $orderNumber = Sale::generateOrderNumber($locationId);
        }

        return [
            'invoice_no'   => null,
            'order_number' => $orderNumber,
            'order_status' => $requestOrderStatus ?? 'pending',
        ];
    }

    /**
     * PATH 2 — Sale Order being converted to a final Invoice.
     * Keep the order number, generate a brand-new invoice number.
     */
    private function forSaleOrderToInvoiceConversion(Sale $sale, int $locationId): array
    {
        $invoiceNo = Sale::generateInvoiceNo($locationId);

        Log::info('🔄 Converting Sale Order to Invoice', [
            'sale_id'        => $sale->id,
            'order_number'   => $sale->order_number,
            'new_invoice_no' => $invoiceNo,
        ]);

        return [
            'invoice_no'   => $invoiceNo,
            'order_number' => $sale->order_number,
            'order_status' => 'completed',
        ];
    }

    /**
     * PATH 3 — Job Ticket being converted to a Final/Suspend sale.
     * Generate a new standard invoice number.
     */
    private function forJobticketConversion(int $locationId): array
    {
        return [
            'invoice_no'   => Sale::generateInvoiceNo($locationId),
            'order_number' => null,
            'order_status' => null,
        ];
    }

    /**
     * PATH 4 — Brand-new Job Ticket.
     * Format: J/YYYY/0001, J/YYYY/0002, …
     */
    private function forNewJobticket(): array
    {
        $prefix = 'J/';
        $year   = now()->format('Y');

        $last   = Sale::whereYear('created_at', now())
            ->where('invoice_no', 'like', "{$prefix}{$year}/%")
            ->latest()
            ->first();

        $number    = $last ? ((int) substr($last->invoice_no, -4)) + 1 : 1;
        $invoiceNo = "{$prefix}{$year}/" . str_pad($number, 4, '0', STR_PAD_LEFT);

        return [
            'invoice_no'   => $invoiceNo,
            'order_number' => null,
            'order_status' => null,
        ];
    }

    /**
     * PATH 5 — New sale (not an update).
     * Draft/Quotation get D/ or Q/ prefix; everything else gets a standard invoice number.
     */
    private function forNewSale(string $newStatus, int $locationId): array
    {
        if (in_array($newStatus, ['quotation', 'draft'])) {
            $prefix = $newStatus === 'quotation' ? 'Q/' : 'D/';
            $year   = now()->format('Y');

            $last   = Sale::whereYear('created_at', now())
                ->where('invoice_no', 'like', "{$prefix}{$year}/%")
                ->latest()
                ->first();

            $number    = $last ? ((int) substr($last->invoice_no, -4)) + 1 : 1;
            $invoiceNo = "{$prefix}{$year}/" . str_pad($number, 4, '0', STR_PAD_LEFT);
        } else {
            $invoiceNo = Sale::generateInvoiceNo($locationId);
        }

        return [
            'invoice_no'   => $invoiceNo,
            'order_number' => null,
            'order_status' => null,
        ];
    }

    /**
     * PATH 6 — Editing an existing sale.
     * If the old invoice was a draft/quotation prefix and the new status is final/suspend,
     * generate a clean standard invoice number.
     * Otherwise keep the original invoice number unchanged.
     */
    private function forExistingSaleEdit(Sale $sale, ?string $oldStatus, string $newStatus, int $locationId): array
    {
        if (
            in_array($oldStatus, ['draft', 'quotation']) &&
            in_array($newStatus, ['final', 'suspend']) &&
            !preg_match('/^\d+$/', $sale->invoice_no)
        ) {
            $invoiceNo = Sale::generateInvoiceNo($locationId);
        } else {
            $invoiceNo = $sale->invoice_no;
        }

        return [
            'invoice_no'   => $invoiceNo,
            'order_number' => null,
            'order_status' => null,
        ];
    }
}
