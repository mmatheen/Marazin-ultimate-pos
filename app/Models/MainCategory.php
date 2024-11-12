<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MainCategory extends Model
{
    use HasFactory,LocationTrait;
    protected $table='main_categories';
    protected $fillable=[
              'mainCategoryName',
              'description',
    ];

    public function subCategory()
    {
        return $this->hasMany(SubCategory::class); // SubCategory is SubCategory modal name
    }

      // Relationship to products (one-to-many)
      public function products()
      {
          return $this->hasMany(Product::class, 'main_category_id');  // Adjust foreign key if needed
      }
}
