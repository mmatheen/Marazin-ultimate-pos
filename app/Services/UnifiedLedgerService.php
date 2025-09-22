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

class UnifiedLedgerService
{
    /**
     * Record opening balance for customer or supplier
     */
    public function recordOpeningBalance($contactId, $contactType, $amount, $notes = '')
    {
        return Ledger::createEntry([
            'user_id' => $contactId,
            'contact_type' => $contactType,
            'transaction_date' => Carbon::now(),
            'reference_no' => 'OB-' . strtoupper($contactType) . '-' . $contactId,
            'transaction_type' => 'opening_balance',
            'amount' => $amount,
            'notes' => $notes ?: "Opening balance for {$contactType}"
        ]);
    }

    /**
     * Record sale transaction
     */
    public function recordSale($sale)
    {
        return Ledger::createEntry([
            'user_id' => $sale->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $sale->sales_date,
            'reference_no' => $sale->invoice_no,
            'transaction_type' => 'sale',
            'amount' => $sale->final_total,
            'notes' => "Sale invoice #{$sale->invoice_no}"
        ]);
    }

    /**
     * Record purchase transaction
     */
    public function recordPurchase($purchase)
    {
        return Ledger::createEntry([
            'user_id' => $purchase->supplier_id,
            'contact_type' => 'supplier',
            'transaction_date' => $purchase->purchase_date,
            'reference_no' => $purchase->reference_no,
            'transaction_type' => 'purchase',
            'amount' => $purchase->final_total,
            'notes' => "Purchase invoice #{$purchase->reference_no}"
        ]);
    }

    /**
     * Record sale payment
     */
    public function recordSalePayment($payment, $sale = null)
    {
        $referenceNo = $payment->reference_no ?: ($sale ? $sale->invoice_no : 'PAY-' . $payment->id);
        
        return Ledger::createEntry([
            'user_id' => $payment->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $payment->payment_date,
            'reference_no' => $referenceNo,
            'transaction_type' => 'sale_payment',
            'amount' => $payment->amount,
            'notes' => $payment->notes ?: "Payment for sale #{$referenceNo}"
        ]);
    }

    /**
     * Record purchase payment
     */
    public function recordPurchasePayment($payment, $purchase = null)
    {
        $referenceNo = $payment->reference_no ?: ($purchase ? $purchase->reference_no : 'PAY-' . $payment->id);
        
        return Ledger::createEntry([
            'user_id' => $payment->supplier_id,
            'contact_type' => 'supplier',
            'transaction_date' => $payment->payment_date,
            'reference_no' => $referenceNo,
            'transaction_type' => 'purchase_payment',
            'amount' => $payment->amount,
            'notes' => $payment->notes ?: "Payment for purchase #{$referenceNo}"
        ]);
    }

    /**
     * Record sale return
     */
    public function recordSaleReturn($saleReturn)
    {
        return Ledger::createEntry([
            'user_id' => $saleReturn->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $saleReturn->return_date,
            'reference_no' => $saleReturn->return_no,
            'transaction_type' => 'sale_return',
            'amount' => $saleReturn->return_total,
            'notes' => "Sale return #{$saleReturn->return_no}"
        ]);
    }

    /**
     * Record purchase return
     */
    public function recordPurchaseReturn($purchaseReturn)
    {
        return Ledger::createEntry([
            'user_id' => $purchaseReturn->supplier_id,
            'contact_type' => 'supplier',
            'transaction_date' => $purchaseReturn->return_date,
            'reference_no' => $purchaseReturn->return_no,
            'transaction_type' => 'purchase_return',
            'amount' => $purchaseReturn->return_total,
            'notes' => "Purchase return #{$purchaseReturn->return_no}"
        ]);
    }

    /**
     * Record return payment (money paid back to customer or received from supplier)
     */
    public function recordReturnPayment($payment, $contactType)
    {
        return Ledger::createEntry([
            'user_id' => $contactType === 'customer' ? $payment->customer_id : $payment->supplier_id,
            'contact_type' => $contactType,
            'transaction_date' => $payment->payment_date,
            'reference_no' => $payment->reference_no,
            'transaction_type' => 'return_payment',
            'amount' => $payment->amount,
            'notes' => $payment->notes ?: "Return payment"
        ]);
    }

    /**
     * Record opening balance payment
     */
    public function recordOpeningBalancePayment($payment, $contactType)
    {
        return Ledger::createEntry([
            'user_id' => $contactType === 'customer' ? $payment->customer_id : $payment->supplier_id,
            'contact_type' => $contactType,
            'transaction_date' => $payment->payment_date,
            'reference_no' => $payment->reference_no,
            'transaction_type' => 'opening_balance_payment',
            'amount' => $payment->amount,
            'notes' => $payment->notes ?: "Opening balance payment"
        ]);
    }

    /**
     * Get customer ledger with proper unified logic
     */
    public function getCustomerLedger($customerId, $startDate, $endDate, $locationId = null)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        // Get ledger transactions for the customer within the date range
        $ledgerTransactions = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->byDateRange($startDate, $endDate)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Transform ledger data for frontend display
        $transactions = $ledgerTransactions->map(function ($ledger) {
            return [
                'date' => $ledger->transaction_date->format('Y-m-d'),
                'reference_no' => $ledger->reference_no,
                'type' => Ledger::formatTransactionType($ledger->transaction_type),
                'location' => 'N/A', // Can be enhanced if location tracking is needed
                'payment_status' => $this->getPaymentStatus($ledger),
                'debit' => $ledger->debit,
                'credit' => $ledger->credit,
                'running_balance' => $ledger->balance,
                'payment_method' => 'N/A', // Can be enhanced if needed
                'notes' => $ledger->notes ?: '',
                'created_at' => $ledger->created_at,
                'transaction_type' => $ledger->transaction_type
            ];
        });

        // Calculate totals from ledger transactions
        $totalDebits = $ledgerTransactions->sum('debit');
        $totalCredits = $ledgerTransactions->sum('credit');
        
        // Get current balance from ledger (most recent entry)
        $currentBalance = Ledger::getLatestBalance($customerId, 'customer');
        
        // Get opening balance (balance before start date)
        $openingBalanceLedger = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('transaction_date', '<', $startDate)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
            
        $openingBalance = $openingBalanceLedger ? $openingBalanceLedger->balance : $customer->opening_balance;

        // Calculate outstanding and advance amounts
        $totalOutstandingDue = max(0, $currentBalance);
        $advanceAmount = $currentBalance < 0 ? abs($currentBalance) : 0;

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'mobile' => $customer->mobile_no,
                'email' => $customer->email,
                'address' => $customer->address,
                'opening_balance' => $customer->opening_balance,
                'current_balance' => $currentBalance,
            ],
            'transactions' => $transactions,
            'summary' => [
                'total_invoices' => $totalDebits,
                'total_paid' => $totalCredits,
                'total_returns' => $ledgerTransactions->where('transaction_type', 'sale_return')->sum('credit'),
                'balance_due' => $totalOutstandingDue,
                'advance_amount' => $advanceAmount,
                'effective_due' => $totalOutstandingDue,
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
     * Get supplier ledger with proper unified logic
     */
    public function getSupplierLedger($supplierId, $startDate, $endDate, $locationId = null)
    {
        $supplier = Supplier::find($supplierId);
        if (!$supplier) {
            throw new \Exception('Supplier not found');
        }

        // Get ledger transactions for the supplier within the date range
        $ledgerTransactions = Ledger::where('user_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->byDateRange($startDate, $endDate)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Transform ledger data for frontend display
        $transactions = $ledgerTransactions->map(function ($ledger) {
            return [
                'date' => $ledger->transaction_date->format('Y-m-d'),
                'reference_no' => $ledger->reference_no,
                'type' => Ledger::formatTransactionType($ledger->transaction_type),
                'location' => 'N/A', // Can be enhanced if location tracking is needed
                'payment_status' => $this->getPaymentStatus($ledger),
                'debit' => $ledger->debit,
                'credit' => $ledger->credit,
                'running_balance' => $ledger->balance,
                'payment_method' => 'N/A', // Can be enhanced if needed
                'notes' => $ledger->notes ?: '',
                'created_at' => $ledger->created_at,
                'transaction_type' => $ledger->transaction_type
            ];
        });

        // Calculate totals from ledger transactions
        $totalDebits = $ledgerTransactions->sum('debit');
        $totalCredits = $ledgerTransactions->sum('credit');
        
        // Get current balance from ledger (most recent entry)
        $currentBalance = Ledger::getLatestBalance($supplierId, 'supplier');
        
        // Get opening balance (balance before start date)
        $openingBalanceLedger = Ledger::where('user_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->where('transaction_date', '<', $startDate)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
            
        $openingBalance = $openingBalanceLedger ? $openingBalanceLedger->balance : $supplier->opening_balance;

        // Calculate outstanding and advance amounts
        $totalOutstandingDue = max(0, $currentBalance);
        $advanceAmount = $currentBalance < 0 ? abs($currentBalance) : 0;

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
                'total_purchases' => $totalCredits,
                'total_paid' => $totalDebits,
                'total_returns' => $ledgerTransactions->where('transaction_type', 'purchase_return')->sum('debit'),
                'balance_due' => $totalOutstandingDue,
                'advance_amount' => $advanceAmount,
                'effective_due' => $totalOutstandingDue,
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
            'sale_payment', 'purchase_payment', 'opening_balance_payment', 'payments' => 'Paid',
            'sale_return', 'purchase_return' => 'Returned',
            'return_payment' => 'Refunded',
            'opening_balance' => 'Due',
            default => 'N/A'
        };
    }

    /**
     * Sync existing data to ledger (migration helper)
     */
    public function syncExistingDataToLedger()
    {
        // This method can be used to migrate existing sales, purchases, payments etc. to the unified ledger
        // Implementation would depend on your specific migration needs
    }
}