<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBackorderAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_backorder_id',
        'purchase_id',
        'purchase_product_id',
        'batch_id',
        'location_id',
        'allocated_paid_qty',
        'allocated_free_qty',
        'allocation_type',
        'allocated_at',
        'notes',
    ];

    protected $casts = [
        'allocated_paid_qty' => 'float',
        'allocated_free_qty' => 'float',
        'allocated_at' => 'datetime',
    ];

    public function backorder()
    {
        return $this->belongsTo(StockBackorder::class, 'stock_backorder_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function purchaseProduct()
    {
        return $this->belongsTo(PurchaseProduct::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
