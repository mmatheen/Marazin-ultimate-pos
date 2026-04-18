<?php

namespace App\Services\Product\Concerns;

use App\Models\Product;
use App\Models\StockHistory;
use App\Services\Location\LocationAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ProductStockHistory
{
    public function getStockHistory(Request $request, $productId)
    {
        $locationId = $request->input('location_id');
        $searchTerm = $request->input('term'); // For select2 search

        // Handle AJAX search requests (for select2)
        if ($request->ajax() && $searchTerm) {
            return Product::where('product_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('sku', 'like', '%' . $searchTerm . '%')
                ->select('id', 'product_name', 'sku')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'text' => $product->product_name . ' - ' . $product->sku
                    ];
                });
        }

        try {
            // Fetch product with all necessary relationships
            $productQuery = Product::with([
                'locationBatches' => function ($query) use ($locationId) {
                    if ($locationId) {
                        $query->where('location_id', $locationId);
                    }
                    $query->with('batch');
                },
                'locationBatches.stockHistories' => function ($query) {
                    $query->orderByDesc('updated_at')
                        ->orderByDesc('created_at')
                        ->orderByDesc('id')
                        ->with([
                            'locationBatch.batch.purchaseProducts.purchase.supplier',
                            'locationBatch.batch.salesProducts.sale.customer',
                            'locationBatch.batch.purchaseReturns.purchaseReturn.supplier',
                            'locationBatch.batch.saleReturns.salesReturn.customer',
                            'locationBatch.batch.stockAdjustments.stockAdjustment',
                            'locationBatch.batch.stockTransfers.stockTransfer',
                        ]);
                }
            ]);

            $product = $productQuery->findOrFail($productId);

            // Authoritative on-hand stock comes from location_batches (qty + free_qty).
            // Stock history can be incomplete for older data/imports, which would incorrectly show 0.
            $currentStockFromBatches = (float) $product->locationBatches->sum(function ($locBatch) {
                return (float) ($locBatch->qty ?? 0) + (float) ($locBatch->free_qty ?? 0);
            });

            // Flatten all stock histories across location batches
            $stockHistories = $product->locationBatches->flatMap(function ($locBatch) {
                return $locBatch->stockHistories;
            })->sort(function ($a, $b) {
                $aDate = $a->updated_at ?: $a->created_at;
                $bDate = $b->updated_at ?: $b->created_at;

                $aTs = $aDate ? $aDate->getTimestamp() : 0;
                $bTs = $bDate ? $bDate->getTimestamp() : 0;

                if ($aTs === $bTs) {
                    return ($b->id ?? 0) <=> ($a->id ?? 0);
                }

                return $bTs <=> $aTs;
            })->values();

            // Attach server-computed reference fields to reduce N/A in UI.
            $stockHistories = $stockHistories->map(function ($history) {
                $resolved = $this->resolveHistoryReferenceAndParty($history);
                $history->reference_no = $resolved['reference_no'];
                $history->party_name = $resolved['party_name'];
                return $history;
            });

            if ($stockHistories->isEmpty()) {
                if ($request->ajax()) {
                    return response()->json([
                        'error' => 'No stock history found for this product' . ($locationId ? ' in the selected location' : ''),
                        'product' => $product,
                        'stock_histories' => [],
                        'stock_type_sums' => [],
                        'current_stock' => round($currentStockFromBatches, 2),
                        'current_stock_source' => 'location_batches',
                    ]);
                }
                return redirect()->back()->withErrors('No stock history found for this product.');
            }

            // Group by stock_type and sum quantities
            $stockTypeSums = $stockHistories->groupBy('stock_type')->map(function ($group) {
                return $group->sum('quantity');
            });

            // Define types for In and Out
            $inTypes = [
                StockHistory::STOCK_TYPE_OPENING,
                StockHistory::STOCK_TYPE_PURCHASE,
                StockHistory::STOCK_TYPE_SALE_RETURN_WITH_BILL,
                StockHistory::STOCK_TYPE_SALE_RETURN_WITHOUT_BILL,
                StockHistory::STOCK_TYPE_SALE_REVERSAL,
                StockHistory::STOCK_TYPE_PURCHASE_RETURN_REVERSAL,
                StockHistory::STOCK_TYPE_TRANSFER_IN,
            ];

            $outTypes = [
                StockHistory::STOCK_TYPE_SALE,
                StockHistory::STOCK_TYPE_ADJUSTMENT,
                StockHistory::STOCK_TYPE_PURCHASE_RETURN,
                StockHistory::STOCK_TYPE_TRANSFER_OUT,
            ];

            // Calculate totals
            $quantitiesIn = $stockTypeSums->filter(fn($val, $key) => in_array($key, $inTypes))->sum();
            $quantitiesOut = $stockTypeSums->filter(fn($val, $key) => in_array($key, $outTypes))->sum(fn($val) => abs($val));
            $currentStockFromHistory = $quantitiesIn - $quantitiesOut;

            $responseData = [
                'product' => $product,
                'stock_histories' => $stockHistories,
                'stock_type_sums' => $stockTypeSums,
                'current_stock' => round($currentStockFromBatches, 2),
                'derived_current_stock' => round($currentStockFromHistory, 2),
                'current_stock_source' => 'location_batches',
            ];

            if ($request->ajax()) {
                return response()->json($responseData);
            }

            // For initial page load (non-AJAX)
            $products = Product::where('id', $productId)->get(); // Only load the current product initially
            $locations = app(LocationAccessService::class)->forUser(Auth::user());

            return view('product.product_stock_history', compact('products', 'locations'))->with($responseData);

        } catch (\Exception $e) {
            Log::error('Error in Web getStockHistory: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'error' => 'Error loading stock history: ' . $e->getMessage(),
                    'product' => null,
                    'stock_histories' => [],
                    'stock_type_sums' => [],
                    'current_stock' => 0,
                ], 500);
            }

            return redirect()->back()->withErrors('Error loading stock history. Please try again.');
        }
    }

    /**
     * Resolve reference number + party name for a stock history row.
     * This reduces UI-side "N/A" by providing authoritative, server-computed fields.
     */
    private function resolveHistoryReferenceAndParty(StockHistory $history): array
    {
        $batch = $history->locationBatch?->batch;
        if (!$batch) {
            return ['reference_no' => null, 'party_name' => null];
        }

        $historyQty = abs((float) ($history->quantity ?? 0));
        $historyTime = $history->created_at ?? $history->updated_at;
        $historyTs = $historyTime ? $historyTime->getTimestamp() : null;

        $candidates = collect();

        $addCandidate = function ($ref, $party, $qtyTotal, $ts) use (&$candidates) {
            $ref = $ref ? trim((string) $ref) : null;
            $party = $party ? trim((string) $party) : null;
            $qtyTotal = abs((float) ($qtyTotal ?? 0));

            if (!$ref && !$party) {
                return;
            }

            $candidates->push([
                'ref' => $ref,
                'party' => $party,
                'qty' => $qtyTotal,
                'ts' => $ts,
            ]);
        };

        $formatParty = function ($contact) {
            if (!$contact) return null;
            $first = trim((string) ($contact->first_name ?? ''));
            $last = trim((string) ($contact->last_name ?? ''));
            $name = trim($first . ' ' . $last);
            return $name !== '' ? $name : null;
        };

        switch ($history->stock_type) {
            case StockHistory::STOCK_TYPE_PURCHASE:
                foreach (($batch->purchaseProducts ?? collect()) as $pp) {
                    $purchase = $pp->purchase ?? null;
                    $addCandidate(
                        $purchase?->reference_no,
                        $formatParty($purchase?->supplier),
                        ((float) ($pp->quantity ?? 0)) + ((float) ($pp->free_quantity ?? 0)),
                        ($pp->created_at ?? $pp->updated_at)?->getTimestamp()
                    );
                }
                break;

            case StockHistory::STOCK_TYPE_SALE:
            case StockHistory::STOCK_TYPE_VIRTUAL_SALE:
            case StockHistory::STOCK_TYPE_SALE_ORDER:
                foreach (($batch->salesProducts ?? collect()) as $sp) {
                    $sale = $sp->sale ?? null;
                    $addCandidate(
                        $sale?->invoice_no,
                        $formatParty($sale?->customer),
                        ((float) ($sp->quantity ?? 0)) + ((float) ($sp->free_quantity ?? 0)),
                        ($sp->created_at ?? $sp->updated_at)?->getTimestamp()
                    );
                }
                break;

            case StockHistory::STOCK_TYPE_PURCHASE_RETURN:
            case StockHistory::STOCK_TYPE_PURCHASE_RETURN_REVERSAL:
                foreach (($batch->purchaseReturns ?? collect()) as $pr) {
                    $doc = $pr->purchaseReturn ?? null;
                    $addCandidate(
                        $doc?->reference_no,
                        $formatParty($doc?->supplier),
                        ((float) ($pr->quantity ?? 0)) + ((float) ($pr->free_quantity ?? 0)),
                        ($pr->created_at ?? $pr->updated_at)?->getTimestamp()
                    );
                }
                break;

            case StockHistory::STOCK_TYPE_SALE_RETURN_WITH_BILL:
            case StockHistory::STOCK_TYPE_SALE_RETURN_WITHOUT_BILL:
            case StockHistory::STOCK_TYPE_SALE_REVERSAL:
                foreach (($batch->saleReturns ?? collect()) as $sr) {
                    $doc = $sr->salesReturn ?? null;
                    $addCandidate(
                        $doc?->reference_no ?? $doc?->invoice_no,
                        $formatParty($doc?->customer),
                        ((float) ($sr->quantity ?? 0)) + ((float) ($sr->free_quantity ?? 0)),
                        ($sr->created_at ?? $sr->updated_at)?->getTimestamp()
                    );
                }
                break;

            case StockHistory::STOCK_TYPE_ADJUSTMENT:
                foreach (($batch->stockAdjustments ?? collect()) as $adj) {
                    $doc = $adj->stockAdjustment ?? null;
                    $addCandidate(
                        $doc?->reference_no,
                        null,
                        (float) ($adj->quantity ?? 0),
                        ($adj->created_at ?? $adj->updated_at)?->getTimestamp()
                    );
                }
                break;

            case StockHistory::STOCK_TYPE_TRANSFER_IN:
            case StockHistory::STOCK_TYPE_TRANSFER_OUT:
                foreach (($batch->stockTransfers ?? collect()) as $tr) {
                    $doc = $tr->stockTransfer ?? null;
                    $addCandidate(
                        $doc?->reference_no,
                        null,
                        (float) ($tr->quantity ?? 0),
                        ($tr->created_at ?? $tr->updated_at)?->getTimestamp()
                    );
                }
                break;
        }

        $salesProductsFallback = function () use ($history, $batch, $historyQty, $historyTime, $historyTs): ?array {
            if (!in_array($history->stock_type, [
                StockHistory::STOCK_TYPE_SALE,
                StockHistory::STOCK_TYPE_SALE_REVERSAL,
                StockHistory::STOCK_TYPE_VIRTUAL_SALE,
                StockHistory::STOCK_TYPE_SALE_ORDER,
            ], true)) {
                return null;
            }

            $lb = $history->locationBatch;
            if (!$lb || !$historyTime) {
                return null;
            }

            $windowStart = $historyTime->copy()->subMinutes(30);
            $windowEnd = $historyTime->copy()->addMinutes(30);

            $rows = DB::table('sales_products as sp')
                ->join('sales as s', 's.id', '=', 'sp.sale_id')
                ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
                ->where('sp.batch_id', (int) $lb->batch_id)
                ->where('sp.location_id', (int) $lb->location_id)
                ->where('sp.product_id', (int) $batch->product_id)
                ->whereBetween('sp.created_at', [$windowStart, $windowEnd])
                ->select([
                    's.invoice_no',
                    'c.first_name',
                    'c.last_name',
                    'sp.quantity',
                    'sp.free_quantity',
                    'sp.created_at',
                ])
                ->get();

            if ($rows->isEmpty()) {
                return null;
            }

            $best = $rows->map(function ($row) use ($historyQty, $historyTs) {
                $total = abs((float) ($row->quantity ?? 0) + (float) ($row->free_quantity ?? 0));
                $ts = $row->created_at ? strtotime($row->created_at) : null;
                $timeDiff = ($ts && $historyTs) ? abs($ts - $historyTs) : PHP_INT_MAX;
                $qtyDiff = abs($total - $historyQty);
                $row->match_score = ($qtyDiff <= 0.01 ? 0 : 100000) + $timeDiff;
                $row->total_qty = $total;
                return $row;
            })->sortBy('match_score')->first();

            if (!$best || ($best->match_score ?? PHP_INT_MAX) === PHP_INT_MAX) {
                return null;
            }

            if (abs(((float) ($best->total_qty ?? 0)) - $historyQty) > 0.01) {
                return null;
            }

            $party = trim(trim((string) ($best->first_name ?? '')) . ' ' . trim((string) ($best->last_name ?? '')));
            return [
                'reference_no' => $best->invoice_no ?: null,
                'party_name' => $party !== '' ? $party : null,
            ];
        };

        if ($candidates->isEmpty()) {
            return $salesProductsFallback() ?? ['reference_no' => null, 'party_name' => null];
        }

        $qtyMatches = $candidates->filter(fn ($c) => abs(($c['qty'] ?? 0) - $historyQty) <= 0.01);
        $pool = $qtyMatches->isNotEmpty() ? $qtyMatches : $candidates;

        if ($historyTs !== null) {
            $pool = $pool
                ->map(function ($c) use ($historyTs) {
                    $c['time_diff'] = $c['ts'] ? abs($c['ts'] - $historyTs) : PHP_INT_MAX;
                    return $c;
                })
                ->sortBy('time_diff')
                ->values();

            $best = $pool->first();
            if ($best && ($best['time_diff'] ?? PHP_INT_MAX) <= 600) {
                return ['reference_no' => $best['ref'] ?? null, 'party_name' => $best['party'] ?? null];
            }
        }

        $uniqueKey = $pool->map(fn ($c) => ($c['ref'] ?? 'N/A') . '|' . ($c['party'] ?? 'N/A'))->unique()->values();
        if ($uniqueKey->count() === 1 && $uniqueKey->first() !== 'N/A|N/A') {
            $best = $pool->first();
            return ['reference_no' => $best['ref'] ?? null, 'party_name' => $best['party'] ?? null];
        }

        return $salesProductsFallback() ?? ['reference_no' => null, 'party_name' => null];
    }
}
