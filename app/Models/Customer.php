<?php

namespace App\Models;

use App\Traits\LocationTrait;
use App\Models\Ledger;
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
        'current_balance',
        'credit_limit',
        'city_id',
        'customer_type',
    ];

    protected $appends = ['full_name', 'total_sale_due', 'total_return_due', 'current_due', 'available_credit'];

    /* ============================
     * Boot events
     * ============================
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            $customer->current_balance = $customer->opening_balance;

            if ($customer->city_id && !$customer->credit_limit) {
                $customer->credit_limit = self::calculateCreditLimitForCity($customer->city_id);
            }
        });

        static::updating(function ($customer) {
            if ($customer->isDirty('city_id') && $customer->city_id) {
                $originalCreditLimit = self::calculateCreditLimitForCity($customer->getOriginal('city_id'));
                if ($customer->getOriginal('credit_limit') == $originalCreditLimit) {
                    $customer->credit_limit = self::calculateCreditLimitForCity($customer->city_id);
                }
            }
        });

        static::saved(function ($customer) {
            if ($customer->id != 1) {
                $customer->recalculateCurrentBalance();
                
                // Sync opening balance to ledger if it was changed OR if it's a new customer with opening balance
                if ($customer->wasChanged('opening_balance') || ($customer->wasRecentlyCreated && $customer->opening_balance != 0)) {
                    $customer->syncOpeningBalanceToLedger();
                }
            } else {
                $customer->current_balance = 0;
                $customer->saveQuietly();
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
     * Accessors
     * ============================
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getTotalSaleDueAttribute()
    {
        return $this->sales()
            ->whereIn('status', ['final', 'suspend'])
            ->where('total_due', '>', 0)
            ->sum('total_due');
    }

    public function getTotalReturnDueAttribute()
    {
        return $this->salesReturns()->sum('total_due');
    }

    public function getCurrentDueAttribute()
    {
        if ($this->id == 1) return 0;

        // Use the latest ledger balance for consistency with the ledger system
        // This ensures POS customer due matches the ledger due calculation
        $latestEntry = Ledger::where('user_id', $this->id)
            ->where('contact_type', 'customer')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $currentBalance = $latestEntry ? $latestEntry->balance : ($this->opening_balance ?? 0);
        
        // Return only positive balances as "due" (customer owes money)
        return max(0, $currentBalance);
    }

    public function getAvailableCreditAttribute()
    {
        if ($this->id == 1) return 0;
        return max(0, $this->credit_limit - $this->current_balance);
    }



public function recalculateCurrentBalance()
{
    if ($this->id == 1) {
        $this->current_balance = 0;
    } else {
        // Calculate balance from ledger entries for accuracy
        $this->current_balance = $this->calculateBalanceFromLedger();
    }
    $this->saveQuietly();
}

    /**
     * Calculate current balance directly from ledger entries
     */
    public function calculateBalanceFromLedger()
    {
        if ($this->id == 1) return 0;

        // Get the latest balance using created_at for proper chronological order
        // This ensures we get the most recently created ledger entry
        $latestEntry = Ledger::where('user_id', $this->id)
            ->where('contact_type', 'customer')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $latestEntry ? $latestEntry->balance : ($this->opening_balance ?? 0);
    }

    /**
     * Check if customer has a city assigned
     */
    public function hasCity()
    {
        return !is_null($this->city_id);
    }

    /**
     * Get city name with fallback
     */
    public function getCityNameAttribute()
    {
        return $this->city?->name ?? 'No City Assigned';
    }

    /**
     * Scope to filter customers by cities (including those without cities)
     */
    public function scopeFilterByCityNames($query, array $cityNames)
    {
        return $query->where(function ($q) use ($cityNames) {
            $q->whereHas('city', function ($cityQuery) use ($cityNames) {
                $cityQuery->whereIn('name', $cityNames);
            })->orWhereNull('city_id');
        });
    }

    public static function calculateCreditLimitForCity($cityId)
    {
        if (!$cityId) return 0;

        return \App\Models\SalesRep::whereHas('route.cities', fn($q) => $q->where('cities.id', $cityId))
            ->max('default_credit_limit') ?: 0;
    }

    /**
     * Create or update opening balance entry in ledger table
     */
    public function syncOpeningBalanceToLedger()
    {
        // Check if opening balance entry already exists
        $existingEntry = Ledger::where('user_id', $this->id)
            ->where('contact_type', 'customer')
            ->where('transaction_type', 'opening_balance')
            ->first();

        if ($this->opening_balance == 0) {
            // If opening balance is 0, remove existing entry
            if ($existingEntry) {
                $existingEntry->delete();
                // Recalculate balances for remaining entries
                Ledger::calculateBalance($this->id, 'customer');
            }
            return;
        }

        $data = [
            'user_id' => $this->id,
            'contact_type' => 'customer',
            'transaction_date' => $this->created_at ?: now(),
            'reference_no' => 'OPENING-' . $this->id,
            'transaction_type' => 'opening_balance',
            'debit' => $this->opening_balance > 0 ? $this->opening_balance : 0,
            'credit' => $this->opening_balance < 0 ? abs($this->opening_balance) : 0,
            'balance' => $this->opening_balance,
            'notes' => 'Opening Balance for Customer: ' . $this->first_name . ' ' . $this->last_name,
        ];

        if ($existingEntry) {
            // Update existing entry
            $existingEntry->update($data);
        } else {
            // Create new entry
            Ledger::create($data);
        }

        // Recalculate balances for all entries
        Ledger::calculateBalance($this->id, 'customer');
    }
}