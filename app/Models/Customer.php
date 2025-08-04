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

    /* ============================
     * Boot events
     * ============================
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            // Initialize current balance equal to opening balance
            $customer->current_balance = $customer->opening_balance;

            // Auto-assign credit limit based on city if not set manually
            if ($customer->city_id && !$customer->credit_limit) {
                $customer->credit_limit = self::calculateCreditLimitForCity($customer->city_id);
            }
        });

        static::updating(function ($customer) {
            // Auto-update credit limit if city changes & old limit was default
            if ($customer->isDirty('city_id') && $customer->city_id) {
                $originalCreditLimit = self::calculateCreditLimitForCity($customer->getOriginal('city_id'));
                if ($customer->getOriginal('credit_limit') == $originalCreditLimit) {
                    $customer->credit_limit = self::calculateCreditLimitForCity($customer->city_id);
                }
            }
        });
    }

    /* ============================
     * Relationships
     * ============================
     */
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

    public function city()
    {
        return $this->belongsTo(City::class);
    }



    /* ============================
     * Accessors & Calculations
     * ============================
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getTotalSaleDueAttribute()
    {
        // Get all unpaid sales
        $unpaidSales = $this->sales()->where('payment_status', '!=', 'Paid')->get();

        $totalDue = 0;

        foreach ($unpaidSales as $sale) {
            // Sum payments for this sale
            $paymentsForSale = $sale->payments()->sum('amount');

            // Calculate due = total - payments
            $due = $sale->final_total - $paymentsForSale;

            if ($due > 0) {
                $totalDue += $due;
            }
        }

        return $totalDue;
    }

    public function getTotalReturnDueAttribute()
    {
        // Total value of sales returns (credit to customer)
        return $this->salesReturns()->sum('total_due');
    }

    public function getCurrentDueAttribute()
    {
        // Opening balance
        $opening = $this->opening_balance;

        // Total sales value
        $totalSales = $this->sales()->sum('final_total');

        // Total payments for sales + opening balance
        $totalPayments = $this->payments()->sum('amount');

        // Total returns (credit)
        $totalReturns = $this->salesReturns()->sum('total_due');

        // Current due calculation matching POS
        return ($opening + $totalSales - $totalPayments - $totalReturns);
    }


    /* ============================
     * Helpers
     * ============================
     */
    public function recalculateCurrentBalance()
    {
        // Keep DB current_balance in sync with calculated due
        $this->current_balance = $this->getCurrentDueAttribute();
        $this->saveQuietly();
    }

    public static function calculateCreditLimitForCity($cityId)
    {
        if (!$cityId) {
            return 0;
        }

        $salesRepsWithRoutes = SalesRep::whereHas('route.cities', function ($q) use ($cityId) {
            $q->where('cities.id', $cityId);
        })->get();

        return $salesRepsWithRoutes->max('default_credit_limit') ?: 0;
    }
}
