<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warranty extends Model
{
    use HasFactory,LocationTrait,SoftDeletes;
    protected $table='warranties';
    protected $fillable=[
              'name',
              'duration',
              'duration_type',
              'description',
    ];
}
