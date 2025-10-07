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
     */
    public function getCustomerLedger($customerId, $startDate, $endDate, $locationId = null)
    {
        $customer = Customer::find($customerId);
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
            ->orderBy('transaction_date', 'asc') // Order by actual transaction date for chronological view
            ->orderBy('id', 'asc') // Secondary sort by ID for same-date transactions
            ->get();

        // Transform ledger data for frontend display
        $transactions = $ledgerTransactions->map(function ($ledger) {
            // Use created_at converted to Asia/Colombo timezone for display
            $displayDate = $ledger->created_at ? 
                Carbon::parse($ledger->created_at)->setTimezone('Asia/Colombo')->format('d/m/Y H:i:s') : 
                'N/A';
            
            // Get location information based on transaction type
            $locationName = $this->getLocationForTransaction($ledger);
            
            return [
                'date' => $displayDate,
                'reference_no' => $ledger->reference_no,
                'type' => Ledger::formatTransactionType($ledger->transaction_type),
                'location' => $locationName,
                'payment_status' => $this->getPaymentStatus($ledger),
                'debit' => $ledger->debit,
                'credit' => $ledger->credit,
                'running_balance' => $ledger->balance,
                'payment_method' => $this->extractPaymentMethod($ledger), // Extract from notes
                'notes' => $ledger->notes ?: '',
                'others' => $ledger->notes ?: '', // Show ledger notes in others column
                'created_at' => $ledger->created_at,
                'transaction_type' => $ledger->transaction_type
            ];
        });

        // Calculate totals from ledger transactions
        $totalDebits = $ledgerTransactions->sum('debit');
        $totalCredits = $ledgerTransactions->sum('credit');
        
        // Calculate specific totals for account summary
        $totalInvoices = $ledgerTransactions->whereIn('transaction_type', ['sale'])->sum('debit');
        $totalPayments = $ledgerTransactions->whereIn('transaction_type', ['payments', 'sale_payment'])->sum('credit');
        $totalReturns = $ledgerTransactions->whereIn('transaction_type', ['sale_return', 'sale_return_with_bill', 'sale_return_without_bill'])->sum('credit');
        
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

        // Calculate correct outstanding due for the period
        // Outstanding Due = Opening Balance + Total Invoices - Total Payments - Total Returns
        $calculatedOutstanding = $openingBalance + $totalInvoices - $totalPayments - $totalReturns;
        $totalOutstandingDue = max(0, $calculatedOutstanding);
        $advanceAmount = $calculatedOutstanding < 0 ? abs($calculatedOutstanding) : 0;
        
        // Effective due should reflect the actual current balance, not period-based calculation
        // Use the current balance from ledger which represents the true outstanding amount
        $effectiveDue = max(0, $currentBalance);

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
     */
    public function getSupplierLedger($supplierId, $startDate, $endDate, $locationId = null)
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

        $ledgerTransactions = $ledgerQuery
            ->orderBy('transaction_date', 'asc') // Order by actual transaction date
            ->orderBy('id', 'asc') // Secondary sort by ID for same-date transactions
            ->get();

        // Transform ledger data for frontend display
        $transactions = $ledgerTransactions->map(function ($ledger) {
            // Use created_at converted to Asia/Colombo timezone for display
            $displayDate = $ledger->created_at ? 
                Carbon::parse($ledger->created_at)->setTimezone('Asia/Colombo')->format('d/m/Y H:i:s') : 
                'N/A';
            
            // Get location information based on transaction type
            $locationName = $this->getLocationForTransaction($ledger);
            
            return [
                'date' => $displayDate,
                'reference_no' => $ledger->reference_no,
                'type' => Ledger::formatTransactionType($ledger->transaction_type),
                'location' => $locationName,
                'payment_status' => $this->getPaymentStatus($ledger),
                'debit' => $ledger->debit,
                'credit' => $ledger->credit,
                'running_balance' => $ledger->balance,
                'payment_method' => $this->extractPaymentMethod($ledger), // Extract from notes
                'notes' => $ledger->notes ?: '',
                'others' => $ledger->notes ?: '', // Show ledger notes in others column
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
     * Extract payment method from ledger notes
     */
    private function extractPaymentMethod($ledger)
    {
        // For payment transactions, try to extract payment method from notes
        if (in_array($ledger->transaction_type, ['payments', 'sale_payment', 'purchase_payment'])) {
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
     * Update sale transaction - properly handles ledger cleanup and recreation
     */
    public function updateSale($sale, $oldReferenceNo = null)
    {
        // Clean up old ledger entries for this sale
        $referenceNo = $oldReferenceNo ?: ($sale->invoice_no ?: 'INV-' . $sale->id);
        
        Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'sale')
            ->where('user_id', $sale->customer_id)
            ->delete();
            
        // Also clean up any associated payment entries for this sale
        Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'payments')
            ->where('user_id', $sale->customer_id)
            ->delete();
            
        // Record the updated sale
        return $this->recordSale($sale);
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
        // If we have old payment data, clean it up first
        if ($oldPayment) {
            $oldReferenceNo = $oldPayment->reference_no ?: 'PAY-' . $oldPayment->id;
            Ledger::where('reference_no', $oldReferenceNo)
                ->where('transaction_type', 'payments')
                ->where('user_id', $oldPayment->customer_id ?: $oldPayment->supplier_id)
                ->delete();
        }
        
        // Determine contact type and record appropriate payment
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
    public function deletePaymentLedger($payment)
    {
        $referenceNo = $payment->reference_no ?: 'PAY-' . $payment->id;
        $userId = $payment->customer_id ?: $payment->supplier_id;
        
        return Ledger::where('reference_no', $referenceNo)
            ->where('user_id', $userId)
            ->where('transaction_type', 'payments')
            ->delete();
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
}
