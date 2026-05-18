<?php

namespace App\Services\Sale;

use App\Models\Payment;
use App\Models\Sale;
use App\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

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
            return $this->recentTransactions($request);
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
        $sale = Sale::with([
            'products.product',
            'customer' => fn ($q) => $q->withoutGlobalScopes(),
            'location',
            'invoicePayments',
        ])->findOrFail($id);

        $sale->setRelation('payments', $sale->invoicePayments);
        $sale->unsetRelation('invoicePayments');

        $paymentIds = $sale->payments->pluck('id');

        $activities = Activity::query()
            ->where(function (Builder $q) use ($sale, $paymentIds) {
                $q->where(function (Builder $q2) use ($sale) {
                    $q2->where('subject_type', Sale::class)
                        ->where('subject_id', $sale->id);
                });
                if ($paymentIds->isNotEmpty()) {
                    $q->orWhere(function (Builder $q2) use ($paymentIds) {
                        $q2->where('subject_type', Payment::class)
                            ->whereIn('subject_id', $paymentIds);
                    });
                }
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->with('causer')
            ->get();

        $sale->setRelation('activities', $activities);

        return $sale;
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
    public function getByInvoiceNo(string $invoiceNo, ?int $forEditSalesReturnId = null): array
    {
        $query = Sale::withoutGlobalScope(LocationScope::class)
            ->with(['products.product.unit', 'salesReturns'])
            ->where('invoice_no', $invoiceNo);

        $user = auth()->user();
        if ($user && ! LocationScope::userBypassesLocationScope($user)) {
            $locationIds = $this->getAccessibleLocationIds($user->id);

            if ($locationIds === []) {
                throw new ModelNotFoundException('Sale not found');
            }

            $query->whereIn('location_id', $locationIds);
        }

        $this->applyViewOwnSalesRestriction($query);

        $sale = $query->first();

        if (!$sale) {
            throw new ModelNotFoundException('Sale not found');
        }

        if ($forEditSalesReturnId) {
            $editingReturn = \App\Models\SalesReturn::query()->find($forEditSalesReturnId);
            if (! $editingReturn || (int) $editingReturn->sale_id !== (int) $sale->id) {
                throw new ModelNotFoundException('Sale return not found for this invoice');
            }
        }

        $otherReturns = $forEditSalesReturnId
            ? $sale->salesReturns->where('id', '!=', $forEditSalesReturnId)
            : $sale->salesReturns;

        if ($otherReturns->count() > 0) {
            throw new \DomainException(json_encode([
                'error'          => 'This sale has already been returned. Multiple returns for the same invoice are not allowed.',
                'returned_count' => $otherReturns->count(),
                'return_details' => $otherReturns->map(fn ($r) => [
                    'return_date'  => $r->return_date,
                    'return_total' => $r->return_total,
                    'notes'        => $r->notes,
                ])->all(),
            ]));
        }

        $editingReturnProducts = $forEditSalesReturnId
            ? \App\Models\SalesReturnProduct::where('sales_return_id', $forEditSalesReturnId)->get()
            : collect();

        $products = $sale->products->map(function ($product) use ($sale, $forEditSalesReturnId, $editingReturnProducts) {
            // Per invoice line (not per product_id) — same product twice = two separate rows
            $excludeReturnId = $forEditSalesReturnId;
            $product->current_quantity = $sale->getCurrentSaleLineQuantity($product, $excludeReturnId);
            $product->current_free_quantity = $sale->getCurrentSaleLineFreeQuantity($product, $excludeReturnId);
            $product->return_price = $product->price;

            $matchedReturnLine = $editingReturnProducts->first(function ($rp) use ($product) {
                if ((int) $rp->product_id !== (int) $product->product_id) {
                    return false;
                }
                if (abs((float) $rp->return_price - (float) $product->price) >= 0.01) {
                    return false;
                }

                return (string) ($rp->batch_id ?? '') === (string) ($product->batch_id ?? '');
            });
            $product->return_quantity = $matchedReturnLine ? (float) $matchedReturnLine->quantity : 0;
            $product->return_free_quantity = $matchedReturnLine ? (float) ($matchedReturnLine->free_quantity ?? 0) : 0;

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
        });

        if ($forEditSalesReturnId) {
            // Edit: show every invoice line so user can add/remove return qty per line
            $products = $products->filter(
                fn ($p) => (float) ($p->quantity ?? 0) > 0 || (float) ($p->free_quantity ?? 0) > 0
            )->values();
        } else {
            $products = $products->filter(
                fn ($p) => $p->current_quantity > 0 || $p->current_free_quantity > 0
            )->values();
        }

        $billDiscountType = $sale->discount_type;
        if (is_string($billDiscountType) && strtolower($billDiscountType) === 'percent') {
            $billDiscountType = 'percentage';
        }

        $sale->loadMissing('location:id,name');

        return [
            'sale_id'           => $sale->id,
            'invoice_no'        => $invoiceNo,
            'customer_id'       => $sale->customer_id,
            'location_id'       => $sale->location_id,
            'location_name'     => $sale->location?->name,
            'products'          => $products,
            'edit_mode'         => $forEditSalesReturnId !== null,
            'original_discount' => [
                'discount_type'           => $billDiscountType,
                'discount_amount'         => (float) ($sale->discount_amount ?? 0),
                'subtotal'                => (float) ($sale->subtotal ?? 0),
                'final_total'             => (float) ($sale->final_total ?? 0),
                'total_original_quantity' => $sale->products->sum('quantity'),
            ],
        ];
    }

    /**
     * Simple invoice-number / ID search.
     */
    public function search(string $term): Collection
    {
        $query = Sale::withoutGlobalScope(LocationScope::class)
            ->where(function ($q) use ($term) {
                $q->where('invoice_no', 'LIKE', '%' . $term . '%')
                    ->orWhere('id', 'LIKE', '%' . $term . '%');
            });

        $user = auth()->user();
        if ($user && ! LocationScope::userBypassesLocationScope($user)) {
            $locationIds = $this->getAccessibleLocationIds($user->id);

            if ($locationIds === []) {
                return new \Illuminate\Database\Eloquent\Collection();
            }

            $query->whereIn('location_id', $locationIds);
        }

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
        if ($user && method_exists($user, 'can') && $user->can('view own sales') && ! $user->can('view all sales')) {
            $table = $query->getModel()->getTable();
            $query->where($table . '.user_id', $user->id);
        }
    }

    /**
     * Recent Transactions modal (POS): list rows only — do NOT eager-load products/payments.
     * Full relations were ~1MB+ JSON and 10–20s; the UI only needs invoice, customer name, location, date, total.
     */
    private function recentTransactions(Request $request): Collection
    {
        $limit = (int) $request->get('limit', 100);
        $limit = max(10, min(200, $limit));

        $salesTable = (new Sale())->getTable();

        // Ignore session "selected_location" so managers with multiple locations
        // see recent bills from every outlet they are assigned to (same as list-sale).
        $query = Sale::withoutGlobalScope(LocationScope::class)
            ->select([
                $salesTable.'.id',
                $salesTable.'.status',
                $salesTable.'.invoice_no',
                $salesTable.'.final_total',
                $salesTable.'.sales_date',
                $salesTable.'.created_at',
                $salesTable.'.customer_id',
                $salesTable.'.location_id',
            ])
            ->with([
                'customer' => fn ($q) => $q->withoutGlobalScopes()
                    ->select('id', 'prefix', 'first_name', 'last_name'),
                'location:id,name',
            ])
            ->where(fn ($q) => $q->where($salesTable.'.transaction_type', 'invoice')->orWhereNull($salesTable.'.transaction_type'))
            ->whereIn($salesTable.'.status', ['final', 'quotation', 'draft', 'jobticket', 'suspend'])
            ->where($salesTable.'.payment_status', '!=', 'Cancelled')
            ->orderByDesc($salesTable.'.created_at')
            ->orderByDesc($salesTable.'.id')
            ->limit($limit);

        $user = auth()->user();
        if (! LocationScope::userBypassesLocationScope($user)) {
            if (! $user) {
                $query->whereRaw('0 = 1');
            } else {
                $locationIds = $this->getAccessibleLocationIds($user->id);
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

    private function getAccessibleLocationIds(int $userId): array
    {
        return DB::table('location_user')
            ->where('user_id', $userId)
            ->pluck('location_id')
            ->all();
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
