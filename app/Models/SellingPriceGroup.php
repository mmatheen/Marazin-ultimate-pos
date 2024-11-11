<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SellingPriceGroup extends Model
{
    use HasFactory,LocationTrait;
    protected $table='selling_price_groups';
    protected $fillable=[
              'name',
              'description',
              'is_active',
    ];

    public function customerGroup()
    {
        return $this->hasMany(CustomerGroup::class); // MainCategory is MainCategory modal name
    }
}
