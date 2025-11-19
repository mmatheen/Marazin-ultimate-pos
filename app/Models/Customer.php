<?php

namespace App\Models;

use App\Traits\LocationTrait;
use App\Models\Ledger;
use Carbon\Carbon;
use App\Helpers\BalanceHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory,LocationTrait;

    /*
     * BALANCE CALCULATION ARCHITECTURE
     * ================================
     * This model uses a unified approach for all customer balance calculations:
     * 
     * 1. SINGLE SOURCE OF TRUTH: BalanceHelper::getCustomerBalance() 
     * 2. STATUS FILTERING: Only 'active' ledger entries are considered (excludes 'reversed')
     * 3. UNIFIED LEDGER: All transactions flow through UnifiedLedgerService
     * 
     * Key Methods:
     * - getCurrentBalance(): Main balance calculation method
     * - getCurrentBalanceAttribute(): Laravel accessor for API responses
     * - getCurrentDueAttribute(): Positive balance only (customer owes money)
     * - getTotalOutstanding(): Same as getCurrentBalance but with max(0, balance)
     * - getFloatingBalance(): Specific floating transactions (bounced cheques, etc.)
     * - getBillWiseOutstanding(): Sale-related balance from ledger
     */

    protected $table = 'customers';

    protected $fillable = [
        'prefix',
        'first_name',
        'last_name',
        'mobile_no',
        'email',
        'address',
        'opening_balance',
        'credit_limit',
        'city_id',
        'customer_type',
    ];

    protected $appends = ['full_name', 'total_sale_due', 'total_return_due', 'current_due', 'current_balance', 'available_credit'];

    /* ============================
     * Boot events
     * ============================
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
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
                // Sync opening balance to ledger if it was changed OR if it's a new customer with opening balance
                if ($customer->wasChanged('opening_balance') || ($customer->wasRecentlyCreated && $customer->opening_balance != 0)) {
                    $customer->syncOpeningBalanceToLedger();
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

    public function ledgerEntries()
    {
        return $this->hasMany(Ledger::class, 'contact_id')->where('contact_type', 'customer');
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
        
        // Use BalanceHelper - the ONLY place for balance calculations
        return BalanceHelper::getCustomerDue($this->id);
    }

    public function getAvailableCreditAttribute()
    {
        if ($this->id == 1) return 0;
        return max(0, $this->credit_limit - $this->getTotalOutstanding());
    }

    public function getCurrentBalanceAttribute()
    {
        if ($this->id == 1) return 0; // Walk-in customer
        return BalanceHelper::getCustomerBalance($this->id);
    }

    /**
     * Calculate balance from ledger entries - delegates to BalanceHelper
     * This method is called by POS system for credit limit validation
     */
    public function calculateBalanceFromLedger()
    {
        if ($this->id == 1) return 0; // Walk-in customer
        return BalanceHelper::getCustomerBalance($this->id);
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
        // Return default credit limit of 0 (no automatic calculation)
        return 0;
    }

    /**
     * Create or update opening balance entry in ledger table
     */
    public function syncOpeningBalanceToLedger()
    {
        // Check if opening balance entry already exists
        $existingEntry = Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'customer')
            ->where('transaction_type', 'opening_balance')
            ->where('status', 'active')
            ->first();

        if ($this->opening_balance == 0) {
            // If opening balance is 0, mark existing entry as reversed
            if ($existingEntry) {
                $existingEntry->update([
                    'status' => 'reversed',
                    'notes' => $existingEntry->notes . ' [REVERSED: Opening balance set to zero]'
                ]);
            }
            return;
        }

        // Use the UnifiedLedgerService to create proper opening balance entries
        $unifiedLedgerService = app(\App\Services\UnifiedLedgerService::class);
        
        if ($existingEntry) {
            // If there's an existing entry, mark it as reversed and create a new one
            $existingEntry->update([
                'status' => 'reversed',
                'notes' => $existingEntry->notes . ' [REVERSED: Opening balance updated]'
            ]);
        }
        
        // Create new opening balance entry using the service
        $unifiedLedgerService->recordOpeningBalance(
            $this->id, 
            'customer', 
            $this->opening_balance, 
            'Opening Balance for Customer: ' . $this->full_name
        );
    }

    /**
     * Get customer's floating balance - DELEGATES to BalanceHelper
     * (bounced cheques, bank charges, adjustments)
     */
    public function getFloatingBalance()
    {
        // This is a specialized calculation, keep it here for now
        // But ideally this should move to BalanceHelper too
        $floatingDebits = Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->whereIn('transaction_type', ['cheque_bounce', 'bank_charges', 'penalty', 'adjustment_debit'])
            ->sum('debit');

        $floatingCredits = Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->whereIn('transaction_type', ['bounce_recovery', 'adjustment_credit', 'refund'])
            ->sum('credit');

        return $floatingDebits - $floatingCredits;
    }

    /**
     * Get total outstanding balance (positive balances only)
     * Uses BalanceHelper for consistency
     */
    public function getTotalOutstanding()
    {
        if ($this->id == 1) return 0;
        return BalanceHelper::getCustomerDue($this->id);
    }

    /**
     * Get bounced cheques summary
     */
    public function getBouncedChequeSummary()
    {
        // Get payments that have bounced status in their latest status history
        $bouncedPayments = Payment::where('customer_id', $this->id)
            ->where('payment_method', 'cheque')
            ->whereHas('chequeStatusHistory', function($query) {
                $query->whereIn('id', function($subQuery) {
                    $subQuery->select(DB::raw('MAX(id)'))
                        ->from('cheque_status_histories')
                        ->groupBy('payment_id');
                })->where('status', 'bounced');
            })
            ->with(['chequeStatusHistory' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(1);
            }, 'sale'])
            ->get();

        return [
            'count' => $bouncedPayments->count(),
            'total_amount' => $bouncedPayments->sum('amount'),
            'total_charges' => $bouncedPayments->sum('bank_charges'),
            'cheques' => $bouncedPayments->map(function($payment) {
                $latestStatus = $payment->chequeStatusHistory->first();
                return [
                    'id' => $payment->id,
                    'cheque_number' => $payment->cheque_number,
                    'amount' => $payment->amount,
                    'bounce_date' => $latestStatus ? $latestStatus->status_date : null,
                    'bounce_reason' => $latestStatus ? $latestStatus->remarks : null,
                    'bank_charges' => $payment->bank_charges,
                    'bill_number' => $payment->sale ? $payment->sale->invoice_no : null
                ];
            })
        ];
    }

    /**
     * Get customer risk score based on payment history
     */
    public function getRiskScore()
    {
        $totalCheques = Payment::where('customer_id', $this->id)
            ->where('payment_method', 'cheque')
            ->count();

        $bouncedCheques = Payment::where('customer_id', $this->id)
            ->where('payment_method', 'cheque')
            ->whereHas('chequeStatusHistory', function($query) {
                $query->whereIn('id', function($subQuery) {
                    $subQuery->select(DB::raw('MAX(id)'))
                        ->from('cheque_status_histories')
                        ->groupBy('payment_id');
                })->where('status', 'bounced');
            })
            ->count();

        $overdueDays = 0; // You can implement overdue calculation based on your business rules
        
        $riskScore = 0;
        
        if ($totalCheques > 0) {
            $bounceRate = ($bouncedCheques / $totalCheques) * 100;
            $riskScore += $bounceRate * 2; // Bounce rate contributes heavily to risk
        }
        
        $riskScore += min($overdueDays * 0.5, 30); // Overdue days (max 30 points)
        $riskScore += min($this->getTotalOutstanding() / 10000, 20); // Outstanding amount factor
        
        return min(round($riskScore), 100); // Cap at 100
    }

    /**
     * Check if customer should be blocked for cheque payments
     */
    public function shouldBlockChequePayments()
    {
        $bouncedCount = Payment::where('customer_id', $this->id)
            ->where('payment_method', 'cheque')
            ->whereHas('chequeStatusHistory', function($query) {
                $query->whereIn('id', function($subQuery) {
                    $subQuery->select(DB::raw('MAX(id)'))
                        ->from('cheque_status_histories')
                        ->groupBy('payment_id');
                })->where('status', 'bounced');
            })
            ->count();

        $riskScore = $this->getRiskScore();
        
        // Block if more than 2 bounced cheques OR risk score > 70
        return $bouncedCount > 2 || $riskScore > 70;
    }

    /**
     * Get detailed balance breakdown for dashboard
     */
    public function getBalanceBreakdown()
    {
        return [
            'bill_wise_outstanding' => $this->getBillWiseOutstanding(),
            'floating_balance' => $this->getFloatingBalance(),
            'bounced_cheques' => $this->getBouncedChequeSummary(),
            'total_outstanding' => $this->getTotalOutstanding(),
            'credit_limit' => $this->credit_limit,
            'available_credit' => max(0, $this->credit_limit - $this->getTotalOutstanding()),
            'risk_score' => $this->getRiskScore(),
            'cheque_payment_blocked' => $this->shouldBlockChequePayments()
        ];
    }
}