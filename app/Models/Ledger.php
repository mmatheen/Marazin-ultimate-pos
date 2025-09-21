<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Ledger extends Model
{
    protected $fillable = [
        'transaction_date',
        'reference_no',
        'transaction_type',
        'debit',
        'credit',
        'balance',
        'contact_type',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2'
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'user_id')->where('contact_type', 'supplier');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id')->where('contact_type', 'customer');
    }

    // Scopes
    public function scopeSupplier($query)
    {
        return $query->where('contact_type', 'supplier');
    }

    public function scopeCustomer($query)
    {
        return $query->where('contact_type', 'customer');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $fromDate = null, $toDate = null)
    {
        if ($fromDate) {
            $query->where('transaction_date', '>=', Carbon::parse($fromDate));
        }
        if ($toDate) {
            $query->where('transaction_date', '<=', Carbon::parse($toDate));
        }
        return $query;
    }

    public function scopeByTransactionType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    // Helper methods
    public function getFormattedTransactionDateAttribute()
    {
        return $this->transaction_date->format('Y-m-d H:i:s');
    }

    public function getFormattedDebitAttribute()
    {
        return number_format($this->debit, 2);
    }

    public function getFormattedCreditAttribute()
    {
        return number_format($this->credit, 2);
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2);
    }

    // Calculate balance cumulatively for each transaction
    public static function calculateBalance($user_id, $contact_type)
    {
        // Get all ledgers for the given user and contact type, ordered by transaction date
        // Use CASE to prioritize opening_balance entries first, then order by date and id
        $ledgers = self::where('user_id', $user_id)
                        ->where('contact_type', $contact_type)
                        ->orderByRaw("
                            CASE 
                                WHEN transaction_type = 'opening_balance' THEN 1
                                WHEN transaction_type = 'sale' THEN 2
                                WHEN transaction_type = 'opening_balance_payment' THEN 3
                                WHEN transaction_type = 'payments' THEN 4
                                ELSE 5
                            END
                        ")
                        ->orderBy('transaction_date', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

        $previous_balance = 0;

        foreach ($ledgers as $ledger) {
            // Calculate the cumulative balance 
            // For customers: debit (sales/opening balance) increases what customer owes us
            //               credit (payments) decreases what customer owes us
            // For suppliers: debit (payments) decreases what we owe them  
            //               credit (purchases/opening balance) increases what we owe them
            if ($contact_type === 'customer') {
                // Customer balance: positive = customer owes us, negative = we owe customer (advance)
                $balance = $previous_balance + $ledger->debit - $ledger->credit;
            } else {
                // Supplier balance: positive = we owe supplier, negative = supplier owes us (advance)
                $balance = $previous_balance + $ledger->credit - $ledger->debit;
            }

            // Update the balance in the ledger record
            $ledger->balance = $balance;
            $ledger->save();

            // Set the previous balance for the next iteration
            $previous_balance = $balance;
        }

        return $previous_balance;
    }

    /**
     * Get the latest balance for a specific user and contact type
     */
    public static function getLatestBalance($user_id, $contact_type)
    {
        $latestEntry = self::where('user_id', $user_id)
            ->where('contact_type', $contact_type)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $latestEntry ? $latestEntry->balance : 0;
    }

    /**
     * Get ledger statement for a specific period
     */
    public static function getStatement($user_id, $contact_type, $fromDate = null, $toDate = null)
    {
        $query = self::where('user_id', $user_id)
            ->where('contact_type', $contact_type)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        if ($fromDate) {
            $query->where('transaction_date', '>=', Carbon::parse($fromDate));
        }

        if ($toDate) {
            $query->where('transaction_date', '<=', Carbon::parse($toDate));
        }

        return $query->get();
    }
}
