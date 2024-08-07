<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellingPriceGroup extends Model
{
    use HasFactory;
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
