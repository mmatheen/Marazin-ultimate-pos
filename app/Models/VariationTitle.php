<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VariationTitle extends Model
{
    use HasFactory,LocationTrait;
    protected $table='variation_titles';

    protected $fillable=[
        'variation_title',
    ];

    public function variations()
    {
        return $this->hasMany(Variation::class);
    }
}
