<?php

namespace App\Models;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory,LocationTrait;

    protected $table = 'customers';
    protected $fillable = [
        'prefix',
        'first_name',
        'last_name',
        'mobile_no',
        'email',
        'address',
        'opening_balance',
        // 'location_id',
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

    public function payments()
{
    return $this->hasMany(Payment::class);
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

    public function getCurrentDueAttribute()
    {
        // Get total sales due
        $totalSalesDue = $this->sales()->sum('total_due');
    
        // Get total return due
        $totalReturnDue = $this->salesReturns()->sum('total_due');
        
        // // Calculate the total payments made by the customer
        // $totalPaymentsMade = $this->payments()->sum('amount');
        
        // Current due calculation considering sales, payments, and returns
        $currentDue = ($this->opening_balance + $totalSalesDue  - $totalReturnDue);
    
        return $currentDue;
    }
    
    
}
