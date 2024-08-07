<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    use HasFactory;
    protected $table='customers';
    protected $fillable=[
              'customerGroupName',
              'priceCalculationType',
    ];

    public function SellingPriceGroup()
    {
        return $this->hasMany(SellingPriceGroup::class); // SubCategory is SubCategory modal name
    }
}
