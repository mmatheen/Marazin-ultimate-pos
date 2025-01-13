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
    const STOCK_TYPE_PURCHASE = 'Purchase';
    const STOCK_TYPE_PURCHASE_RETURN = 'Purchase Return';
    const STOCK_TYPE_OPENING_STOCK = 'Opening Stock';
    const STOCK_TYPE_SALE = 'Sale';
    const STOCK_TYPE_SALE_RETURN = 'Sale Return';
    const STOCK_TYPE_STOCK_TRANSFER = 'Stock Transfer';
    const STOCK_TYPE_ADJUSTMENT = 'Adjustment';

    public function locationBatch()
    {
        return $this->belongsTo(LocationBatch::class);
    }
}
