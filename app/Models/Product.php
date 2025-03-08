<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'sku',
        'unit_id',
        'brand_id',
        'main_category_id',
        'sub_category_id',
        'stock_alert',
        'alert_quantity',
        'product_image',
        'description',
        'is_imei_or_serial_no',
        'is_for_selling',
        'product_type',
        'pax',
        'retail_price',
        'whole_sale_price',
        'special_price',
        'original_price',
        'max_retail_price',
    ];

    public function locations()
    {
        return $this->belongsToMany(Location::class)->withPivot('qty');
    }

    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function stockHistories()
    {
        return $this->hasManyThrough(StockHistory::class, LocationBatch::class, 'batch_id', 'loc_batch_id', 'id', 'id');
    }

    public function salesProducts()
    {
        return $this->hasManyThrough(SalesProduct::class, Batch::class, 'product_id', 'batch_id', 'id', 'id');
    }

    public function purchases()
    {
        return $this->hasManyThrough(Purchase::class, PurchaseProduct::class, 'product_id', 'purchase_id', 'id', 'id');
    }
}