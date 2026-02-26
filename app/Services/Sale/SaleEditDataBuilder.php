<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Models\SalesProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the saleDetails payload for the POS edit view.
 *
 * Performance guarantee: regardless of product count, stock is resolved
 * in exactly 2 DB queries (one for "all-batch" products, one for
 * specific-batch products) instead of one query per product.
 *
 * Expected eager loads on the Sale model before calling build():
 *   products.product.unit
 *   products.product.batches.locationBatches.location
 *   products.batch
 *   products.imeis
 *   customer
 *   location
 */
class SaleEditDataBuilder
{
    // ----- Field lists (single source of truth) -----

    private const WITH_RELATIONS = [
        'products.product.unit',
        'products.product.batches.locationBatches.location',
        'products.batch',
        'products.imeis',
        'customer',
        'location',
    ];

    /**
     * Load a Sale with all relations required by the POS edit view.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findWithRelations(int $id): \App\Models\Sale
    {
        return \App\Models\Sale::with(self::WITH_RELATIONS)->findOrFail($id);
    }

    private const SALE_FIELDS = [
        'id', 'customer_id', 'location_id', 'sales_date', 'sale_type',
        'status', 'invoice_no', 'subtotal', 'discount_type', 'discount_amount',
        'final_total', 'total_paid', 'total_due', 'payment_status',
        'sale_notes', 'created_at', 'updated_at',
    ];

    private const PRODUCT_FIELDS = [
        'id', 'product_name', 'sku', 'unit_id', 'brand_id',
        'main_category_id', 'sub_category_id', 'stock_alert', 'alert_quantity',
        'product_image', 'description', 'is_imei_or_serial_no', 'is_for_selling',
        'product_type', 'pax', 'original_price', 'retail_price',
        'whole_sale_price', 'special_price', 'max_retail_price',
    ];

    private const BATCH_FIELDS = [
        'id', 'batch_no', 'product_id', 'qty', 'unit_cost',
        'wholesale_price', 'special_price', 'retail_price',
        'max_retail_price', 'expiry_date',
    ];

    private const CUSTOMER_FIELDS = [
        'id', 'prefix', 'first_name', 'last_name', 'mobile_no',
        'email', 'address', 'opening_balance', 'current_balance',
        'location_id', 'customer_type',
    ];

    private const LOCATION_FIELDS = [
        'id', 'name', 'location_id', 'address', 'province',
        'district', 'city', 'email', 'mobile', 'telephone_no',
    ];

    // -------------------------------------------------------------------------

    /**
     * Build and return the full saleDetails array.
     */
    public function build(Sale $sale): array
    {
        // Pre-fetch all stock values in 2 queries (instead of 1 per product)
        [$productStockMap, $batchStockMap] = $this->buildStockMaps($sale->products);

        return [
            'sale'          => $sale->only(self::SALE_FIELDS),
            'sale_products' => $sale->products
                ->map(fn($p) => $this->mapProduct($p, $productStockMap, $batchStockMap))
                ->values()
                ->all(),
            'customer'      => optional($sale->customer)->only(self::CUSTOMER_FIELDS),
            'location'      => optional($sale->location)->only(self::LOCATION_FIELDS),
        ];
    }

    // -------------------------------------------------------------------------
    // Stock pre-fetching
    // -------------------------------------------------------------------------

    /**
     * Build two lookup maps using at most 2 DB queries.
     *
     * Map 1 — $productStockMap["{productId}:{locationId}"] = totalQty
     *   Used for products that track stock across ALL batches (batch_id = 'all').
     *
     * Map 2 — $batchStockMap["{batchId}:{locationId}"] = qty
     *   Used for products tied to a SPECIFIC batch.
     *
     * Products with stock_alert === 0 are skipped (unlimited stock, no DB needed).
     *
     * @return array{0: array<string,float>, 1: array<string,float>}
     */
    private function buildStockMaps(Collection $products): array
    {
        // Only tracked (non-unlimited) products need DB queries
        $tracked = $products->filter(
            fn($p) => $p->product && $p->product->stock_alert !== 0
        );

        $allBatchProducts      = $tracked->filter(fn($p) => ($p->batch_id ?? 'all') === 'all');
        $specificBatchProducts = $tracked->filter(fn($p) => ($p->batch_id ?? 'all') !== 'all');

        $productStockMap = $this->fetchProductLevelStock($allBatchProducts);
        $batchStockMap   = $this->fetchBatchLevelStock($specificBatchProducts);

        return [$productStockMap, $batchStockMap];
    }

    /**
     * For products using batch_id = 'all':
     * SUM all batches' qty per (product_id, location_id) in one query.
     *
     * @return array<string,float>  key = "{productId}:{locationId}"
     */
    private function fetchProductLevelStock(Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $productIds  = $products->pluck('product_id')->unique()->values()->all();
        $locationIds = $products->pluck('location_id')->unique()->values()->all();

        // Single query: sum qty across all batches per (product_id, location_id)
        $rows = DB::table('location_batches')
            ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
            ->whereIn('batches.product_id', $productIds)
            ->whereIn('location_batches.location_id', $locationIds)
            ->selectRaw('batches.product_id, location_batches.location_id, SUM(location_batches.qty) as total')
            ->groupBy('batches.product_id', 'location_batches.location_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map["{$row->product_id}:{$row->location_id}"] = (float) $row->total;
        }

        return $map;
    }

    /**
     * For products with a specific batch_id:
     * Fetch qty per (batch_id, location_id) in one query.
     *
     * @return array<string,float>  key = "{batchId}:{locationId}"
     */
    private function fetchBatchLevelStock(Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $batchIds    = $products->pluck('batch_id')->unique()->values()->all();
        $locationIds = $products->pluck('location_id')->unique()->values()->all();

        $rows = DB::table('location_batches')
            ->whereIn('batch_id', $batchIds)
            ->whereIn('location_id', $locationIds)
            ->select('batch_id', 'location_id', 'qty')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map["{$row->batch_id}:{$row->location_id}"] = (float) $row->qty;
        }

        return $map;
    }

    // -------------------------------------------------------------------------
    // Product mapping
    // -------------------------------------------------------------------------

    /**
     * Map a single SalesProduct row into the frontend-expected shape.
     *
     * @param array<string,float> $productStockMap
     * @param array<string,float> $batchStockMap
     */
    private function mapProduct(
        SalesProduct $p,
        array $productStockMap,
        array $batchStockMap
    ): array {
        $imeiDetails = $p->imeis->map(fn($imei) => [
            'id'          => $imei->id,
            'imei_number' => $imei->imei_number,
            'batch_id'    => $imei->batch_id,
            'location_id' => $imei->location_id,
            'created_at'  => $imei->created_at,
            'updated_at'  => $imei->updated_at,
        ]);

        $unitModel   = optional($p->product)->unit;
        $unitDetails = $unitModel
            ? $unitModel->only(['id', 'name', 'short_name', 'allow_decimal'])
            : null;

        // ── Unlimited-stock products ──────────────────────────────────────────
        if ($p->product && $p->product->stock_alert === 0) {
            return $this->buildPayload(
                p           : $p,
                batchId     : 'all',
                totalQty    : 'Unlimited',
                currentStock: 'Unlimited',
                unitDetails : $unitDetails,
                batches     : [],
                imeiDetails : $imeiDetails,
                batchDetails: null,
            );
        }

        // ── Stock-tracked products ────────────────────────────────────────────
        $batchId = $p->batch_id ?? 'all';
        $locId   = $p->location_id;

        $currentStock = $batchId === 'all'
            ? ($productStockMap["{$p->product_id}:{$locId}"] ?? 0)
            : ($batchStockMap["{$batchId}:{$locId}"]         ?? 0);

        // Max allowed = current available + qty already tied up in THIS sale
        $freeQty  = $p->free_quantity ?? 0;
        $totalQty = $currentStock + $p->quantity + $freeQty;

        return $this->buildPayload(
            p           : $p,
            batchId     : $p->batch_id,          // original DB value (may be numeric or null)
            totalQty    : $totalQty,
            currentStock: $currentStock,
            unitDetails : $unitDetails,
            batches     : $this->buildBatchList($p),
            imeiDetails : $imeiDetails,
            batchDetails: optional($p->batch)->only(self::BATCH_FIELDS),
        );
    }

    /**
     * Build the per-product batch list from the already-eager-loaded relation.
     * No DB query — uses what was loaded in `products.product.batches.locationBatches.location`.
     */
    private function buildBatchList(SalesProduct $p): array
    {
        if (!$p->product || $p->product->batches->isEmpty()) {
            return [];
        }

        return $p->product->batches->map(fn($batch) => [
            'id'               => $batch->id,
            'batch_no'         => $batch->batch_no,
            'product_id'       => $batch->product_id,
            'unit_cost'        => $batch->unit_cost,
            'wholesale_price'  => $batch->wholesale_price,
            'special_price'    => $batch->special_price,
            'retail_price'     => $batch->retail_price,
            'max_retail_price' => $batch->max_retail_price,
            'expiry_date'      => $batch->expiry_date,
            'location_batches' => $batch->locationBatches
                ? $batch->locationBatches->map(fn($lb) => [
                    'batch_id'      => $lb->batch_id,
                    'location_id'   => $lb->location_id,
                    'location_name' => optional($lb->location)->name ?? 'N/A',
                    'quantity'      => $lb->qty,
                ])->toArray()
                : [],
        ])->toArray();
    }

    /**
     * Assemble the final product array that the frontend expects.
     *
     * @param mixed  $batchId     numeric batch ID, null, or 'all'
     * @param mixed  $totalQty    int|float|'Unlimited'
     * @param mixed  $currentStock int|float|'Unlimited'
     * @param array|null $batchDetails
     */
    private function buildPayload(
        SalesProduct $p,
        mixed $batchId,
        mixed $totalQty,
        mixed $currentStock,
        ?array $unitDetails,
        array $batches,
        Collection $imeiDetails,
        ?array $batchDetails
    ): array {
        return [
            'id'              => $p->id,
            'sale_id'         => $p->sale_id,
            'product_id'      => $p->product_id,
            'batch_id'        => $batchId,
            'location_id'     => $p->location_id,
            'quantity'        => $p->quantity,
            'free_quantity'   => $p->free_quantity ?? 0,
            'custom_name'     => $p->custom_name ?? null,
            'price_type'      => $p->price_type,
            'price'           => $p->price,
            'discount_type'   => $p->discount_type,
            'discount_amount' => $p->discount_amount,
            'tax'             => $p->tax,
            'created_at'      => $p->created_at,
            'updated_at'      => $p->updated_at,
            'total_quantity'  => $totalQty,
            'current_stock'   => $currentStock,
            'product'         => array_merge(
                optional($p->product)->only(self::PRODUCT_FIELDS) ?? [],
                ['batches' => $batches]
            ),
            'unit'            => $unitDetails,
            'batch'           => $batchDetails,
            'imei_numbers'    => $p->imeis->pluck('imei_number')->toArray(),
            'imeis'           => $imeiDetails,
        ];
    }
}
