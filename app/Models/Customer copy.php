<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, LocationTrait;

    protected $table = 'customers';

    protected $fillable = [
        'prefix',
        'first_name',
        'last_name',
        'mobile_no',
        'email',
        'address',
        'opening_balance',
        'current_balance',
        'credit_limit',
        'city_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            // Initialize current balance with opening balance
            $customer->current_balance = $customer->opening_balance;

            // Auto-assign credit limit based on city
            if ($customer->city_id && !$customer->credit_limit) {
                $customer->credit_limit = self::calculateCreditLimitForCity($customer->city_id);
            }
        });

        static::updating(function ($customer) {
            // Update credit limit if city changes & credit limit was default
            if ($customer->isDirty('city_id') && $customer->city_id) {
                $originalCreditLimit = self::calculateCreditLimitForCity($customer->getOriginal('city_id'));
                if ($customer->getOriginal('credit_limit') == $originalCreditLimit) {
                    $customer->credit_limit = self::calculateCreditLimitForCity($customer->city_id);
                }
            }
        });
    }

    // Accessor for full name
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    // Relationships
    public function sales() { return $this->hasMany(Sale::class); }
    public function salesReturns() { return $this->hasMany(SalesReturn::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function city() { return $this->belongsTo(City::class); }

    // Calculate total dues
    public function getCurrentDueAttribute()
    {
        $totalSalesDue   = $this->sales()->sum('total_due');
        $totalReturnDue  = $this->salesReturns()->sum('total_due');
        $totalPayments   = $this->payments()->sum('amount');

        return ($this->opening_balance + $totalSalesDue - $totalReturnDue - $totalPayments);
    }

    // Recalculate current balance from all transactions
    public function recalculateCurrentBalance()
    {
        $this->current_balance = $this->getCurrentDueAttribute();
        $this->saveQuietly();
    }

    // Credit limit based on city
    public static function calculateCreditLimitForCity($cityId)
    {
        // Return default credit limit of 0 (no automatic calculation)
        return 0;
    }
}
