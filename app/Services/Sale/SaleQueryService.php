<?php

namespace App\Services\Sale;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * SaleQueryService
 *
 * Centralises all Sale read-queries that were previously scattered
 * across SaleController methods (index, salesDetails, getSaleByInvoiceNo,
 * searchSales, fetchSuspendedSales).
 */
class SaleQueryService
{
    private const WITH_FULL = ['products.product', 'customer', 'location', 'payments', 'user'];

    // -------------------------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------------------------

    /**
     * Resolve the correct sales collection for the index() endpoint based on
     * request parameters.
     */
    public function resolveIndex(Request $request): Collection
    {
        if ($request->has('recent_transactions') && $request->get('recent_transactions') == 'true') {
            return $this->recentTransactions();
        }

        if ($request->has('sale_orders') && $request->get('sale_orders') == 'true') {
            return $this->saleOrders();
        }

        if ($request->has('status') && in_array($request->get('status'), ['draft', 'quotation', 'suspend'])) {
            return $this->byStatus($request->get('status'));
        }

        return $this->finalInvoices($request);
    }

    /**
     * Load a single sale with relations for the salesDetails endpoint.
     *
     * @throws ModelNotFoundException
     */
    public function getDetails(int $id): Sale
    {
        return Sale::with('products.product', 'customer', 'location', 'payments')
            ->findOrFail($id);
    }

    /**
     * Build the full payload for the getSaleByInvoiceNo (sale-return) endpoint.
     *
     * @throws ModelNotFoundException  when invoice is not found (404)
     * @throws \DomainException        when the invoice was already returned (409)
     *
     * @return array{
     *     sale_id: int,
     *     invoice_no: string,
     *     customer_id: int,
     *     location_id: int,
     *     products: \Illuminate\Support\Collection,
     *     original_discount: array,
     *     already_returned?: array
     * }
     */
    public function getByInvoiceNo(string $invoiceNo): array
    {
        $sale = Sale::with(['products.product.unit', 'salesReturns'])
            ->where('invoice_no', $invoiceNo)
            ->first();

        if (!$sale) {
            throw new ModelNotFoundException('Sale not found');
        }

        if ($sale->salesReturns->count() > 0) {
            throw new \DomainException(json_encode([
                'error'          => 'This sale has already been returned. Multiple returns for the same invoice are not allowed.',
                'returned_count' => $sale->salesReturns->count(),
                'return_details' => $sale->salesReturns->map(fn ($r) => [
                    'return_date'  => $r->return_date,
                    'return_total' => $r->return_total,
                    'notes'        => $r->notes,
                ])->all(),
            ]));
        }

        $products = $sale->products->map(function ($product) use ($sale) {
            $product->current_quantity = $sale->getCurrentSaleQuantity($product->product_id);
            $product->return_price     = $product->price;

            $productModel  = $product->product;
            $product->unit = ($productModel && $productModel->unit)
                ? [
                    'id'            => $productModel->unit->id,
                    'name'          => $productModel->unit->name,
                    'short_name'    => $productModel->unit->short_name,
                    'allow_decimal' => $productModel->unit->allow_decimal,
                ]
                : ['id' => null, 'name' => 'Pieces', 'short_name' => 'Pc(s)', 'allow_decimal' => false];

            return $product;
        })->filter(fn ($p) => $p->current_quantity > 0)->values();

        return [
            'sale_id'           => $sale->id,
            'invoice_no'        => $invoiceNo,
            'customer_id'       => $sale->customer_id,
            'location_id'       => $sale->location_id,
            'products'          => $products,
            'original_discount' => [
                'discount_type'           => $sale->discount_type,
                'discount_amount'         => $sale->discount_amount ?? 0,
                'subtotal'                => $sale->subtotal ?? 0,
                'final_total'             => $sale->final_total ?? 0,
                'total_original_quantity' => $sale->products->sum('quantity'),
            ],
        ];
    }

    /**
     * Simple invoice-number / ID search.
     */
    public function search(string $term): Collection
    {
        return Sale::where('invoice_no', 'LIKE', '%' . $term . '%')
            ->orWhere('id', 'LIKE', '%' . $term . '%')
            ->get(['invoice_no as value', 'id']);
    }

    /**
     * Return all suspended sales formatted for the POS suspended-sales list.
     */
    public function getSuspended(): \Illuminate\Support\Collection
    {
        return Sale::where('status', 'suspend')
            ->with(['customer', 'products.product'])
            ->get()
            ->map(fn ($sale) => [
                'id'          => $sale->id,
                'invoice_no'  => $sale->invoice_no,
                'sales_date'  => $sale->created_at,
                'customer'    => $sale->customer
                    ? ['name' => trim($sale->customer->first_name . ' ' . $sale->customer->last_name)]
                    : ['name' => 'Walk-In Customer'],
                'products'    => $sale->products->toArray(),
                'final_total' => $sale->final_total,
            ]);
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    private function recentTransactions(): Collection
    {
        return Sale::with(self::WITH_FULL)
            ->where(fn ($q) => $q->where('transaction_type', 'invoice')->orWhereNull('transaction_type'))
            ->whereIn('status', ['final', 'quotation', 'draft', 'jobticket', 'suspend'])
            ->where('payment_status', '!=', 'Cancelled')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();
    }

    private function saleOrders(): Collection
    {
        return Sale::with(self::WITH_FULL)
            ->where('transaction_type', 'sale_order')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();
    }

    private function byStatus(string $status): Collection
    {
        return Sale::with(self::WITH_FULL)
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();
    }

    private function finalInvoices(Request $request): Collection
    {
        $query = $request->has('customer_id')
            ? Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                ->with(self::WITH_FULL)
                ->where('customer_id', $request->customer_id)
            : Sale::with(self::WITH_FULL);

        return $query
            ->where('status', 'final')
            ->where('transaction_type', '!=', 'sale_order')
            ->where(fn ($q) => $q->where('transaction_type', 'invoice')->orWhereNull('transaction_type'))
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
    }
}
