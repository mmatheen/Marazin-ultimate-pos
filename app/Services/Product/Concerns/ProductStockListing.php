<?php

namespace App\Services\Product\Concerns;

use App\Models\ImeiNumber;
use App\Models\Location;
use App\Models\Product;
use App\Services\Location\LocationAccessService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ProductStockListing
{
    public function getAllProductStocks(Request $request): JsonResponse
    {
        try {
           

            if (ob_get_level()) {
                ob_clean();
            }

            header('Content-Type: application/json');

            $startTime = microtime(true);
            $now = now();

            // DataTable params with validation (legacy support)
            $rawLength = (int)$request->input('length', 50);
            $forExport = $request->boolean('for_export');
            $maxPage = ($forExport && !$request->has('per_page') && !$request->has('page')) ? 25000 : 100;
            $perPageDataTable = min(max($rawLength, 1), $maxPage);
            $startDataTable = max(0, (int)$request->input('start', 0));
            $pageDataTable = intval($startDataTable / $perPageDataTable) + 1;

            // Standard pagination params (for POS)
            $perPageStandard = min((int)$request->input('per_page', 24), 100);
            $pageStandard = max(1, (int)$request->input('page', 1));

            $perPage = $request->has('per_page') || $request->has('page') ? $perPageStandard : $perPageDataTable;
            $page = $request->has('per_page') || $request->has('page') ? $pageStandard : $pageDataTable;

            // DataTable search and ordering
            $search = $request->input('search.value');
            $orderColumnIndex = $request->input('order.0.column');
            $orderColumn = $request->input("columns.$orderColumnIndex.data", 'id');
            $orderDir = $request->input('order.0.dir', 'asc');

            // Custom filters (from filter dropdowns)
            $filterProductName = $request->input('product_name');
            $filterCategory = $request->input('main_category_id');
            $filterSubCategory = $request->input('sub_category_id');
            $filterBrand = $request->input('brand_id');
            $locationId = $request->input('location_id');
            $stockStatus = $request->input('stock_status');
            $withStock = $request->input('with_stock');

            if ($locationId && is_numeric($locationId)) {
                $locationId = (int) $locationId;
            }

            $user = auth()->user();
            /** @var LocationAccessService $locationAccess */
            $locationAccess = app(LocationAccessService::class);
            $userAccessibleLocations = $locationAccess->forUser($user);
            $userLocationIds = $userAccessibleLocations->pluck('id')->toArray();

            if ($locationId && !empty($userLocationIds) && !in_array($locationId, $userLocationIds)) {
                $locationId = null;
            }

            $query = Product::select([
                'id',
                'product_name',
                'sku',
                'unit_id',
                'brand_id',
                'main_category_id',
                'sub_category_id',
                'stock_alert',
                'alert_quantity',
                'product_image',
                'description',
                'is_imei_or_serial_no',
                'is_for_selling',
                'product_type',
                'pax',
                'original_price',
                'tax_percent',
                'selling_price_tax_type',
                'retail_price',
                'whole_sale_price',
                'special_price',
                'max_retail_price',
                'is_active'
            ])
                ->from(DB::raw('products FORCE INDEX FOR JOIN (PRIMARY)'))
                ->with([
                    'locations' => function ($query) use ($userLocationIds) {
                        $query->select('locations.id', 'locations.name');
                        if (!empty($userLocationIds)) {
                            $query->whereIn('locations.id', $userLocationIds);
                        }
                    },
                    'unit:id,name,short_name,allow_decimal',
                    'discounts' => function ($query) use ($now) {
                        $query->where('is_active', true)
                            ->where('start_date', '<=', $now);
                    },
                    'batches' => function ($query) {
                        $query->select([
                            'id',
                            'batch_no',
                            'product_id',
                            'unit_cost',
                            'wholesale_price',
                            'special_price',
                            'retail_price',
                            'max_retail_price',
                            'expiry_date',
                            'created_at'
                        ])->orderBy('created_at', 'desc');
                    },
                    'batches.locationBatches' => function ($query) use ($locationId, $userLocationIds) {
                        if (!empty($userLocationIds)) {
                            $query->whereIn('location_id', $userLocationIds);
                        }
                        if ($locationId) {
                            $query->where('location_id', $locationId);
                        }

                        $query->select(['id', 'batch_id', 'location_id', 'qty', 'free_qty'])
                            ->with('location:id,name');
                    }
                ])
                ->when(!$request->has('show_all'), function ($query) {
                    return $query->where('is_active', true);
                });

            if (!empty($locationId)) {
                $query->where(function ($q) use ($locationId) {
                    $q->whereHas('locations', function ($q2) use ($locationId) {
                        $q2->where('locations.id', $locationId);
                    })->orWhereHas('batches.locationBatches', function ($q2) use ($locationId) {
                        $q2->where('location_id', $locationId);
                    });
                });
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if (!empty($filterProductName)) {
                $query->where('product_name', $filterProductName);
            }
            if (!empty($filterCategory)) {
                $query->where('main_category_id', $filterCategory);
            }
            if (!empty($filterSubCategory)) {
                $query->where('sub_category_id', $filterSubCategory);
            }
            if (!empty($filterBrand)) {
                $query->where('brand_id', $filterBrand);
            }

            $filterProductIds = $request->input('product_ids');
            if ($filterProductIds !== null && $filterProductIds !== '') {
                if (is_string($filterProductIds)) {
                    $ids = array_filter(array_map('intval', explode(',', $filterProductIds)));
                } elseif (is_array($filterProductIds)) {
                    $ids = array_values(array_filter(array_map('intval', $filterProductIds)));
                } else {
                    $ids = [];
                }
                $ids = array_values(array_unique($ids));
                if (!empty($ids)) {
                    $ids = array_slice($ids, 0, 25000);
                    $query->whereIn('id', $ids);
                }
            }

            if ($withStock == '1' || $withStock === true || $withStock === 1) {
                $query->where(function ($q) use ($locationId, $userLocationIds) {
                    $q->where('stock_alert', 0)
                        ->orWhere(function ($subQ) use ($locationId, $userLocationIds) {
                            $subQ->whereHas('batches', function ($batchQ) use ($locationId, $userLocationIds) {
                                $batchQ->whereHas('locationBatches', function ($locBatchQ) use ($locationId, $userLocationIds) {
                                    $locBatchQ->where('qty', '>', 0);
                                    if ($locationId) {
                                        $locBatchQ->where('location_id', $locationId);
                                    } elseif (!empty($userLocationIds)) {
                                        $locBatchQ->whereIn('location_id', $userLocationIds);
                                    }
                                });
                            });
                        });
                });
            }

            if (!empty($stockStatus)) {
                switch ($stockStatus) {
                    case 'in_stock':
                        $query->whereHas('batches.locationBatches', function ($q) use ($locationId, $userLocationIds) {
                            $q->where('qty', '>', 0);
                            if ($locationId) {
                                $q->where('location_id', $locationId);
                            } elseif (!empty($userLocationIds)) {
                                $q->whereIn('location_id', $userLocationIds);
                            }
                        });
                        break;

                    case 'free_stock':
                        $query->whereHas('batches.locationBatches', function ($q) use ($locationId, $userLocationIds) {
                            $q->where('free_qty', '>', 0);
                            if ($locationId) {
                                $q->where('location_id', $locationId);
                            } elseif (!empty($userLocationIds)) {
                                $q->whereIn('location_id', $userLocationIds);
                            }
                        });
                        break;

                    case 'out_of_stock':
                        $query->whereDoesntHave('batches.locationBatches', function ($q) use ($locationId, $userLocationIds) {
                            $q->where('qty', '>', 0);
                            if ($locationId) {
                                $q->where('location_id', $locationId);
                            } elseif (!empty($userLocationIds)) {
                                $q->whereIn('location_id', $userLocationIds);
                            }
                        });
                        break;

                    case 'low_stock':
                        $query->whereHas('batches.locationBatches', function ($q) use ($locationId, $userLocationIds) {
                            $q->where('qty', '>', 0)
                                ->whereRaw('qty <= (SELECT alert_quantity FROM products WHERE products.id = location_batches.batch_id)');
                            if ($locationId) {
                                $q->where('location_id', $locationId);
                            } elseif (!empty($userLocationIds)) {
                                $q->whereIn('location_id', $userLocationIds);
                            }
                        });
                        break;
                }
            }

            $validOrderCols = [
                'id',
                'product_name',
                'sku',
                'retail_price',
                'total_stock',
                'main_category_id',
                'brand_id'
            ];
            if (in_array($orderColumn, $validOrderCols)) {
                $query->orderBy($orderColumn, $orderDir);
            } else {
                $query->orderBy('id', 'asc');
            }

            $totalCount = Product::count();

            try {
                $products = $query->paginate($perPage, ['*'], 'page', $page);
            } catch (\Exception $e) {
                Log::error('Error during pagination: ' . $e->getMessage());
                throw new \Exception('Database query failed: ' . $e->getMessage());
            }

            $filteredCount = $products->total();

            $productIds = $products->pluck('id');
            $pageProductIds = $productIds->all();

            $imeisQuery = ImeiNumber::whereIn('product_id', $productIds)
                ->with(['location:id,name']);

            if ($locationId) {
                $imeisQuery->where('location_id', $locationId);
            }

            $imeis = $imeisQuery->get()->groupBy('product_id');

            $stockPaidByProduct = collect();
            $stockFreeByProduct = collect();
            $batchSumsByBatchId = [];
            $productIdsWithBatchAtLocationSet = [];
            $locationsWithStockByProduct = collect();
            $locationForFilter = null;

            if (!empty($pageProductIds)) {
                $stockPaidByProduct = DB::table('location_batches')
                    ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                    ->whereIn('batches.product_id', $pageProductIds)
                    ->when($locationId, function ($q) use ($locationId) {
                        return $q->where('location_batches.location_id', $locationId);
                    })
                    ->groupBy('batches.product_id')
                    ->selectRaw('batches.product_id as product_id, SUM(location_batches.qty) as sq')
                    ->pluck('sq', 'product_id');

                $stockFreeByProduct = DB::table('location_batches')
                    ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                    ->whereIn('batches.product_id', $pageProductIds)
                    ->when($locationId, function ($q) use ($locationId) {
                        return $q->where('location_batches.location_id', $locationId);
                    })
                    ->groupBy('batches.product_id')
                    ->selectRaw('batches.product_id as product_id, SUM(location_batches.free_qty) as sfq')
                    ->pluck('sfq', 'product_id');

                $allBatchIds = $products->flatMap(function ($p) {
                    return $p->batches->pluck('id');
                })->unique()->values()->all();

                if (!empty($allBatchIds)) {
                    $sumRows = DB::table('location_batches')
                        ->whereIn('batch_id', $allBatchIds)
                        ->when($locationId, function ($q) use ($locationId) {
                            return $q->where('location_id', $locationId);
                        })
                        ->groupBy('batch_id')
                        ->selectRaw('batch_id, SUM(qty) as q, SUM(free_qty) as fq')
                        ->get();
                    foreach ($sumRows as $br) {
                        $batchSumsByBatchId[(int)$br->batch_id] = [
                            'q' => $br->q,
                            'fq' => $br->fq,
                        ];
                    }
                }

                if ($locationId) {
                    $pidsWithBatch = DB::table('location_batches')
                        ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                        ->whereIn('batches.product_id', $pageProductIds)
                        ->where('location_batches.location_id', $locationId)
                        ->distinct()
                        ->pluck('batches.product_id');
                    foreach ($pidsWithBatch as $pid) {
                        $productIdsWithBatchAtLocationSet[(int)$pid] = true;
                    }
                    $locationForFilter = Location::find($locationId);
                } else {
                    $locationsWithStockByProduct = DB::table('location_batches')
                        ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                        ->join('locations', 'location_batches.location_id', '=', 'locations.id')
                        ->whereIn('batches.product_id', $pageProductIds)
                        ->where('location_batches.qty', '>', 0)
                        ->select('batches.product_id', 'locations.id as loc_id', 'locations.name as loc_name')
                        ->distinct()
                        ->get()
                        ->groupBy('product_id');
                }
            }

            $productStocks = [];

            foreach ($products as $product) {
                $productBatches = $product->batches;
                $allowDecimal = $product->unit && $product->unit->allow_decimal;

                $pid = $product->id;
                $totalStock = (float)($stockPaidByProduct->get($pid) ?? $stockPaidByProduct->get((string)$pid) ?? 0);
                $totalFreeStock = (float)($stockFreeByProduct->get($pid) ?? $stockFreeByProduct->get((string)$pid) ?? 0);

                if ($allowDecimal) {
                    $totalStock = round($totalStock, 2);
                    $totalFreeStock = round($totalFreeStock, 2);
                } else {
                    $totalStock = (int)$totalStock;
                    $totalFreeStock = (int)$totalFreeStock;
                }

                $filteredBatches = $productBatches;

                $activeDiscounts = $product->discounts->map(function ($discount) use ($now) {
                    return [
                        'id' => $discount->id,
                        'name' => $discount->name,
                        'description' => $discount->description,
                        'type' => $discount->type,
                        'amount' => $discount->amount,
                        'start_date' => $discount->start_date ? $discount->start_date->format('Y-m-d H:i:s') : null,
                        'end_date' => $discount->end_date ? $discount->end_date->format('Y-m-d H:i:s') : null,
                        'is_active' => (bool)$discount->is_active,
                        'apply_to_all' => (bool)$discount->apply_to_all,
                        'is_expired' => $discount->end_date && $discount->end_date < $now,
                    ];
                });

                $productImeis = $imeis->get($product->id, collect())->map(function ($imei) use ($productBatches) {
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
                });

                $productLocations = collect();

                if ($locationId) {
                    $hasBatchRowAtLocation = isset($productIdsWithBatchAtLocationSet[(int)$product->id]);
                    $isAssignedToLocation = $product->locations->contains('id', $locationId);

                    if (($hasBatchRowAtLocation || $isAssignedToLocation) && $locationForFilter) {
                        $productLocations->push($locationForFilter);
                    }
                } else {
                    $assignedLocations = $product->locations;
                    $locationsWithStock = $locationsWithStockByProduct->get($product->id)
                        ?? $locationsWithStockByProduct->get((string)$product->id)
                        ?? collect();

                    $allLocationIds = $assignedLocations->pluck('id')
                        ->merge($locationsWithStock->pluck('loc_id'))
                        ->unique();

                    $productLocations = Location::whereIn('id', $allLocationIds)->get();
                }

                $locationData = $productLocations->map(fn($loc) => [
                    'location_id' => $loc->id,
                    'location_name' => $loc->name
                ])->values();

                if ($locationData->isEmpty()) {
                    $locationData = collect([]);
                }

                $latestBatch = $filteredBatches
                    ->filter(function ($batch) use ($locationId) {
                        $locationBatches = $locationId
                            ? $batch->locationBatches->where('location_id', $locationId)
                            : $batch->locationBatches;
                        return $locationBatches->sum('qty') > 0;
                    })
                    ->sortByDesc('created_at')
                    ->first();

                $currentRetailPrice = $latestBatch ? (float)$latestBatch->retail_price : (float)$product->retail_price;
                $currentWholesalePrice = $latestBatch ? (float)$latestBatch->wholesale_price : (float)$product->whole_sale_price;
                $currentSpecialPrice = $latestBatch ? (float)$latestBatch->special_price : (float)$product->special_price;
                $currentMaxRetailPrice = $latestBatch ? (float)$latestBatch->max_retail_price : (float)$product->max_retail_price;

                $productStocks[] = [
                    'product' => [
                        'id' => $product->id,
                        'product_name' => $product->product_name ?? '',
                        'sku' => $product->sku ?? '',
                        'unit_id' => $product->unit_id,
                        'unit' => $product->unit ? [
                            'id' => $product->unit->id,
                            'name' => $product->unit->name ?? '',
                            'short_name' => $product->unit->short_name ?? '',
                            'allow_decimal' => (bool)($product->unit->allow_decimal ?? 0),
                        ] : null,
                        'brand_id' => $product->brand_id,
                        'main_category_id' => $product->main_category_id,
                        'sub_category_id' => $product->sub_category_id,
                        'stock_alert' => $product->stock_alert,
                        'alert_quantity' => $product->alert_quantity ?? 0,
                        'product_image' => $product->product_image ?? '',
                        'description' => $product->description ?? '',
                        'is_imei_or_serial_no' => $product->is_imei_or_serial_no ?? 0,
                        'is_for_selling' => $product->is_for_selling ?? 1,
                        'product_type' => $product->product_type ?? '',
                        'pax' => $product->pax ?? 0,
                        'original_price' => $product->original_price ?? 0,
                        'tax_percent' => (float)($product->tax_percent ?? 0),
                        'selling_price_tax_type' => (string)($product->selling_price_tax_type ?? 'inclusive'),
                        'retail_price' => $currentRetailPrice,
                        'whole_sale_price' => $currentWholesalePrice,
                        'special_price' => $currentSpecialPrice,
                        'max_retail_price' => $currentMaxRetailPrice,
                        'is_active' => $product->is_active ?? 0,
                        'latest_batch_id' => $latestBatch ? $latestBatch->id : null,
                    ],
                    'total_stock' => $totalStock,
                    'total_free_stock' => $totalFreeStock,
                    'batches' => $filteredBatches->map(function ($batch) use ($allowDecimal, $locationId, $batchSumsByBatchId) {
                        $locationBatches = $locationId
                            ? $batch->locationBatches->where('location_id', $locationId)
                            : $batch->locationBatches;

                        $sums = $batchSumsByBatchId[(int)$batch->id] ?? ['q' => 0, 'fq' => 0];
                        $batchQty = $sums['q'];
                        $batchFreeQty = $sums['fq'];

                        return [
                            'id' => $batch->id,
                            'batch_no' => $batch->batch_no ?? '',
                            'unit_cost' => $batch->unit_cost ?? 0,
                            'wholesale_price' => $batch->wholesale_price ?? 0,
                            'special_price' => $batch->special_price ?? 0,
                            'retail_price' => $batch->retail_price ?? 0,
                            'max_retail_price' => $batch->max_retail_price ?? 0,
                            'expiry_date' => $batch->expiry_date,
                            'total_batch_quantity' => $allowDecimal
                                ? round((float)$batchQty, 2)
                                : (int)$batchQty,
                            'total_batch_free_quantity' => $allowDecimal
                                ? round((float)$batchFreeQty, 2)
                                : (int)$batchFreeQty,
                            'location_batches' => $locationBatches->map(function ($lb) use ($allowDecimal) {
                                return [
                                    'batch_id' => $lb->batch_id,
                                    'location_id' => $lb->location_id,
                                    'location_name' => optional($lb->location)->name ?? 'N/A',
                                    'quantity' => $allowDecimal ? round((float)($lb->qty ?? 0), 2) : (int)($lb->qty ?? 0),
                                    'free_quantity' => $allowDecimal ? round((float)($lb->free_qty ?? 0), 2) : (int)($lb->free_qty ?? 0)
                                ];
                            })->values()
                        ];
                    })->values(),
                    'locations' => $locationData,
                    'has_batches' => $filteredBatches->isNotEmpty(),
                    'discounts' => $activeDiscounts,
                    'imei_numbers' => $productImeis
                ];
            }

            $executionTime = round(microtime(true) - $startTime, 3);
            Log::info("Product stocks fetched in {$executionTime}s", [
                'page' => $page,
                'per_page' => $perPage,
                'total_products' => count($productStocks),
            ]);

            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => $totalCount,
                'recordsFiltered' => $filteredCount,
                'data' => array_values($productStocks),
                'status' => 200,
                'pagination' => [
                    'total' => $filteredCount,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            if (ob_get_level()) {
                ob_clean();
            }

            Log::error('Error fetching product stocks:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all(),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB',
                'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024 . 'MB',
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ]);

            header('Content-Type: application/json');

            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'status' => 500,
                'message' => 'An error occurred while fetching product stocks.',
                'error' => $e->getMessage(),
                'debug' => [
                    'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB',
                    'php_version' => PHP_VERSION
                ]
            ], 500);
        }
    }
}
