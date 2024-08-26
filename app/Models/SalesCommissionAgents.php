<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCommissionAgents extends Model
{
    use HasFactory;
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

