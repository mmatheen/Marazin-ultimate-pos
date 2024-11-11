<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, LocationTrait;
    protected $table='products';
    protected $fillable=[

              'product_name',
              'sku',
              'unit_id',
              'brand_id',
              'main_category_id',
              'sub_category_id',
              'location_id',
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
    ];
}

