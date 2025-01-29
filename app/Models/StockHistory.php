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

    // Stock type constants
    const STOCK_TYPE_OPENING = 'opening_stock';
    const STOCK_TYPE_PURCHASE = 'purchase';
    const STOCK_TYPE_PURCHASE_RETURN = 'purchase_return';
    const STOCK_TYPE_PURCHASE_RETURN_REVERSAL= 'purchase_return_reversal';
    const STOCK_TYPE_SALE = 'sale';
    const STOCK_TYPE_SALE_REVERSAL = 'sale_reversal';
    const STOCK_TYPE_SALE_RETURN_WITH_BILL = 'sales_return_with_bill';
    const STOCK_TYPE_SALE_RETURN_WITHOUT_BILL = 'sales_return_without_bill';
    const STOCK_TYPE_TRANSFER_IN = 'transfer_in';
    const STOCK_TYPE_TRANSFER_OUT = 'transfer_out';
    const STOCK_TYPE_ADJUSTMENT = 'adjustment';


    public function locationBatch()
    {
        return $this->belongsTo(LocationBatch::class);
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
            self::STOCK_TYPE_SALE_RETURN_WITH_BILL,
            self::STOCK_TYPE_SALE_RETURN_WITHOUT_BILL,
            self::STOCK_TYPE_TRANSFER_IN,
            self::STOCK_TYPE_TRANSFER_OUT,
            self::STOCK_TYPE_ADJUSTMENT,
        ];
    }
}
