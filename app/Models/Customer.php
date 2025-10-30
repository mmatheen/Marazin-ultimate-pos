<?php

namespace App\Models;

use App\Traits\LocationTrait;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

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
        // Return default credit limit of 0 (no automatic calculation)
        return 0;
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
            'transaction_date' => $this->created_at ? 
                Carbon::parse($this->created_at)->setTimezone('Asia/Colombo') : 
                Carbon::now('Asia/Colombo'),
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

    /**
     * Get customer's bill-wise outstanding balance (unpaid bills only)
     * This excludes bounced cheque floating balance
     */
    public function getBillWiseOutstanding()
    {
        return Sale::where('customer_id', $this->id)
            ->whereNotIn('payment_status', ['Paid'])
            ->sum(DB::raw('final_total - COALESCE(total_paid, 0)'));
    }

    /**
     * Get customer's floating balance (bounced cheques, bank charges, adjustments)
     * This is separate from bill-wise balance
     */
    public function getFloatingBalance()
    {
        $floatingDebits = Ledger::where('user_id', $this->id)
            ->where('contact_type', 'customer')
            ->whereIn('transaction_type', ['cheque_bounce', 'bank_charges', 'penalty', 'adjustment_debit'])
            ->sum('amount');

        $floatingCredits = Ledger::where('user_id', $this->id)
            ->where('contact_type', 'customer')
            ->whereIn('transaction_type', ['bounce_recovery', 'adjustment_credit', 'refund'])
            ->sum('amount');

        return $floatingDebits - $floatingCredits;
    }

    /**
     * Get total outstanding including both bill-wise and floating balance
     */
    public function getTotalOutstanding()
    {
        return $this->getBillWiseOutstanding() + $this->getFloatingBalance();
    }

    /**
     * Get bounced cheques summary
     */
    public function getBouncedChequeSummary()
    {
        $bouncedPayments = Payment::where('customer_id', $this->id)
            ->where('payment_method', 'cheque')
            ->where('cheque_status', 'bounced')
            ->get();

        return [
            'count' => $bouncedPayments->count(),
            'total_amount' => $bouncedPayments->sum('amount'),
            'total_charges' => $bouncedPayments->sum('bank_charges'),
            'cheques' => $bouncedPayments->map(function($payment) {
                return [
                    'cheque_number' => $payment->cheque_number,
                    'amount' => $payment->amount,
                    'bounce_date' => $payment->cheque_bounce_date,
                    'bounce_reason' => $payment->cheque_bounce_reason,
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
            ->where('cheque_status', 'bounced')
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
            ->where('cheque_status', 'bounced')
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