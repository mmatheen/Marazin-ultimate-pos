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

    // Calculate balance cumulatively for each transaction using unified ledger logic
    public static function calculateBalance($user_id, $contact_type)
    {
        // Get all ledgers for the given user and contact type, ordered by transaction date and creation time
        // Opening balance should be first, then chronological order by actual date/time
        $ledgers = self::where('user_id', $user_id)
                        ->where('contact_type', $contact_type)
                        ->orderByRaw("
                            CASE 
                                WHEN transaction_type = 'opening_balance' THEN 1
                                ELSE 2
                            END
                        ")
                        ->orderBy('transaction_date', 'asc')
                        ->orderBy('created_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

        $running_balance = 0;

        foreach ($ledgers as $ledger) {
            // Unified ledger logic: running_balance = previous_balance + debit - credit
            // The transaction type determines what goes to debit vs credit based on business logic
            $running_balance = $running_balance + $ledger->debit - $ledger->credit;
        }

        // Return the final calculated balance without updating any records
        return $running_balance;
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

    /**
     * Create unified ledger entry with proper debit/credit logic
     */
    public static function createEntry($data)
    {
        // Validate required fields
        $required = ['user_id', 'contact_type', 'transaction_date', 'reference_no', 'transaction_type', 'amount'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Required field {$field} is missing");
            }
        }

        $debit = 0;
        $credit = 0;

        // Unified debit/credit logic based on transaction type
        switch ($data['transaction_type']) {
            case 'opening_balance':
                // Customer opening balance: if positive, customer owes us (debit)
                // Supplier opening balance: if positive, we owe supplier (credit)
                if ($data['contact_type'] === 'customer') {
                    if ($data['amount'] > 0) {
                        $debit = $data['amount'];
                    } else {
                        $credit = abs($data['amount']);
                    }
                } else {
                    if ($data['amount'] > 0) {
                        $credit = $data['amount'];
                    } else {
                        $debit = abs($data['amount']);
                    }
                }
                break;

            case 'sale':
                // Sale increases what customer owes us (debit)
                $debit = $data['amount'];
                break;

            case 'purchase':
                // Purchase increases what we owe supplier (credit)
                $credit = $data['amount'];
                break;

            case 'sale_payment':
            case 'payments':
                // Check if this is a return payment based on notes
                if (isset($data['notes']) && strpos(strtolower($data['notes']), 'return') !== false) {
                    // Return payment: when we pay customer for returns, it's a debit (money flowing out to customer)
                    if ($data['contact_type'] === 'customer') {
                        $debit = $data['amount'];
                    } else {
                        // Return payment from supplier (money coming in from supplier) is credit
                        $credit = $data['amount'];
                    }
                } else {
                    // Regular payment: customer paying us reduces what they owe (credit)
                    // Supplier payment: we pay supplier, reduces what we owe them (debit)
                    if ($data['contact_type'] === 'customer') {
                        $credit = $data['amount'];
                    } else {
                        $debit = $data['amount'];
                    }
                }
                break;

            case 'purchase_payment':
                // Purchase payment reduces what we owe supplier (debit)
                $debit = $data['amount'];
                break;

            case 'sale_return':
            case 'sale_return_with_bill':
            case 'sale_return_without_bill':
                // Sale return reduces what customer owes us (credit)
                $credit = $data['amount'];
                break;

            case 'purchase_return':
                // Purchase return reduces what we owe supplier (debit)
                $debit = $data['amount'];
                break;

            case 'return_payment':
                // Return payment to customer reduces what they owe us (credit)
                // Return payment from supplier reduces what we owe them (debit)
                if ($data['contact_type'] === 'customer') {
                    $credit = $data['amount'];
                } else {
                    // Return payment from supplier reduces what we owe them (debit)
                    $debit = $data['amount'];
                }
                break;

            case 'opening_balance_payment':
                // Opening balance payment
                if ($data['contact_type'] === 'customer') {
                    $credit = $data['amount'];
                } else {
                    $debit = $data['amount'];
                }
                break;

            default:
                throw new \Exception("Unknown transaction type: {$data['transaction_type']}");
        }

        // Get the previous balance from the most recent entry
        $previousBalance = self::where('user_id', $data['user_id'])
            ->where('contact_type', $data['contact_type'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->value('balance') ?? 0;

        // Calculate the new balance for this entry only
        $newBalance = $previousBalance + $debit - $credit;

        // Create the ledger entry with the calculated balance
        $ledger = self::create([
            'user_id' => $data['user_id'],
            'contact_type' => $data['contact_type'],
            'transaction_date' => $data['transaction_date'],
            'reference_no' => $data['reference_no'],
            'transaction_type' => $data['transaction_type'],
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $newBalance, // Calculated balance for this entry only
            'notes' => $data['notes'] ?? ''
        ]);

        return $ledger;
    }

    /**
     * Get unified ledger view (customers and suppliers combined)
     */
    public static function getUnifiedLedger($fromDate = null, $toDate = null, $contactType = null)
    {
        $query = self::with(['customer', 'supplier'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        if ($fromDate) {
            $query->where('transaction_date', '>=', Carbon::parse($fromDate));
        }

        if ($toDate) {
            $query->where('transaction_date', '<=', Carbon::parse($toDate));
        }

        if ($contactType) {
            $query->where('contact_type', $contactType);
        }

        return $query->get()->map(function ($ledger) {
            $contact = $ledger->contact_type === 'customer' ? $ledger->customer : $ledger->supplier;
            $contactName = $contact ? ($contact->first_name . ' ' . $contact->last_name) : 'Unknown';

            return [
                'id' => $ledger->id,
                'transaction_date' => $ledger->transaction_date,
                'reference_no' => $ledger->reference_no,
                'transaction_type' => $ledger->transaction_type,
                'debit' => $ledger->debit,
                'credit' => $ledger->credit,
                'balance' => $ledger->balance,
                'contact_type' => $ledger->contact_type,
                'contact_name' => $contactName,
                'notes' => $ledger->notes,
                'formatted_type' => self::formatTransactionType($ledger->transaction_type)
            ];
        });
    }

    /**
     * Format transaction type for display
     */
    public static function formatTransactionType($type)
    {
        return match($type) {
            'opening_balance' => 'Opening Balance',
            'sale' => 'Sale',
            'purchase' => 'Purchase',
            'sale_payment' => 'Sale Payment',
            'purchase_payment' => 'Purchase Payment',
            'payments' => 'Payment',
            'sale_return' => 'Sale Return',
            'purchase_return' => 'Purchase Return',
            'return_payment' => 'Return Payment',
            'opening_balance_payment' => 'Opening Balance Payment',
            default => ucfirst(str_replace('_', ' ', $type))
        };
    }

    /**
     * Get current outstanding balance for a contact
     */
    public static function getCurrentOutstanding($user_id, $contact_type)
    {
        $currentBalance = self::getLatestBalance($user_id, $contact_type);
        
        if ($contact_type === 'customer') {
            // For customers: positive balance = they owe us
            return max(0, $currentBalance);
        } else {
            // For suppliers: positive balance = we owe them
            return max(0, $currentBalance);
        }
    }

    /**
     * Get advance balance for a contact
     */
    public static function getAdvanceBalance($user_id, $contact_type)
    {
        $currentBalance = self::getLatestBalance($user_id, $contact_type);
        
        if ($contact_type === 'customer') {
            // For customers: negative balance = we owe them (advance)
            return $currentBalance < 0 ? abs($currentBalance) : 0;
        } else {
            // For suppliers: negative balance = they owe us (advance)
            return $currentBalance < 0 ? abs($currentBalance) : 0;
        }
    }
}
