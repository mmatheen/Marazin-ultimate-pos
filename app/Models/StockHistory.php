<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'loc_batch_id',
        'quantity',
        'stock_type',
    ];

    // Append computed attributes to JSON/array output
    protected $appends = ['free_quantity'];

    // Stock type constants - must match the migration enum exactly
    const STOCK_TYPE_OPENING = 'opening_stock';
    const STOCK_TYPE_PURCHASE = 'purchase';
    const STOCK_TYPE_PURCHASE_RETURN = 'purchase_return';
    const STOCK_TYPE_PURCHASE_RETURN_REVERSAL = 'purchase_return_reversal';
    const STOCK_TYPE_SALE = 'sale';
    const STOCK_TYPE_SALE_REVERSAL = 'sale_reversal';
    const STOCK_TYPE_SALE_ORDER = 'sale_order';
    const STOCK_TYPE_SALE_ORDER_REVERSAL = 'sale_order_reversal';
    const STOCK_TYPE_SALE_RETURN_WITH_BILL = 'sales_return_with_bill';
    const STOCK_TYPE_SALE_RETURN_WITHOUT_BILL = 'sales_return_without_bill';
    const STOCK_TYPE_TRANSFER_IN = 'transfer_in';
    const STOCK_TYPE_TRANSFER_OUT = 'transfer_out';
    const STOCK_TYPE_ADJUSTMENT = 'adjustment';

    public function locationBatch()
    {
        return $this->belongsTo(LocationBatch::class, 'loc_batch_id');
    }

    /**
     * Calculate free quantity for this stock history entry based on transaction type
     * Uses eager-loaded relationships for better performance
     *
     * @return float
     */
    public function getFreeQuantityAttribute()
    {
        // Check if we already calculated this
        if (isset($this->attributes['free_quantity'])) {
            return (float) $this->attributes['free_quantity'];
        }

        if (!$this->locationBatch || !$this->locationBatch->batch) {
            return 0;
        }

        $batch = $this->locationBatch->batch;
        $stockType = $this->stock_type;
        $historyQuantity = abs($this->quantity);
        $historyTime = $this->created_at;

        // Match the transaction by quantity and closest time
        $matchedTransaction = null;

        switch ($stockType) {
            case self::STOCK_TYPE_PURCHASE:
                // Match purchase product transactions
                $matchedTransaction = $this->findClosestTransaction(
                    $batch->purchaseProducts ?? collect(),
                    $historyQuantity,
                    $historyTime
                );
                break;

            case self::STOCK_TYPE_SALE:
                // Match sale product transactions
                $matchedTransaction = $this->findClosestTransaction(
                    $batch->salesProducts ?? collect(),
                    $historyQuantity,
                    $historyTime
                );
                break;

            case self::STOCK_TYPE_PURCHASE_RETURN:
            case self::STOCK_TYPE_PURCHASE_RETURN_REVERSAL:
                // Match purchase return transactions (now using fixed relationship)
                $matchedTransaction = $this->findClosestTransaction(
                    $batch->purchaseReturns ?? collect(),
                    $historyQuantity,
                    $historyTime
                );
                break;

            case self::STOCK_TYPE_SALE_RETURN_WITH_BILL:
            case self::STOCK_TYPE_SALE_RETURN_WITHOUT_BILL:
            case self::STOCK_TYPE_SALE_REVERSAL:
                // Match sale return transactions
                $matchedTransaction = $this->findClosestTransaction(
                    $batch->saleReturns ?? collect(),
                    $historyQuantity,
                    $historyTime
                );
                break;

            default:
                return 0;
        }

        return $matchedTransaction ? (float) ($matchedTransaction->free_quantity ?? 0) : 0;
    }

    /**
     * Find the transaction that matches the stock history by quantity and time
     *
     * @param \Illuminate\Support\Collection $transactions
     * @param float $historyQuantity
     * @param \Carbon\Carbon $historyTime
     * @return mixed|null
     */
    private function findClosestTransaction($transactions, $historyQuantity, $historyTime)
    {
        if ($transactions->isEmpty()) {
            return null;
        }

        // Filter transactions that match the quantity (within 0.01 tolerance)
        $matchingTransactions = $transactions->filter(function ($transaction) use ($historyQuantity) {
            $transactionTotal = (float) ($transaction->quantity ?? 0) + (float) ($transaction->free_quantity ?? 0);
            return abs($transactionTotal - $historyQuantity) < 0.01;
        });

        // If no quantity match, try without quantity filter (for FIFO splits)
        if ($matchingTransactions->isEmpty()) {
            $matchingTransactions = $transactions;
        }

        // Find the one with closest timestamp
        return $matchingTransactions->sortBy(function ($transaction) use ($historyTime) {
            $transactionTime = $transaction->created_at ?? $transaction->updated_at;
            return abs($historyTime->diffInSeconds($transactionTime));
        })->first();
    }

    /**
     * Get all valid stock types.
     *
     * @return array
     */
    public static function getStockTypes()
    {
        return [
            self::STOCK_TYPE_OPENING,
            self::STOCK_TYPE_PURCHASE,
            self::STOCK_TYPE_PURCHASE_RETURN,
            self::STOCK_TYPE_PURCHASE_RETURN_REVERSAL,
            self::STOCK_TYPE_SALE,
            self::STOCK_TYPE_SALE_REVERSAL,
            self::STOCK_TYPE_SALE_ORDER,
            self::STOCK_TYPE_SALE_ORDER_REVERSAL,
            self::STOCK_TYPE_SALE_RETURN_WITH_BILL,
            self::STOCK_TYPE_SALE_RETURN_WITHOUT_BILL,
            self::STOCK_TYPE_TRANSFER_IN,
            self::STOCK_TYPE_TRANSFER_OUT,
            self::STOCK_TYPE_ADJUSTMENT,
        ];
    }
}
