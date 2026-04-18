<?php

namespace App\Services\Product\Concerns;

use App\Models\ImeiNumber;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ProductStockAutocomplete
{
    public function autocompleteStock(Request $request): JsonResponse
    {
       
        $locationId = $request->input('location_id');
        $search = $request->input('search');
        $context = $request->input('context', 'pos');
        $backordersEnabled = (bool) (Setting::value('enable_backorders') ?? 0);
        $allowBackorderSearch = $context === 'pos'
            && $backordersEnabled
            && $request->boolean('allow_backorder_search');
        // POS: small page for desktop-like speed; purchase/others can pass per_page
        $perPage = (int) $request->input('per_page', $context === 'pos' ? 15 : 100);

        $query = Product::with([
            'unit:id,name,short_name,allow_decimal',
            'discounts' => function ($query) {
                $query->where('is_active', true);
            },
            'batches' => function ($query) {
                $query->select([
                    'id',
                    'batch_no',
                    'product_id',
                    'unit_cost',
                    'qty',
                    'wholesale_price',
                    'special_price',
                    'retail_price',
                    'max_retail_price',
                    'expiry_date'
                ]);
            },
            'batches.locationBatches' => function ($q) use ($locationId) {
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
                $q->select(['id', 'batch_id', 'location_id', 'qty', 'free_qty'])
                    ->with('location:id,name');
            }
        ])
            // Only show active products in POS/autocomplete
            ->where('is_active', true)
            // For POS: by default show only products with stock > 0.
            // If backorder search is explicitly enabled, also include 0-stock items
            // that are mapped at the selected location so sale-order backorder flow
            // can search and add them.
            // For purchase context: skip the qty>0 filter so all products (including 0 stock) are searchable.
            ->when($locationId && $context !== 'purchase', function ($query) use ($locationId, $allowBackorderSearch) {
                return $query->where(function ($q) use ($locationId, $allowBackorderSearch) {
                    // Unlimited stock products are always visible regardless of location
                    $q->where('stock_alert', 0)
                        // Backorder-enabled POS can search 0-stock items mapped to this location.
                        ->when($allowBackorderSearch, function ($qb) use ($locationId) {
                            $qb->orWhereHas('batches.locationBatches', function ($inner) use ($locationId) {
                                $inner->where('location_id', $locationId);
                            });
                        }, function ($qb) use ($locationId) {
                            // Otherwise keep strict visibility: only positive paid/free stock.
                            $qb->orWhereHas('batches.locationBatches', function ($inner) use ($locationId) {
                                $inner->where('location_id', $locationId)
                                    ->where('qty', '>', 0);
                            })->orWhereHas('batches.locationBatches', function ($inner) use ($locationId) {
                                $inner->where('location_id', $locationId)
                                    ->where('free_qty', '>', 0);
                            });
                        });
                });
            });

        if ($search) {
            // POS autocomplete: keep search lightweight (name + SKU only, description is heavy)
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })->orderByRaw("
                CASE
                    WHEN sku = ? THEN 1
                    WHEN LOWER(product_name) = LOWER(?) THEN 2
                    WHEN sku LIKE ? THEN 3
                    WHEN LOWER(product_name) LIKE LOWER(?) THEN 4
                    WHEN product_name LIKE ? THEN 5
                    ELSE 6
                END,
                CHAR_LENGTH(product_name) ASC,
                product_name ASC
            ", [
                $search,                    // Exact SKU match (priority 1)
                $search,                    // Exact product name match (priority 2)
                $search . '%',              // SKU starts with search term (priority 3)
                $search . '%',              // Product name starts with search term (priority 4)
                '%' . $search . '%',        // Product name contains search term anywhere (priority 5)
            ]);
        } else {
            $query->orderBy('product_name', 'ASC');
        }

        $products = $query->take($perPage)->get();

        $productIds = $products->pluck('id')->toArray();
        $batchIds = $products->flatMap(fn($p) => $p->batches->pluck('id'))->unique()->values()->toArray();

        // One query for all product-level stock totals (avoids N+1 per-product SUMs)
        $productTotals = collect();
        if (!empty($productIds)) {
            $productTotals = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->whereIn('batches.product_id', $productIds)
                ->when($locationId, fn($q) => $q->where('location_batches.location_id', $locationId))
                ->groupBy('batches.product_id')
                ->selectRaw('batches.product_id, COALESCE(SUM(location_batches.qty), 0) as total_qty, COALESCE(SUM(location_batches.free_qty), 0) as total_free_qty')
                ->get()
                ->keyBy('product_id');
        }

        // One query for all batch-level qty/free_qty (avoids N+1 per-batch SUMs and model calls)
        $batchTotals = collect();
        if (!empty($batchIds)) {
            $batchTotals = DB::table('location_batches')
                ->whereIn('batch_id', $batchIds)
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->groupBy('batch_id')
                ->selectRaw('batch_id, COALESCE(SUM(qty), 0) as qty, COALESCE(SUM(free_qty), 0) as free_qty')
                ->get()
                ->keyBy('batch_id');
        }

        // Only fetch IMEIs for IMEI products (reduces payload and DB load for non-IMEI items)
        $imeiProductIds = $products->where('is_imei_or_serial_no', true)->pluck('id');
        $imeis = collect();
        if ($imeiProductIds->isNotEmpty()) {
            $imeisQuery = ImeiNumber::whereIn('product_id', $imeiProductIds)
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->with('location:id,name');
            $imeis = $imeisQuery->get()->groupBy('product_id');
        }

        $results = $products->map(function ($product) use ($locationId, $imeis, $productTotals, $batchTotals) {
            $productBatches = $product->batches;

            // Filter batches with locationBatches based on location filter
            $filteredBatches = $productBatches->filter(function ($batch) use ($locationId) {
                if ($locationId) {
                    return $batch->locationBatches->where('location_id', $locationId)->isNotEmpty();
                }
                return $batch->locationBatches->isNotEmpty();
            });

            $allowDecimal = $product->unit && $product->unit->allow_decimal;

            // Use precomputed product totals (no per-product DB calls)
            $pt = $productTotals->get($product->id);
            $totalStock = $pt ? (float) $pt->total_qty : 0;
            $totalFreeStock = $pt ? (float) $pt->total_free_qty : 0;
            if ($allowDecimal) {
                $totalStock = round($totalStock, 2);
                $totalFreeStock = round($totalFreeStock, 2);
            } else {
                $totalStock = (int) $totalStock;
                $totalFreeStock = (int) $totalFreeStock;
            }

            // Map active discounts
            $activeDiscounts = $product->discounts->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'description' => $discount->description,
                    'type' => $discount->type,
                    'amount' => $discount->amount,
                    'start_date' => $discount->start_date ? $discount->start_date->format('Y-m-d H:i:s') : null,
                    'end_date' => $discount->end_date ? $discount->end_date->format('Y-m-d H:i:s') : null,
                    'is_active' => (bool) $discount->is_active,
                    'apply_to_all' => (bool) $discount->apply_to_all,
                    'is_expired' => $discount->end_date && $discount->end_date < now(),
                ];
            });

            // POS autocomplete: minimal product fields (no brand_id, category ids, description)
            $productPayload = [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'unit_id' => $product->unit_id,
                'unit' => $product->unit ? [
                    'id' => $product->unit->id,
                    'name' => $product->unit->name,
                    'short_name' => $product->unit->short_name,
                    'allow_decimal' => (bool) $product->unit->allow_decimal,
                ] : null,
                'stock_alert' => $product->stock_alert,
                'alert_quantity' => $product->alert_quantity,
                'product_image' => $product->product_image,
                'is_imei_or_serial_no' => $product->is_imei_or_serial_no,
                'is_for_selling' => $product->is_for_selling,
                'product_type' => $product->product_type,
                'pax' => $product->pax,
                'original_price' => $product->original_price,
                'tax_percent' => (float) ($product->tax_percent ?? 0),
                'selling_price_tax_type' => (string) ($product->selling_price_tax_type ?? 'inclusive'),
                'retail_price' => $product->retail_price,
                'whole_sale_price' => $product->whole_sale_price,
                'special_price' => $product->special_price,
                'max_retail_price' => $product->max_retail_price,
            ];

            // IMEI products only: include imei_numbers; others get empty array (smaller payload)
            $productImeis = $product->is_imei_or_serial_no
                ? $imeis->get($product->id, collect())->map(function ($imei) use ($productBatches) {
                    $batch = $productBatches->firstWhere('id', $imei->batch_id);
                    return [
                        'id' => $imei->id,
                        'imei_number' => $imei->imei_number,
                        'location_id' => $imei->location_id,
                        'location_name' => optional($imei->location)->name ?? 'N/A',
                        'batch_id' => $imei->batch_id,
                        'batch_no' => optional($batch)->batch_no ?? 'N/A',
                        'status' => $imei->status ?? 'available'
                    ];
                })->values()->all()
                : [];

            return [
                'product' => $productPayload,
                'total_stock' => $product->stock_alert == 0 ? 'Unlimited' : $totalStock,
                // ✅ FIX: Expose free stock so JS dropdown and billing can compute total sellable qty
                'total_free_stock' => $product->stock_alert == 0 ? 0 : $totalFreeStock,
                'batches' => $filteredBatches->map(function ($batch) use ($allowDecimal, $locationId, $batchTotals) {
                    $locationBatches = $locationId
                        ? $batch->locationBatches->where('location_id', $locationId)
                        : $batch->locationBatches;

                    // Use precomputed batch totals (no per-batch DB or model calls)
                    $bt = $batchTotals->get($batch->id);
                    $batchQty = $bt ? (float) $bt->qty : 0;
                    $freeQty = $bt ? (float) $bt->free_qty : 0;
                    $paidQty = max(0, $batchQty - $freeQty);
                    $freeQtyPercentage = $batchQty > 0 ? round(($freeQty / $batchQty) * 100, 1) : 0;

                    return [
                        'id' => $batch->id,
                        'batch_no' => $batch->batch_no,
                        'unit_cost' => $batch->unit_cost,
                        'wholesale_price' => $batch->wholesale_price,
                        'special_price' => $batch->special_price,
                        'retail_price' => $batch->retail_price,
                        'max_retail_price' => $batch->max_retail_price,
                        'expiry_date' => $batch->expiry_date,
                        'total_batch_quantity' => $allowDecimal
                            ? round((float) $batchQty, 2)
                            : (int) $batchQty,
                        'free_qty' => $allowDecimal ? round($freeQty, 2) : (int) $freeQty,
                        'paid_qty' => $allowDecimal ? round($paidQty, 2) : (int) $paidQty,
                        'free_qty_percentage' => $freeQtyPercentage,
                        'location_batches' => $locationBatches->map(function ($lb) use ($allowDecimal) {
                            return [
                                'batch_id' => $lb->batch_id,
                                'location_id' => $lb->location_id,
                                'location_name' => optional($lb->location)->name ?? 'N/A',
                                'quantity' => $allowDecimal ? round((float) $lb->qty, 2) : (int) $lb->qty,
                                // ✅ FIX: Include free_qty so JS can compute total sellable qty per batch
                                'free_quantity' => $allowDecimal ? round((float) ($lb->free_qty ?? 0), 2) : (int) ($lb->free_qty ?? 0)
                            ];
                        })
                    ];
                }),
                'locations' => $locationId ?
                    // If location filter is applied, show the filtered location data
                    $filteredBatches->flatMap(function ($batch) {
                        return $batch->locationBatches->map(function ($lb) {
                            return [
                                'location_id' => $lb->location_id,
                                'location_name' => optional($lb->location)->name ?? 'N/A',
                                'quantity' => $lb->qty
                            ];
                        });
                    })->unique('location_id')->values()->toArray() :
                    // If no location filter, show all locations where this product exists
                    $filteredBatches->flatMap(function ($batch) {
                        return $batch->locationBatches->map(function ($lb) {
                            return [
                                'location_id' => $lb->location_id,
                                'location_name' => optional($lb->location)->name ?? 'N/A',
                                'quantity' => $lb->qty
                            ];
                        });
                    })->groupBy('location_id')->map(function ($locBatches, $locId) {
                        $firstLoc = $locBatches->first();
                        return [
                            'location_id' => $locId,
                            'location_name' => $firstLoc['location_name'],
                            'quantity' => $locBatches->sum('quantity')
                        ];
                    })->values()->toArray(),
                'has_batches' => $filteredBatches->isNotEmpty(),
                'discounts' => $activeDiscounts,
                'imei_numbers' => $productImeis
            ];
        })->filter()->values(); // Remove null values and re-index

        return response()->json([
            'status' => 200,
            'data' => $results,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
