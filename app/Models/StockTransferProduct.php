<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'batch_id',
        'quantity',
        'unit_price',
        'sub_total'

    ];

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batches()
    {
        return $this->belongsTo(Batch::class,'batch_id');
    }
}
