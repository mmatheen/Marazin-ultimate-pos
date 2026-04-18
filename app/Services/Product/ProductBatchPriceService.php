<?php

namespace App\Services\Product;

use App\Models\Batch;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductBatchPriceService
{
    public function getProductBatchesWithLocations(int $productId): array
    {
        $product = Product::with(['unit:id,name,short_name,allow_decimal'])->findOrFail($productId);

        $batches = Batch::where('product_id', $productId)
            ->with(['locationBatches.location:id,name'])
            ->select([
                'id',
                'batch_no',
                'product_id',
                'unit_cost',
                'wholesale_price',
                'special_price',
                'retail_price',
                'max_retail_price',
                'expiry_date',
                'qty',
                'free_qty',
                'created_at',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $allowDecimal = (bool) optional($product->unit)->allow_decimal;

        return [
            'product' => $product,
            'batches' => $batches->map(function ($batch) use ($allowDecimal) {
                $lbTotals = DB::table('location_batches')
                    ->where('batch_id', $batch->id)
                    ->selectRaw('COALESCE(SUM(qty),0) as sum_paid, COALESCE(SUM(free_qty),0) as sum_free')
                    ->first();

                $sumPaid = (float) ($lbTotals->sum_paid ?? 0);
                $sumFree = (float) ($lbTotals->sum_free ?? 0);
                $actualQty = $sumPaid + $sumFree;

                $locationsMerged = $batch->locationBatches
                    ->groupBy('location_id')
                    ->map(function ($rows) use ($allowDecimal) {
                        $first = $rows->first();

                        return [
                            'id' => optional($first->location)->id,
                            'name' => optional($first->location)->name ?? 'N/A',
                            'qty' => $allowDecimal
                                ? round((float) $rows->sum('qty'), 2)
                                : (int) $rows->sum('qty'),
                            'free_qty' => $allowDecimal
                                ? round((float) $rows->sum('free_qty'), 2)
                                : (int) $rows->sum('free_qty'),
                        ];
                    })
                    ->values()
                    ->sortByDesc(function ($row) {
                        return (float) ($row['qty'] ?? 0) + (float) ($row['free_qty'] ?? 0);
                    })
                    ->values();

                return [
                    'id' => $batch->id,
                    'batch_no' => $batch->batch_no,
                    'unit_cost' => $batch->unit_cost,
                    'wholesale_price' => $batch->wholesale_price,
                    'special_price' => $batch->special_price,
                    'retail_price' => $batch->retail_price,
                    'max_retail_price' => $batch->max_retail_price,
                    'expiry_date' => $batch->expiry_date,
                    'qty' => $allowDecimal ? round((float) $actualQty, 2) : (int) $actualQty,
                    'qty_paid' => $allowDecimal ? round($sumPaid, 2) : (int) $sumPaid,
                    'qty_free' => $allowDecimal ? round($sumFree, 2) : (int) $sumFree,
                    'locations' => $locationsMerged,
                ];
            }),
        ];
    }

    public function updateBatchPricesAndSyncProducts(array $batches): void
    {
        DB::transaction(function () use ($batches) {
            $productIds = [];

            foreach ($batches as $batchData) {
                $batch = Batch::findOrFail($batchData['id']);

                $batch->update([
                    'unit_cost' => $batchData['unit_cost'],
                    'wholesale_price' => $batchData['wholesale_price'],
                    'special_price' => $batchData['special_price'],
                    'retail_price' => $batchData['retail_price'],
                    'max_retail_price' => $batchData['max_retail_price'],
                ]);

                $productIds[$batch->product_id] = true;
            }

            foreach (array_keys($productIds) as $productId) {
                $this->updateProductPricesFromLatestBatch((int) $productId);
            }
        });
    }

    private function updateProductPricesFromLatestBatch(int $productId): void
    {
        try {
            $latestBatch = Batch::where('product_id', $productId)
                ->whereHas('locationBatches', function ($q) {
                    $q->where('qty', '>', 0);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$latestBatch) {
                Log::warning("⚠️ No batch with stock found for product #{$productId}");
                return;
            }

            Product::where('id', $productId)->update([
                'retail_price' => $latestBatch->retail_price,
                'whole_sale_price' => $latestBatch->wholesale_price,
                'special_price' => $latestBatch->special_price,
                'max_retail_price' => $latestBatch->max_retail_price,
            ]);

            Log::info("✅ Updated product #{$productId} prices from latest batch #{$latestBatch->id}");
        } catch (\Exception $e) {
            Log::error('❌ Error updating product prices from batch: ' . $e->getMessage());
        }
    }

    public function respondProductBatchesJson(int $productId): JsonResponse
    {
        try {
            $result = $this->getProductBatchesWithLocations($productId);
            $product = $result['product'];

            return response()->json([
                'status' => 200,
                'product' => [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                ],
                'batches' => $result['batches'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product batches: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error fetching product batches'
            ], 500);
        }
    }

    public function respondUpdateBatchPricesJson(array $batches): JsonResponse
    {
        try {
            $this->updateBatchPricesAndSyncProducts($batches);

            app(ProductCacheService::class)->clearAllProductRelatedCachesAggressive();

            return response()->json([
                'status' => 200,
                'message' => 'Batch prices updated successfully!',
                'cache_cleared' => true,
                'timestamp' => time(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating batch prices: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error updating batch prices'
            ], 500);
        }
    }
}

