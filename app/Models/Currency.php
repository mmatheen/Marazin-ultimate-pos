<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use HasFactory,LocationTrait;
    protected $table='currencies';
    protected $fillable=[
              'country',
              'currency',
              'symbol',
              'code',
              'thousand_separator',
              'decimal_separator',
    ];
}

