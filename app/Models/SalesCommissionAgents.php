<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesCommissionAgents extends Model
{
    use HasFactory,LocationTrait;
    protected $table='sales_commission_agents';

    protected $fillable=[
        'prefix',
        'first_name',
        'last_name',
        'email',
        'contact_number',
        'sales_commission_percentage',
        'description',
    ];
}

