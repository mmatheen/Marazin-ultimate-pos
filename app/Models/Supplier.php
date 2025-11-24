<?php
namespace App\Models;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\Log;
use App\Models\Ledger;
use App\Helpers\BalanceHelper;

class Supplier extends Model
{
    use HasFactory,LocationTrait;
    protected $table = 'suppliers';
    protected $fillable = [
        'prefix',
        'first_name',
        'last_name',
        'mobile_no',
        'email',
        'address',
        'opening_balance',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // When a supplier is created, automatically create opening balance ledger entry
        static::created(function ($supplier) {
            if ($supplier->opening_balance && $supplier->opening_balance != 0) {
                try {
                    $unifiedLedgerService = new UnifiedLedgerService();
                    $unifiedLedgerService->recordOpeningBalance(
                        $supplier->id, 
                        'supplier', 
                        $supplier->opening_balance,
                        "Opening balance for supplier {$supplier->full_name}"
                    );
                } catch (\Exception $e) {
                    Log::error("Failed to create opening balance ledger for supplier {$supplier->id}: " . $e->getMessage());
                }
            }
        });
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get opening balance from ledger (for display in bulk payment selectors)
     * This is the actual current opening balance considering all payments
     */
    public function getOpeningBalanceFromLedger()
    {
        // Get the latest active opening balance entry (after all edits and reversals)
        $latestOpeningBalance = Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'opening_balance') // Only the actual opening balance, not adjustment entries
            ->where('status', 'active')
            ->orderBy('id', 'desc') // Use id for reliable ordering when created_at is same
            ->first();
        
        $currentOpeningBalance = $latestOpeningBalance ? $latestOpeningBalance->credit - $latestOpeningBalance->debit : 0;
        
        // Get total payments made towards opening balance
        $totalPayments = Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'opening_balance_payment')
            ->where('status', 'active')
            ->sum('credit'); // For suppliers, payments are credits to reduce what we owe
        
        // Return remaining unpaid opening balance
        return max(0, $currentOpeningBalance - $totalPayments);
    }

    /**
     * Get current total due amount (CLEAR METHOD NAME)
     * This is the actual amount owed to the supplier from ledger
     */
    public function getCurrentTotalBalance()
    {
        // Return the current total balance using BalanceHelper (SINGLE SOURCE OF TRUTH)
        // This includes opening balance + purchases - payments - returns = current due
        $currentBalance = BalanceHelper::getSupplierBalance($this->id);
        
        // Return only positive balance (amount we owe to supplier)
        return max(0, $currentBalance);
    }

    /**
     * Calculate current balance for the supplier using BalanceHelper (SINGLE SOURCE OF TRUTH)
     * @deprecated This method is deprecated. Use BalanceHelper::getSupplierBalance() directly.
     */
    public function getCurrentBalanceAttribute()
    {
        // Use BalanceHelper for consistent calculation with the unified ledger system
        return \App\Helpers\BalanceHelper::getSupplierBalance($this->id);
    }

    /**
     * Calculate total due for the supplier using BalanceHelper (SINGLE SOURCE OF TRUTH)
     * @deprecated This method is deprecated. Use BalanceHelper::getSupplierBalance() directly.
     */
    public function getTotalDue()
    {
        // Use BalanceHelper for consistency with unified ledger system
        return max(0, \App\Helpers\BalanceHelper::getSupplierBalance($this->id));
    }

    /**
     * Calculate total paid for the supplier based on ledger entries (for consistency)
     * @deprecated This method is deprecated. Calculate from ledger entries instead.
     */
    public function getTotalPaid()
    {
        // Calculate total payments from ledger entries for consistency
        return \App\Models\Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'payments')
            ->where('status', 'active')
            ->sum('debit');
    }

    public function purchases()
    {
        return $this->hasMany(\App\Models\Purchase::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(\App\Models\PurchaseReturn::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(\App\Models\Ledger::class, 'contact_id')->where('contact_type', 'supplier');
    }

    // Total Purchase Due for the supplier (using ledger for consistency)
    // @deprecated This method is deprecated. Use BalanceHelper::getSupplierBalance() instead.
    public function getTotalPurchaseDueAttribute()
    {
        // Calculate from ledger entries for consistency
        $totalPurchases = \App\Models\Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'purchase')
            ->where('status', 'active')
            ->sum('credit');
            
        $totalPayments = \App\Models\Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'payments')
            ->where('status', 'active')
            ->sum('debit');
            
        return max(0, $totalPurchases - $totalPayments);
    }

    // Total Return Due for the supplier (using ledger for consistency)
    // @deprecated This method is deprecated. Calculate returns from ledger entries instead.
    public function getTotalReturnDueAttribute()
    {
        // Calculate from ledger entries for consistency
        $totalReturns = \App\Models\Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'purchase_return')
            ->where('status', 'active')
            ->sum('debit');
            
        $totalReturnPayments = \App\Models\Ledger::where('contact_id', $this->id)
            ->where('contact_type', 'supplier')
            ->where('transaction_type', 'payments')
            ->where('status', 'active')
            ->whereRaw('notes LIKE "%return%"') // Only return-related payments
            ->sum('debit');
            
        return max(0, $totalReturns - $totalReturnPayments);
    }

    // ==================== EXPENSE BALANCE TRACKING METHODS ====================

    /**
     * Get all balance logs for this supplier
     */
    public function balanceLogs()
    {
        return $this->hasMany(SupplierBalanceLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get all expenses for this supplier
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Calculate current expense balance (opening balance + all balance log transactions)
     */
    public function getExpenseBalanceAttribute()
    {
        $openingBalance = $this->opening_balance ?? 0;
        $balanceAdjustments = $this->balanceLogs()->sum('amount') ?? 0;
        return $openingBalance + $balanceAdjustments;
    }

    /**
     * Get formatted expense balance
     */
    public function getFormattedExpenseBalanceAttribute()
    {
        $balance = $this->expense_balance;
        return 'Rs.' . number_format(abs($balance), 2) . ($balance >= 0 ? ' (Credit)' : ' (Debit)');
    }

    /**
     * Add a balance transaction for this supplier
     */
    public function addBalanceTransaction($amount, $type, $description, $expenseId = null, $paymentId = null, $metadata = null)
    {
        $currentBalance = $this->expense_balance;
        $debitCredit = $amount > 0 ? 'credit' : 'debit';
        
        return SupplierBalanceLog::create([
            'supplier_id' => $this->id,
            'expense_id' => $expenseId,
            'payment_id' => $paymentId,
            'transaction_type' => $type,
            'amount' => $amount,
            'debit_credit' => $debitCredit,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance + $amount,
            'description' => $description,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_by' => auth()->id()
        ]);
    }

    /**
     * Handle expense overpayment
     */
    public function handleOverpayment($overpaidAmount, $expenseId, $expenseNo)
    {
        if ($overpaidAmount > 0) {
            return $this->addBalanceTransaction(
                $overpaidAmount,
                'expense_overpayment',
                "Overpayment of Rs.{$overpaidAmount} for expense {$expenseNo}. Amount credited to supplier account.",
                $expenseId,
                null,
                [
                    'overpaid_amount' => $overpaidAmount,
                    'expense_no' => $expenseNo
                ]
            );
        }
        return null;
    }

    /**
     * Handle expense amount changes
     */
    public function handleExpenseAmountChange($oldAmount, $newAmount, $expenseId, $expenseNo)
    {
        $difference = $oldAmount - $newAmount;
        
        if ($difference != 0) {
            if ($difference > 0) {
                // Old amount was higher - credit supplier
                return $this->addBalanceTransaction(
                    $difference,
                    'expense_edit',
                    "Expense {$expenseNo} amount reduced from Rs.{$oldAmount} to Rs.{$newAmount}. Amount credited to supplier account.",
                    $expenseId,
                    null,
                    [
                        'old_amount' => $oldAmount,
                        'new_amount' => $newAmount,
                        'difference' => $difference,
                        'expense_no' => $expenseNo
                    ]
                );
            } else {
                // New amount is higher - debit supplier
                return $this->addBalanceTransaction(
                    $difference, // This will be negative
                    'expense_edit',
                    "Expense {$expenseNo} amount increased from Rs.{$oldAmount} to Rs.{$newAmount}. Amount debited from supplier account.",
                    $expenseId,
                    null,
                    [
                        'old_amount' => $oldAmount,
                        'new_amount' => $newAmount,
                        'difference' => abs($difference),
                        'expense_no' => $expenseNo
                    ]
                );
            }
        }
        return null;
    }

    /**
     * Handle payment edits
     */
    public function handlePaymentEdit($oldAmount, $newAmount, $paymentId, $expenseNo)
    {
        $difference = $newAmount - $oldAmount;
        
        if ($difference != 0) {
            $type = $difference > 0 ? 'Increased' : 'Decreased';
            $action = $difference > 0 ? 'debited from' : 'credited to';
            
            return $this->addBalanceTransaction(
                -$difference, // Opposite of payment change
                'payment_edit',
                "Payment for expense {$expenseNo} {$type} by Rs.{abs($difference)}. Amount {$action} supplier account.",
                null,
                $paymentId,
                [
                    'old_payment_amount' => $oldAmount,
                    'new_payment_amount' => $newAmount,
                    'difference' => abs($difference),
                    'expense_no' => $expenseNo
                ]
            );
        }
        return null;
    }

    /**
     * Handle payment deletion
     */
    public function handlePaymentDeletion($deletedAmount, $paymentId, $expenseNo)
    {
        if ($deletedAmount > 0) {
            return $this->addBalanceTransaction(
                $deletedAmount,
                'payment_delete',
                "Payment of Rs.{$deletedAmount} for expense {$expenseNo} was deleted. Amount credited to supplier account.",
                null,
                $paymentId,
                [
                    'deleted_payment_amount' => $deletedAmount,
                    'expense_no' => $expenseNo
                ]
            );
        }
        return null;
    }

    /**
     * Get recent balance transactions
     */
    public function getRecentBalanceTransactions($limit = 10)
    {
        return $this->balanceLogs()->with(['expense', 'payment', 'creator'])->limit($limit)->get();
    }

    /**
     * Get balance summary
     */
    public function getBalanceSummary()
    {
        $logs = $this->balanceLogs();
        
        return [
            'opening_balance' => $this->opening_balance ?? 0,
            'total_credits' => $logs->where('debit_credit', 'credit')->sum('amount'),
            'total_debits' => abs($logs->where('debit_credit', 'debit')->sum('amount')),
            'balance' => $this->expense_balance,
            'total_transactions' => $logs->count(),
            'last_transaction_date' => $logs->max('created_at')
        ];
    }
}
