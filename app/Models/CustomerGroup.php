<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerGroup extends Model
{
    use HasFactory,LocationTrait;
    protected $table='customer_groups';
    protected $fillable=[

              'customerGroupName',
              'priceCalculationType',
              'selling_price_group_id',
              'calculationPercentage'
    ];

    public function sellingPriceGroup()
    {
        return $this->belongsTo(SellingPriceGroup::class); // SellingPriceGroup is SellingPriceGroup modal name
    }
}
