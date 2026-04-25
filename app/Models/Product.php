<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        'tax_percent',
        'selling_price_tax_type',
        'max_retail_price',
        'is_active'
    ];

    /**
     * Only expose product_image when the file exists (avoids 404s in POS/frontend).
     *
     * @param  mixed  $value
     * @return string|null
     */
    public function getProductImageAttribute($value)
    {
        if ($value === null || $value === '' || !is_string($value)) {
            return null;
        }

        // Allow stored values like:
        // - "1766294682.png" (legacy)
        // - "products/abc.jpg" (new)
        $relative = ltrim($value, '/');
        $relative = str_replace('\\', '/', $relative);
        $relative = preg_replace('#^assets/images/#', '', $relative);

        $path = public_path('assets/images/' . $relative);
        if (file_exists($path)) {
            return $relative;
        }

        // Back-compat: if DB stored only filename but file is inside products/
        $filename = basename($relative);
        $fallback = public_path('assets/images/products/' . $filename);
        return file_exists($fallback) ? ('products/' . $filename) : null;
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class)->withPivot('qty');
    }

    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
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

    public function salesProducts()
    {
        return $this->hasManyThrough(SalesProduct::class, Batch::class, 'product_id', 'batch_id', 'id', 'id');
    }

    public function purchases()
    {
        return $this->hasManyThrough(Purchase::class, PurchaseProduct::class, 'product_id', 'purchase_id', 'id', 'id');
    }

    public function purchaseReturn()
    {
        return $this->hasManyThrough(PurchaseReturn::class, PurchaseReturnProduct::class, 'product_id', 'purchase_id', 'id', 'id');
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class)->withTimestamps();
    }

    public function imeiNumbers()
    {
        return $this->hasMany(ImeiNumber::class);
    }

    public function locationBatches()
    {
        return $this->hasManyThrough(LocationBatch::class, Batch::class);
    }

    public function stockHistories()
    {
        return $this->hasManyThrough(StockHistory::class, LocationBatch::class);
    }

    /**
     * Products allowed on the POS sale grid / POS product search.
     * "Not for selling" is stored as is_for_selling = 1 (see import template / product form).
     */
    public function scopeWhereSellableOnPos(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('is_for_selling')
                ->orWhereIn('is_for_selling', [0, '0', '']);
        });
    }
}
