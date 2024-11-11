<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory,LocationTrait;
    protected $table='units';
    protected $fillable=[
              'name',
              'short_name',
              'allow_decimal',
              
    ];
}
