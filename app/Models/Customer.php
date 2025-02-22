<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';
    protected $fillable = [
        'prefix',
        'first_name',
        'last_name',
        'mobile_no',
        'email',
        'address',
        'opening_balance',
        'location_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            // Initialize current_balance with opening_balance
            $customer->current_balance = $customer->opening_balance;
        });
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    // Total Sale Due for the customer
    public function getTotalSaleDueAttribute()
    {
        return $this->sales()->sum('total_due');
    }

    // Total Return Due for the customer
    public function getTotalReturnDueAttribute()
    {
        return $this->salesReturns()->sum('total_due');
    }

}
