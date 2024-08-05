<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;
    protected $table='sub_categories';
    protected $fillable=[
              'subCategoryname',
              'minCategory_id',
              'subCategoryCode',
              'description',
    ];

    public function mainCategories()
    {
        return $this->belongsTo(MainCategory::class); // MainCategory is MainCategory modal name
    }
}    
