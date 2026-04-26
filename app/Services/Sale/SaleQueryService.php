<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Builder;
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
    /**
     * Eager loads for POS recent transactions and similar lists.
     * Customer must use withoutGlobalScopes() — otherwise LocationScope (and sales-rep city filter)
     * drops the relation and the UI shows "Walk-In Customer" for every row.
     */
    private static function withFullListing(): array
    {
        return [
            'products.product',
            'customer' => fn ($q) => $q->withoutGlobalScopes(),
            'location',
            'payments',
            'user',
        ];
    }

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
        return Sale::with([
            'products.product',
            'customer' => fn ($q) => $q->withoutGlobalScopes(),
            'location',
            'payments',
        ])->findOrFail($id);
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
        $query = Sale::with(['products.product.unit', 'salesReturns'])
            ->where('invoice_no', $invoiceNo);

        $this->applyViewOwnSalesRestriction($query);

        $sale = $query->first();

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
        $query = Sale::where(function ($q) use ($term) {
            $q->where('invoice_no', 'LIKE', '%' . $term . '%')
                ->orWhere('id', 'LIKE', '%' . $term . '%');
        });

        $this->applyViewOwnSalesRestriction($query);

        return $query->get(['invoice_no as value', 'id']);
    }

    /**
     * Return all suspended sales formatted for the POS suspended-sales list.
     */
    public function getSuspended(): \Illuminate\Support\Collection
    {
        $query = Sale::where('status', 'suspend')
            ->with([
                'customer' => fn ($q) => $q->withoutGlobalScopes(),
                'products.product',
            ]);

        $this->applyViewOwnSalesRestriction($query);

        return $query->get()
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

    /**
     * Same rule as SaleDataTableService: users with only "view own sales" must not see others' rows.
     * Applied here because Recent Transactions uses SaleQueryService, not the DataTable service.
     */
    private function applyViewOwnSalesRestriction(Builder $query): void
    {
        $user = auth()->user();
        if ($user && $user->can('view own sales') && ! $user->can('view all sales')) {
            $table = $query->getModel()->getTable();
            $query->where($table . '.user_id', $user->id);
        }
    }

    private function recentTransactions(): Collection
    {
        // Ignore session "selected_location" so managers with multiple locations
        // see recent bills from every outlet they are assigned to (same as list-sale).
        $query = Sale::withoutGlobalScope(LocationScope::class)
            ->with(self::withFullListing())
            ->where(fn ($q) => $q->where('transaction_type', 'invoice')->orWhereNull('transaction_type'))
            ->whereIn('status', ['final', 'quotation', 'draft', 'jobticket', 'suspend'])
            ->where('payment_status', '!=', 'Cancelled')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200);

        $user = auth()->user();
        if (! LocationScope::userBypassesLocationScope($user)) {
            if (! $user) {
                $query->whereRaw('0 = 1');
            } else {
                $user->loadMissing('locations');
                $locationIds = $user->locations->pluck('id')->all();
                if ($locationIds === []) {
                    $query->whereRaw('0 = 1');
                } else {
                    $query->whereIn($query->getModel()->getTable() . '.location_id', $locationIds);
                }
            }
        }

        $this->applyViewOwnSalesRestriction($query);

        return $query->get();
    }

    private function saleOrders(): Collection
    {
        $query = Sale::with(self::withFullListing())
            ->where('transaction_type', 'sale_order')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200);

        $this->applyViewOwnSalesRestriction($query);

        return $query->get();
    }

    private function byStatus(string $status): Collection
    {
        $query = Sale::with(self::withFullListing())
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200);

        $this->applyViewOwnSalesRestriction($query);

        return $query->get();
    }

    private function finalInvoices(Request $request): Collection
    {
        $query = $request->has('customer_id')
            ? Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                ->with(self::withFullListing())
                ->where('customer_id', $request->customer_id)
            : Sale::with(self::withFullListing());

        $query
            ->where('status', 'final')
            ->where('transaction_type', '!=', 'sale_order')
            ->where(fn ($q) => $q->where('transaction_type', 'invoice')->orWhereNull('transaction_type'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100);

        $this->applyViewOwnSalesRestriction($query);

        return $query->get();
    }
}
