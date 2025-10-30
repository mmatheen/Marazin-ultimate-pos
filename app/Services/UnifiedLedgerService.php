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
            'transaction_date' => Carbon::now('Asia/Colombo'), // Use current time in Asia/Colombo
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
        // Generate a proper reference number for the sale
        $referenceNo = $sale->invoice_no ?: 'INV-' . $sale->id;
        
        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $sale->created_at ? 
            Carbon::parse($sale->created_at)->setTimezone('Asia/Colombo') : 
            Carbon::now('Asia/Colombo');
        
        return Ledger::createEntry([
            'user_id' => $sale->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $transactionDate, // Use normalized date
            'reference_no' => $referenceNo,
            'transaction_type' => 'sale',
            'amount' => $sale->final_total,
            'notes' => "Sale invoice #{$referenceNo}"
        ]);
    }

    /**
     * Record purchase transaction
     */
    public function recordPurchase($purchase)
    {
        // Generate a proper reference number for the purchase
        $referenceNo = $purchase->reference_no ?: 'PUR-' . $purchase->id;
        
        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $purchase->created_at ? 
            Carbon::parse($purchase->created_at)->setTimezone('Asia/Colombo') : 
            Carbon::now('Asia/Colombo');
        
        return Ledger::createEntry([
            'user_id' => $purchase->supplier_id,
            'contact_type' => 'supplier',
            'transaction_date' => $transactionDate, // Use normalized date
            'reference_no' => $referenceNo,
            'transaction_type' => 'purchase',
            'amount' => $purchase->final_total,
            'notes' => "Purchase invoice #{$referenceNo}"
        ]);
    }

    /**
     * Record sale payment
     */
    public function recordSalePayment($payment, $sale = null)
    {
        $referenceNo = $payment->reference_no ?: ($sale ? $sale->invoice_no : 'PAY-' . $payment->id);
        
        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $payment->created_at ? 
            Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo') : 
            Carbon::now('Asia/Colombo');
        
        return Ledger::createEntry([
            'user_id' => $payment->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $transactionDate, // Use normalized date
            'reference_no' => $referenceNo,
            'transaction_type' => 'payments',
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
        
        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $payment->created_at ? 
            Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo') : 
            Carbon::now('Asia/Colombo');
        
        return Ledger::createEntry([
            'user_id' => $payment->supplier_id,
            'contact_type' => 'supplier',
            'transaction_date' => $transactionDate, // Use normalized date
            'reference_no' => $referenceNo,
            'transaction_type' => 'payments',
            'amount' => $payment->amount,
            'notes' => $payment->notes ?: "Payment for purchase #{$referenceNo}"
        ]);
    }

    /**
     * Record sale return
     */
    public function recordSaleReturn($saleReturn)
    {
        // Generate a proper reference number for the sale return
        $referenceNo = $saleReturn->invoice_number ?: 'SR-' . $saleReturn->id;
        
        // Determine transaction type based on whether it's linked to a sale
        $transactionType = $saleReturn->sale_id ? 'sale_return_with_bill' : 'sale_return_without_bill';
        
        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $saleReturn->created_at ? 
            Carbon::parse($saleReturn->created_at)->setTimezone('Asia/Colombo') : 
            Carbon::now('Asia/Colombo');
        
        return Ledger::createEntry([
            'user_id' => $saleReturn->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $transactionDate, // Use normalized date
            'reference_no' => $referenceNo,
            'transaction_type' => $transactionType,
            'amount' => $saleReturn->return_total,
            'notes' => "Sale return #{$referenceNo}"
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
            'user_id' => $purchaseReturn->supplier_id,
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
            'user_id' => $contactType === 'customer' ? $payment->customer_id : $payment->supplier_id,
            'contact_type' => $contactType,
            'transaction_date' => $transactionDate, // Use normalized date
            'reference_no' => $payment->reference_no,
            'transaction_type' => 'payments',
            'amount' => $payment->amount,
            'notes' => 'Return payment - ' . ($payment->notes ?: "Payment for returned items")
        ]);
    }

    /**
     * Edit sale with proper ledger management
     * This creates reverse entries for proper audit trail
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

            // Create reverse entry for old sale amount
            $reverseEntry = Ledger::createEntry([
                'user_id' => $sale->customer_id,
                'contact_type' => 'customer',
                'transaction_date' => Carbon::now('Asia/Colombo'),
                'reference_no' => "EDIT-REV-{$sale->invoice_no}",
                'transaction_type' => 'sale',
                'amount' => $oldFinalTotal,
                'notes' => "Sale Edit - Reverse Old Amount (Rs{$oldFinalTotal})" . 
                          ($editReason ? " | Reason: {$editReason}" : '')
            ]);

            // Create new entry for updated sale amount
            $newEntry = Ledger::createEntry([
                'user_id' => $sale->customer_id,
                'contact_type' => 'customer',
                'transaction_date' => Carbon::now('Asia/Colombo'),
                'reference_no' => $sale->invoice_no,
                'transaction_type' => 'sale',
                'amount' => $newFinalTotal,
                'notes' => "Sale Edit - New Amount (Rs{$newFinalTotal})" . 
                          ($difference >= 0 ? " | Increase: +Rs{$difference}" : " | Decrease: Rs{$difference}")
            ]);

            return [
                'reverse_entry' => $reverseEntry,
                'new_entry' => $newEntry,
                'amount_difference' => $difference
            ];
        });
    }

    /**
     * Get customer balance summary for reporting
     */
    public function getCustomerBalanceSummary($customerId)
    {
        $currentBalance = Ledger::getLatestBalance($customerId, 'customer');
        
        // Calculate different balance types
        $billWiseBalance = $this->getCustomerBillWiseBalance($customerId);
        $floatingBalance = $this->getCustomerFloatingBalance($customerId);
        
        return [
            'customer_id' => $customerId,
            'current_balance' => $currentBalance,
            'bill_wise_balance' => $billWiseBalance,
            'floating_balance' => $floatingBalance,
            'bounced_cheques_amount' => $this->getCustomerBouncedChequesAmount($customerId),
            'outstanding_amount' => max(0, $currentBalance),
            'advance_amount' => $currentBalance < 0 ? abs($currentBalance) : 0,
            'balance_status' => $currentBalance > 0 ? 'receivable' : ($currentBalance < 0 ? 'payable' : 'cleared'),
            'last_updated' => Carbon::now('Asia/Colombo')->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Get customer bill-wise outstanding balance (unpaid bills only)
     */
    public function getCustomerBillWiseBalance($customerId)
    {
        return Sale::where('customer_id', $customerId)
            ->whereNotIn('payment_status', ['Paid'])
            ->sum('final_total') - 
        Sale::where('customer_id', $customerId)
            ->whereNotIn('payment_status', ['Paid'])
            ->sum('total_paid');
    }

    /**
     * Get customer floating balance (bounced cheques, bank charges, etc.)
     */
    public function getCustomerFloatingBalance($customerId)
    {
        $floatingDebits = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->whereIn('transaction_type', ['cheque_bounce', 'bank_charges'])
            ->sum('amount');

        $floatingCredits = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->whereIn('transaction_type', ['bounce_recovery', 'adjustment_credit'])
            ->sum('amount');

        return $floatingDebits - $floatingCredits;
    }

    /**
     * Get total bounced cheques amount for customer
     */
    public function getCustomerBouncedChequesAmount($customerId)
    {
        return Payment::where('customer_id', $customerId)
            ->where('payment_method', 'cheque')
            ->where('cheque_status', 'bounced')
            ->sum('amount');
    }

    /**
     * Record floating balance recovery payment
     */
    public function recordFloatingBalanceRecovery($customerId, $amount, $paymentMethod = 'cash', $notes = '')
    {
        $referenceNo = 'RECOVERY-' . $customerId . '-' . time();
        
        return Ledger::createEntry([
            'user_id' => $customerId,
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
        // Get opening balance
        $openingBalance = 0;
        if ($fromDate) {
            $openingBalanceEntry = Ledger::where('user_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_date', '<', $fromDate)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $openingBalance = $openingBalanceEntry ? $openingBalanceEntry->balance : 0;
        }

        // Get transactions for the period
        $transactions = Ledger::getStatement($customerId, 'customer', $fromDate, $toDate);

        return [
            'customer_id' => $customerId,
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'closing_balance' => $transactions->last()->balance ?? $openingBalance,
            'period' => [
                'from_date' => $fromDate,
                'to_date' => $toDate
            ]
        ];
    }

    /**
     * Record opening balance payment
     */
    public function recordOpeningBalancePayment($payment, $contactType)
    {
        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $payment->created_at ? 
            Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo') : 
            Carbon::now('Asia/Colombo');
        
        return Ledger::createEntry([
            'user_id' => $contactType === 'customer' ? $payment->customer_id : $payment->supplier_id,
            'contact_type' => $contactType,
            'transaction_date' => $transactionDate, // Use normalized date
            'reference_no' => $payment->reference_no,
            'transaction_type' => 'payments',
            'amount' => $payment->amount,
            'notes' => $payment->notes ?: "Opening balance payment"
        ]);
    }

    /**
     * Record opening balance adjustment (when customer/supplier opening balance is updated)
     */
    public function recordOpeningBalanceAdjustment($contactId, $contactType, $oldAmount, $newAmount, $notes = '')
    {
        $adjustmentAmount = $newAmount - $oldAmount;
        
        // Only create ledger entry if there's an actual change
        if ($adjustmentAmount == 0) {
            return null;
        }
        
        $adjustmentType = $adjustmentAmount > 0 ? 'increase' : 'decrease';
        $referenceNo = 'OB-ADJ-' . strtoupper($contactType) . '-' . $contactId . '-' . time();
        
        return Ledger::createEntry([
            'user_id' => $contactId,
            'contact_type' => $contactType,
            'transaction_date' => Carbon::now('Asia/Colombo'), // Use current time in Asia/Colombo
            'reference_no' => $referenceNo,
            'transaction_type' => 'opening_balance',
            'amount' => $adjustmentAmount, // Pass the actual adjustment amount (can be negative)
            'notes' => $notes ?: "Opening balance adjustment ({$adjustmentType}): {$oldAmount} -> {$newAmount}"
        ]);
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
        $ledgerQuery = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->byDateRange($startDate, $endDate);

        // Apply location filtering if specified
        if ($locationId) {
            $ledgerQuery = $this->applyLocationFilter($ledgerQuery, $locationId, 'customer');
        }

        $ledgerTransactions = $ledgerQuery
            ->orderBy('created_at', 'asc') // Order by created_at ascending (chronological order)
            ->orderBy('id', 'asc') // Secondary sort by ID for same-date transactions (chronological)
            ->get();

        // Apply filtering based on $showFullHistory parameter
        if (!$showFullHistory) {
            // **CLEAN VIEW: Hide technical reversal entries for better user experience**
            // Hide EDIT-REV entries which are technical reversal entries created during payment edits
            // These are kept in database for audit trail but hidden from normal business view
            $transactionsToProcess = $ledgerTransactions->filter(function ($ledger) {
                // Hide entries with EDIT-REV in reference number (payment edit reversals)
                if (strpos($ledger->reference_no, 'EDIT-REV') !== false) {
                    return false;
                }
                
                // Hide entries with reversal indicators in notes
                if (strpos($ledger->notes, 'REVERSAL:') !== false) {
                    return false;
                }
                
                // Show all other entries including the Rs. 2,000 payment
                return true;
            });
        } else {
            // **FULL AUDIT TRAIL: Show ALL transactions including technical entries**
            $transactionsToProcess = $ledgerTransactions;
        }

        // Transform ledger data for frontend display
        $transactions = $transactionsToProcess->map(function ($ledger) use ($showFullHistory) {
            // Use created_at converted to Asia/Colombo timezone for display (UTC to Asia/Colombo)
            $displayDate = $ledger->created_at ? 
                Carbon::parse($ledger->created_at)->setTimezone('Asia/Colombo')->format('d/m/Y H:i:s') : 
                'N/A';
            
            // Get location information based on transaction type
            $locationName = $this->getLocationForTransaction($ledger);
            
            // Enhanced transaction type and description for full history mode
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
                'running_balance' => $ledger->balance,
                'payment_method' => $this->extractPaymentMethod($ledger), // Extract from notes
                'notes' => $enhancedNotes,
                'others' => $enhancedNotes, // Show enhanced notes in others column for full history
                'created_at' => $ledger->created_at,
                'transaction_type' => $ledger->transaction_type
            ];
        });

        // Calculate totals from actual business records (not ledger) to avoid double counting
        // Total Invoices should be from unique sales, not multiple ledger entries from edits
        // Get current sale amounts (after all edits), not historical ledger amounts
        $salesInPeriod = \App\Models\Sale::where('customer_id', $customerId)
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->when($locationId, function($query) use ($locationId) {
                return $query->where('location_id', $locationId);
            })
            ->get();
            
        $totalInvoices = $salesInPeriod->sum('final_total');
        
        // Calculate total payments from actual payment records 
        $totalPayments = \App\Models\Payment::where('customer_id', $customerId)
            ->where('payment_type', 'sale')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
            
        // Calculate returns from ledger (since returns might not have separate table)
        $totalReturns = $ledgerTransactions->whereIn('transaction_type', ['sale_return', 'sale_return_with_bill', 'sale_return_without_bill'])->sum('credit');
        
        // For display totals from ledger (already filtered)  
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

        // For accurate calculations, use the current balance from ledger system
        // This represents the true outstanding amount considering all transactions
        $effectiveDue = max(0, $currentBalance);
        $advanceAmount = $currentBalance < 0 ? abs($currentBalance) : 0;
        
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
                'current_balance' => $currentBalance,
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
        $supplier = Supplier::find($supplierId);
        if (!$supplier) {
            throw new \Exception('Supplier not found');
        }

        // Get ledger transactions for the supplier within the date range
        $ledgerQuery = Ledger::where('user_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->byDateRange($startDate, $endDate);

        // Apply location filtering if specified
        if ($locationId) {
            $ledgerQuery = $this->applyLocationFilter($ledgerQuery, $locationId, 'supplier');
        }

        // Apply filtering based on $showFullHistory parameter
        if (!$showFullHistory) {
            // **IMPORTANT: Exclude reversal and deleted entries from display for clean view**
            // These entries are kept in database for audit trail but hidden from user view
            // -REV, -OLD-REV, -OLD: From payment edits
            // -DELETED, -DEL-REV: From payment deletions
            $ledgerQuery->where(function($query) {
                $query->where('reference_no', 'NOT LIKE', '%-REV')
                      ->where('reference_no', 'NOT LIKE', '%-OLD-REV')
                      ->where('reference_no', 'NOT LIKE', '%-OLD')
                      ->where('reference_no', 'NOT LIKE', '%-DELETED')
                      ->where('reference_no', 'NOT LIKE', '%-DEL-REV')
                      ->where('notes', 'NOT LIKE', 'REVERSAL:%'); // Exclude reversal entries
            });
        }

        $ledgerTransactions = $ledgerQuery
            ->orderBy('created_at', 'asc') // Order by created_at ascending (chronological order)
            ->orderBy('id', 'asc') // Secondary sort by ID for same-date transactions (chronological)
            ->get();

        // Apply duplicate filtering only for clean view
        if (!$showFullHistory) {
            // Filter to show only the latest entry for each reference number to avoid showing edit history
            $filteredTransactions = collect();
            $seenReferences = [];
            
            // Process in reverse order to keep the latest entries
            foreach ($ledgerTransactions->reverse() as $ledger) {
                $key = $ledger->reference_no . '_' . $ledger->transaction_type;
                
                if (!isset($seenReferences[$key])) {
                    $seenReferences[$key] = true;
                    $filteredTransactions->prepend($ledger); // Add to beginning to maintain order
                }
            }
            $transactionsToProcess = $filteredTransactions;
        } else {
            // For full history, show all transactions with enhanced information
            $transactionsToProcess = $ledgerTransactions;
        }

        // Transform ledger data for frontend display
        $transactions = $transactionsToProcess->map(function ($ledger) use ($showFullHistory) {
            // Use created_at converted to Asia/Colombo timezone for display (UTC to Asia/Colombo)
            $displayDate = $ledger->created_at ? 
                Carbon::parse($ledger->created_at)->setTimezone('Asia/Colombo')->format('d/m/Y H:i:s') : 
                'N/A';
            
            // Get location information based on transaction type
            $locationName = $this->getLocationForTransaction($ledger);
            
            // Enhanced transaction type and description for full history mode
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
                'running_balance' => $ledger->balance,
                'payment_method' => $this->extractPaymentMethod($ledger), // Extract from notes
                'notes' => $enhancedNotes,
                'others' => $enhancedNotes, // Show enhanced notes in others column for full history
                'created_at' => $ledger->created_at,
                'transaction_type' => $ledger->transaction_type
            ];
        });

        // Calculate totals from ledger transactions
        $totalDebits = $ledgerTransactions->sum('debit');
        $totalCredits = $ledgerTransactions->sum('credit');
        
        // Calculate specific totals for account summary
        $totalPurchases = $ledgerTransactions->whereIn('transaction_type', ['purchase'])->sum('credit');
        $totalPayments = $ledgerTransactions->whereIn('transaction_type', ['payments', 'purchase_payment'])->sum('debit');
        $totalReturns = $ledgerTransactions->whereIn('transaction_type', ['purchase_return'])->sum('debit');
        
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

        // Calculate correct outstanding due for the period
        // For suppliers: Outstanding Due = Opening Balance + Total Purchases - Total Payments - Total Returns
        $calculatedOutstanding = $openingBalance + $totalPurchases - $totalPayments - $totalReturns;
        $totalOutstandingDue = max(0, $calculatedOutstanding);
        $advanceAmount = $calculatedOutstanding < 0 ? abs($calculatedOutstanding) : 0;
        
        // Effective due should reflect the actual current balance, not period-based calculation
        // Use the current balance from ledger which represents the true outstanding amount
        $effectiveDue = max(0, $currentBalance);

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
            'sale_payment', 'purchase_payment', 'opening_balance_payment', 'payments' => 'Paid',
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
        if (in_array($ledger->transaction_type, ['payments', 'sale_payment', 'purchase_payment'])) {
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
                    $customer = Customer::find($ledger->user_id);
                    if ($customer && $customer->location_id) {
                        $location = \App\Models\Location::find($customer->location_id);
                        if ($location) {
                            return $location->name;
                        }
                    }
                } elseif ($ledger->contact_type === 'supplier') {
                    $supplier = Supplier::find($ledger->user_id);
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
            if (in_array($ledger->transaction_type, ['purchase', 'purchase_payment'])) {
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
     * Update sale transaction - creates proper reversal entries for audit trail
     */
    public function updateSale($sale, $oldReferenceNo = null)
    {
        $referenceNo = $oldReferenceNo ?: ($sale->invoice_no ?: 'INV-' . $sale->id);
        
        // Find the original sale ledger entry to reverse
        $originalEntry = Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'sale')
            ->where('user_id', $sale->customer_id)
            ->where('debit', '>', 0) // Only get entries with actual amounts
            ->orderBy('created_at', 'desc') // Get the latest sale entry
            ->first();
            
        if ($originalEntry && $originalEntry->debit != $sale->final_total) {
            // Only create reversal if amount is actually changing
            $reversalEntry = Ledger::create([
                'user_id' => $sale->customer_id,
                'contact_type' => 'customer',
                'transaction_date' => now(),
                'reference_no' => $referenceNo,
                'transaction_type' => 'sale',
                'debit' => 0,
                'credit' => $originalEntry->debit, // Reverse the original debit
                'balance' => 0, // Will be calculated by observer
                'notes' => "REVERSAL: Sale Edit - Original amount Rs{$originalEntry->debit} (ID: {$originalEntry->id})",
            ]);
        }
        
        // Clean up old payment entries (these will be recreated)
        $oldPaymentEntries = Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'payments')
            ->where('user_id', $sale->customer_id)
            ->where(function($query) {
                $query->where('debit', '>', 0)->orWhere('credit', '>', 0);
            })
            ->get();
            
        foreach ($oldPaymentEntries as $paymentEntry) {
            // Only create reversals for entries with meaningful amounts
            if ($paymentEntry->credit > 0) {
                Ledger::create([
                    'user_id' => $sale->customer_id,
                    'contact_type' => 'customer',
                    'transaction_date' => now(),
                    'reference_no' => $referenceNo,
                    'transaction_type' => 'payments',
                    'debit' => $paymentEntry->credit, // Reverse the payment credit
                    'credit' => 0,
                    'balance' => 0, // Will be calculated
                    'notes' => "REVERSAL: Sale Edit - Payment Rs{$paymentEntry->credit} (ID: {$paymentEntry->id})",
                ]);
            }
        }
            
        // Record the updated sale (creates new entry)
        $newSaleEntry = $this->recordSale($sale);
        
        return $newSaleEntry;
    }

    /**
     * Update purchase transaction - properly handles ledger cleanup and recreation
     */
    public function updatePurchase($purchase, $oldReferenceNo = null)
    {
        // Clean up old ledger entries for this purchase
        $referenceNo = $oldReferenceNo ?: ('PUR-' . $purchase->id);
        
        Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'purchase')
            ->where('user_id', $purchase->supplier_id)
            ->delete();
            
        // Also clean up any associated payment entries for this purchase
        Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'payments')
            ->where('user_id', $purchase->supplier_id)
            ->delete();
            
        // Record the updated purchase
        return $this->recordPurchase($purchase);
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
                ->where('user_id', $userId)
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
                $reversalEntry->balance = 0; // Will be recalculated
                $reversalEntry->contact_type = $contactType;
                $reversalEntry->user_id = $userId;
                $reversalEntry->save();
                
                Log::info("Ledger reversal entry created for payment edit", [
                    'original_reference' => $oldReferenceNo,
                    'reversal_reference' => $oldReferenceNo . '-REV',
                    'original_debit' => $oldEntry->debit,
                    'original_credit' => $oldEntry->credit,
                    'reversal_debit' => $reversalEntry->debit,
                    'reversal_credit' => $reversalEntry->credit,
                    'old_balance' => $oldEntry->balance,
                    'contact_type' => $contactType,
                    'user_id' => $userId
                ]);
            }
            
            // CRITICAL: Recalculate ALL balances after reversal to ensure accuracy
            // This ensures running balance is correct for both customer and supplier
            Ledger::recalculateAllBalances($userId, $contactType);
            
            Log::info("Ledger balances recalculated after reversal", [
                'contact_type' => $contactType,
                'user_id' => $userId,
                'final_balance' => Ledger::where('user_id', $userId)
                    ->where('contact_type', $contactType)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->first()->balance ?? 0
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
        // Clean up old ledger entries for this return
        $referenceNo = $oldReferenceNo ?: ('SR-' . $saleReturn->id);
        
        Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'sale_return')
            ->where('user_id', $saleReturn->customer_id)
            ->delete();
            
        // Record the updated return
        return $this->recordSaleReturn($saleReturn);
    }

    /**
     * Update purchase return - properly handles ledger cleanup and recreation
     */
    public function updatePurchaseReturn($purchaseReturn, $oldReferenceNo = null)
    {
        // Clean up old ledger entries for this return
        $referenceNo = $oldReferenceNo ?: ('PR-' . $purchaseReturn->id);
        
        Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'purchase_return')
            ->where('user_id', $purchaseReturn->supplier_id)
            ->delete();
            
        // Record the updated return
        return $this->recordPurchaseReturn($purchaseReturn);
    }

    /**
     * Delete transaction ledger entries - for when transactions are completely removed
     */
    public function deleteSaleLedger($sale)
    {
        $referenceNo = $sale->invoice_no ?: 'INV-' . $sale->id;
        
        return Ledger::where('reference_no', $referenceNo)
            ->where('user_id', $sale->customer_id)
            ->whereIn('transaction_type', ['sale', 'payments'])
            ->delete();
    }

    /**
     * Delete purchase ledger entries - for when transactions are completely removed
     */
    public function deletePurchaseLedger($purchase)
    {
        $referenceNo = 'PUR-' . $purchase->id;
        
        return Ledger::where('reference_no', $referenceNo)
            ->where('user_id', $purchase->supplier_id)
            ->whereIn('transaction_type', ['purchase', 'payments'])
            ->delete();
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
            'user_id' => $userId,
            'contact_type' => $contactType
        ]);
        
        // ACCOUNTING BEST PRACTICE: Don't delete ledger entries, MARK them as deleted
        // This maintains complete audit trail - all transactions remain visible in logs
        // Similar to how we mark -OLD entries during edits
        
        // STEP 1: Find ALL related entries to mark as deleted
        $entriesToMark = Ledger::where('user_id', $userId)
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
                $reversalEntry->user_id = $userId;
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
        
        // STEP 4: Recalculate balances after marking as deleted
        Ledger::recalculateAllBalances($userId, $contactType);
        
        Log::info("Balances recalculated after payment deletion", [
            'user_id' => $userId,
            'contact_type' => $contactType,
            'final_balance' => Ledger::getLatestBalance($userId, $contactType)
        ]);
        
        return $markedCount;
    }

    /**
     * Delete return ledger entries - for when returns are removed
     */
    public function deleteReturnLedger($return, $type = 'sale_return')
    {
        $referenceNo = $type === 'sale_return' ? 'SR-' . $return->id : 'PR-' . $return->id;
        $userId = $type === 'sale_return' ? $return->customer_id : $return->supplier_id;
        
        return Ledger::where('reference_no', $referenceNo)
            ->where('user_id', $userId)
            ->where('transaction_type', $type)
            ->delete();
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
            'current_balance' => $supplier->current_balance ?? 0,
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
        $entries = Ledger::where('user_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;
        
        foreach ($entries as $entry) {
            $runningBalance += $entry->debit - $entry->credit;
            $entry->update(['balance' => $runningBalance]);
        }

        // Update supplier's current_balance
        $supplier = Supplier::find($supplierId);
        if ($supplier) {
            $supplier->update(['current_balance' => $runningBalance]);
        }
    }

    /**
     * Validate ledger consistency for a supplier
     * 
     * @param int $supplierId
     * @return array
     */
    public function validateSupplierLedger(int $supplierId): array
    {
        $entries = Ledger::where('user_id', $supplierId)
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
            ->where('user_id', $contactId)
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
    private function getCurrentBalance(string $contactType, int $userId): float
    {
        // Get the latest ledger entry balance
        $latestEntry = Ledger::where('contact_type', $contactType)
            ->where('user_id', $userId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($latestEntry) {
            return $latestEntry->balance;
        }
        
        // If no ledger entries, check opening balance from customer/supplier table
        if ($contactType === 'customer') {
            $customer = \App\Models\Customer::find($userId);
            return $customer ? ($customer->current_balance ?? 0) : 0;
        } else {
            $supplier = \App\Models\Supplier::find($userId);
            return $supplier ? ($supplier->current_balance ?? 0) : 0;
        }
    }

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
}
