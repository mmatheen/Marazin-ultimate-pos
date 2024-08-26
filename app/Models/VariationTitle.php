<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariationTitle extends Model
{
    use HasFactory;
    protected $table='variation_titles';

    protected $fillable=[
        'variation_title',
    ];

    public function variations()
    {
        return $this->hasMany(Variation::class);
    }
}
