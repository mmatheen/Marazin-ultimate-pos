<?php

namespace App\Services\Inventory;

use App\Models\LocationBatch;
use App\Models\Product;
use App\Models\SalesProduct;
use App\Models\Setting;
use App\Models\StockBackorder;
use App\Models\StockBackorderAllocation;
use App\Models\StockHistory;

class BackorderService
{
    public function isEnabled(): bool
    {
        return (bool) (Setting::value('enable_backorders') ?? 0);
    }

    public function canBackorderProduct(?Product $product): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Serial/IMEI items should remain strict to avoid phantom serialized commitments.
        if ($product && (bool) ($product->is_imei_or_serial_no ?? false)) {
            return false;
        }

        return true;
    }

    /**
     * Record shortage as a backorder tied to a sales_product line.
     * Minimal: only sale_product_id and location are stored; product info derives from sales_product.
     */
    public function recordShortage(
        ?int $saleProductId,
        int $locationId,
        float $shortagePaidQty,
        float $shortageFreeQty
    ): ?StockBackorder {
        if (!$this->isEnabled()) {
            return null;
        }

        $shortagePaidQty = $this->f($shortagePaidQty);
        $shortageFreeQty = $this->f($shortageFreeQty);
        if ($shortagePaidQty <= 0 && $shortageFreeQty <= 0) {
            return null;
        }

        return StockBackorder::create([
            'sale_product_id' => $saleProductId,
            'location_id' => $locationId,
            'ordered_paid_qty' => $shortagePaidQty,
            'ordered_free_qty' => $shortageFreeQty,
            'fulfilled_paid_qty' => 0,
            'fulfilled_free_qty' => 0,
            'status' => StockBackorder::STATUS_OPEN,
        ]);
    }

    public function reserveFromPurchase(
        int $purchaseId,
        ?int $purchaseProductId,
        int $productId,
        int $locationId,
        ?int $batchId,
        float $incomingPaidQty,
        float $incomingFreeQty
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $remainingPaid = $this->f($incomingPaidQty);
        $remainingFree = $this->f($incomingFreeQty);

        if ($remainingPaid <= 0 && $remainingFree <= 0) {
            return;
        }

        // Join to sales_products to filter by product_id (product no longer stored in backorder)
        // FIFO: oldest backorder created first
        $backorders = StockBackorder::query()
            ->join('sales_products', 'stock_backorders.sale_product_id', '=', 'sales_products.id')
            ->where('sales_products.product_id', $productId)
            ->where('stock_backorders.location_id', $locationId)
            ->whereIn('stock_backorders.status', [
                StockBackorder::STATUS_OPEN,
                StockBackorder::STATUS_PARTIALLY_ALLOCATED,
                // Legacy compatibility: previously used by older flow.
                StockBackorder::STATUS_FULLY_ALLOCATED,
            ])
            ->orderBy('stock_backorders.created_at', 'ASC')
            ->lockForUpdate('stock_backorders')
            ->select('stock_backorders.*')
            ->get();

        foreach ($backorders as $backorder) {
            if ($remainingPaid <= 0 && $remainingFree <= 0) {
                break;
            }

            // Simple rule: purchase auto-fulfills pending backorder quantities.
            $needPaid = $this->f(($backorder->ordered_paid_qty ?? 0) - ($backorder->fulfilled_paid_qty ?? 0));
            $needFree = $this->f(($backorder->ordered_free_qty ?? 0) - ($backorder->fulfilled_free_qty ?? 0));
            $needPaid = max(0, $needPaid);
            $needFree = max(0, $needFree);

            if ($needPaid <= 0 && $needFree <= 0) {
                continue;
            }

            $allocatedPaid = min($remainingPaid, $needPaid);
            $allocatedFree = min($remainingFree, $needFree);

            if ($allocatedPaid <= 0 && $allocatedFree <= 0) {
                continue;
            }

            StockBackorderAllocation::create([
                'stock_backorder_id' => $backorder->id,
                'purchase_id' => $purchaseId,
                'purchase_product_id' => $purchaseProductId,
                'batch_id' => $batchId,
                'location_id' => $locationId,
                'allocated_paid_qty' => $allocatedPaid,
                'allocated_free_qty' => $allocatedFree,
                'allocation_type' => 'purchase_reservation',
            ]);

            // Keep the backing sales_products row tied to the first purchase batch that reserves stock
            // for this backorder. The row starts with batch_id = null because the shortage existed at sale time.
            $saleProduct = SalesProduct::query()
                ->whereKey($backorder->sale_product_id)
                ->lockForUpdate()
                ->first();
            if ($saleProduct && empty($saleProduct->batch_id) && $batchId) {
                $saleProduct->batch_id = $batchId;
                $saleProduct->save();
            }

            // Consume received stock immediately against this backorder to keep available stock accurate.
            if ($batchId && ($allocatedPaid > 0 || $allocatedFree > 0)) {
                $locationBatch = LocationBatch::where('batch_id', $batchId)
                    ->where('location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                if (!$locationBatch) {
                    throw new \Exception("Backorder allocation failed: missing location batch. Batch ID {$batchId}, Location ID {$locationId}.");
                }

                $currentPaid = $this->f($locationBatch->qty ?? 0);
                $currentFree = $this->f($locationBatch->free_qty ?? 0);
                if ($allocatedPaid > $currentPaid + 0.0001 || $allocatedFree > $currentFree + 0.0001) {
                    throw new \Exception(
                        "Backorder auto-fulfill would cause negative stock. " .
                        "Batch {$batchId}, Location {$locationId}, " .
                        "Need paid/free {$allocatedPaid}/{$allocatedFree}, " .
                        "Available paid/free {$currentPaid}/{$currentFree}."
                    );
                }

                if ($allocatedPaid > 0) {
                    $locationBatch->decrement('qty', $allocatedPaid);
                }
                if ($allocatedFree > 0) {
                    $locationBatch->decrement('free_qty', $allocatedFree);
                }

                $effectiveDeduction = $this->f($allocatedPaid + $allocatedFree);
                if ($effectiveDeduction > 0) {
                    StockHistory::create([
                        'loc_batch_id' => $locationBatch->id,
                        'quantity' => -$effectiveDeduction,
                        'stock_type' => StockHistory::STOCK_TYPE_SALE_ORDER,
                        'paid_qty' => $this->f($allocatedPaid),
                        'free_qty' => $this->f($allocatedFree),
                        'source_pool' => 'backorder_auto_fulfill',
                        'movement_type' => 'deduction',
                    ]);
                }
            }

            // Mark these allocated quantities as fulfilled now.
            $backorder->fulfilled_paid_qty = $this->f(($backorder->fulfilled_paid_qty ?? 0) + $allocatedPaid);
            $backorder->fulfilled_free_qty = $this->f(($backorder->fulfilled_free_qty ?? 0) + $allocatedFree);

            $remainingAfterAllocPaid = $this->f(($backorder->ordered_paid_qty ?? 0) - ($backorder->fulfilled_paid_qty ?? 0));
            $remainingAfterAllocFree = $this->f(($backorder->ordered_free_qty ?? 0) - ($backorder->fulfilled_free_qty ?? 0));
            $remainingAfterAllocPaid = max(0, $remainingAfterAllocPaid);
            $remainingAfterAllocFree = max(0, $remainingAfterAllocFree);

            if ($remainingAfterAllocPaid <= 0 && $remainingAfterAllocFree <= 0) {
                $backorder->status = StockBackorder::STATUS_FULFILLED;
                $backorder->fulfilled_at = $backorder->fulfilled_at ?? now();
            } else {
                $backorder->status = StockBackorder::STATUS_PARTIALLY_ALLOCATED;
            }

            $backorder->save();

            // Keep sales_products fulfillment fields consistent with backorder progress.
            $saleProduct = SalesProduct::query()
                ->whereKey($backorder->sale_product_id)
                ->lockForUpdate()
                ->first();
            if ($saleProduct) {
                $saleProduct->fulfilled_quantity = $this->f($backorder->fulfilled_paid_qty ?? 0);
                $saleProduct->fulfilled_free_quantity = $this->f($backorder->fulfilled_free_qty ?? 0);
                $saleProduct->backordered_quantity = $remainingAfterAllocPaid;
                $saleProduct->backordered_free_quantity = $remainingAfterAllocFree;
                $saleProduct->fulfillment_status = ($remainingAfterAllocPaid <= 0 && $remainingAfterAllocFree <= 0)
                    ? 'fulfilled'
                    : 'partial';
                $saleProduct->save();
            }

            $remainingPaid = $this->f($remainingPaid - $allocatedPaid);
            $remainingFree = $this->f($remainingFree - $allocatedFree);
        }
    }

    /**
     * Release reservations/fulfillment generated from a specific purchase-product row.
     *
     * Used when purchase lines are removed/rolled back, so backorder state returns to pending.
     * Returns released paid/free quantities that were previously consumed via auto-fulfill.
     *
     * @return array{paid_qty: float, free_qty: float}
     */
    public function releasePurchaseReservationsForPurchaseProduct(
        int $purchaseId,
        int $purchaseProductId,
        int $productId,
        int $locationId,
        ?int $batchId,
        ?float $maxReleasePaidQty = null,
        ?float $maxReleaseFreeQty = null,
        bool $forceReleaseWhenDisabled = false
    ): array {
        // Default behavior respects feature toggle.
        // Force mode is used by purchase edit/delete rollback paths so historical
        // reservation rows can be cleaned up even when setting is currently off.
        if (!$this->isEnabled() && !$forceReleaseWhenDisabled) {
            return ['paid_qty' => 0.0, 'free_qty' => 0.0];
        }

        $reservations = StockBackorderAllocation::query()
            ->where('purchase_id', $purchaseId)
            ->where('purchase_product_id', $purchaseProductId)
            ->where('location_id', $locationId)
            ->where('allocation_type', 'purchase_reservation')
            ->when($batchId, function ($q) use ($batchId) {
                $q->where('batch_id', $batchId);
            })
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->get();

        if ($reservations->isEmpty()) {
            return ['paid_qty' => 0.0, 'free_qty' => 0.0];
        }

        $remainingReleasePaid = $maxReleasePaidQty === null ? null : $this->f(max(0, $maxReleasePaidQty));
        $remainingReleaseFree = $maxReleaseFreeQty === null ? null : $this->f(max(0, $maxReleaseFreeQty));
        $releasedPaid = 0.0;
        $releasedFree = 0.0;

        foreach ($reservations as $reservation) {
            if ($remainingReleasePaid !== null && $remainingReleasePaid <= 0 && $remainingReleaseFree !== null && $remainingReleaseFree <= 0) {
                break;
            }

            $reservationPaid = $this->f($reservation->allocated_paid_qty ?? 0);
            $reservationFree = $this->f($reservation->allocated_free_qty ?? 0);

            $releasePaid = $remainingReleasePaid === null ? $reservationPaid : min($reservationPaid, $remainingReleasePaid);
            $releaseFree = $remainingReleaseFree === null ? $reservationFree : min($reservationFree, $remainingReleaseFree);

            if ($releasePaid <= 0 && $releaseFree <= 0) {
                continue;
            }

            $releasedPaid = $this->f($releasedPaid + $releasePaid);
            $releasedFree = $this->f($releasedFree + $releaseFree);

            $backorder = StockBackorder::query()
                ->join('sales_products', 'stock_backorders.sale_product_id', '=', 'sales_products.id')
                ->where('stock_backorders.id', $reservation->stock_backorder_id)
                ->where('sales_products.product_id', $productId)
                ->select('stock_backorders.*')
                ->lockForUpdate('stock_backorders')
                ->first();

            if ($backorder) {
                $backorder->fulfilled_paid_qty = $this->f(max(0, ($backorder->fulfilled_paid_qty ?? 0) - $releasePaid));
                $backorder->fulfilled_free_qty = $this->f(max(0, ($backorder->fulfilled_free_qty ?? 0) - $releaseFree));

                $remainingPaid = $this->f(max(0, ($backorder->ordered_paid_qty ?? 0) - ($backorder->fulfilled_paid_qty ?? 0)));
                $remainingFree = $this->f(max(0, ($backorder->ordered_free_qty ?? 0) - ($backorder->fulfilled_free_qty ?? 0)));

                if ($remainingPaid <= 0 && $remainingFree <= 0) {
                    $backorder->status = StockBackorder::STATUS_FULFILLED;
                    $backorder->fulfilled_at = $backorder->fulfilled_at ?? now();
                } elseif (($backorder->fulfilled_paid_qty ?? 0) > 0 || ($backorder->fulfilled_free_qty ?? 0) > 0) {
                    $backorder->status = StockBackorder::STATUS_PARTIALLY_ALLOCATED;
                    $backorder->fulfilled_at = null;
                } else {
                    $backorder->status = StockBackorder::STATUS_OPEN;
                    $backorder->fulfilled_at = null;
                }

                $backorder->save();

                $saleProduct = SalesProduct::query()
                    ->whereKey($backorder->sale_product_id)
                    ->lockForUpdate()
                    ->first();

                if ($saleProduct) {
                    $saleProduct->fulfilled_quantity = $this->f($backorder->fulfilled_paid_qty ?? 0);
                    $saleProduct->fulfilled_free_quantity = $this->f($backorder->fulfilled_free_qty ?? 0);
                    $saleProduct->backordered_quantity = $remainingPaid;
                    $saleProduct->backordered_free_quantity = $remainingFree;
                    $saleProduct->fulfillment_status = ($remainingPaid <= 0 && $remainingFree <= 0)
                        ? 'fulfilled'
                        : (($saleProduct->fulfilled_quantity > 0 || $saleProduct->fulfilled_free_quantity > 0) ? 'partial' : 'pending');
                    $saleProduct->save();
                }
            }

            StockBackorderAllocation::create([
                'stock_backorder_id' => $reservation->stock_backorder_id,
                'purchase_id' => $purchaseId,
                'purchase_product_id' => $purchaseProductId,
                'batch_id' => $reservation->batch_id,
                'location_id' => $locationId,
                'allocated_paid_qty' => -$releasePaid,
                'allocated_free_qty' => -$releaseFree,
                'allocation_type' => 'reservation_release',
                'notes' => 'Auto release due to purchase product removal',
            ]);

            $remainingReservationPaid = $this->f(max(0, $reservationPaid - $releasePaid));
            $remainingReservationFree = $this->f(max(0, $reservationFree - $releaseFree));

            if ($remainingReservationPaid <= 0 && $remainingReservationFree <= 0) {
                // Remove fully consumed reservation row to avoid repeated release on retries/edits.
                $reservation->delete();
            } else {
                $reservation->allocated_paid_qty = $remainingReservationPaid;
                $reservation->allocated_free_qty = $remainingReservationFree;
                $reservation->save();
            }

            if ($remainingReleasePaid !== null) {
                $remainingReleasePaid = $this->f(max(0, $remainingReleasePaid - $releasePaid));
            }
            if ($remainingReleaseFree !== null) {
                $remainingReleaseFree = $this->f(max(0, $remainingReleaseFree - $releaseFree));
            }
        }

        return [
            'paid_qty' => $this->f($releasedPaid),
            'free_qty' => $this->f($releasedFree),
        ];
    }

    /**
     * Cancel any backorders created for a sale product and restore purchase-backed stock.
     *
     * Used when a sale order is cancelled so any later purchase allocations are unwound
     * and the backorder rows no longer participate in future reservations.
     */
    public function cancelSaleBackordersForSaleProduct(SalesProduct $saleProduct): void
    {
        $backorders = StockBackorder::query()
            ->where('sale_product_id', $saleProduct->id)
            ->lockForUpdate()
            ->get();

        foreach ($backorders as $backorder) {
            $allocations = StockBackorderAllocation::query()
                ->where('stock_backorder_id', $backorder->id)
                ->where('allocation_type', 'purchase_reservation')
                ->lockForUpdate()
                ->delete();

            $backorder->status = StockBackorder::STATUS_CANCELLED;
            $backorder->fulfilled_paid_qty = 0;
            $backorder->fulfilled_free_qty = 0;
            $backorder->fulfilled_at = null;
            $backorder->save();

            $backorder->delete();
        }
    }

    private function f($value): float
    {
        return round((float) $value, 4);
    }
}
