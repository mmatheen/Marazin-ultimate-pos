<?php

namespace App\Services\Sale;

use App\Models\LocationBatch;
use App\Models\Sale;
use App\Models\StockHistory;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SaleOrderConversionService
 *
 * Handles all Sale-Order ⇄ Invoice conversion logic extracted from the Sale model.
 *
 * Public API:
 *   convert(Sale $sale): Sale          – convert a confirmed sale order into an invoice
 *   revert(Sale $sale): bool           – revert a converted invoice back to a sale order
 *   cancelOrder(Sale, array): Sale     – cancel a sale order and restore its stock
 */
class SaleOrderConversionService
{
    protected SaleProductProcessor $saleProductProcessor;
    protected UnifiedLedgerService $unifiedLedgerService;

    public function __construct(
        SaleProductProcessor $saleProductProcessor,
        UnifiedLedgerService $unifiedLedgerService
    ) {
        $this->saleProductProcessor = $saleProductProcessor;
        $this->unifiedLedgerService = $unifiedLedgerService;
    }

    // -------------------------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------------------------

    /**
     * Convert a Sale Order into a finalised Invoice.
     *
     * @throws \Exception on validation failure
     */
    public function convert(Sale $sale): Sale
    {
        // Must be a sale order
        if (!$sale->isSaleOrder()) {
            if ($sale->transaction_type === 'invoice') {
                throw new \Exception(
                    'This record is already an invoice (Invoice No: ' . ($sale->invoice_no ?? 'N/A') . ')'
                );
            }
            throw new \Exception('Only Sale Orders can be converted to invoices');
        }

        // Prevent double conversion
        if ($sale->order_status === 'completed') {
            throw new \Exception('This Sale Order has already been converted to an invoice');
        }

        if (!empty($sale->invoice_no) && $sale->transaction_type === 'invoice') {
            throw new \Exception('This Sale Order was already converted to Invoice No: ' . $sale->invoice_no);
        }

        // Order must be confirmed
        if ($sale->order_status === 'pending') {
            throw new \Exception('Cannot convert pending orders. Please confirm the order first.');
        }

        if ($sale->order_status === 'cancelled') {
            throw new \Exception('Cannot convert cancelled orders.');
        }

        if ($sale->order_status === 'on_hold') {
            throw new \Exception('Cannot convert orders on hold. Please change status first.');
        }

        // Validate batch/stock data
        $this->validateStock($sale);

        // Load IMEI numbers for products
        $sale->load('products.imeis');

        return DB::transaction(function () use ($sale) {
            // Generate invoice number
            $invoiceNo = Sale::generateInvoiceNo($sale->location_id);

            // Recalculate subtotal precisely from line items
            $correctSubtotal = $sale->products->sum(fn ($p) => $p->quantity * $p->price);

            $discountAmount = $sale->discount_amount ?? 0;
            if ($sale->discount_type === 'percentage') {
                $discountAmount = ($correctSubtotal * $discountAmount) / 100;
            }
            $shippingCharges   = $sale->shipping_charges ?? 0;
            $correctFinalTotal = $correctSubtotal - $discountAmount + $shippingCharges;

            if (abs($sale->subtotal - $correctSubtotal) > 0.01) {
                Log::warning('Sale Order subtotal corrected during conversion', [
                    'sale_id'             => $sale->id,
                    'order_number'        => $sale->order_number,
                    'old_subtotal'        => $sale->subtotal,
                    'corrected_subtotal'  => $correctSubtotal,
                    'difference'          => $sale->subtotal - $correctSubtotal,
                ]);
            }

            $sale->update([
                'transaction_type' => 'invoice',
                'invoice_no'       => $invoiceNo,
                'sales_date'       => now(),
                'status'           => 'final',
                'order_status'     => 'completed',
                'subtotal'         => $correctSubtotal,
                'final_total'      => $correctFinalTotal,
                'total_paid'       => 0,
                'total_due'        => $correctFinalTotal,
                'payment_status'   => 'Due',
            ]);

            // Update stock history type: sale_order → sale
            foreach ($sale->products as $item) {
                $this->updateStockOnConversion($item);
            }

            Log::info("Sale Order {$sale->order_number} converted to Invoice {$invoiceNo}", [
                'sale_id'      => $sale->id,
                'order_number' => $sale->order_number,
                'invoice_no'   => $invoiceNo,
            ]);

            $sale->refresh();

            // Record ledger entry for the new invoice (skip Walk-In customer)
            if ($sale->customer_id && $sale->customer_id != 1) {
                $this->unifiedLedgerService->recordSale($sale);
            }

            return $sale;
        });
    }

    /**
     * Cancel a Sale Order: restore its stock and mark it as cancelled.
     *
     * @param Sale  $saleOrder The sale order to cancel.
     * @param array $data      Optional overrides: order_notes, expected_delivery_date.
     *
     * @throws \Exception if the sale is not a sale order.
     */
    public function cancelOrder(Sale $saleOrder, array $data): Sale
    {
        if ($saleOrder->transaction_type !== 'sale_order') {
            throw new \Exception('This is not a Sale Order');
        }

        DB::transaction(function () use ($saleOrder, $data) {
            foreach ($saleOrder->products as $product) {
                $this->saleProductProcessor->restoreStock(
                    $product,
                    StockHistory::STOCK_TYPE_SALE_ORDER_REVERSAL
                );
            }

            $saleOrder->order_status = 'cancelled';
            $saleOrder->status       = 'cancelled';

            if (isset($data['order_notes'])) {
                $saleOrder->order_notes = $data['order_notes'];
            }

            if (isset($data['expected_delivery_date'])) {
                $saleOrder->expected_delivery_date = $data['expected_delivery_date'];
            }

            $saleOrder->save();
        });

        return $saleOrder->fresh();
    }

    /**
     * Update editable fields on an existing Sale Order.
     * Handles both plain field updates and full cancellation.
     *
     * @throws \Exception if the sale is not a sale order
     */
    public function updateOrder(Sale $saleOrder, array $data): Sale
    {
        if ($saleOrder->transaction_type !== 'sale_order') {
            throw new \Exception('This is not a Sale Order');
        }

        $isCancellation = isset($data['order_status'])
            && $data['order_status'] === 'cancelled'
            && $saleOrder->order_status !== 'cancelled';

        if ($isCancellation) {
            return $this->cancelOrder($saleOrder, $data);
        }

        if (isset($data['order_status']))           $saleOrder->order_status          = $data['order_status'];
        if (isset($data['order_notes']))            $saleOrder->order_notes           = $data['order_notes'];
        if (isset($data['expected_delivery_date'])) $saleOrder->expected_delivery_date = $data['expected_delivery_date'];
        $saleOrder->save();

        return $saleOrder->fresh();
    }

    /**
     * Revert an Invoice back to a Sale Order (e.g. on invoice cancellation).
     *
     * @throws \Exception on validation failure
     */
    public function revert(Sale $sale): bool
    {
        return DB::transaction(function () use ($sale) {
            if ($sale->transaction_type !== 'invoice') {
                throw new \Exception('This is not an invoice');
            }

            if ($sale->total_paid > 0) {
                throw new \Exception('Cannot revert invoice with payments. Please process refund first.');
            }

            $invoiceNo = $sale->invoice_no;

            $sale->update([
                'transaction_type' => 'sale_order',
                'order_status'     => 'confirmed',
                'status'           => 'final',
                'payment_status'   => 'Due',
                // Keep invoice_no for reference/history
            ]);

            // Revert stock history type: sale → sale_order
            foreach ($sale->products as $item) {
                $locationBatch = LocationBatch::where('batch_id', $item->batch_id)
                    ->where('location_id', $item->location_id)
                    ->first();

                if (!$locationBatch) {
                    continue;
                }

                $stockHistory = StockHistory::where('loc_batch_id', $locationBatch->id)
                    ->where('stock_type', StockHistory::STOCK_TYPE_SALE)
                    ->where('quantity', -$item->quantity)
                    ->whereHas('locationBatch.batch', fn ($q) => $q->where('product_id', $item->product_id))
                    ->orderByDesc('created_at')
                    ->first();

                if ($stockHistory) {
                    $stockHistory->update(['stock_type' => StockHistory::STOCK_TYPE_SALE_ORDER]);

                    Log::info("Reverted stock history type from sale to sale_order", [
                        'stock_history_id' => $stockHistory->id,
                        'batch_id'         => $item->batch_id,
                        'location_id'      => $item->location_id,
                        'quantity'         => $item->quantity,
                    ]);
                }
            }

            Log::info("Invoice {$invoiceNo} reverted back to Sale Order {$sale->order_number}", [
                'sale_id'      => $sale->id,
                'order_number' => $sale->order_number,
                'invoice_no'   => $invoiceNo,
            ]);

            $sale->refresh();
            return true;
        });
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * Validate that every line item still has a valid batch/location combination.
     *
     * @throws \Exception listing all invalid items
     */
    private function validateStock(Sale $sale): void
    {
        $invalidItems = [];

        foreach ($sale->products as $item) {
            // Non-batch-tracked items skip validation
            if (empty($item->batch_id)) {
                Log::info("Skipping batch validation for non-batch tracked product", [
                    'sale_product_id' => $item->id,
                    'product_id'      => $item->product_id,
                    'product_name'    => $item->product->product_name ?? 'Unknown',
                ]);
                continue;
            }

            $locationBatch = LocationBatch::where('batch_id', $item->batch_id)
                ->where('location_id', $item->location_id)
                ->first();

            if (!$locationBatch) {
                $productName = $item->product->product_name ?? 'Unknown Product';
                $invalidItems[] = [
                    'product'     => $productName,
                    'batch_id'    => $item->batch_id,
                    'location_id' => $item->location_id,
                ];
            }
        }

        if (!empty($invalidItems)) {
            $message = "Invalid batch/location combinations found:\n\n";
            foreach ($invalidItems as $item) {
                $message .= "• {$item['product']}: Batch ID {$item['batch_id']} at Location {$item['location_id']}\n";
            }
            $message .= "\nPlease contact administrator to resolve these batch issues.";
            throw new \Exception($message);
        }

        Log::info("Stock availability validation passed for sale order conversion", [
            'sale_order_id'  => $sale->id,
            'products_count' => $sale->products->count(),
        ]);
    }

    /**
     * Update a single line item's stock history from sale_order → sale.
     */
    private function updateStockOnConversion($item): void
    {
        // Skip non-batch-tracked items
        if (empty($item->batch_id)) {
            Log::info("Skipping stock history update for non-batch tracked product", [
                'sale_product_id' => $item->id,
                'product_id'      => $item->product_id,
            ]);
            return;
        }

        $locationBatch = LocationBatch::where('batch_id', $item->batch_id)
            ->where('location_id', $item->location_id)
            ->first();

        if (!$locationBatch) {
            return;
        }

        $stockHistory = StockHistory::where('loc_batch_id', $locationBatch->id)
            ->where('stock_type', StockHistory::STOCK_TYPE_SALE_ORDER)
            ->where('quantity', -$item->quantity)
            ->whereHas('locationBatch.batch', fn ($q) => $q->where('product_id', $item->product_id))
            ->orderByDesc('created_at')
            ->first();

        if ($stockHistory) {
            $stockHistory->update(['stock_type' => StockHistory::STOCK_TYPE_SALE]);

            Log::info("Updated stock history type from sale_order to sale", [
                'stock_history_id' => $stockHistory->id,
                'batch_id'         => $item->batch_id,
                'location_id'      => $item->location_id,
                'quantity'         => $item->quantity,
            ]);
        } else {
            Log::warning("Stock history record not found for sale order conversion", [
                'batch_id'   => $item->batch_id,
                'location_id' => $item->location_id,
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
            ]);
        }
    }
}
