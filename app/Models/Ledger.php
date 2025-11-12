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
            $query->where('transaction_date', '>=', Carbon::parse($fromDate)->startOfDay());
        }
        if ($toDate) {
            $query->where('transaction_date', '<=', Carbon::parse($toDate)->endOfDay());
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
        // Get all ledgers for the given user and contact type, ordered by creation time in Asia/Colombo timezone
        // Opening balance should be first, then chronological order by created_at timestamps in Colombo time
        $ledgers = self::where('user_id', $user_id)
                        ->where('contact_type', $contact_type)
                        ->orderByRaw("
                            CASE 
                                WHEN transaction_type = 'opening_balance' THEN 1
                                ELSE 2
                            END
                        ")
                        ->orderByRaw("CONVERT_TZ(created_at, 'UTC', 'Asia/Colombo') ASC")
                        ->orderBy('id', 'asc')
                        ->get();

        $running_balance = 0;

        foreach ($ledgers as $ledger) {
            // Balance calculation depends on contact type:
            // CUSTOMERS: Debit increases what they owe us, Credit reduces it
            // SUPPLIERS: Credit increases what we owe them, Debit reduces it
            if ($contact_type === 'customer') {
                // Customer balance: debit increases, credit decreases
                $running_balance = $running_balance + $ledger->debit - $ledger->credit;
            } else {
                // Supplier balance: credit increases, debit decreases
                $running_balance = $running_balance + $ledger->credit - $ledger->debit;
            }
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
            ->orderBy('created_at', 'desc') // Use created_at for latest entry
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
                // Handle negative amounts for reversals (negative sale = credit)
                if ($data['amount'] > 0) {
                    $debit = $data['amount'];
                } else {
                    $credit = abs($data['amount']); // Negative sale becomes credit
                }
                break;

            case 'purchase':
                // Purchase increases what we owe supplier (credit)
                $credit = $data['amount'];
                break;

            case 'sale_payment':
            case 'payments':
                // Handle negative amounts for payment reversals
                $amount = abs($data['amount']); // Work with positive amount
                $isReversal = $data['amount'] < 0; // Check if this is a reversal
                
                // Check if this is a return payment based on notes
                if (isset($data['notes']) && strpos(strtolower($data['notes']), 'return') !== false) {
                    // Return payment: when we pay customer for returns, it's a debit (money flowing out to customer)
                    if ($data['contact_type'] === 'customer') {
                        if ($isReversal) {
                            $credit = $amount; // Reversal of return payment becomes credit
                        } else {
                            $debit = $amount;
                        }
                    } else {
                        // Return payment from supplier (money coming in from supplier) reduces what we owe them = DEBIT
                        if ($isReversal) {
                            $credit = $amount; // Reversal becomes credit
                        } else {
                            $debit = $amount;
                        }
                    }
                } else {
                    // Regular payment: customer paying us reduces what they owe (credit)
                    // Supplier payment: we pay supplier, reduces what we owe them (debit)
                    if ($data['contact_type'] === 'customer') {
                        if ($isReversal) {
                            $debit = $amount; // Reversal of customer payment becomes debit
                        } else {
                            $credit = $amount;
                        }
                    } else {
                        if ($isReversal) {
                            $credit = $amount; // Reversal of supplier payment becomes credit
                        } else {
                            $debit = $amount;
                        }
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

            case 'cheque_bounce':
                // Cheque bounce increases customer debt (they owe us the bounced amount)
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount'];
                } else {
                    // Unlikely scenario for supplier cheque bounce, but handle it
                    $credit = $data['amount'];
                }
                break;

            case 'bank_charges':
                // Bank charges increase customer debt (additional charges they owe us)
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount'];
                } else {
                    // Bank charges for supplier payments reduce what we owe them
                    $debit = $data['amount'];
                }
                break;

            case 'penalty':
                // Penalty increases customer debt
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount'];
                } else {
                    $credit = $data['amount'];
                }
                break;

            case 'adjustment_debit':
                // Manual adjustment - debit
                $debit = $data['amount'];
                break;

            case 'adjustment_credit':
                // Manual adjustment - credit
                $credit = $data['amount'];
                break;

            case 'bounce_recovery':
                // Recovery of bounced cheque reduces customer debt
                if ($data['contact_type'] === 'customer') {
                    $credit = $data['amount'];
                } else {
                    $debit = $data['amount'];
                }
                break;

            case 'invoice':
                // Invoice transaction (similar to sale)
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount'];
                } else {
                    $credit = $data['amount'];
                }
                break;

            default:
                throw new \Exception("Unknown transaction type: {$data['transaction_type']}");
        }

        // Create the ledger entry first without balance
        $ledger = self::create([
            'user_id' => $data['user_id'],
            'contact_type' => $data['contact_type'],
            'transaction_date' => $data['transaction_date'],
            'reference_no' => $data['reference_no'],
            'transaction_type' => $data['transaction_type'],
            'debit' => $debit,
            'credit' => $credit,
            'balance' => 0, // Temporary, will be recalculated
            'notes' => $data['notes'] ?? ''
        ]);

        // Now recalculate all balance fields for this user/contact_type to ensure chronological order
        self::recalculateAllBalances($data['user_id'], $data['contact_type']);

        // Refresh the ledger model to get the updated balance
        $ledger->refresh();

        return $ledger;
    }

    /**
     * Recalculate all balance fields for a specific user and contact type in chronological order
     * This ensures that balance fields are always correct regardless of entry creation order
     */
    public static function recalculateAllBalances($user_id, $contact_type)
    {
        // Get all entries in chronological order based on created_at timestamps
        $entries = self::where('user_id', $user_id)
                      ->where('contact_type', $contact_type)
                      ->orderByRaw("
                          CASE 
                              WHEN transaction_type = 'opening_balance' THEN 1
                              ELSE 2
                          END
                      ")
                      ->orderBy('created_at', 'asc') // Use created_at without timezone conversion
                      ->orderBy('id', 'asc')
                      ->get();

        $running_balance = 0;

        foreach ($entries as $entry) {
            // Balance calculation depends on contact type:
            // CUSTOMERS: Debit increases what they owe us, Credit reduces it
            // SUPPLIERS: Credit increases what we owe them, Debit reduces it
            if ($contact_type === 'customer') {
                // Customer balance: debit increases, credit decreases
                $running_balance = $running_balance + $entry->debit - $entry->credit;
            } else {
                // Supplier balance: credit increases, debit decreases  
                $running_balance = $running_balance + $entry->credit - $entry->debit;
            }
            
            // Update only the balance field without changing created_at/updated_at
            self::where('id', $entry->id)
                ->update(['balance' => $running_balance]);
        }
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
