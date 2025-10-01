<?php
namespace App\Models;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\Log;

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
        'current_balance',

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
     * Calculate current balance for the supplier.
     * Formula: (Purchases - Purchase_Payments) - (Returns - Return_Payments)
     * This represents: What I owe supplier - What supplier owes me
     */
    public function getCurrentBalanceAttribute()
    {
        $openingBalance = $this->opening_balance ?? 0;
        $totalPurchases = $this->purchases()->sum('final_total') ?? 0;
        $totalPurchasePayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount') ?? 0;
        $totalReturns = $this->purchaseReturns()->sum('return_total') ?? 0;
        $totalReturnPayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase_return')->sum('amount') ?? 0;
        
        // What I owe supplier
        $iOweSupplier = $totalPurchases - $totalPurchasePayments;
        
        // What supplier owes me  
        $supplierOwesMe = $totalReturns - $totalReturnPayments;
        
        // Net balance = What I owe - What they owe me
        return $openingBalance + $iOweSupplier - $supplierOwesMe;
    }

    /**
     * Calculate total due for the supplier.
     * This includes purchases minus purchase payments, minus returns minus return payments
     */
    public function getTotalDue()
    {
        $totalPurchases = \App\Models\Purchase::where('supplier_id', $this->id)->sum('final_total');
        $totalPurchasePayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount');
        $totalReturns = \App\Models\PurchaseReturn::where('supplier_id', $this->id)->sum('return_total');
        $totalReturnPayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase_return')->sum('amount');
        
        return $totalPurchases - $totalPurchasePayments - $totalReturns - $totalReturnPayments;
    }

    /**
     * Calculate total paid for the supplier (includes both purchase and return payments).
     */
    public function getTotalPaid()
    {
        $purchasePayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount');
        $returnPayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase_return')->sum('amount');
        return $purchasePayments + $returnPayments;
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
        return $this->hasMany(\App\Models\Ledger::class, 'user_id')->where('contact_type', 'supplier');
    }

    // Total Purchase Due for the supplier
    public function getTotalPurchaseDueAttribute()
    {
        $totalPurchases = $this->purchases()->sum('final_total') ?? 0;
        $totalPayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount') ?? 0;
        return $totalPurchases - $totalPayments;
    }

    // Total Return Due for the supplier
    public function getTotalReturnDueAttribute()
    {
        $totalReturns = $this->purchaseReturns()->sum('return_total') ?? 0;
        $totalReturnPayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase_return')->sum('amount') ?? 0;
        return $totalReturns - $totalReturnPayments;
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
            'current_balance' => $this->expense_balance,
            'total_transactions' => $logs->count(),
            'last_transaction_date' => $logs->max('created_at')
        ];
    }
}
