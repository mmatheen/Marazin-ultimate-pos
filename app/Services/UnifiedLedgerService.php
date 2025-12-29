<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\BalanceHelper;

class UnifiedLedgerService
{

    /**
     * Record opening balance for customer or supplier
     */
    public function recordOpeningBalance($contactId, $contactType, $amount, $notes = '', $createdBy = null)
    {
        return Ledger::createEntry([
            'contact_id' => $contactId,
            'contact_type' => $contactType,
            'transaction_date' => Carbon::now('Asia/Colombo'),
            'reference_no' => 'OB-' . strtoupper($contactType) . '-' . $contactId,
            'transaction_type' => 'opening_balance',
            'amount' => $amount,
            'notes' => $notes ?: "Opening balance for {$contactType}",
            'created_by' => $createdBy ?? auth()->id() ?? 1
        ]);
    }

    /**
     * Record sale transaction
     */
    public function recordSale($sale, $createdBy = null, $customTransactionDate = null)
    {
        // ✅ FIX: Validate that sale has customer_id before proceeding
        if (empty($sale->customer_id)) {
            Log::error('RecordSale called with empty customer_id', [
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'invoice_no' => $sale->invoice_no ?? 'N/A'
            ]);
            throw new \Exception("Cannot record sale in ledger: customer_id is missing or empty. Sale ID: {$sale->id}");
        }

        // Generate a proper reference number for the sale
        $referenceNo = $sale->invoice_no ?: 'INV-' . $sale->id;

        // ✅ FIX: Use custom transaction date if provided (for updates), otherwise use original creation time
        $transactionDate = $customTransactionDate ?:
            ($sale->created_at ?
                Carbon::parse($sale->created_at)->setTimezone('Asia/Colombo') :
                Carbon::now('Asia/Colombo'));

        return Ledger::createEntry([
            'contact_id' => $sale->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $transactionDate,
            'reference_no' => $referenceNo,
            'transaction_type' => 'sale',
            'amount' => $sale->final_total,
            'notes' => "Sale invoice #{$referenceNo}",
            'created_by' => $createdBy ?? auth()->id() ?? 1
        ]);
    }

    /**
     * Record purchase transaction
     */
    public function recordPurchase($purchase, $createdBy = null)
    {
        // Generate a proper reference number for the purchase
        $referenceNo = $purchase->reference_no ?: 'PUR-' . $purchase->id;

        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $purchase->created_at ?
            Carbon::parse($purchase->created_at)->setTimezone('Asia/Colombo') :
            Carbon::now('Asia/Colombo');

        return Ledger::createEntry([
            'contact_id' => $purchase->supplier_id,
            'contact_type' => 'supplier',
            'transaction_date' => $transactionDate,
            'reference_no' => $referenceNo,

            'transaction_type' => 'purchase',
            'amount' => $purchase->final_total,
            'notes' => "Purchase invoice #{$referenceNo}",
            'created_by' => $createdBy
        ]);
    }

    /**
     * Record sale payment
     */
    public function recordSalePayment($payment, $sale = null, $createdBy = null)
    {
        // ✅ PERFORMANCE FIX: Skip ledger entries for Walk-In customers (customer_id = 1)
        // Walk-In customers don't need credit tracking, so no ledger entries needed
        if ($payment->customer_id == 1) {
            Log::info('Skipping ledger entry for Walk-In customer payment', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount
            ]);
            return null;
        }

        $referenceNo = $payment->reference_no ?: ($sale ? $sale->invoice_no : 'PAY-' . $payment->id);

        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $payment->created_at ?
            Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo') :
            Carbon::now('Asia/Colombo');

        return Ledger::createEntry([
            'contact_id' => $payment->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $transactionDate,
            'reference_no' => $referenceNo,
            'transaction_type' => 'payments', // ✅ FIXED: Standardized to 'payments'
            'amount' => $payment->amount,
            'notes' => $payment->notes ?: "Payment for sale #{$referenceNo}",
            'created_by' => $createdBy
        ]);
    }

    /**
     * Record purchase payment
     */
    public function recordPurchasePayment($payment, $purchase = null, $createdBy = null)
    {
        $referenceNo = $payment->reference_no ?: ($purchase ? $purchase->reference_no : 'PAY-' . $payment->id);

        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $payment->created_at ?
            Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo') :
            Carbon::now('Asia/Colombo');

        return Ledger::createEntry([
            'contact_id' => $payment->supplier_id,
            'contact_type' => 'supplier',
            'transaction_date' => $transactionDate,
            'reference_no' => $referenceNo,
            'transaction_type' => 'payments',
            'amount' => $payment->amount,
            'notes' => $payment->notes ?: "Payment for purchase #{$referenceNo}",
            'created_by' => $createdBy
        ]);
    }

    /**
     * Record sale return
     */
    public function recordSaleReturn($saleReturn, $createdBy = null)
    {
        // Generate a proper reference number for the sale return
        $referenceNo = $saleReturn->invoice_number ?: 'SR-' . $saleReturn->id;

        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $saleReturn->created_at ?
            Carbon::parse($saleReturn->created_at)->setTimezone('Asia/Colombo') :
            Carbon::now('Asia/Colombo');

        return Ledger::createEntry([
            'contact_id' => $saleReturn->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $transactionDate,
            'reference_no' => $referenceNo,

            'transaction_type' => 'sale_return',
            'amount' => $saleReturn->return_total,
            'notes' => "Sale return #{$referenceNo}",
            'created_by' => $createdBy
        ]);
    }

    /**
     * Record purchase return
     */
    public function recordPurchaseReturn($purchaseReturn)
    {
        // Generate a proper reference number for the purchase return
        $referenceNo = $purchaseReturn->reference_no ?: 'PR-' . $purchaseReturn->id;

        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $purchaseReturn->created_at ?
            Carbon::parse($purchaseReturn->created_at)->setTimezone('Asia/Colombo') :
            Carbon::now('Asia/Colombo');

        return Ledger::createEntry([
            'contact_id' => $purchaseReturn->supplier_id,
            'contact_type' => 'supplier',
            'transaction_date' => $transactionDate, // Use normalized date
            'reference_no' => $referenceNo,
            'transaction_type' => 'purchase_return',
            'amount' => $purchaseReturn->return_total,
            'notes' => "Purchase return #{$referenceNo}"
        ]);
    }

    /**
     * Record return payment (money paid back to customer or received from supplier)
     */
    public function recordReturnPayment($payment, $contactType)
    {
        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $payment->created_at ?
            Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo') :
            Carbon::now('Asia/Colombo');

        return Ledger::createEntry([
            'contact_id' => $contactType === 'customer' ? $payment->customer_id : $payment->supplier_id,
            'contact_type' => $contactType,
            'transaction_date' => $transactionDate, // Use normalized date
            'reference_no' => $payment->reference_no,
            'transaction_type' => 'payments',
            'amount' => $payment->amount,
            'notes' => 'Return payment - ' . ($payment->notes ?: "Payment for returned items")
        ]);
    }

    /**
     * Record cheque bounce
     */
    public function recordChequeBounce($payment, $bounceDate, $bounceReason, $createdBy = null)
    {
        $transactionDate = Carbon::parse($bounceDate)->setTimezone('Asia/Colombo');

        return Ledger::createEntry([
            'contact_id' => $payment->customer_id ?? $payment->supplier_id,
            'contact_type' => $payment->customer_id ? 'customer' : 'supplier',
            'transaction_date' => $transactionDate,
            'transaction_type' => 'cheque_bounce',
            'reference_no' => 'BOUNCE-' . $payment->cheque_number,
            'amount' => $payment->amount,
            'notes' => "Cheque bounce: {$payment->cheque_number} - {$bounceReason}",
            'created_by' => $createdBy
        ]);
    }

    /**
     * Record advance payment (customer credit / overpayment)
     */
    public function recordAdvancePayment($payment, $contactType = 'customer', $createdBy = null)
    {
        $transactionDate = $payment->created_at ?
            Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo') :
            Carbon::now('Asia/Colombo');

        $contactId = $contactType === 'customer' ? $payment->customer_id : $payment->supplier_id;

        return Ledger::createEntry([
            'contact_id' => $contactId,
            'contact_type' => $contactType,
            'transaction_date' => $transactionDate,
            'reference_no' => $payment->reference_no ?: 'ADV-' . $payment->id,
            'transaction_type' => 'advance_payment',
            'amount' => $payment->amount,
            'notes' => $payment->notes ?: "Advance payment - customer credit",
            'created_by' => $createdBy
        ]);
    }

    /**
     * Edit sale with proper ledger management - FIXED LOGIC
     * When editing a sale amount, we should only have the final amount count
     */
    public function editSale($sale, $oldFinalTotal, $editReason = null)
    {
        return DB::transaction(function () use ($sale, $oldFinalTotal, $editReason) {
            $newFinalTotal = $sale->final_total;
            $difference = $newFinalTotal - $oldFinalTotal;

            // Skip ledger entries for Walk-In customers or if amounts are identical
            if ($sale->customer_id == 1 || $difference == 0) {
                return null;
            }

            // 🔥 PERFECT REVERSAL ACCOUNTING APPROACH:
            // 1. Mark original sale entry as REVERSED (for audit trail)
            // 2. Create CREDIT entry to reverse the old DEBIT amount
            // 3. Create NEW DEBIT entry for the correct amount
            // Example: Edit 5800 → 4000: +5800(reversed), -5800(reversal), +4000(new) = +4000 ✅

            $reversalNote = '[REVERSED: Sale edited on ' . now()->format('Y-m-d H:i:s') . ']';

            // Step 1: Mark original sale entry as reversed
            Ledger::where('reference_no', $sale->invoice_no)
                ->where('contact_id', $sale->customer_id)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'sale')
                ->where('status', 'active')
                ->update([
                    'status' => 'reversed',
                    'notes' => DB::raw("CONCAT(notes, ' " . addslashes($reversalNote) . "')")
                ]);

            // Step 2: Create REVERSAL entry (CREDIT to cancel old DEBIT) - STATUS = 'reversed'
            $reversalEntry = Ledger::createEntry([
                'contact_id' => $sale->customer_id,
                'contact_type' => 'customer',
                'transaction_date' => Carbon::now('Asia/Colombo'),
                'reference_no' => $sale->invoice_no . '-REV-' . time(),
                'transaction_type' => 'sale_adjustment',
                'amount' => -$oldFinalTotal, // Negative amount creates CREDIT to reverse old DEBIT
                'status' => 'reversed', // ✅ CRITICAL FIX: Reversal entries should have status='reversed'
                'notes' => 'REVERSAL: Sale Edit - Cancel previous amount Rs.' . number_format($oldFinalTotal, 2) . ($editReason ? ' | Reason: ' . $editReason : '')
            ]);

            // ✅ CRITICAL FIX: Ensure reversal entry has correct status
            if ($reversalEntry->status !== 'reversed') {
                $reversalEntry->status = 'reversed';
                $reversalEntry->save();
            }

            // Step 3: Create NEW sale entry with correct amount
            // ✅ CRITICAL FIX: Check for existing active sale entry to prevent duplicates
            $existingNewSaleEntry = Ledger::where('contact_id', $sale->customer_id)
                ->where('contact_type', 'customer')
                ->where('reference_no', $sale->invoice_no)
                ->where('transaction_type', 'sale')
                ->where('status', 'active')
                ->where('debit', $newFinalTotal)
                ->first();

            if ($existingNewSaleEntry) {
                Log::info("Active sale entry already exists, skipping creation", [
                    'sale_id' => $sale->id,
                    'existing_entry_id' => $existingNewSaleEntry->id,
                    'amount' => $newFinalTotal
                ]);
                $newSaleEntry = $existingNewSaleEntry;
            } else {
                $newSaleEntry = Ledger::createEntry([
                    'contact_id' => $sale->customer_id,
                    'contact_type' => 'customer',
                    'transaction_date' => Carbon::now('Asia/Colombo'),
                    'reference_no' => $sale->invoice_no,
                    'transaction_type' => 'sale',
                    'amount' => $newFinalTotal,
                    'status' => 'active', // ✅ ENSURE new sale entry is active
                    'notes' => 'Sale Edit - New Amount Rs.' . number_format($newFinalTotal, 2) .
                              ($difference >= 0 ? ' | Increase: +Rs' . number_format($difference, 2) : ' | Decrease: Rs' . number_format(abs($difference), 2)) .
                              ($editReason ? ' | Reason: ' . $editReason : '')
                ]);
            }

            // ✅ CRITICAL FIX: Ensure new sale entry has correct status
            if ($newSaleEntry->status !== 'active') {
                $newSaleEntry->status = 'active';
                $newSaleEntry->save();
            }

            Log::info("Perfect reversal accounting completed for sale edit", [
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'reference_no' => $sale->invoice_no,
                'old_amount' => $oldFinalTotal,
                'new_amount' => $newFinalTotal,
                'difference' => $difference,
                'reversal_entry_id' => $reversalEntry->id,
                'new_entry_id' => $newSaleEntry->id
            ]);

            return [
                'reversal_entry' => $reversalEntry,
                'new_entry' => $newSaleEntry,
                'old_amount' => $oldFinalTotal,
                'new_amount' => $newFinalTotal,
                'amount_difference' => $difference,
                'method' => 'perfect_reversal_accounting'
            ];
        });
    }

    /**
     * Get customer balance summary for reporting
     */
    /**
     * Get customer balance summary - DELEGATES to BalanceHelper
     */
    public function getCustomerBalanceSummary($customerId)
    {
        $currentBalance = BalanceHelper::getCustomerBalance($customerId);

        return [
            'customer_id' => $customerId,
            'current_balance' => $currentBalance,
            'outstanding_amount' => BalanceHelper::getCustomerDue($customerId),
            'advance_amount' => BalanceHelper::getCustomerAdvance($customerId),
            'balance_status' => $currentBalance > 0 ? 'receivable' : ($currentBalance < 0 ? 'payable' : 'cleared'),
            'last_updated' => Carbon::now('Asia/Colombo')->format('Y-m-d H:i:s')
        ];
    }

    /**
     * @deprecated Use BalanceHelper::getCustomerBalance() instead
     */
    public function getCustomerBillWiseBalance($customerId)
    {
        return BalanceHelper::getCustomerBalance($customerId);
    }

    /**
     * @deprecated Use BalanceHelper methods instead
     */
    /**
     * Get customer floating balance (cheque bounces, bank charges, etc.)
     * TODO: Move this logic to BalanceHelper for consistency
     */
    public function getCustomerFloatingBalance($customerId)
    {
        $floatingDebits = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->whereIn('transaction_type', ['cheque_bounce', 'bank_charges'])
            ->where('status', 'active') // ✅ FIXED: Only count active entries
            ->sum('debit');

        $floatingCredits = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->whereIn('transaction_type', ['bounce_recovery', 'adjustment_credit'])
            ->where('status', 'active') // ✅ FIXED: Only count active entries
            ->sum('credit');

        return $floatingDebits - $floatingCredits;
    }

    /**
     * Get total bounced cheques amount for customer
     */
    public function getCustomerBouncedChequesAmount($customerId)
    {
        return Payment::where('customer_id', $customerId)
            ->where('payment_method', 'cheque')
            ->whereHas('chequeStatusHistory', function($query) {
                $query->whereIn('id', function($subQuery) {
                    $subQuery->select(DB::raw('MAX(id)'))
                        ->from('cheque_status_histories')
                        ->groupBy('payment_id');
                })->where('status', 'bounced');
            })
            ->sum('amount');
    }

    /**
     * Record floating balance recovery payment
     */
    public function recordFloatingBalanceRecovery($customerId, $amount, $paymentMethod = 'cash', $notes = '')
    {
        $referenceNo = 'RECOVERY-' . $customerId . '-' . time();

        return Ledger::createEntry([
            'contact_id' => $customerId,
            'contact_type' => 'customer',
            'transaction_date' => Carbon::now('Asia/Colombo'),
            'reference_no' => $referenceNo,
            'transaction_type' => 'bounce_recovery',
            'amount' => $amount,
            'notes' => $notes ?: "Recovery payment for floating balance via {$paymentMethod}"
        ]);
    }

    /**
     * Get customer ledger statement for a date range
     */
    public function getCustomerStatement($customerId, $fromDate = null, $toDate = null)
    {
        // Get opening balance - calculate dynamically from debit-credit
        $openingBalance = 0;
        if ($fromDate) {
            $openingBalance = Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_date', '<', $fromDate)
                ->where('status', 'active')
                ->sum(DB::raw('debit - credit'));
        }

        // Get transactions for the period
        $transactions = Ledger::getStatement($customerId, 'customer', $fromDate, $toDate);

        // Calculate closing balance dynamically
        $closingBalance = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->sum(DB::raw('debit - credit'));

        return [
            'customer_id' => $customerId,
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'closing_balance' => $closingBalance,
            'period' => [
                'from_date' => $fromDate,
                'to_date' => $toDate
            ]
        ];
    }

    /**
     * Record opening balance payment and update customer/supplier opening balance
     * Business Logic: When opening balance is paid, reduce the opening balance in customer/supplier table
     * CLEAN VERSION: Only creates payment entry and updates table, no reversal entries
     */
    public function recordOpeningBalancePayment($payment, $contactType)
    {
        return DB::transaction(function () use ($payment, $contactType) {
            // Use the actual creation time converted to Asia/Colombo timezone
            $transactionDate = $payment->created_at ?
                Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo') :
                Carbon::now('Asia/Colombo');

            $contactId = $contactType === 'customer' ? $payment->customer_id : $payment->supplier_id;

            // 1. Create ledger entry for the payment
            $ledgerEntry = Ledger::createEntry([
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'transaction_date' => $transactionDate,
                'reference_no' => $payment->reference_no,
                'transaction_type' => 'opening_balance_payment', // Use specific opening balance payment type
                'amount' => $payment->amount,
                'notes' => $payment->notes ?: "Opening balance payment"
            ]);

            // 2. Update opening balance in customer/supplier table (Business Logic)
            // IMPORTANT: Use updateQuietly to prevent triggering model events that create reversal entries
            if ($contactType === 'customer') {
                $customer = Customer::withoutGlobalScopes()->find($contactId);
                if ($customer) {
                    $oldOpeningBalance = $customer->opening_balance;
                    $newOpeningBalance = max(0, $customer->opening_balance - $payment->amount);

                    // Use updateQuietly to prevent triggering syncOpeningBalanceToLedger()
                    $customer->updateQuietly(['opening_balance' => $newOpeningBalance]);

                    Log::info("Customer opening balance updated via payment (no reversals)", [
                        'customer_id' => $contactId,
                        'old_opening_balance' => $oldOpeningBalance,
                        'payment_amount' => $payment->amount,
                        'new_opening_balance' => $newOpeningBalance,
                        'method' => 'updateQuietly'
                    ]);
                }
            } else {
                $supplier = Supplier::withoutGlobalScopes()->find($contactId);
                if ($supplier) {
                    $oldOpeningBalance = $supplier->opening_balance;
                    $newOpeningBalance = max(0, $supplier->opening_balance - $payment->amount);

                    // Use updateQuietly to prevent triggering model events
                    $supplier->updateQuietly(['opening_balance' => $newOpeningBalance]);

                    Log::info("Supplier opening balance updated via payment (no reversals)", [
                        'supplier_id' => $contactId,
                        'old_opening_balance' => $oldOpeningBalance,
                        'payment_amount' => $payment->amount,
                        'new_opening_balance' => $newOpeningBalance,
                        'method' => 'updateQuietly'
                    ]);
                }
            }

            return $ledgerEntry;
        });
    }

    /**
     * Record opening balance adjustment (when customer/supplier opening balance is updated)
     * Creates proper balancing entries for clean audit trail
     */
    public function recordOpeningBalanceAdjustment($contactId, $contactType, $oldAmount, $newAmount, $notes = '')
    {
        return DB::transaction(function () use ($contactId, $contactType, $oldAmount, $newAmount, $notes) {
            // Only create ledger entries if there's an actual change
            if ($oldAmount == $newAmount) {
                return null;
            }

            $referenceBase = 'OB-' . strtoupper($contactType) . '-' . $contactId;

            // 🔥 THREE-RECORD REVERSAL ACCOUNTING:
            // Step 1: Find and mark existing opening balance as 'reversed'
            $oldEntry = null;
            if ($oldAmount != 0) {
                $oldEntry = Ledger::where('contact_id', $contactId)
                    ->where('contact_type', $contactType)
                    ->whereIn('transaction_type', ['opening_balance', 'opening_balance_adjustment']) // ✅ Check both types
                    ->where('status', 'active')
                    ->orderBy('id', 'desc') // Use id for reliable ordering when created_at is same
                    ->first();

                // Mark the old entry as reversed (for audit trail)
                if ($oldEntry) {
                    $oldEntry->update([
                        'status' => 'reversed',
                        'notes' => ($oldEntry->notes ?: '') . ' [REVERSED: Opening balance edited on ' . now()->format('Y-m-d H:i:s') . ']'
                    ]);
                }
            }

            // Step 2: Create REVERSAL entry to mathematically cancel the old amount
            $reversalEntry = null;
            if ($oldAmount != 0) {
                $reversalReferenceNo = $referenceBase . '-REV-' . time();

                if ($contactType === 'customer') {
                    // For customers: Old opening was DEBIT, so create CREDIT to reverse it
                    $reversalEntry = Ledger::createEntry([
                        'contact_id' => $contactId,
                        'contact_type' => $contactType,
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => $reversalReferenceNo,
                        'transaction_type' => 'opening_balance_adjustment',
                        'amount' => -$oldAmount, // Negative amount creates CREDIT to reverse old DEBIT
                        'status' => 'reversed', // ✅ FIXED: Reversal entries should have status='reversed'
                        'notes' => 'REVERSAL: Opening Balance Edit - Cancel previous amount Rs.' . number_format($oldAmount, 2) . ($oldEntry ? ' [Cancels Entry ID: ' . $oldEntry->id . ']' : '')
                    ]);
                } else {
                    // For suppliers: Old opening was CREDIT, so create DEBIT to reverse it
                    $reversalEntry = Ledger::createEntry([
                        'contact_id' => $contactId,
                        'contact_type' => $contactType,
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => $reversalReferenceNo,
                        'transaction_type' => 'opening_balance_adjustment',
                        'amount' => $oldAmount, // Positive amount creates DEBIT to reverse old CREDIT
                        'status' => 'reversed', // ✅ FIXED: Reversal entries should have status='reversed'
                        'notes' => 'REVERSAL: Opening Balance Edit - Cancel previous amount Rs.' . number_format($oldAmount, 2) . ($oldEntry ? ' [Cancels Entry ID: ' . $oldEntry->id . ']' : '')
                    ]);
                }
            }

            // Step 3: Create NEW opening balance entry with the correct amount
            $newOpeningEntry = null;
            if ($newAmount != 0) {
                $newReferenceNo = $referenceBase;

                $newOpeningEntry = Ledger::createEntry([
                    'contact_id' => $contactId,
                    'contact_type' => $contactType,
                    'transaction_date' => Carbon::now('Asia/Colombo'),
                    'reference_no' => $newReferenceNo,
                    'transaction_type' => 'opening_balance',
                    'amount' => $newAmount,
                    'notes' => $notes ?: 'Opening Balance for ' . ucfirst($contactType) . ': ' . ($contactType === 'customer' ? Customer::find($contactId)->name ?? 'Unknown' : Supplier::find($contactId)->name ?? 'Unknown')
                ]);
            }

            // **IMPORTANT: Update the customer/supplier table to keep opening_balance field in sync**
            // This ensures UI consistency between customer info panel and ledger calculations
            if ($contactType === 'customer') {
                Customer::where('id', $contactId)->update(['opening_balance' => $newAmount]);
            } elseif ($contactType === 'supplier') {
                \App\Models\Supplier::where('id', $contactId)->update(['opening_balance' => $newAmount]);
            }

            Log::info("Three-record reversal accounting completed for opening balance", [
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'old_entry_id' => $oldEntry ? $oldEntry->id : null,
                'reversal_entry_id' => $reversalEntry ? $reversalEntry->id : null,
                'new_entry_id' => $newOpeningEntry ? $newOpeningEntry->id : null
            ]);

            return [
                'old_entry' => $oldEntry,
                'reversal_entry' => $reversalEntry,
                'new_entry' => $newOpeningEntry,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'method' => 'three_record_reversal'
            ];
        });
    }

    /**
     * Get customer ledger with proper unified logic
     * @param bool $showFullHistory - true to show all transactions including edit history
     */
    public function getCustomerLedger($customerId, $startDate, $endDate, $locationId = null, $showFullHistory = false)
    {
        // Use withoutGlobalScopes to bypass LocationScope filtering
        // This is necessary because LocationScope filters customers by user's location permissions
        // but for ledger reports, we need to access all customers regardless of location
        $customer = Customer::withoutGlobalScopes()->find($customerId);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        // Get ledger transactions for the customer within the date range
        $ledgerQuery = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->byDateRange($startDate, $endDate);

        // Apply location filtering if specified
        if ($locationId) {
            $ledgerQuery = $this->applyLocationFilter($ledgerQuery, $locationId, 'customer');
        }

        // Get all ledger transactions and calculate running balance properly based on view mode
        $ledgerQuery = DB::table('ledgers')
            ->where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('transaction_date', '>=', Carbon::parse($startDate)->startOfDay())
            ->where('transaction_date', '<=', Carbon::parse($endDate)->endOfDay())
            ->select('*')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        // Apply status filtering based on view mode
        if (!$showFullHistory) {
            // NORMAL VIEW: Only show active entries
            $ledgerQuery->where('status', 'active');
        }
        // FULL AUDIT TRAIL: Show all entries (no status filter)

        $ledgerTransactions = $ledgerQuery->get()
            ->map(function($row) {
                // Convert stdClass to object with proper properties for compatibility
                $ledger = new \stdClass();
                foreach ($row as $key => $value) {
                    $ledger->$key = $value;
                }
                return $ledger;
            });

        // Calculate running balance properly based on what entries are included
        $runningBalance = 0;

        $ledgerTransactions = $ledgerTransactions->map(function($ledger) use (&$runningBalance, $showFullHistory) {
            // Calculate running balance based on entry status and view mode
            if ($showFullHistory) {
                // Full audit trail: Include all entries in running balance
                $runningBalance += ($ledger->debit - $ledger->credit);
            } else {
                // Normal view: Only active entries contribute to running balance
                if ($ledger->status === 'active') {
                    $runningBalance += ($ledger->debit - $ledger->credit);
                }
            }

            $ledger->calculated_running_balance = $runningBalance;
            $ledger->is_reversal_entry = ($ledger->status !== 'active');

            return $ledger;
        });

        // For normal view, entries are already filtered by status in query
        // For full audit trail, show everything
        $transactionsToProcess = $ledgerTransactions;

        // Transform ledger data for frontend display
        $transactions = $transactionsToProcess->map(function ($ledger) use ($showFullHistory) {
            // Use the calculated running balance from above
            $displayBalance = (float) $ledger->calculated_running_balance;

            // Use created_at converted to Asia/Colombo timezone for display
            $displayDate = $ledger->created_at ?
                Carbon::parse($ledger->created_at)->setTimezone('Asia/Colombo')->format('d/m/Y H:i:s') :
                'N/A';

            // Get location and transaction details
            $locationName = $this->getLocationForTransaction($ledger);

            if ($showFullHistory) {
                $transactionType = $this->getDetailedTransactionType($ledger);
                $enhancedNotes = $this->getEnhancedTransactionDescription($ledger);
            } else {
                $transactionType = Ledger::formatTransactionType($ledger->transaction_type);
                $enhancedNotes = $ledger->notes ?: '';
            }

            return [
                'date' => $displayDate,
                'reference_no' => $ledger->reference_no,
                'type' => $transactionType,
                'location' => $locationName,
                'payment_status' => $this->getPaymentStatus($ledger),
                'debit' => $ledger->debit,
                'credit' => $ledger->credit,
                'running_balance' => $displayBalance, // Display balance only
                'payment_method' => $this->extractPaymentMethod($ledger),
                'notes' => $enhancedNotes,
                'others' => $enhancedNotes,
                'created_at' => $ledger->created_at,
                'transaction_type' => $ledger->transaction_type
            ];
        });

        // SIMPLIFIED TOTALS CALCULATION - Use BalanceHelper for consistency

        // Use active transactions only for totals (consistent with BalanceHelper logic)
        $activeTransactions = $ledgerTransactions->where('status', 'active');
        $totalDebits = $activeTransactions->sum('debit');
        $totalCredits = $activeTransactions->sum('credit');

        // Calculate specific totals from ledger entries
        // Total Transactions: Only sale amounts (not opening balance)
        $totalInvoices = $activeTransactions->whereIn('transaction_type', ['sale'])->sum('debit');

        // Total Paid: Only sale payments (not opening balance payments or advance payments)
        $totalPayments = $activeTransactions->whereIn('transaction_type', [
            'payment',                  // Generic payment for sales
            'payments',                 // Original payment type for sales
            'sale_payment',            // Sale-specific payment only
        ])->sum('credit');

        $totalReturns = $activeTransactions->whereIn('transaction_type', ['sale_return'])->sum('credit');

        // Get current balance using BalanceHelper (SINGLE SOURCE OF TRUTH)
        $currentBalance = BalanceHelper::getCustomerBalance($customerId);

        // Get opening balance (balance before start date)
        $openingBalanceLedger = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('transaction_date', '<', $startDate)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $openingBalance = $openingBalanceLedger ? $openingBalanceLedger->balance : $customer->opening_balance;

        // CRITICAL FIX: When showing full audit trail, the effective due should match the final running balance
        // This ensures consistency between what's displayed and the effective due amount
        if ($showFullHistory && !$transactionsToProcess->isEmpty()) {
            // Use the final running balance from the displayed transactions
            $finalTransaction = $transactionsToProcess->last();
            if ($finalTransaction && isset($finalTransaction->calculated_running_balance)) {
                // For full audit trail, the effective due should match the displayed running balance
                $auditTrailFinalBalance = (float) $finalTransaction->calculated_running_balance;

                // IMPORTANT: For advance calculation, we should still use business logic
                // Even in full audit mode, advances should be calculated correctly
                // The customer should have 0 advance since they owe Rs. 8,000

                $effectiveDue = max(0, $auditTrailFinalBalance);
                $advanceAmount = $auditTrailFinalBalance < 0 ? abs($auditTrailFinalBalance) : 0;

                // However, if the audit trail shows a positive balance (customer owes money),
                // then advance amount should be 0
                if ($auditTrailFinalBalance > 0) {
                    $advanceAmount = 0; // No advance when customer owes money
                }

                // Store both values for comparison
                $currentBalanceForDisplay = $auditTrailFinalBalance; // Use audit trail balance for display
                $auditTrailBalance = $auditTrailFinalBalance; // Audit trail balance
            } else {
                // Fallback to BalanceHelper calculation
                $effectiveDue = max(0, $currentBalance);
                $advanceAmount = $currentBalance < 0 ? abs($currentBalance) : 0;
                $currentBalanceForDisplay = $currentBalance;
                $auditTrailBalance = $currentBalance;
            }
        } else {
            // For normal view, ALWAYS use BalanceHelper for consistency
            // This ensures POS, ledger, and all other places show the same balance
            $filteredBalance = BalanceHelper::getCustomerBalance($customerId);

            $effectiveDue = max(0, $filteredBalance);
            $advanceAmount = $filteredBalance < 0 ? abs($filteredBalance) : 0;
            $currentBalanceForDisplay = $filteredBalance;
            $auditTrailBalance = $filteredBalance;
        }

        // Outstanding Due should match Effective Due for consistency in reports
        $totalOutstandingDue = $effectiveDue;

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'mobile' => $customer->mobile_no,
                'email' => $customer->email,
                'address' => $customer->address,
                'opening_balance' => $customer->opening_balance,
                'current_balance' => isset($currentBalanceForDisplay) ? $currentBalanceForDisplay : $currentBalance,
            ],
            'transactions' => $transactions,
            'summary' => [
                'total_transactions' => $totalInvoices, // Total sale transactions for display
                'total_invoices' => $totalInvoices, // Only actual sales/invoices
                'total_paid' => $totalPayments, // Only actual payments
                'total_returns' => $totalReturns, // Only actual returns
                'balance_due' => $totalOutstandingDue,
                'advance_amount' => $advanceAmount,
                'effective_due' => $effectiveDue,
                'outstanding_due' => $totalOutstandingDue,
                'opening_balance' => $openingBalance,
                // Debug information for troubleshooting
                'current_balance_from_helper' => $currentBalance, // From BalanceHelper (business logic)
                'audit_trail_final_balance' => isset($auditTrailBalance) ? $auditTrailBalance : null, // From audit trail
                'show_full_history' => $showFullHistory, // Mode indicator
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'advance_application' => [
                'available_advance' => $advanceAmount,
                'applied_to_outstanding' => 0,
                'remaining_advance' => $advanceAmount,
            ]
        ];
    }

    /**
     * Get supplier ledger with proper unified logic
     * @param bool $showFullHistory - true to show all transactions including edit history
     */
    public function getSupplierLedger($supplierId, $startDate, $endDate, $locationId = null, $showFullHistory = false)
    {
        // Use withoutGlobalScopes to bypass LocationScope filtering
        // This is necessary because LocationScope filters suppliers by user's location permissions
        // but for ledger reports, we need to access all suppliers regardless of location
        $supplier = Supplier::withoutGlobalScopes()->find($supplierId);
        if (!$supplier) {
            throw new \Exception('Supplier not found');
        }

        // Get ledger transactions with proper status filtering based on view mode
        $ledgerQuery = Ledger::where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->byDateRange($startDate, $endDate);

        // Apply location filtering if specified
        if ($locationId) {
            $ledgerQuery = $this->applyLocationFilter($ledgerQuery, $locationId, 'supplier');
        }

        // Apply status filtering based on view mode
        if (!$showFullHistory) {
            // NORMAL VIEW: Only show active entries
            $ledgerQuery->where('status', 'active');
        }
        // FULL AUDIT TRAIL: Show all entries (no status filter)

        $ledgerTransactions = $ledgerQuery
            ->orderBy('created_at', 'asc') // Order by created_at ascending (chronological order)
            ->orderBy('id', 'asc') // Secondary sort by ID for same-date transactions (chronological)
            ->get();

        // For normal view, entries are already filtered by status in query
        // For full audit trail, show everything
        $transactionsToProcess = $ledgerTransactions;

        // Calculate running balance properly based on view mode
        $runningBalance = 0;
        $transactions = $transactionsToProcess->map(function ($ledger) use ($showFullHistory, &$runningBalance) {
            // Calculate running balance based on view mode
            if ($showFullHistory) {
                // Full audit trail: Include all entries in running balance
                $runningBalance += ($ledger->credit - $ledger->debit); // For suppliers: credit increases balance
            } else {
                // Normal view: Only active entries contribute to running balance
                if ($ledger->status === 'active') {
                    $runningBalance += ($ledger->credit - $ledger->debit); // For suppliers: credit increases balance
                }
            }

            // Use created_at converted to Asia/Colombo timezone for display
            $displayDate = $ledger->created_at ?
                Carbon::parse($ledger->created_at)->setTimezone('Asia/Colombo')->format('d/m/Y H:i:s') :
                'N/A';

            // Get location and transaction details
            $locationName = $this->getLocationForTransaction($ledger);

            if ($showFullHistory) {
                $transactionType = $this->getDetailedTransactionType($ledger);
                $enhancedNotes = $this->getEnhancedTransactionDescription($ledger);
            } else {
                $transactionType = Ledger::formatTransactionType($ledger->transaction_type);
                $enhancedNotes = $ledger->notes ?: '';
            }

            return [
                'date' => $displayDate,
                'reference_no' => $ledger->reference_no,
                'type' => $transactionType,
                'location' => $locationName,
                'payment_status' => $this->getPaymentStatus($ledger),
                'debit' => $ledger->debit,
                'credit' => $ledger->credit,
                'running_balance' => $runningBalance,
                'payment_method' => $this->extractPaymentMethod($ledger),
                'notes' => $enhancedNotes,
                'others' => $enhancedNotes,
                'created_at' => $ledger->created_at,
                'transaction_type' => $ledger->transaction_type
            ];
        });

        // SIMPLIFIED TOTALS - Use active transactions only (consistent with BalanceHelper)
        $activeTransactions = $ledgerTransactions->where('status', 'active');
        $totalDebits = $activeTransactions->sum('debit');
        $totalCredits = $activeTransactions->sum('credit');

        // Calculate specific totals for account summary
        // Total Transactions: Only purchase amounts (not opening balance)
        $totalPurchases = $activeTransactions->whereIn('transaction_type', ['purchase'])->sum('credit');

        // Total Paid: Only purchase payments (not opening balance payments or advance payments)
        $totalPayments = $activeTransactions->whereIn('transaction_type', [
            'payment',                  // Generic payment for purchases
            'payments',                 // Original payment type for purchases
            'purchase_payment',        // Purchase-specific payment only
        ])->sum('debit');

        $totalReturns = $activeTransactions->whereIn('transaction_type', ['purchase_return'])->sum('debit');

        // Get current balance using BalanceHelper (SINGLE SOURCE OF TRUTH)
        $currentBalance = BalanceHelper::getSupplierBalance($supplierId);

        // SIMPLIFIED balance calculations
        $totalOutstandingDue = max(0, $currentBalance);
        $advanceAmount = $currentBalance < 0 ? abs($currentBalance) : 0;
        $effectiveDue = $totalOutstandingDue;
        $openingBalance = $supplier->opening_balance ?? 0;

        return [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->first_name . ' ' . $supplier->last_name,
                'mobile' => $supplier->mobile_no,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'opening_balance' => $supplier->opening_balance,
                'current_balance' => $currentBalance,
            ],
            'transactions' => $transactions,
            'summary' => [
                'total_transactions' => $totalPurchases, // Total purchase transactions for display
                'total_purchases' => $totalPurchases, // Only actual purchases
                'total_paid' => $totalPayments, // Only actual payments
                'total_returns' => $totalReturns, // Only actual returns
                'balance_due' => $totalOutstandingDue,
                'advance_amount' => $advanceAmount,
                'effective_due' => $effectiveDue,
                'outstanding_due' => $totalOutstandingDue,
                'opening_balance' => $openingBalance,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'advance_application' => [
                'available_advance' => $advanceAmount,
                'applied_to_outstanding' => 0,
                'remaining_advance' => $advanceAmount,
            ]
        ];
    }

    /**
     * Get unified ledger view (both customers and suppliers)
     */
    public function getUnifiedLedgerView($startDate, $endDate, $contactType = null)
    {
        return Ledger::getUnifiedLedger($startDate, $endDate, $contactType);
    }

    /**
     * Get payment status based on ledger transaction type
     */
    private function getPaymentStatus($ledger)
    {
        return match($ledger->transaction_type) {
            'sale', 'purchase' => 'Due',
            'sale_payment', 'opening_balance_payment', 'payments' => 'Paid',
            'sale_return', 'purchase_return' => 'Returned',
            'return_payment' => 'Refunded',
            'opening_balance' => 'Due',
            default => 'N/A'
        };
    }

    /**
     * Extract payment method from actual Payment record or ledger notes
     */
    private function extractPaymentMethod($ledger)
    {
        // For payment transactions, try to get actual payment method from Payment table first
        if (in_array($ledger->transaction_type, ['payments', 'sale_payment'])) {
            try {
                // Try to find the actual Payment record by reference number
                $referenceNo = $ledger->reference_no;

                // Look for payment by reference number or extract payment ID
                $payment = null;

                // Try direct reference match first
                $payment = \App\Models\Payment::where('reference_no', $referenceNo)->first();

                // If not found, try to extract payment ID from reference
                if (!$payment && strpos($referenceNo, 'PAY-') === 0) {
                    $paymentId = str_replace('PAY-', '', $referenceNo);
                    $payment = \App\Models\Payment::find($paymentId);
                }

                // If found payment record, use its payment method
                if ($payment && $payment->payment_method) {
                    return ucfirst($payment->payment_method);
                }
            } catch (\Exception $e) {
                // Fall back to notes extraction if Payment lookup fails
                Log::warning("Could not fetch payment method from Payment table: " . $e->getMessage());
            }

            // Fallback: Extract from notes if Payment record not found
            $notes = strtolower($ledger->notes ?: '');

            if (stripos($notes, 'cash') !== false) {
                return 'Cash';
            } elseif (stripos($notes, 'card') !== false || stripos($notes, 'credit') !== false || stripos($notes, 'debit') !== false) {
                return 'Card';
            } elseif (stripos($notes, 'bank') !== false || stripos($notes, 'transfer') !== false || stripos($notes, 'neft') !== false || stripos($notes, 'rtgs') !== false) {
                return 'Bank Transfer';
            } elseif (stripos($notes, 'cheque') !== false || stripos($notes, 'check') !== false) {
                return 'Cheque';
            } elseif (stripos($notes, 'upi') !== false || stripos($notes, 'gpay') !== false || stripos($notes, 'paytm') !== false || stripos($notes, 'phonepe') !== false) {
                return 'UPI';
            } elseif ($ledger->notes) {
                return 'Other';
            }
        }

        return 'N/A';
    }

    /**
     * Get location information for a ledger transaction
     */
    private function getLocationForTransaction($ledger)
    {
        try {
            // Extract invoice/reference numbers to find related records
            $referenceNo = $ledger->reference_no;

            // For opening balance transactions, get customer/supplier's location_id
            if ($ledger->transaction_type === 'opening_balance') {
                if ($ledger->contact_type === 'customer') {
                    $customer = Customer::find($ledger->contact_id);
                    if ($customer && $customer->location_id) {
                        $location = \App\Models\Location::find($customer->location_id);
                        if ($location) {
                            return $location->name;
                        }
                    }
                } elseif ($ledger->contact_type === 'supplier') {
                    $supplier = Supplier::find($ledger->contact_id);
                    if ($supplier && $supplier->location_id) {
                        $location = \App\Models\Location::find($supplier->location_id);
                        if ($location) {
                            return $location->name;
                        }
                    }
                }
                // If customer/supplier doesn't have location_id, use default location
                $defaultLocation = \App\Models\Location::first();
                if ($defaultLocation) {
                    return $defaultLocation->name;
                }
            }

            // For sale transactions, find the sale and get its location
            if (in_array($ledger->transaction_type, ['sale', 'sale_payment'])) {
                // Try multiple patterns to find the sale
                $sale = null;

                // Pattern 1: Direct invoice_no match
                $sale = Sale::where('invoice_no', $referenceNo)->with('location')->first();

                // Pattern 2: MLX prefix (MLX001, MLX002, etc.)
                if (!$sale && strpos($referenceNo, 'MLX') === 0) {
                    $saleId = str_replace('MLX', '', $referenceNo);
                    $sale = Sale::where('id', $saleId)->with('location')->first();
                }

                // Pattern 3: INV- prefix
                if (!$sale && strpos($referenceNo, 'INV-') === 0) {
                    $saleId = str_replace('INV-', '', $referenceNo);
                    $sale = Sale::where('id', $saleId)->with('location')->first();
                }

                if ($sale && $sale->location) {
                    return $sale->location->name;
                }
            }

            // For purchase transactions, find the purchase and get its location
            if (in_array($ledger->transaction_type, ['purchase', 'payments'])) {
                $purchase = null;

                // Pattern 1: Direct reference_no match
                $purchase = Purchase::where('reference_no', $referenceNo)->with('location')->first();

                // Pattern 2: PUR- prefix
                if (!$purchase && strpos($referenceNo, 'PUR-') === 0) {
                    $purchaseId = str_replace('PUR-', '', $referenceNo);
                    $purchase = Purchase::where('id', $purchaseId)->with('location')->first();
                }

                if ($purchase && $purchase->location) {
                    return $purchase->location->name;
                }
            }

            // For sale return transactions
            if (in_array($ledger->transaction_type, ['sale_return', 'sale_return_with_bill', 'sale_return_without_bill'])) {
                $saleReturn = SalesReturn::where('invoice_number', $referenceNo)
                    ->orWhere('id', str_replace('SR-', '', $referenceNo))
                    ->with(['sale.location'])
                    ->first();

                if ($saleReturn && $saleReturn->sale && $saleReturn->sale->location) {
                    return $saleReturn->sale->location->name;
                }
            }

            // For purchase return transactions
            if (in_array($ledger->transaction_type, ['purchase_return'])) {
                $purchaseReturn = PurchaseReturn::where('reference_no', $referenceNo)
                    ->orWhere('id', str_replace('PR-', '', $referenceNo))
                    ->with(['purchase.location'])
                    ->first();

                if ($purchaseReturn && $purchaseReturn->purchase && $purchaseReturn->purchase->location) {
                    return $purchaseReturn->purchase->location->name;
                }
            }

            // For payment transactions, try to find the related sale/purchase through payment table
            if (in_array($ledger->transaction_type, ['payments'])) {
                $payment = Payment::where('reference_no', $referenceNo)
                    ->orWhere('id', str_replace('PAY-', '', $referenceNo))
                    ->first();

                if ($payment) {
                    // If it's a sale payment, get location from sale
                    if ($payment->payment_type === 'sale' && $payment->reference_id) {
                        $sale = Sale::where('id', $payment->reference_id)->with('location')->first();
                        if ($sale && $sale->location) {
                            return $sale->location->name;
                        }
                    }

                    // If it's a purchase payment, get location from purchase
                    if ($payment->payment_type === 'purchase' && $payment->reference_id) {
                        $purchase = Purchase::where('id', $payment->reference_id)->with('location')->first();
                        if ($purchase && $purchase->location) {
                            return $purchase->location->name;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            // Log error if needed, but don't break the flow
            Log::warning("Error getting location for transaction {$ledger->id}: " . $e->getMessage());
        }

        // If we still can't find location, try to get default location
        try {
            $defaultLocation = \App\Models\Location::first();
            if ($defaultLocation) {
                return $defaultLocation->name;
            }
        } catch (\Exception $e) {
            Log::warning("Error getting default location: " . $e->getMessage());
        }

        return 'N/A';
    }

    /**
     * Apply location filter to ledger query by joining with related transaction tables
     */
    private function applyLocationFilter($ledgerQuery, $locationId, $contactType)
    {
        // Get reference numbers for transactions that belong to the specified location
        $saleReferences = DB::table('sales')
            ->where('location_id', $locationId)
            ->pluck('invoice_no')
            ->merge(DB::table('sales')->where('location_id', $locationId)->pluck('id')->map(function($id) {
                return "INV-{$id}";
            }))
            ->merge(DB::table('sales')->where('location_id', $locationId)->pluck('id')->map(function($id) {
                return "MLX{$id}";
            }))
            ->filter()
            ->toArray();

        // Get payment references for sales at this location
        $paymentReferences = DB::table('payments')
            ->join('sales', 'sales.id', '=', 'payments.reference_id')
            ->where('sales.location_id', $locationId)
            ->where('payments.payment_type', 'sale')
            ->pluck('payments.reference_no')
            ->filter()
            ->toArray();

        // Get sale return references for sales at this location
        $saleReturnReferences = DB::table('sales_returns')
            ->join('sales', 'sales.id', '=', 'sales_returns.sale_id')
            ->where('sales.location_id', $locationId)
            ->pluck('sales_returns.invoice_number')
            ->merge(DB::table('sales_returns')
                ->join('sales', 'sales.id', '=', 'sales_returns.sale_id')
                ->where('sales.location_id', $locationId)
                ->pluck('sales_returns.id')->map(function($id) {
                    return "SR-{$id}";
                }))
            ->filter()
            ->toArray();

        // Get return payment references (for returned items at this location)
        $returnPaymentReferences = DB::table('payments')
            ->join('sales_returns', 'sales_returns.invoice_number', '=', 'payments.reference_no')
            ->join('sales', 'sales.id', '=', 'sales_returns.sale_id')
            ->where('sales.location_id', $locationId)
            ->where('payments.payment_type', 'sale_return_with_bill')
            ->pluck('payments.reference_no')
            ->filter()
            ->toArray();

        $allReferences = array_merge($saleReferences, $paymentReferences, $saleReturnReferences, $returnPaymentReferences);

        if ($contactType === 'supplier') {
            $purchaseReferences = DB::table('purchases')
                ->where('location_id', $locationId)
                ->pluck('reference_no')
                ->merge(DB::table('purchases')->where('location_id', $locationId)->pluck('id')->map(function($id) {
                    return "PUR-{$id}";
                }))
                ->filter()
                ->toArray();

            $purchasePaymentReferences = DB::table('payments')
                ->join('purchases', 'purchases.id', '=', 'payments.reference_id')
                ->where('purchases.location_id', $locationId)
                ->where('payments.payment_type', 'purchase')
                ->pluck('payments.reference_no')
                ->filter()
                ->toArray();

            $purchaseReturnReferences = DB::table('purchase_returns')
                ->join('purchases', 'purchases.id', '=', 'purchase_returns.purchase_id')
                ->where('purchases.location_id', $locationId)
                ->pluck('purchase_returns.reference_no')
                ->merge(DB::table('purchase_returns')
                    ->join('purchases', 'purchases.id', '=', 'purchase_returns.purchase_id')
                    ->where('purchases.location_id', $locationId)
                    ->pluck('purchase_returns.id')->map(function($id) {
                        return "PR-{$id}";
                    }))
                ->filter()
                ->toArray();

            $allReferences = array_merge($allReferences, $purchaseReferences, $purchasePaymentReferences, $purchaseReturnReferences);
        }

        // Always include opening balance transactions
        $ledgerQuery->where(function ($query) use ($allReferences) {
            if (!empty($allReferences)) {
                $query->whereIn('reference_no', $allReferences);
            }
            $query->orWhere('transaction_type', 'opening_balance');
        });

        return $ledgerQuery;
    }

    /**
     * Sync existing data to ledger (migration helper)
     */
    public function syncExistingDataToLedger()
    {
        // This method can be used to migrate existing sales, purchases, payments etc. to the unified ledger
        // Implementation would depend on your specific migration needs
    }

    /**
     * Handle sale edit with customer change - properly manages ledger transfers
     */
    public function editSaleWithCustomerChange($sale, $oldCustomerId, $newCustomerId, $oldFinalTotal, $editReason = null)
    {
        // ✅ FIX: Validate customer IDs before proceeding
        if (empty($newCustomerId) || $newCustomerId === null) {
            Log::error('EditSaleWithCustomerChange called with empty newCustomerId', [
                'sale_id' => $sale->id,
                'old_customer_id' => $oldCustomerId,
                'new_customer_id' => $newCustomerId,
                'sale_customer_id' => $sale->customer_id ?? 'N/A'
            ]);
            throw new \Exception("Cannot edit sale: new customer_id is missing or empty. Sale ID: {$sale->id}");
        }

        return DB::transaction(function () use ($sale, $oldCustomerId, $newCustomerId, $oldFinalTotal, $editReason) {
            $referenceNo = $sale->invoice_no ?: 'INV-' . $sale->id;
            $newFinalTotal = $sale->final_total;

            // Skip if both customers are Walk-In (no ledger impact)
            if ($oldCustomerId == 1 && $newCustomerId == 1) {
                return null;
            }

            Log::info("Processing sale edit with customer change", [
                'sale_id' => $sale->id,
                'reference_no' => $referenceNo,
                'old_customer_id' => $oldCustomerId,
                'new_customer_id' => $newCustomerId,
                'old_amount' => $oldFinalTotal,
                'new_amount' => $newFinalTotal
            ]);

            // STEP 1: Remove/reverse entries from old customer (if not Walk-In)
            if ($oldCustomerId != 1) {
                // Find ACTIVE sale entries for old customer (don't reverse already reversed entries)
                $oldSaleEntries = Ledger::where('reference_no', $referenceNo)
                    ->where('contact_id', $oldCustomerId)
                    ->where('contact_type', 'customer')
                    ->where('transaction_type', 'sale')
                    ->where('debit', '>', 0) // Only actual sale entries
                    ->where('status', '!=', 'reversed') // Only reverse ACTIVE entries
                    ->get();

                foreach ($oldSaleEntries as $entry) {
                    // Mark original entry as reversed
                    $entry->update([
                        'status' => 'reversed',
                        'notes' => $entry->notes . ' [REVERSED: Customer changed on ' . now()->format('Y-m-d H:i:s') . ']'
                    ]);

                    // Create reversal entry for old customer
                    Ledger::createEntry([
                        'contact_id' => $oldCustomerId,
                        'contact_type' => 'customer',
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => 'EDIT-CUST-REV-' . $referenceNo,
                        'transaction_type' => 'sale',
                        'amount' => -$entry->debit, // Negative to create credit (reversal)
                        'status' => 'reversed', // ✅ CRITICAL FIX: Reversal entries should have status='reversed'
                        'notes' => 'Sale Customer Change - Removed from Customer #' . $oldCustomerId . ' (Rs' . number_format($entry->debit, 2) . ')' .
                                  ($editReason ? ' | Reason: ' . $editReason : '')
                    ]);
                }

                // Also reverse any related payment entries from old customer (ACTIVE only)
                $oldPaymentEntries = Ledger::where('reference_no', $referenceNo)
                    ->where('contact_id', $oldCustomerId)
                    ->where('contact_type', 'customer')
                    ->where('transaction_type', 'payments')
                    ->where('credit', '>', 0) // Only actual payment entries
                    ->where('status', '!=', 'reversed') // Only reverse ACTIVE entries
                    ->get();

                foreach ($oldPaymentEntries as $entry) {
                    // Mark original payment entry as reversed
                    $entry->update([
                        'status' => 'reversed',
                        'notes' => $entry->notes . ' [REVERSED: Customer changed on ' . now()->format('Y-m-d H:i:s') . ']'
                    ]);

                    // Create reversal entry for old customer payments
                    Ledger::createEntry([
                        'contact_id' => $oldCustomerId,
                        'contact_type' => 'customer',
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => 'EDIT-PAY-REV-' . $referenceNo,
                        'transaction_type' => 'payments',
                        'amount' => $entry->credit, // Positive to create debit (reversal of credit)
                        'status' => 'reversed', // ✅ CRITICAL FIX: Reversal entries should have status='reversed'
                        'notes' => 'Payment Customer Change - Removed from Customer #' . $oldCustomerId . ' (Rs' . number_format($entry->credit, 2) . ')' .
                                  ($editReason ? ' | Reason: ' . $editReason : '')
                    ]);
                }
            }

            // STEP 2: Add entries to new customer (if not Walk-In)
            if ($newCustomerId != 1) {
                // Create sale entry for new customer
                Ledger::createEntry([
                    'contact_id' => $newCustomerId,
                    'contact_type' => 'customer',
                    'transaction_date' => Carbon::now('Asia/Colombo'),
                    'reference_no' => $referenceNo,
                    'transaction_type' => 'sale',
                    'amount' => $newFinalTotal,
                    'notes' => "Sale Customer Change - Added to Customer #{$newCustomerId} (Rs{$newFinalTotal})" .
                              ($editReason ? " | Reason: {$editReason}" : '')
                ]);

                // Note: Payment entries will be recreated by the payment processing in controller
            }

            return [
                'old_customer_id' => $oldCustomerId,
                'new_customer_id' => $newCustomerId,
                'amount_transferred' => $newFinalTotal,
                'status' => 'customer_change_completed'
            ];
        });
    }

    /**
     * Update sale transaction - creates proper reversal entries for audit trail
     */
    /**
     * Reverse old sale ledger entry (Step 1 of sale edit)
     * This creates sale reversal entries but does NOT create the new sale entry
     */
    public function reverseSale($sale, $oldReferenceNo = null)
    {
        // ✅ FIX: Validate that sale has customer_id before proceeding
        if (empty($sale->customer_id)) {
            Log::error('ReverseSale called with empty customer_id', [
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'invoice_no' => $sale->invoice_no ?? 'N/A'
            ]);
            throw new \Exception("Cannot reverse sale ledger: customer_id is missing or empty. Sale ID: {$sale->id}");
        }

        $referenceNo = $oldReferenceNo ?: ($sale->invoice_no ?: 'INV-' . $sale->id);

        Log::info('ReverseSale: Starting sale reversal', [
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'reference_no' => $referenceNo
        ]);

        // Find the original sale ledger entry to reverse
        $originalEntry = Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'sale')
            ->where('contact_id', $sale->customer_id)
            ->where('debit', '>', 0)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($originalEntry) {
            Log::info('ReverseSale: Found original entry to reverse', [
                'original_entry_id' => $originalEntry->id,
                'original_amount' => $originalEntry->debit
            ]);

            // Mark original entry as reversed
            $originalEntry->update([
                'status' => 'reversed',
                'notes' => $originalEntry->notes . ' [REVERSED: Sale updated on ' . now()->format('Y-m-d H:i:s') . ']'
            ]);

            // Create reversal entry for audit trail
            $reversalEntry = Ledger::createEntry([
                'contact_id' => $sale->customer_id,
                'contact_type' => 'customer',
                'transaction_date' => now(),
                'reference_no' => $referenceNo . '-REV-' . time(),
                'transaction_type' => 'sale',
                'amount' => -$originalEntry->debit,
                'status' => 'reversed',
                'notes' => "REVERSAL: Sale Edit - Original amount Rs{$originalEntry->debit} (ID: {$originalEntry->id})",
            ]);

            Log::info('ReverseSale: Created reversal entry', [
                'reversal_entry_id' => $reversalEntry->id,
                'reversal_amount' => -$originalEntry->debit
            ]);

            return $reversalEntry;
        }

        Log::info('ReverseSale: No active entry found to reverse');
        return null;
    }

    /**
     * Record new sale ledger entry (Step 2 of sale edit - called AFTER payment reversals)
     * This should be called after all reversals are complete
     */
    public function recordNewSaleEntry($sale)
    {
        if (empty($sale->customer_id)) {
            throw new \Exception("Cannot record sale: customer_id is missing. Sale ID: {$sale->id}");
        }

        Log::info('RecordNewSaleEntry: Creating new sale entry', [
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'final_total' => $sale->final_total
        ]);

        $newSaleEntry = $this->recordSale($sale, null, Carbon::now('Asia/Colombo'));

        Log::info('RecordNewSaleEntry: Successfully created new sale entry', [
            'new_entry_id' => $newSaleEntry->id,
            'new_amount' => $newSaleEntry->debit,
            'reference_no' => $newSaleEntry->reference_no
        ]);

        return $newSaleEntry;
    }

    /**
     * Update sale (legacy method - now split into reverseSale + recordNewSaleEntry)
     * Kept for backward compatibility
     */
    public function updateSale($sale, $oldReferenceNo = null)
    {
        // For backward compatibility, call both steps
        $this->reverseSale($sale, $oldReferenceNo);
        return $this->recordNewSaleEntry($sale);
    }

    /**
     * Update purchase transaction - properly handles ledger cleanup and recreation
     */
    public function updatePurchase($purchase, $oldReferenceNo = null)
    {
        return DB::transaction(function () use ($purchase, $oldReferenceNo) {
            $referenceNo = $oldReferenceNo ?: ('PUR-' . $purchase->id);

            // ✅ ENHANCED: Handle supplier changes and payment reassignments in UnifiedLedgerService
            // First check if there are any existing ledger entries for this purchase
            $existingEntries = Ledger::where('reference_no', $referenceNo)
                ->whereIn('transaction_type', ['purchase', 'payments'])
                ->where('status', 'active')
                ->get();

            Log::info('Updating purchase ledger entries', [
                'purchase_id' => $purchase->id,
                'reference_no' => $referenceNo,
                'current_supplier_id' => $purchase->supplier_id,
                'existing_entries_count' => $existingEntries->count()
            ]);

            // ✅ Handle supplier changes by reassigning payments first
            $supplierChanged = false;
            if ($existingEntries->count() > 0) {
                $oldSupplierId = $existingEntries->first()->contact_id;
                $supplierChanged = $oldSupplierId != $purchase->supplier_id;

                if ($supplierChanged) {
                    Log::info('Supplier change detected - reassigning payments', [
                        'old_supplier_id' => $oldSupplierId,
                        'new_supplier_id' => $purchase->supplier_id,
                        'purchase_id' => $purchase->id
                    ]);

                    // Reassign all payments to new supplier
                    $payments = Payment::where('reference_id', $purchase->id)
                        ->where('payment_type', 'purchase')
                        ->where('supplier_id', $oldSupplierId)
                        ->get();

                    foreach ($payments as $payment) {
                        $payment->update(['supplier_id' => $purchase->supplier_id]);
                        Log::info('Payment reassigned to new supplier', [
                            'payment_id' => $payment->id,
                            'amount' => $payment->amount,
                            'old_supplier_id' => $oldSupplierId,
                            'new_supplier_id' => $purchase->supplier_id
                        ]);
                    }
                }
            }

            // ✅ FIXED: Use proper reversal accounting for ALL existing entries
            foreach ($existingEntries as $entry) {
                Log::info('Processing existing ledger entry', [
                    'entry_id' => $entry->id,
                    'entry_type' => $entry->transaction_type,
                    'old_supplier_id' => $entry->contact_id,
                    'new_supplier_id' => $purchase->supplier_id,
                    'supplier_changed' => $supplierChanged,
                    'amount' => $entry->credit ?: $entry->debit
                ]);

                // Mark original entry as reversed
                $entry->update([
                    'status' => 'reversed',
                    'notes' => $entry->notes . ' [REVERSED: Purchase updated on ' . now()->format('Y-m-d H:i:s') .
                              ($supplierChanged ? ' - Supplier changed' : ' - Amount/details updated') . ']'
                ]);

                // Create reversal entry to maintain audit trail
                $reversalAmount = 0;
                if ($entry->transaction_type === 'purchase') {
                    $reversalAmount = -$entry->credit; // Reverse the credit
                } else if ($entry->transaction_type === 'payments') {
                    $reversalAmount = $entry->debit; // Reverse the debit
                }

                if ($reversalAmount != 0) {
                    Ledger::createEntry([
                        'contact_id' => $entry->contact_id, // Keep original supplier for reversal
                        'contact_type' => 'supplier',
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => $referenceNo . '-REV-' . $entry->id . '-' . time(),
                        'transaction_type' => $entry->transaction_type,
                        'amount' => $reversalAmount,
                        'status' => 'reversed',
                        'notes' => 'REVERSAL: Purchase Update - ' .
                                 ($supplierChanged ? 'Supplier Change' : 'Amount Update') .
                                 ' - Cancel amount Rs.' . number_format(abs($reversalAmount), 2)
                    ]);
                }
            }

            // Step 2: Record the updated purchase with current supplier
            return $this->recordPurchase($purchase);
        });
    }

    /**
     * Update payment - properly handles ledger cleanup and recreation
     */
    public function updatePayment($payment, $oldPayment = null)
    {
        // ACCOUNTING BEST PRACTICE: Don't DELETE old entries, create REVERSAL entries
        // This maintains complete audit trail and prevents data loss

        if ($oldPayment) {
            $oldReferenceNo = $oldPayment->reference_no ?: 'PAY-' . $oldPayment->id;
            $contactType = $oldPayment->customer_id ? 'customer' : 'supplier';
            $userId = $oldPayment->customer_id ?: $oldPayment->supplier_id;

            // Instead of deleting, create REVERSAL entries
            $oldLedgerEntries = Ledger::where('reference_no', $oldReferenceNo)
                ->where('transaction_type', 'payments')
                ->where('contact_id', $userId)
                ->where('contact_type', $contactType)
                ->get();

            foreach ($oldLedgerEntries as $oldEntry) {
                // Create reversal entry - swap debit and credit amounts to reverse the effect
                $reversalEntry = new Ledger();
                $reversalEntry->transaction_date = now();
                $reversalEntry->reference_no = $oldReferenceNo . '-REV';
                $reversalEntry->transaction_type = 'payments';
                $reversalEntry->debit = $oldEntry->credit;  // Swap: original credit becomes debit
                $reversalEntry->credit = $oldEntry->debit;  // Swap: original debit becomes credit
                // Note: Balance column removed from ledgers table - calculated dynamically
                $reversalEntry->contact_type = $contactType;
                $reversalEntry->contact_id = $userId;
                $reversalEntry->save();

                Log::info("Ledger reversal entry created for payment edit", [
                    'original_reference' => $oldReferenceNo,
                    'reversal_reference' => $oldReferenceNo . '-REV',
                    'original_debit' => $oldEntry->debit,
                    'original_credit' => $oldEntry->credit,
                    'reversal_debit' => $reversalEntry->debit,
                    'reversal_credit' => $reversalEntry->credit,
                    'note' => 'Balance calculated dynamically - not stored in DB',
                    'contact_type' => $contactType,
                    'contact_id' => $userId
                ]);
            }

            // CRITICAL: Balances will be automatically recalculated by the ledger system
            // This ensures running balance is correct for both customer and supplier

            Log::info("Ledger balances will be recalculated automatically after reversal", [
                'contact_type' => $contactType,
                'contact_id' => $userId,
                'note' => 'Final balance calculated dynamically using sum of debit-credit'
            ]);
        }

        // Now record the new/updated payment
        // This will automatically trigger recalculateAllBalances again in createEntry
        if ($payment->customer_id) {
            return $this->recordSalePayment($payment);
        } elseif ($payment->supplier_id) {
            return $this->recordPurchasePayment($payment);
        }

        throw new \Exception('Payment must have either customer_id or supplier_id');
    }

    /**
     * Update sale return - properly handles ledger cleanup and recreation
     */
    public function updateSaleReturn($saleReturn, $oldReferenceNo = null)
    {
        return DB::transaction(function () use ($saleReturn, $oldReferenceNo) {
            $referenceNo = $oldReferenceNo ?: ('SR-' . $saleReturn->id);

            // ✅ FIXED: Use proper reversal accounting instead of hard delete
            // Step 1: Mark original return entry as reversed
            $originalEntry = Ledger::where('reference_no', $referenceNo)
                ->where('transaction_type', 'sale_return')
                ->where('contact_id', $saleReturn->customer_id)
                ->where('status', 'active')
                ->first();

            if ($originalEntry) {
                $originalEntry->update([
                    'status' => 'reversed',
                    'notes' => $originalEntry->notes . ' [REVERSED: Return updated on ' . now()->format('Y-m-d H:i:s') . ']'
                ]);

                // Step 2: Create REVERSAL entry to cancel old return
                Ledger::createEntry([
                    'contact_id' => $saleReturn->customer_id,
                    'contact_type' => 'customer',
                    'transaction_date' => Carbon::now('Asia/Colombo'),
                    'reference_no' => $referenceNo . '-REV-' . time(),
                    'transaction_type' => 'sale_return',
                    'amount' => -$originalEntry->credit, // Reverse the credit
                    'status' => 'reversed',
                    'notes' => 'REVERSAL: Sale Return Update - Cancel previous amount Rs.' . number_format($originalEntry->credit, 2)
                ]);
            }

            // Step 3: Record the updated return
            return $this->recordSaleReturn($saleReturn);
        });
    }

    /**
     * Update purchase return - properly handles ledger cleanup and recreation
     */
    public function updatePurchaseReturn($purchaseReturn, $oldReferenceNo = null)
    {
        return DB::transaction(function () use ($purchaseReturn, $oldReferenceNo) {
            $referenceNo = $oldReferenceNo ?: $purchaseReturn->reference_no;

            // Step 1: Find the original purchase return entry
            $originalEntry = Ledger::where('reference_no', $referenceNo)
                ->where('transaction_type', 'purchase_return')
                ->where('contact_id', $purchaseReturn->supplier_id)
                ->where('status', 'active')
                ->first();

            if ($originalEntry) {
                // Step 2: Mark original entry as reversed
                $originalEntry->update([
                    'status' => 'reversed',
                    'notes' => $originalEntry->notes . ' [REVERSED: Return updated on ' . now()->format('Y-m-d H:i:s') . ']'
                ]);

                // Step 3: Create REVERSAL entry to cancel the old return
                // The Ledger model will automatically handle the debit/credit logic for purchase_return_reversal
                // Use the original entry's debit amount (since purchase_return creates debit entries)
                $originalAmount = $originalEntry->debit ?: $originalEntry->amount;

                Ledger::createEntry([
                    'contact_id' => $purchaseReturn->supplier_id,
                    'contact_type' => 'supplier',
                    'transaction_date' => Carbon::now('Asia/Colombo'),
                    'reference_no' => $referenceNo . '-REV-' . time(),
                    'transaction_type' => 'purchase_return_reversal',
                    'amount' => $originalAmount,
                    'status' => 'reversed',
                    'notes' => 'REVERSAL: Purchase Return Update - Reversing previous return of Rs.' . number_format($originalAmount, 2)
                ]);
            }

            // Step 4: Record the updated purchase return as new entry
            return $this->recordPurchaseReturn($purchaseReturn);
        });
    }

    /**
     * Delete transaction ledger entries - for when transactions are completely removed
     */
    public function deleteSaleLedger($sale)
    {
        return DB::transaction(function () use ($sale) {
            $referenceNo = $sale->invoice_no ?: 'INV-' . $sale->id;

            // ✅ FIXED: Proper reversal accounting for deletion
            // Step 1: Find and mark original entries as reversed
            $originalEntries = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $sale->customer_id)
                ->whereIn('transaction_type', ['sale', 'payments'])
                ->where('status', 'active')
                ->get();

            $affectedRows = 0;
            $reversalNote = '[REVERSED: Sale deleted on ' . now()->format('Y-m-d H:i:s') . ']';

            foreach ($originalEntries as $entry) {
                // Step 1: Mark original as reversed
                $entry->update([
                    'status' => 'reversed',
                    'notes' => $entry->notes . ' ' . $reversalNote
                ]);

                // Step 2: Create REVERSAL entry for complete audit trail
                if ($entry->transaction_type === 'sale') {
                    // Sale was DEBIT, so create CREDIT to reverse it
                    Ledger::createEntry([
                        'contact_id' => $sale->customer_id,
                        'contact_type' => 'customer',
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => $referenceNo . '-DEL-REV-' . time(),
                        'transaction_type' => 'sale_adjustment',
                        'amount' => -$entry->debit, // Negative creates CREDIT to reverse DEBIT
                        'status' => 'reversed',
                        'notes' => 'REVERSAL: Sale Deletion - Cancel amount Rs.' . number_format($entry->debit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']'
                    ]);
                } else {
                    // Payment was CREDIT, so create DEBIT to reverse it
                    Ledger::createEntry([
                        'contact_id' => $sale->customer_id,
                        'contact_type' => 'customer',
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => $referenceNo . '-DEL-PAY-REV-' . time(),
                        'transaction_type' => 'payment_adjustment',
                        'amount' => $entry->credit, // Positive creates DEBIT to reverse CREDIT
                        'status' => 'reversed',
                        'notes' => 'REVERSAL: Sale Payment Deletion - Cancel amount Rs.' . number_format($entry->credit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']'
                    ]);
                }

                $affectedRows++;
            }

            Log::info("Complete reversal accounting completed for sale deletion", [
                'sale_id' => $sale->id,
                'reference_no' => $referenceNo,
                'customer_id' => $sale->customer_id,
                'affected_rows' => $affectedRows,
                'reversal_entries_created' => $affectedRows
            ]);

            return $affectedRows;
        });
    }

    /**
     * Delete purchase ledger entries - for when transactions are completely removed
     */
    public function deletePurchaseLedger($purchase)
    {
        return DB::transaction(function () use ($purchase) {
            $referenceNo = 'PUR-' . $purchase->id;

            // ✅ FIXED: Proper reversal accounting for deletion
            // Step 1: Find and mark original entries as reversed
            $originalEntries = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $purchase->supplier_id)
                ->whereIn('transaction_type', ['purchase', 'payments'])
                ->where('status', 'active')
                ->get();

            $affectedRows = 0;
            $reversalNote = '[REVERSED: Purchase deleted on ' . now()->format('Y-m-d H:i:s') . ']';

            foreach ($originalEntries as $entry) {
                // Step 1: Mark original as reversed
                $entry->update([
                    'status' => 'reversed',
                    'notes' => $entry->notes . ' ' . $reversalNote
                ]);

                // Step 2: Create REVERSAL entry for complete audit trail
                if ($entry->transaction_type === 'purchase') {
                    // Purchase was CREDIT, so create DEBIT to reverse it
                    Ledger::createEntry([
                        'contact_id' => $purchase->supplier_id,
                        'contact_type' => 'supplier',
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => $referenceNo . '-DEL-REV-' . time(),
                        'transaction_type' => 'purchase_adjustment',
                        'amount' => $entry->credit, // Positive creates DEBIT to reverse CREDIT
                        'status' => 'reversed',
                        'notes' => 'REVERSAL: Purchase Deletion - Cancel amount Rs.' . number_format($entry->credit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']'
                    ]);
                } else {
                    // Payment was DEBIT, so create CREDIT to reverse it
                    Ledger::createEntry([
                        'contact_id' => $purchase->supplier_id,
                        'contact_type' => 'supplier',
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => $referenceNo . '-DEL-PAY-REV-' . time(),
                        'transaction_type' => 'payment_adjustment',
                        'amount' => -$entry->debit, // Negative creates CREDIT to reverse DEBIT
                        'status' => 'reversed',
                        'notes' => 'REVERSAL: Purchase Payment Deletion - Cancel amount Rs.' . number_format($entry->debit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']'
                    ]);
                }

                $affectedRows++;
            }

            Log::info("Complete reversal accounting completed for purchase deletion", [
                'purchase_id' => $purchase->id,
                'reference_no' => $referenceNo,
                'supplier_id' => $purchase->supplier_id,
                'affected_rows' => $affectedRows,
                'reversal_entries_created' => $affectedRows
            ]);

            return $affectedRows;
        });
    }

    /**
     * Delete payment ledger entries - for when payments are removed
     */
    /**
     * Delete payment ledger entries - MARKS entries as deleted instead of removing
     * This maintains complete audit trail for accounting compliance
     * Similar to edit logic: -OLD, -REV patterns, now also -DELETED
     */
    public function deletePaymentLedger($payment)
    {
        // CRITICAL: Build the unique reference using payment ID (same as updatePayment)
        // For bulk payments, multiple payments have same base reference
        // We MUST include payment ID to identify the specific payment entries
        $baseReference = $payment->reference_no ?: 'PAY-' . $payment->id;
        $referenceNo = $baseReference . '-PAY' . $payment->id;

        $userId = $payment->customer_id ?: $payment->supplier_id;
        $contactType = $payment->customer_id ? 'customer' : 'supplier';

        Log::info("Marking payment ledger entries as deleted", [
            'payment_id' => $payment->id,
            'base_reference' => $baseReference,
            'unique_reference' => $referenceNo,
            'contact_id' => $userId,
            'contact_type' => $contactType
        ]);

        // ACCOUNTING BEST PRACTICE: Don't delete ledger entries, MARK them as deleted
        // This maintains complete audit trail - all transactions remain visible in logs
        // Similar to how we mark -OLD entries during edits

        // STEP 1: Find ALL related entries to mark as deleted
        $entriesToMark = Ledger::where('contact_id', $userId)
            ->where('contact_type', $contactType)
            ->where('transaction_type', 'payments')
            ->where(function($query) use ($referenceNo) {
                $query->where('reference_no', $referenceNo)              // Original entry
                      ->orWhere('reference_no', $referenceNo . '-REV')   // Reversal entry
                      ->orWhere('reference_no', $referenceNo . '-OLD')   // Old entry (before edit)
                      ->orWhere('reference_no', $referenceNo . '-OLD-REV'); // Old reversal entry
            })
            ->get();

        $markedCount = 0;

        // STEP 2: Mark each entry with -DELETED suffix and update notes
        foreach ($entriesToMark as $entry) {
            // Skip if already marked as deleted
            if (strpos($entry->reference_no, '-DELETED') !== false) {
                continue;
            }

            $oldReference = $entry->reference_no;
            $entry->reference_no = $oldReference . '-DELETED';
            $entry->status = 'reversed'; // Mark as reversed in addition to reference change
            $entry->notes = ($entry->notes ? $entry->notes . ' | ' : '') .
                '[DELETED] Payment deleted on ' . now()->format('Y-m-d H:i:s') .
                ' - Original ref: ' . $oldReference;
            $entry->save();

            $markedCount++;

            Log::info("Ledger entry marked as deleted", [
                'entry_id' => $entry->id,
                'old_reference' => $oldReference,
                'new_reference' => $entry->reference_no,
                'debit' => $entry->debit,
                'credit' => $entry->credit
            ]);
        }

        // STEP 3: Create REVERSAL entries to cancel out the deleted payment's effect
        // This maintains accurate running balance without actually deleting records
        foreach ($entriesToMark as $entry) {
            // Skip reversal entries themselves
            if (strpos($entry->reference_no, '-REV') !== false &&
                strpos($entry->reference_no, '-DELETED') === false) {
                continue;
            }

            // Skip if this was an -OLD entry (already reversed during edit)
            if (strpos($entry->reference_no, '-OLD') !== false &&
                strpos($entry->reference_no, '-DELETED') === false) {
                continue;
            }

            // Create reversal entry ONLY for the main payment entry
            if (strpos($entry->reference_no, '-DELETED') !== false &&
                strpos($entry->reference_no, '-OLD') === false &&
                strpos($entry->reference_no, '-REV-DELETED') === false) {

                $reversalEntry = new Ledger();
                $reversalEntry->transaction_date = now();
                $reversalEntry->reference_no = $referenceNo . '-DEL-REV';
                $reversalEntry->transaction_type = 'payments';
                // SWAP: Original credit becomes reversal debit, original debit becomes reversal credit
                $reversalEntry->debit = $entry->credit;
                $reversalEntry->credit = $entry->debit;
                $reversalEntry->balance = 0; // Will be recalculated
                $reversalEntry->contact_type = $contactType;
                $reversalEntry->contact_id = $userId;
                $reversalEntry->notes = 'Reversal of deleted payment - Amount: Rs ' .
                    number_format($entry->credit > 0 ? $entry->credit : $entry->debit, 2) .
                    ' | Original ref: ' . str_replace('-DELETED', '', $entry->reference_no);
                $reversalEntry->save();

                Log::info("Delete reversal entry created", [
                    'reversal_id' => $reversalEntry->id,
                    'reversal_reference' => $reversalEntry->reference_no,
                    'original_debit' => $entry->debit,
                    'original_credit' => $entry->credit,
                    'reversal_debit' => $reversalEntry->debit,
                    'reversal_credit' => $reversalEntry->credit
                ]);
            }
        }

        Log::info("Payment ledger entries marked as deleted", [
            'marked_count' => $markedCount,
            'entries_found' => $entriesToMark->count(),
            'reference_patterns_checked' => [
                'original' => $referenceNo,
                'reversal' => $referenceNo . '-REV',
                'old' => $referenceNo . '-OLD',
                'old_reversal' => $referenceNo . '-OLD-REV'
            ]
        ]);

        // STEP 4: Balances will be automatically recalculated by the ledger system

        Log::info("Balances will be recalculated automatically after payment deletion", [
            'contact_id' => $userId,
            'contact_type' => $contactType,
            'final_balance' => $contactType === 'customer'
                ? BalanceHelper::getCustomerBalance($userId)
                : BalanceHelper::getSupplierBalance($userId)
        ]);

        return $markedCount;
    }

    /**
     * Delete return ledger entries - for when returns are removed
     */
    public function deleteReturnLedger($return, $type = 'sale_return')
    {
        return DB::transaction(function () use ($return, $type) {
            $referenceNo = $type === 'sale_return' ? 'SR-' . $return->id : 'PR-' . $return->id;
            $userId = $type === 'sale_return' ? $return->customer_id : $return->supplier_id;
            $contactType = $type === 'sale_return' ? 'customer' : 'supplier';

            // ✅ FIXED: Proper reversal accounting for deletion
            // Step 1: Find and mark original entries as reversed
            $originalEntries = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $userId)
                ->where('transaction_type', $type)
                ->where('status', 'active')
                ->get();

            $affectedRows = 0;
            $reversalNote = '[REVERSED: Return deleted on ' . now()->format('Y-m-d H:i:s') . ']';

            foreach ($originalEntries as $entry) {
                // Step 1: Mark original as reversed
                $entry->update([
                    'status' => 'reversed',
                    'notes' => $entry->notes . ' ' . $reversalNote
                ]);

                // Step 2: Create REVERSAL entry for complete audit trail
                if ($type === 'sale_return') {
                    // Sale return was CREDIT, so create DEBIT to reverse it
                    Ledger::createEntry([
                        'contact_id' => $userId,
                        'contact_type' => $contactType,
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => $referenceNo . '-DEL-REV-' . time(),
                        'transaction_type' => 'sale_return',
                        'amount' => $entry->credit, // Positive creates DEBIT to reverse CREDIT
                        'status' => 'reversed',
                        'notes' => 'REVERSAL: Sale Return Deletion - Cancel amount Rs.' . number_format($entry->credit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']'
                    ]);
                } else {
                    // Purchase return was DEBIT, so create CREDIT to reverse it
                    Ledger::createEntry([
                        'contact_id' => $userId,
                        'contact_type' => $contactType,
                        'transaction_date' => Carbon::now('Asia/Colombo'),
                        'reference_no' => $referenceNo . '-DEL-REV-' . time(),
                        'transaction_type' => 'purchase_return',
                        'amount' => -$entry->debit, // Negative creates CREDIT to reverse DEBIT
                        'status' => 'reversed',
                        'notes' => 'REVERSAL: Purchase Return Deletion - Cancel amount Rs.' . number_format($entry->debit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']'
                    ]);
                }

                $affectedRows++;
            }

            Log::info("Complete reversal accounting completed for return deletion", [
                'return_id' => $return->id,
                'reference_no' => $referenceNo,
                'contact_id' => $userId,
                'contact_type' => $contactType,
                'return_type' => $type,
                'affected_rows' => $affectedRows,
                'reversal_entries_created' => $affectedRows
            ]);

            return $affectedRows;
        });
    }

    /**
     * Get supplier summary
     *
     * @param int $supplierId
     * @return array
     */
    public function getSupplierSummary(int $supplierId): array
    {
        $supplier = Supplier::find($supplierId);

        if (!$supplier) {
            throw new \Exception("Supplier not found");
        }

        // Use the existing getSupplierLedger method to get all entries
        $ledgerData = $this->getSupplierLedger($supplierId, null, null);
        $ledgerEntries = collect($ledgerData['transactions']);

        $summary = [
            'supplier' => $supplier,
            'opening_balance' => $supplier->opening_balance ?? 0,
            'total_purchases' => $ledgerEntries->where('transaction_type', 'purchase')->sum('debit'),
            'total_returns' => $ledgerEntries->where('transaction_type', 'purchase_return')->sum('credit'),
            'total_payments' => $ledgerEntries->where('transaction_type', 'payments')->sum('credit'),
            'current_balance' => BalanceHelper::getSupplierBalance($supplier->id) ?? 0,
            'total_transactions' => $ledgerEntries->count()
        ];

        return $summary;
    }

    /**
     * Recalculate all balances for a supplier from scratch
     *
     * @param int $supplierId
     * @return void
     */
    public function recalculateSupplierBalance(int $supplierId): void
    {
        $entries = Ledger::where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;

        foreach ($entries as $entry) {
            $runningBalance += $entry->debit - $entry->credit;
            // Note: Balance column removed from ledgers table - calculated dynamically
            // $entry->update(['balance' => $runningBalance]);
        }

        // Balance is automatically calculated through ledger system
    }

    /**
     * Validate ledger consistency for a supplier
     *
     * @param int $supplierId
     * @return array
     */
    public function validateSupplierLedger(int $supplierId): array
    {
        $entries = Ledger::where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $errors = [];
        $runningBalance = 0;

        foreach ($entries as $entry) {
            $expectedBalance = $runningBalance + $entry->debit - $entry->credit;

            if (abs($expectedBalance - $entry->balance) > 0.01) {
                $errors[] = [
                    'id' => $entry->id,
                    'reference_no' => $entry->reference_no,
                    'expected_balance' => $expectedBalance,
                    'actual_balance' => $entry->balance,
                    'difference' => $entry->balance - $expectedBalance
                ];
            }

            $runningBalance = $expectedBalance;
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'final_balance' => $runningBalance
        ];
    }

    /**
     * Delete ledger entries for a specific reference and contact
     *
     * @param string $referenceNo
     * @param int $contactId
     * @param string $contactType
     * @return void
     */
    public function deleteLedgerEntries(string $referenceNo, int $contactId, string $contactType): void
    {
        Ledger::where('reference_no', $referenceNo)
            ->where('contact_id', $contactId)
            ->where('contact_type', $contactType)
            ->delete();
    }

    /**
     * Get current balance for a customer or supplier
     *
     * @param string $contactType ('customer' or 'supplier')
     * @param int $userId
     * @return float
     */

    /**
     * Get detailed transaction type with audit trail information
     */
    private function getDetailedTransactionType($ledger)
    {
        $refNo = $ledger->reference_no;
        $type = $ledger->transaction_type;

        // Check for audit trail markers
        if (strpos($refNo, '-REV') !== false) {
            return 'Reversal Entry';
        } elseif (strpos($refNo, '-OLD-REV') !== false) {
            return 'Old Entry Reversal';
        } elseif (strpos($refNo, '-OLD') !== false) {
            return 'Original Entry (Before Edit)';
        } elseif (strpos($refNo, '-DELETED') !== false) {
            return 'Deleted Entry';
        } elseif (strpos($refNo, '-DEL-REV') !== false) {
            return 'Deletion Reversal';
        } elseif (strpos($ledger->notes ?: '', 'REVERSAL:') === 0) {
            return 'System Reversal';
        } else {
            return Ledger::formatTransactionType($type) . ' (Current)';
        }
    }

    /**
     * Get enhanced transaction description for audit trail
     */
    private function getEnhancedTransactionDescription($ledger)
    {
        $notes = $ledger->notes ?: '';
        $refNo = $ledger->reference_no;

        // Handle reversal entries
        if (strpos($notes, 'REVERSAL:') === 0) {
            return $notes; // Already formatted reversal message
        }

        // Handle audit trail entries
        if (strpos($refNo, '-REV') !== false) {
            $originalRef = str_replace(['-REV', '-OLD-REV', '-DEL-REV'], '', $refNo);
            return "Reversal entry for payment #{$originalRef}. " . $notes;
        } elseif (strpos($refNo, '-OLD') !== false) {
            $originalRef = str_replace('-OLD', '', $refNo);
            return "Original entry before edit for payment #{$originalRef}. " . $notes;
        } elseif (strpos($refNo, '-DELETED') !== false) {
            $originalRef = str_replace('-DELETED', '', $refNo);
            return "Entry marked as deleted for payment #{$originalRef}. " . $notes;
        } else {
            // Current/active entry
            return $notes ?: "Current active entry for {$refNo}";
        }
    }

    /**
     * 🔥 PERFECT REVERSAL ACCOUNTING: Payment Edit
     * Creates reversal entry and new entry for payment edits
     */
    public function editPayment($payment, $oldAmount, $newAmount, $editReason = '', $editedBy = null)
    {
        return DB::transaction(function () use ($payment, $oldAmount, $newAmount, $editReason, $editedBy) {
            // Skip if amounts are identical
            if ($oldAmount == $newAmount) {
                return null;
            }

            $referenceNo = $payment->reference_no ?: 'PAY-' . $payment->id;
            $contactType = $payment->customer_id ? 'customer' : 'supplier';
            $contactId = $payment->customer_id ?: $payment->supplier_id;

            // Step 1: Mark original payment entry as REVERSED
            $originalEntry = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $contactId)
                ->where('contact_type', $contactType)
                ->where('transaction_type', 'payments')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($originalEntry) {
                $originalEntry->update([
                    'status' => 'reversed',
                    'notes' => $originalEntry->notes . ' [REVERSED: Payment edited on ' . now()->format('Y-m-d H:i:s') . ']'
                ]);
            }

            // Step 2: Create REVERSAL entry to cancel old payment
            $reversalEntry = Ledger::createEntry([
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'transaction_date' => Carbon::now('Asia/Colombo'),
                'reference_no' => $referenceNo . '-REV-' . time(),
                'transaction_type' => 'payment_adjustment',
                'amount' => $oldAmount, // Positive amount creates DEBIT to reverse old CREDIT
                'status' => 'reversed', // ✅ FIXED: Reversal entries should have status='reversed'
                'notes' => 'REVERSAL: Payment Edit - Cancel previous amount Rs.' . number_format($oldAmount, 2) . ($editReason ? ' | Reason: ' . $editReason : ''),
                'created_by' => $editedBy
            ]);

            // Step 3: Create NEW payment entry with correct amount
            $newPaymentEntry = Ledger::createEntry([
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'transaction_date' => Carbon::now('Asia/Colombo'),
                'reference_no' => $referenceNo,
                'transaction_type' => 'payments',
                'amount' => $newAmount,
                'notes' => 'Payment Edit - New Amount Rs.' . number_format($newAmount, 2) .
                          ($editReason ? ' | Reason: ' . $editReason : ''),
                'created_by' => $editedBy
            ]);

            Log::info("Perfect reversal accounting completed for payment edit", [
                'payment_id' => $payment->id,
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'reference_no' => $referenceNo,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'reversal_entry_id' => $reversalEntry->id,
                'new_entry_id' => $newPaymentEntry->id
            ]);

            return [
                'reversal_entry' => $reversalEntry,
                'new_entry' => $newPaymentEntry,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'method' => 'perfect_reversal_accounting'
            ];
        });
    }

    /**
     * 🔥 PERFECT REVERSAL ACCOUNTING: Payment Delete
     * Creates reversal entry for payment deletions
     */
    public function deletePayment($payment, $deleteReason = '', $deletedBy = null)
    {
        return DB::transaction(function () use ($payment, $deleteReason, $deletedBy) {
            $referenceNo = $payment->reference_no ?: 'PAY-' . $payment->id;
            $contactType = $payment->customer_id ? 'customer' : 'supplier';
            $contactId = $payment->customer_id ?: $payment->supplier_id;

            // Check if this is a return payment
            $isReturnPayment = $payment->payment_type === 'purchase_return' ||
                               $payment->payment_type === 'sale_return_with_bill' ||
                               $payment->payment_type === 'sale_return_without_bill' ||
                               (isset($payment->notes) && strpos(strtolower($payment->notes), 'return') !== false);

            // Step 1: Mark original payment entry as REVERSED
            $originalEntry = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $contactId)
                ->where('contact_type', $contactType)
                ->where('transaction_type', 'payments')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($originalEntry) {
                $originalEntry->update([
                    'status' => 'reversed',
                    'notes' => $originalEntry->notes . ' [REVERSED: Payment deleted on ' . now()->format('Y-m-d H:i:s') . ']'
                ]);

                // Step 2: Create REVERSAL entry to cancel the deleted payment
                $reversalNotes = 'REVERSAL: Payment Deleted - Cancel amount Rs.' . number_format($payment->amount, 2);
                if ($isReturnPayment) {
                    $reversalNotes .= ' (Return payment reversal)';
                }
                if ($deleteReason) {
                    $reversalNotes .= ' | Reason: ' . $deleteReason;
                }

                $reversalEntry = Ledger::createEntry([
                    'contact_id' => $contactId,
                    'contact_type' => $contactType,
                    'transaction_date' => Carbon::now('Asia/Colombo'),
                    'reference_no' => $referenceNo . '-DEL-' . time(),
                    'transaction_type' => 'payment_adjustment',
                    'amount' => $payment->amount,
                    'status' => 'reversed', // ✅ FIX: Payment adjustments should be marked as 'reversed' status
                    'notes' => $reversalNotes,
                    'created_by' => $deletedBy
                ]);

                Log::info("Perfect reversal accounting completed for payment deletion", [
                    'payment_id' => $payment->id,
                    'contact_id' => $contactId,
                    'contact_type' => $contactType,
                    'reference_no' => $referenceNo,
                    'amount' => $payment->amount,
                    'reversal_entry_id' => $reversalEntry->id
                ]);

                return [
                    'reversal_entry' => $reversalEntry,
                    'deleted_amount' => $payment->amount,
                    'method' => 'perfect_reversal_accounting'
                ];
            }

            return null;
        });
    }

    /**
     * Reverse a payment entry by marking related ledger entries as reversed
     */
    private function reversePaymentEntry($payment, $reason, $reversedBy = null)
    {
        // Find the ledger entry for this payment using reference_no pattern
        $referenceNo = 'PAY-' . $payment->id;
        $ledgerEntry = Ledger::where('reference_no', $referenceNo)
            ->where('contact_id', $payment->customer_id ?: $payment->supplier_id)
            ->where('status', 'active')
            ->first();

        if ($ledgerEntry) {
            return Ledger::reverseEntry($ledgerEntry->id, $reason, $reversedBy);
        }

        return null;
    }

    /**
     * Handle sale edit - reverse old entries and create new ones
     */
    public function editSaleTransaction($sale, $oldFinalTotal, $newFinalTotal, $editReason = '', $editedBy = null)
    {
        // Reverse the original sale entry using reference_no pattern
        $referenceNo = 'SALE-' . $sale->id;
        $ledgerEntry = Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'sale')
            ->where('contact_id', $sale->customer_id)
            ->where('status', 'active')
            ->first();

        if ($ledgerEntry) {
            Ledger::reverseEntry($ledgerEntry->id, "Sale edited: " . $editReason, $editedBy);
        }

        // Create new sale entry
        return $this->recordSale($sale, $editedBy);
    }

    /**
     * Get balances for multiple customers/suppliers - DELEGATES to BalanceHelper
     */
    public function getBulkBalances($contactIds, $contactType)
    {
        return BalanceHelper::getBulkBalances($contactIds, $contactType);
    }

    /**
     * Get balance summary by contact type - DELEGATES to BalanceHelper
     */
    public function getBalanceSummary($contactType = null)
    {
        // For now, return empty collection. Implement in BalanceHelper if needed.
        return collect();
    }

    /**
     * Get customer statement with running balances - DELEGATES to Ledger
     */
    public function getCustomerStatementWithRunningBalance($customerId, $fromDate = null, $toDate = null)
    {
        return Ledger::getStatement($customerId, 'customer', $fromDate, $toDate);
    }

    /**
     * Get supplier statement with running balances - DELEGATES to Ledger
     */
    public function getSupplierStatementWithRunningBalance($supplierId, $fromDate = null, $toDate = null)
    {
        return Ledger::getStatement($supplierId, 'supplier', $fromDate, $toDate);
    }

    /**
     * Get all customers with their current balances (bulk operation)
     */
    public function getAllCustomersWithBalances()
    {
        $customers = \App\Models\Customer::select('id', 'first_name', 'last_name', 'mobile_no')->get();
        $customerIds = $customers->pluck('id')->toArray();
        $balances = BalanceHelper::getBulkCustomerBalances($customerIds);

        return $customers->map(function ($customer) use ($balances) {
            $balance = $balances->get($customer->id, 0);
            return [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'mobile_no' => $customer->mobile_no,
                'current_balance' => $balance,
                'balance_type' => $balance > 0 ? 'receivable' : 'payable'
            ];
        });
    }

    /**
     * Get all suppliers with their current balances - USES BalanceHelper
     */
    public function getAllSuppliersWithBalances()
    {
        $suppliers = \App\Models\Supplier::select('id', 'name', 'phone')->get();

        return $suppliers->map(function ($supplier) {
            $balance = BalanceHelper::getSupplierBalance($supplier->id);
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'current_balance' => $balance,
                'balance_type' => $balance > 0 ? 'payable' : 'receivable'
            ];
        });
    }

    /**
     * ===================================================================
     * 🎯 CENTRALIZED OPENING BALANCE HANDLER
     * ===================================================================
     *
     * Handles all opening balance operations for customers/suppliers
     * This centralizes logic that was scattered in Customer model
     */
    public function handleCustomerOpeningBalance($customerId, $newOpeningBalance)
    {
        return DB::transaction(function () use ($customerId, $newOpeningBalance) {

            // Find existing opening balance entry (if any)
            $existingEntry = Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'opening_balance')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingEntry) {
                // Customer already has opening balance - need to adjust
                $oldAmount = $existingEntry->amount ?? ($existingEntry->debit - $existingEntry->credit);

                if ($oldAmount != $newOpeningBalance) {
                    // Use proper reversal accounting for adjustment
                    return $this->recordOpeningBalanceAdjustment(
                        $customerId,
                        'customer',
                        $oldAmount,
                        $newOpeningBalance,
                        'Opening Balance Update via Customer Model'
                    );
                }
            } else {
                // No existing entry - create new opening balance
                if ($newOpeningBalance != 0) {
                    return $this->recordOpeningBalance(
                        $customerId,
                        'customer',
                        $newOpeningBalance,
                        'Opening Balance for Customer ID: ' . $customerId
                    );
                }
            }

            return null; // No changes needed
        });
    }

    /**
     * Update purchase payment in ledger when payment amount changes
     */
    public function updatePurchasePayment($payment, $purchase)
    {
        return DB::transaction(function () use ($payment, $purchase) {
            $referenceNo = $payment->reference_no ?: 'PAY-' . $payment->id;

            Log::info('Updating purchase payment in ledger', [
                'payment_id' => $payment->id,
                'purchase_id' => $purchase->id,
                'reference_no' => $referenceNo,
                'amount' => $payment->amount,
                'supplier_id' => $purchase->supplier_id
            ]);

            // Mark existing payment ledger entries as reversed
            $existingEntries = Ledger::where('reference_no', $referenceNo)
                ->where('transaction_type', 'payments')
                ->where('contact_id', $purchase->supplier_id)
                ->where('status', 'active')
                ->get();

            foreach ($existingEntries as $entry) {
                $entry->update([
                    'status' => 'reversed',
                    'notes' => ($entry->notes ?: 'Purchase payment') . ' [REVERSED: Payment updated on ' . now()->format('Y-m-d H:i:s') . ']'
                ]);

                // Create reversal entry
                Ledger::createEntry([
                    'contact_id' => $purchase->supplier_id,
                    'contact_type' => 'supplier',
                    'transaction_date' => Carbon::now('Asia/Colombo'),
                    'reference_no' => $referenceNo . '-REV-' . time(),
                    'transaction_type' => 'payments',
                    'amount' => $entry->debit ? -$entry->debit : $entry->credit,
                    'status' => 'reversed',
                    'notes' => 'REVERSAL: Payment Update - Cancel amount Rs.' . number_format($entry->debit ?: $entry->credit, 2)
                ]);
            }

            // Create new payment entry with updated amount
            return $this->recordPurchasePayment($payment, $purchase);
        });
    }
}
