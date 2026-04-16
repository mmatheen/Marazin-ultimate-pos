<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockBackorder extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_OPEN = 'open';
    public const STATUS_PARTIALLY_ALLOCATED = 'partially_allocated';
    public const STATUS_FULLY_ALLOCATED = 'fully_allocated';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'sale_product_id',
        'location_id',
        'ordered_paid_qty',
        'ordered_free_qty',
        'fulfilled_paid_qty',
        'fulfilled_free_qty',
        'status',
        'fulfilled_at',
    ];

    protected $casts = [
        'ordered_paid_qty' => 'float',
        'ordered_free_qty' => 'float',
        'fulfilled_paid_qty' => 'float',
        'fulfilled_free_qty' => 'float',
        'fulfilled_at' => 'datetime',
    ];

    public function saleProduct()
    {
        return $this->belongsTo(SalesProduct::class, 'sale_product_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function allocations()
    {
        return $this->hasMany(StockBackorderAllocation::class, 'stock_backorder_id');
    }
}
