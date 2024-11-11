<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Variation extends Model
{
    use HasFactory,LocationTrait;
    protected $table='variations';
    protected $fillable=[

              'variation_value',
              'variation_title_id',
    ];

     public function variationTitle()
    {
        return $this->belongsTo(VariationTitle::class);
    }

}
