<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubCategory extends Model
{
    use HasFactory,LocationTrait;
    protected $table='sub_categories';
    protected $fillable=[
              'subCategoryname',
              'main_category_id',
              'subCategoryCode',
              'description',
    ];

    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class); // MainCategory is MainCategory modal name
    }
}
