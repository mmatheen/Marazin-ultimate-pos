<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_no',
        'product_id',
        'unit_cost',
        'qty',
        'wholesale_price',
        'special_price',
        'retail_price',
        'max_retail_price',
        'expiry_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseProducts()
    {
        return $this->hasMany(PurchaseProduct::class);
    }
    
    public function salesProducts()
    {
        return $this->hasMany(SalesProduct::class, 'batch_id');
    }

    public function stockHistories()
    {
        return $this->hasManyThrough(StockHistory::class, LocationBatch::class, 'batch_id', 'loc_batch_id', 'id', 'id');
    }

    public function locationBatches()
    {
        return $this->hasMany(LocationBatch::class);
    }
}