<?php

namespace App\Models;

use App\Enums\LedgerTransactionType;
use App\Services\Ledger\LedgerPostingRuleService;
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

        // Prevent duplicate ledger entries.
        // Singleton transaction types must never have two ACTIVE rows with the same
        // (contact_id, contact_type, reference_no, transaction_type), regardless of time.
        // Other types keep a short time-window guard to prevent double-click submissions.
        $duplicateQuery = self::where('contact_id', $data['contact_id'])
            ->where('contact_type', $data['contact_type'])
            ->where('reference_no', $data['reference_no'])
            ->where('transaction_type', $data['transaction_type'])
            ->where('status', 'active');

        $singletonTransactionTypes = LedgerTransactionType::singletonTypes();

        // For singleton types, always dedupe against any active existing entry.
        if (in_array($data['transaction_type'], $singletonTransactionTypes, true)) {
            // Intentionally no created_at window.
        }
        // For payment transactions, check by reference_no + time window ONLY.
        // Bulk payment reference_no includes -PAY{id}, so this protects retries while
        // allowing legitimate separate payment lines.
        elseif (in_array($data['transaction_type'], LedgerTransactionType::paymentLikeTypes(), true)) {
            $duplicateQuery->where('created_at', '>=', Carbon::now()->subSeconds(5)); // Only within 5 seconds (prevents double-click only)
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

        $posting = app(LedgerPostingRuleService::class)->resolveDebitCredit($data);
        $debit = $posting['debit'];
        $credit = $posting['credit'];

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
            'sale_return_with_bill' => 'Sale Return With Bill',
            'sale_return_without_bill' => 'Sale Return Without Bill',
            'purchase_return' => 'Purchase Return',
            'purchase_return_reversal' => 'Purchase Return Reversal',
            'return_payment' => 'Return Payment',
            'opening_balance_payment' => 'Opening Balance Payment',
            'opening_balance_adjustment' => 'Opening Balance Adjustment',
            'sale_adjustment' => 'Sale Adjustment',
            'purchase_adjustment' => 'Purchase Adjustment',
            'payment_adjustment' => 'Payment Adjustment',
            'bounce_recovery' => 'Bounce Recovery',
            'cheque_bounce' => 'Cheque Bounce',
            'advance_payment' => 'Advance Payment',
            'advance_credit_usage' => 'Advance Credit Usage',
            'bank_charges' => 'Bank Charges',
            'penalty' => 'Penalty',
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
