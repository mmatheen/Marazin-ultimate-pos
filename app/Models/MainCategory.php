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
}
