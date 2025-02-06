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

    // Relationship to locations (many-to-many)
    public function locations()
    {
        return $this->belongsToMany(Location::class)->withPivot('qty');
    }

    // Relationship to main category (belongsTo)
    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
    }

    // Relationship to brand (belongsTo)
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    // Relationship to unit (belongsTo)
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // Relationship to batches (hasMany)
    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    // Relationship to stock histories (hasMany)
    public function stockHistories()
    {
        return $this->hasMany(StockHistory::class);
    }
}
