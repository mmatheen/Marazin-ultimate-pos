<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    use HasFactory;
    protected $table='customer_groups';
    protected $fillable=[
              'customerGroupName',
              'priceCalculationType',
              'customer_group_id',
              'calculationPercentage'
    ];

    public function sellingPriceGroup()
    {
        return $this->hasMany(SellingPriceGroup::class); // SubCategory is SubCategory modal name
    }
}
