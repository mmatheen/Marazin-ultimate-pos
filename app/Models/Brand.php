<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    use HasFactory,LocationTrait;
    protected $table='brands';
    protected $fillable=[
              'name',
              'description',
              
    ];

    // Relationship to products (one-to-many)
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');  // Adjust foreign key if needed
    }
}
