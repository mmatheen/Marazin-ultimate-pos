<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;
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

