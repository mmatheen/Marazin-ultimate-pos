<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustmentProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'batch_id',
        'quantity',
        'free_quantity',    // Free items adjusted
        'unit_price',
        'subtotal',
    ];

    // Relationship with StockAdjustment
    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    // Relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relationship with Batch
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
