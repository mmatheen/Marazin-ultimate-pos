<?php

namespace App\Services\Product\Concerns;

use App\Models\Batch;
use App\Models\LocationBatch;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ProductStockReconcile
{
    /**
     * Stock reconciliation helper: location_batches vs history + transactional rollups.
     */
    public function reconcileStock(Request $request, int $productId): JsonResponse
    {
        $locationId = $request->input('location_id');
        $batchNo = trim((string) $request->input('batch_no', ''));
        $batchId = $request->input('batch_id');

        try {
            $product = Product::select('id', 'product_name', 'sku')->findOrFail($productId);

            $batchQuery = Batch::query()->where('product_id', $productId);
            if ($batchId) {
                $batchQuery->where('id', (int) $batchId);
            } elseif ($batchNo !== '') {
                $batchQuery->where('batch_no', $batchNo);
            }

            $batches = $batchQuery->get(['id', 'batch_no']);
            if ($batches->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No batch found for this product with given batch filter.',
                    'product' => $product,
                    'filters' => ['location_id' => $locationId, 'batch_no' => $batchNo, 'batch_id' => $batchId],
                ], 404);
            }

            $batchIds = $batches->pluck('id')->all();

            $locationBatchesQuery = LocationBatch::query()
                ->whereIn('batch_id', $batchIds);
            if ($locationId) {
                $locationBatchesQuery->where('location_id', (int) $locationId);
            }
            $locationBatches = $locationBatchesQuery->get(['id', 'batch_id', 'location_id', 'qty', 'free_qty']);

            $locBatchIds = $locationBatches->pluck('id')->all();

            $onHand = [
                'paid_qty' => (float) $locationBatches->sum(fn ($lb) => (float) ($lb->qty ?? 0)),
                'free_qty' => (float) $locationBatches->sum(fn ($lb) => (float) ($lb->free_qty ?? 0)),
            ];
            $onHand['total'] = (float) ($onHand['paid_qty'] + $onHand['free_qty']);

            $stockHistoryByType = collect();
            if (!empty($locBatchIds)) {
                $stockHistoryByType = StockHistory::query()
                    ->whereIn('loc_batch_id', $locBatchIds)
                    ->select('stock_type', DB::raw('SUM(quantity) as qty_sum'))
                    ->groupBy('stock_type')
                    ->pluck('qty_sum', 'stock_type');
            }

            $salesTotals = DB::table('sales_products as sp')
                ->join('sales as s', 's.id', '=', 'sp.sale_id')
                ->where('sp.product_id', $productId)
                ->whereIn('sp.batch_id', $batchIds)
                ->when($locationId, fn ($q) => $q->where('sp.location_id', (int) $locationId))
                ->whereIn('s.status', ['final', 'suspend'])
                ->selectRaw('COALESCE(SUM(sp.quantity),0) as paid, COALESCE(SUM(sp.free_quantity),0) as free')
                ->first();

            $returnTotals = DB::table('sales_return_products as srp')
                ->join('sales_returns as sr', 'sr.id', '=', 'srp.sales_return_id')
                ->where('srp.product_id', $productId)
                ->whereIn('srp.batch_id', $batchIds)
                ->when($locationId, fn ($q) => $q->where('srp.location_id', (int) $locationId))
                ->selectRaw('COALESCE(SUM(srp.quantity),0) as paid, COALESCE(SUM(srp.free_quantity),0) as free')
                ->first();

            return response()->json([
                'status' => 'success',
                'product' => $product,
                'filters' => [
                    'location_id' => $locationId ? (int) $locationId : null,
                    'batch_no' => $batchNo !== '' ? $batchNo : null,
                    'batch_id' => $batchId ? (int) $batchId : null,
                ],
                'batches' => $batches,
                'location_batches' => $locationBatches,
                'on_hand_from_location_batches' => [
                    'paid_qty' => round($onHand['paid_qty'], 4),
                    'free_qty' => round($onHand['free_qty'], 4),
                    'total' => round($onHand['total'], 4),
                ],
                'stock_history_sum_by_type' => $stockHistoryByType,
                'transaction_rollups' => [
                    'sales_products_sum' => [
                        'paid_qty' => round((float) ($salesTotals->paid ?? 0), 4),
                        'free_qty' => round((float) ($salesTotals->free ?? 0), 4),
                        'total' => round((float) (($salesTotals->paid ?? 0) + ($salesTotals->free ?? 0)), 4),
                    ],
                    'sales_return_products_sum' => [
                        'paid_qty' => round((float) ($returnTotals->paid ?? 0), 4),
                        'free_qty' => round((float) ($returnTotals->free ?? 0), 4),
                        'total' => round((float) (($returnTotals->paid ?? 0) + ($returnTotals->free ?? 0)), 4),
                    ],
                ],
                'note' => 'If physical stock exists but on_hand_from_location_batches.total is 0, the system stock is currently 0 and needs adjustment or data correction.',
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Stock reconcile failed', [
                'product_id' => $productId,
                'location_id' => $locationId,
                'batch_no' => $batchNo,
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
