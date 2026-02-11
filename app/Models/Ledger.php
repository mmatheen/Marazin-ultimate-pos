<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Ledger extends Model
{
    /*
     * LEDGER MODEL ARCHITECTURE
     * ==========================
     * This model handles individual ledger entries with the new status-based system.
     *
     * IMPORTANT: For balance calculations, use BalanceHelper class instead of
     * methods in this model to ensure consistency across the application.
     *
     * Key Principles:
     * 1. STATUS FILTERING: Only 'active' entries count towards balances
     * 2. UNIFIED APPROACH: BalanceHelper provides consistent calculations
     * 3. DEBIT/CREDIT LOGIC: Proper accounting principles maintained
     * 4. REVERSALS: Use status='reversed' instead of deleting entries
     *
     * Core Methods:
     * - createEntry(): Create new ledger entries
     * - reverseEntry(): Mark entries as reversed
     * - Scopes: active(), reversed(), byDateRange(), etc.
     */

    protected $fillable = [
        'transaction_date',
        'reference_no',
        'transaction_type',
        'debit',
        'credit',
        'status',
        'contact_type',
        'contact_id',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2'
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'contact_id')->where('contact_type', 'supplier');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'contact_id')->where('contact_type', 'customer');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
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

    public function scopeForContact($query, $contactId)
    {
        return $query->where('contact_id', $contactId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeReversed($query)
    {
        return $query->where('status', 'reversed');
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

    public function getStatusBadgeAttribute()
    {
        return $this->status === 'active' ? 'Active' : 'Reversed';
    }

    /**
     * Calculate balance using BalanceHelper (CENTRALIZED APPROACH)
     *
     * @deprecated This method is deprecated. Use BalanceHelper::getCustomerBalance()
     * or BalanceHelper::getSupplierBalance() directly for consistent balance calculations.
     */
    public static function calculateBalance($contact_id, $contact_type)
    {
        // Delegate to BalanceHelper for consistency
        if ($contact_type === 'customer') {
            return \App\Helpers\BalanceHelper::getCustomerBalance($contact_id);
        } else {
            return \App\Helpers\BalanceHelper::getSupplierBalance($contact_id);
        }
    }

    /**
     * Get running balance with history using SQL window function
     */
    public static function getRunningBalanceHistory($contact_id, $contact_type, $limit = null)
    {
        $limitClause = $limit ? "LIMIT {$limit}" : '';

        $results = DB::select("
            SELECT
                *,
                CASE
                    WHEN ? = 'customer' THEN
                        SUM(debit - credit) OVER (
                            ORDER BY
                                CASE WHEN transaction_type = 'opening_balance' THEN 1 ELSE 2 END,
                                CONVERT_TZ(created_at, 'UTC', 'Asia/Colombo'),
                                id
                            ROWS UNBOUNDED PRECEDING
                        )
                    ELSE
                        SUM(credit - debit) OVER (
                            ORDER BY
                                CASE WHEN transaction_type = 'opening_balance' THEN 1 ELSE 2 END,
                                CONVERT_TZ(created_at, 'UTC', 'Asia/Colombo'),
                                id
                            ROWS UNBOUNDED PRECEDING
                        )
                END as running_balance
            FROM ledgers
            WHERE contact_id = ?
                AND contact_type = ?
                AND status = 'active'
            ORDER BY
                CASE WHEN transaction_type = 'opening_balance' THEN 1 ELSE 2 END,
                CONVERT_TZ(created_at, 'UTC', 'Asia/Colombo'),
                id
            {$limitClause}
        ", [$contact_type, $contact_id, $contact_type]);

        return collect($results);
    }

    /**
     * Get running balance with history using SQL window function
     */
    public static function getStatement($contact_id, $contact_type, $fromDate = null, $toDate = null, $includeReversed = false)
    {
        $statusCondition = $includeReversed ? '' : "AND status = 'active'";
        $dateConditions = '';
        $params = [$contact_type, $contact_id, $contact_type];

        if ($fromDate) {
            $dateConditions .= " AND transaction_date >= ?";
            $params[] = $fromDate;
        }

        if ($toDate) {
            $dateConditions .= " AND transaction_date <= ?";
            $params[] = $toDate;
        }

        $results = DB::select("
            SELECT
                *,
                CASE
                    WHEN ? = 'customer' THEN
                        SUM(debit - credit) OVER (
                            ORDER BY
                                CASE WHEN transaction_type = 'opening_balance' THEN 1 ELSE 2 END,
                                transaction_date,
                                id
                            ROWS UNBOUNDED PRECEDING
                        )
                    ELSE
                        SUM(credit - debit) OVER (
                            ORDER BY
                                CASE WHEN transaction_type = 'opening_balance' THEN 1 ELSE 2 END,
                                transaction_date,
                                id
                            ROWS UNBOUNDED PRECEDING
                        )
                END as running_balance
            FROM ledgers
            WHERE contact_id = ?
                AND contact_type = ?
                {$statusCondition}
                {$dateConditions}
            ORDER BY
                CASE WHEN transaction_type = 'opening_balance' THEN 1 ELSE 2 END,
                transaction_date,
                id
        ", $params);

        return collect($results);
    }

    /**
     * Create unified ledger entry with proper debit/credit logic
     */
    public static function createEntry($data)
    {
        // Validate required fields
        $required = ['contact_id', 'contact_type', 'transaction_date', 'reference_no', 'transaction_type', 'amount'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                Log::error('Ledger createEntry validation failed', [
                    'field' => $field,
                    'provided_value' => $data[$field] ?? 'not_set',
                    'data_keys' => array_keys($data),
                    'all_data' => $data
                ]);
                throw new \Exception("Required field '{$field}' is missing or empty in ledger entry. Provided value: " . ($data[$field] ?? 'null') . ". Please check the calling code.");
            }
        }

        // ✅ ADDITIONAL VALIDATION: Ensure transaction_date is valid Carbon instance or parseable date
        try {
            if (!($data['transaction_date'] instanceof Carbon)) {
                $data['transaction_date'] = Carbon::parse($data['transaction_date']);
            }
        } catch (\Exception $e) {
            Log::error('Invalid transaction_date in ledger entry', [
                'transaction_date' => $data['transaction_date'],
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Invalid transaction_date format: " . $e->getMessage());
        }

        // ✅ VALIDATION: Ensure contact_id is numeric and greater than 0
        if (!is_numeric($data['contact_id']) || $data['contact_id'] <= 0) {
            Log::error('Invalid contact_id in ledger entry', [
                'contact_id' => $data['contact_id'],
                'contact_type' => $data['contact_type']
            ]);
            throw new \Exception("Invalid contact_id: must be a positive number. Received: " . $data['contact_id']);
        }

        // ✅ CRITICAL FIX: Prevent duplicate ledger entries (REFINED FOR PAYMENTS)
        // Check if an active entry with the same criteria already exists
        $duplicateQuery = self::where('contact_id', $data['contact_id'])
            ->where('contact_type', $data['contact_type'])
            ->where('reference_no', $data['reference_no'])
            ->where('transaction_type', $data['transaction_type'])
            ->where('status', 'active');

        // For payment transactions, check EXACT DUPLICATE only (same amount + same reference + very recent)
        // This allows multiple legitimate payments to the same supplier/customer
        // NOTE: Bulk payments now have unique reference_no (includes payment ID), so they won't be flagged as duplicates
        if (in_array($data['transaction_type'], ['payment', 'payments', 'sale_payment', 'purchase_payment'])) {
            $duplicateQuery->where(function($query) use ($data) {
                $query->where('debit', abs($data['amount']))
                      ->orWhere('credit', abs($data['amount']));
            })
            ->where('created_at', '>=', Carbon::now()->subSeconds(5)); // Only within 5 seconds (prevents double-click only)
        } else {
            // For non-payment transactions (sales, purchases, etc.), check for exact duplicates
            // within a very short window to prevent double-click submissions
            $duplicateQuery->where('created_at', '>=', Carbon::now()->subSeconds(30)); // Within 30 seconds
        }

        $existingEntry = $duplicateQuery->first();

        if ($existingEntry) {
            // Log with more detail to help diagnose issues
            Log::warning('Duplicate ledger entry detected and prevented', [
                'contact_id' => $data['contact_id'],
                'contact_type' => $data['contact_type'],
                'reference_no' => $data['reference_no'],
                'transaction_type' => $data['transaction_type'],
                'existing_amount' => $existingEntry->debit ?: $existingEntry->credit,
                'attempted_amount' => $data['amount'],
                'existing_id' => $existingEntry->id,
                'time_diff_seconds' => $existingEntry->created_at->diffInSeconds(Carbon::now()),
                'existing_created_at' => $existingEntry->created_at->toDateTimeString(),
                'request_timestamp' => Carbon::now()->toDateTimeString()
            ]);

            // ⚠️ IMPORTANT: Return existing entry (not throw exception)
            // This prevents legitimate retries from failing
            return $existingEntry;
        }

        // ✅ LOG SUCCESSFUL CREATION ATTEMPT for debugging
        Log::info('Creating new ledger entry - validation passed', [
            'contact_id' => $data['contact_id'],
            'contact_type' => $data['contact_type'],
            'reference_no' => $data['reference_no'],
            'transaction_type' => $data['transaction_type'],
            'amount' => $data['amount'],
            'transaction_date' => $data['transaction_date']->toDateTimeString()
        ]);

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
                // Purchase increases what we owe supplier (CREDIT - our liability increases)
                $credit = $data['amount'];
                break;

            case 'sale_payment':
            case 'payment':
            case 'payments':  // ✅ ADDED: Support for standardized 'payments' transaction type
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
                        // Return payment from supplier (money coming in from supplier) = CREDIT (cash inflow)
                        if ($isReversal) {
                            $debit = $amount; // Reversal becomes debit (removes the credit)
                        } else {
                            $credit = $amount; // ✅ FIX: Return payment from supplier is CREDIT (money coming in)
                        }
                    }
                } else {
                    // Regular payment: customer paying us reduces what they owe (credit)
                    // Supplier payment: we pay supplier (debit - cash flow out)
                    if ($data['contact_type'] === 'customer') {
                        if ($isReversal) {
                            $debit = $amount; // Reversal of customer payment becomes debit
                        } else {
                            $credit = $amount;
                        }
                    } else {
                        // Supplier payment: we pay supplier (DEBIT - cash flow out)
                        if ($isReversal) {
                            $credit = $amount; // Reversal of supplier payment becomes CREDIT (restores debt)
                        } else {
                            $debit = $amount; // Payment to supplier is DEBIT (cash flow out)
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
                // Return payment logic:
                // - Customer cash refund: We pay cash to customer (DEBIT - money going out, creates advance)
                // - Supplier return payment: Supplier pays us back for returned goods (CREDIT - money coming in)
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount']; // Cash refund creates advance/credit in customer's favor
                } else {
                    // Supplier return payment: Supplier pays us back (CREDIT - money flowing in from supplier)
                    $credit = $data['amount'];
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

            case 'discount_given':
                // Discount given to customer reduces what they owe (CREDIT - same as payment)
                // This is used when payment_method = 'discount' in bulk payments
                // Discount works like a payment - reduces customer's outstanding debt
                if ($data['contact_type'] === 'customer') {
                    $credit = $data['amount']; // Discount reduces customer's debt (credit entry, like payment)
                } else {
                    // Discount from supplier (reduces what we owe them)
                    $debit = $data['amount'];
                }
                break;

            case 'opening_balance_adjustment':
                // Opening balance adjustment for reversal accounting
                // This handles the reversal entry in perfect reversal accounting
                if ($data['contact_type'] === 'customer') {
                    // For customers: positive amount = debit, negative amount = credit (reversal)
                    if ($data['amount'] > 0) {
                        $debit = $data['amount'];
                    } else {
                        $credit = abs($data['amount']);
                    }
                } else {
                    // For suppliers: positive amount = credit, negative amount = debit (reversal)
                    if ($data['amount'] > 0) {
                        $credit = $data['amount'];
                    } else {
                        $debit = abs($data['amount']);
                    }
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

            case 'advance_payment':
                // Advance payment: Customer pays more than they owe (creates credit balance)
                // This reduces what they owe us (or creates a credit they can use later)
                if ($data['contact_type'] === 'customer') {
                    $credit = $data['amount']; // Credit reduces customer debt / creates advance
                } else {
                    // Advance to supplier (we pay them more than we owe)
                    $debit = $data['amount']; // Debit increases what they owe us
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

            case 'payment_adjustment':
                // Payment adjustment for reversal accounting
                // This creates the exact opposite effect of the original payment
                $amount = abs($data['amount']);

                // Check if this is a return payment adjustment based on notes
                $isReturnPayment = isset($data['notes']) && strpos(strtolower($data['notes']), 'return payment reversal') !== false;

                if ($isReturnPayment) {
                    // Return payment adjustment - we need to reverse the original return payment effect
                    if ($data['contact_type'] === 'customer') {
                        // Original customer return payment was DEBIT (money out to customer)
                        // So reversal should be CREDIT (cancel the money out)
                        $credit = $amount;
                    } else {
                        // Original supplier return payment was CREDIT (money in from supplier)
                        // So reversal should be DEBIT (cancel the money in)
                        $debit = $amount;
                    }
                } else {
                    // Regular payment adjustment - reverse normal payment
                    if ($data['contact_type'] === 'customer') {
                        // Original customer payment was CREDIT (they paid us)
                        // So reversal should be DEBIT (cancel their payment)
                        $debit = $amount;
                    } else {
                        // Original supplier payment was DEBIT (we paid them)
                        // So reversal should be CREDIT (cancel our payment - restore debt)
                        $credit = $amount;
                    }
                }
                break;

            case 'sale_adjustment':
                // Sale adjustment for reversal accounting (used in sale deletion/reversal)
                // This reverses a sale entry
                if ($data['contact_type'] === 'customer') {
                    // Sale was DEBIT (customer owes us), so adjustment is CREDIT (cancel the debt)
                    if ($data['amount'] < 0) {
                        $credit = abs($data['amount']);
                    } else {
                        $debit = $data['amount'];
                    }
                } else {
                    // For suppliers (unlikely but handle it)
                    if ($data['amount'] < 0) {
                        $debit = abs($data['amount']);
                    } else {
                        $credit = $data['amount'];
                    }
                }
                break;

            case 'purchase_adjustment':
                // Purchase adjustment for reversal accounting (used in purchase deletion/reversal)
                // This reverses a purchase entry
                if ($data['contact_type'] === 'supplier') {
                    // Purchase was CREDIT (we owe supplier), so adjustment is DEBIT (cancel the debt)
                    if ($data['amount'] < 0) {
                        $debit = abs($data['amount']);
                    } else {
                        $credit = $data['amount'];
                    }
                } else {
                    // For customers (unlikely but handle it)
                    if ($data['amount'] < 0) {
                        $credit = abs($data['amount']);
                    } else {
                        $debit = $data['amount'];
                    }
                }
                break;

            default:
                // Handle dynamic transaction types with "_reversal" suffix
                if (str_ends_with($data['transaction_type'], '_reversal')) {
                    $baseType = str_replace('_reversal', '', $data['transaction_type']);

                    // For reversal entries, we typically reverse the logic of the base transaction
                    switch ($baseType) {
                        case 'purchase_return':
                            // Purchase return reversal should restore what we owe supplier (credit)
                            $credit = $data['amount'];
                            break;

                        case 'sale':
                        case 'purchase':
                        case 'payment':
                        case 'payments':
                        case 'sale_payment':
                        case 'purchase_payment':
                        case 'sale_return':
                            // For other reversals, we reverse the debit/credit logic
                            if ($data['contact_type'] === 'customer') {
                                if ($data['amount'] > 0) {
                                    $credit = $data['amount'];
                                } else {
                                    $debit = abs($data['amount']);
                                }
                            } else {
                                if ($data['amount'] > 0) {
                                    $debit = $data['amount'];
                                } else {
                                    $credit = abs($data['amount']);
                                }
                            }
                            break;

                        default:
                            throw new \Exception("Unknown reversal transaction type: {$data['transaction_type']}");
                    }
                } else {
                    throw new \Exception("Unknown transaction type: {$data['transaction_type']}");
                }
                break;
        }

        // Create the ledger entry with new structure
        $ledger = self::create([
            'contact_id' => $data['contact_id'],
            'contact_type' => $data['contact_type'],
            'transaction_date' => $data['transaction_date'],
            'reference_no' => $data['reference_no'],
            'transaction_type' => $data['transaction_type'],
            'debit' => $debit,
            'credit' => $credit,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? '',
            'created_by' => $data['created_by'] ?? auth()->id() ?? 1 // Auto-set to authenticated user or default to admin
        ]);

        // ✅ LOG SUCCESSFUL LEDGER CREATION
        Log::info('✅ Ledger entry created successfully', [
            'ledger_id' => $ledger->id,
            'contact_id' => $ledger->contact_id,
            'contact_type' => $ledger->contact_type,
            'reference_no' => $ledger->reference_no,
            'transaction_type' => $ledger->transaction_type,
            'debit' => $ledger->debit,
            'credit' => $ledger->credit,
            'status' => $ledger->status
        ]);

        return $ledger;
    }

    /**
     * Get balances for multiple contacts - DELEGATES to BalanceHelper (CENTRALIZED)
     * @deprecated Use BalanceHelper::getBulkCustomerBalances() directly instead
     */
    public static function getBulkBalances($contactIds, $contactType)
    {
        if ($contactType === 'customer') {
            return \App\Helpers\BalanceHelper::getBulkCustomerBalances($contactIds);
        }
        // For suppliers, return individual calculations (can be optimized later)
        return collect($contactIds)->mapWithKeys(function($id) {
            return [$id => \App\Helpers\BalanceHelper::getSupplierBalance($id)];
        });
    }    /**
     * Get summary balances by contact type
     */
    public static function getBalanceSummary($contactType = null)
    {
        $contactCondition = $contactType ? "AND contact_type = ?" : '';
        $params = $contactType ? [$contactType] : [];

        $results = DB::select("
            SELECT
                contact_type,
                COUNT(DISTINCT contact_id) as total_contacts,
                CASE
                    WHEN contact_type = 'customer' THEN
                        COALESCE(SUM(debit - credit), 0)
                    ELSE
                        COALESCE(SUM(credit - debit), 0)
                END as total_balance,
                CASE
                    WHEN contact_type = 'customer' THEN
                        COALESCE(SUM(CASE WHEN (debit - credit) > 0 THEN (debit - credit) ELSE 0 END), 0)
                    ELSE
                        COALESCE(SUM(CASE WHEN (credit - debit) > 0 THEN (credit - debit) ELSE 0 END), 0)
                END as positive_balance,
                CASE
                    WHEN contact_type = 'customer' THEN
                        COALESCE(SUM(CASE WHEN (debit - credit) < 0 THEN ABS(debit - credit) ELSE 0 END), 0)
                    ELSE
                        COALESCE(SUM(CASE WHEN (credit - debit) < 0 THEN ABS(credit - debit) ELSE 0 END), 0)
                END as negative_balance
            FROM (
                SELECT
                    contact_id,
                    contact_type,
                    SUM(debit) as debit,
                    SUM(credit) as credit
                FROM ledgers
                WHERE status = 'active' {$contactCondition}
                GROUP BY contact_id, contact_type
            ) as contact_totals
            GROUP BY contact_type
        ", $params);

        return collect($results);
    }

    /**
     * Reverse a ledger entry (mark as reversed instead of deleting)
     */
    public static function reverseEntry($entryId, $reason = '', $reversedBy = null)
    {
        $entry = self::find($entryId);
        if (!$entry) {
            throw new \Exception("Ledger entry not found");
        }

        $entry->update([
            'status' => 'reversed',
            'notes' => $entry->notes . "\n[REVERSED: " . $reason . "]",
            'created_by' => $reversedBy
        ]);

        return $entry;
    }

    /**
     * Get unified ledger view (customers and suppliers combined)
     */
    public static function getUnifiedLedger($fromDate = null, $toDate = null, $contactType = null, $includeReversed = false)
    {
        $query = self::with(['customer', 'supplier'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        // By default, exclude reversed entries unless specifically requested
        if (!$includeReversed) {
            $query->where('status', 'active');
        }

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
                'status' => $ledger->status,
                'contact_type' => $ledger->contact_type,
                'contact_name' => $contactName,
                'notes' => $ledger->notes,
                'formatted_type' => self::formatTransactionType($ledger->transaction_type),
                'current_balance' => $ledger->contact_type === 'customer'
                    ? \App\Helpers\BalanceHelper::getCustomerBalance($ledger->contact_id)
                    : \App\Helpers\BalanceHelper::getSupplierBalance($ledger->contact_id)
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
            'opening_balance_adjustment' => 'Opening Balance Adjustment',
            'discount_given' => 'Discount Given',
            default => ucfirst(str_replace('_', ' ', $type))
        };
    }

    /**
     * Get current outstanding balance for a contact
     * @deprecated Use BalanceHelper::getCustomerDue() instead
     */
    public static function getCurrentOutstanding($contact_id, $contact_type)
    {
        // Use BalanceHelper for consistent calculation
        if ($contact_type === 'customer') {
            return \App\Helpers\BalanceHelper::getCustomerDue($contact_id);
        } else {
            $currentBalance = \App\Helpers\BalanceHelper::getSupplierBalance($contact_id);
            return max(0, $currentBalance);
        }
    }

    /**
     * Get advance balance for a contact
     * @deprecated Use BalanceHelper::getCustomerAdvance() instead
     */
    public static function getAdvanceBalance($contact_id, $contact_type)
    {
        // Use BalanceHelper for consistent calculation
        if ($contact_type === 'customer') {
            return \App\Helpers\BalanceHelper::getCustomerAdvance($contact_id);
        } else {
            $currentBalance = \App\Helpers\BalanceHelper::getSupplierBalance($contact_id);
            return $currentBalance < 0 ? abs($currentBalance) : 0;
        }
    }
}
