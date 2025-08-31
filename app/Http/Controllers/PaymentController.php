<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::all();
        return response()->json(['status' => 200, 'data' => $payments]);
    }

    public function addSaleBulkPayments()
    {
        return view('bulk_payments.sales_bulk_payments');
    }

    public function addPurchaseBulkPayments()
    {
        return view('bulk_payments.purchases_bulk_payments');
    }

    public function customerLedger(Request $request)
    {
        $customerId = $request->get('customer_id');
        return view('contact.customer.customer_ledger', compact('customerId'));
    }

    public function getCustomerLedger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'location_id' => 'nullable|exists:locations,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        $customerId = $request->customer_id;
        $locationId = $request->location_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Get customer details
        $customer = Customer::find($customerId);

        // Get sales data with location filter
        $salesQuery = Sale::withoutGlobalScopes()->where('customer_id', $customerId)
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->with(['location', 'user', 'payments']);

        if ($locationId) {
            $salesQuery->where('location_id', $locationId);
        }

        $sales = $salesQuery->get();

        // Get payments data
        $paymentsQuery = Payment::where('customer_id', $customerId)
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($locationId) {
            $paymentsQuery->where(function($query) use ($locationId) {
                $query->whereHas('sale', function($subQuery) use ($locationId) {
                    $subQuery->withoutGlobalScopes()->where('location_id', $locationId);
                })->orWhere(function($subQuery) use ($locationId) {
                    // For payments without sales, include them
                    $subQuery->whereNull('reference_id');
                });
            });
        }

        $payments = $paymentsQuery->with(['sale'])->get();

        // Get sale returns data
        $returnsQuery = SalesReturn::where('customer_id', $customerId)
            ->whereBetween('return_date', [$startDate, $endDate])
            ->with(['sale', 'location', 'user']);

        if ($locationId) {
            $returnsQuery->where('location_id', $locationId);
        }

        $returns = $returnsQuery->get();

        // Combine all transactions and sort by date
        $transactions = collect();

        // Add sales
        foreach ($sales as $sale) {
            $transactions->push([
                'date' => $sale->sales_date,
                'reference_no' => $sale->invoice_no,
                'type' => 'Sale',
                'location' => $sale->location ? $sale->location->name : 'N/A',
                'payment_status' => $sale->payment_status,
                'debit' => $sale->final_total,
                'credit' => 0,
                'payment_method' => 'N/A',
                'others' => $sale->discount_amount > 0 ? "Discount: {$sale->discount_amount}" : '',
                'created_at' => $sale->created_at,
                'sale_id' => $sale->id,
                'transaction_type' => 'sale'
            ]);
        }

        // Add payments
        foreach ($payments as $payment) {
            $location = 'N/A';
            if ($payment->sale && $payment->sale->location) {
                $location = $payment->sale->location->name;
            }

            $transactions->push([
                'date' => $payment->payment_date,
                'reference_no' => $payment->reference_no,
                'type' => 'Payment',
                'location' => $location,
                'payment_status' => 'Paid',
                'debit' => 0,
                'credit' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'others' => $payment->notes ?: '',
                'created_at' => $payment->created_at,
                'payment_id' => $payment->id,
                'sale_id' => $payment->reference_id,
                'transaction_type' => 'payment'
            ]);
        }

        // Add returns
        foreach ($returns as $return) {
            $transactions->push([
                'date' => $return->return_date,
                'reference_no' => $return->invoice_number,
                'type' => 'Return',
                'location' => $return->location ? $return->location->name : 'N/A',
                'payment_status' => $return->payment_status,
                'debit' => 0,
                'credit' => $return->return_total,
                'payment_method' => 'N/A',
                'others' => '',
                'created_at' => $return->created_at,
                'return_id' => $return->id,
                'transaction_type' => 'return'
            ]);
        }

        // Sort transactions by date
        $transactions = $transactions->sortBy('date')->values();

        // Calculate running balance with advance payment logic
        $runningBalance = $customer->opening_balance;
        $advanceBalance = 0; // Track advance (negative balance)
        
        $transactionsWithBalance = $transactions->map(function ($transaction) use (&$runningBalance, &$advanceBalance) {
            // Calculate new balance
            $runningBalance += $transaction['debit'] - $transaction['credit'];
            
            // Store the actual running balance
            $transaction['running_balance'] = $runningBalance;
            
            // Determine if customer has advance (negative balance indicates customer has credit)
            if ($runningBalance < 0) {
                $transaction['advance_amount'] = abs($runningBalance);
                $transaction['due_amount'] = 0;
            } else {
                $transaction['advance_amount'] = 0;
                $transaction['due_amount'] = $runningBalance;
            }
            
            return $transaction;
        });

        // Calculate totals
        $totalInvoices = $sales->sum('final_total');
        $totalPaid = $payments->sum('amount');
        $totalReturns = $returns->sum('return_total');
        
        // Calculate actual current balance
        $actualCurrentBalance = $customer->opening_balance + $totalInvoices - $totalPaid - $totalReturns;
        
        // Get current outstanding sales (all unpaid sales, not limited to date range)
        $outstandingSales = Sale::withoutGlobalScopes()
            ->where('customer_id', $customerId)
            ->where('total_due', '>', 0)
            ->get();

        $totalOutstandingDue = $outstandingSales->sum('total_due');
        
        // Manual advance calculation - only show what's available for manual application
        // Don't auto-calculate - let user decide when to apply
        $manualAdvanceAvailable = 0;
        if ($customer->opening_balance < 0) {
            $manualAdvanceAvailable += abs($customer->opening_balance); // Opening advance
        }
        if ($totalReturns > 0) {
            $manualAdvanceAvailable += $totalReturns; // Return amounts can be used as advance
        }
        // Check for overpayments
        $overpayment = max(0, $totalPaid - ($totalInvoices - $totalReturns));
        if ($overpayment > 0) {
            $manualAdvanceAvailable += $overpayment;
        }
        
        // Calculate effective due (what would be due if advance was applied)
        $effectiveDue = max(0, $totalOutstandingDue - $manualAdvanceAvailable);
        
        // Don't auto-apply advance - just calculate what's available for manual application

        return response()->json([
            'status' => 200,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'mobile' => $customer->mobile_no,
                'email' => $customer->email,
                'address' => $customer->address,
                'opening_balance' => $customer->opening_balance,
                'current_balance' => $actualCurrentBalance,
            ],
            'transactions' => $transactionsWithBalance,
            'summary' => [
                'total_invoices' => $totalInvoices,
                'total_paid' => $totalPaid,
                'total_returns' => $totalReturns,
                'balance_due' => max(0, $actualCurrentBalance),
                'advance_amount' => $manualAdvanceAvailable,
                'effective_due' => $effectiveDue,
                'outstanding_due' => $totalOutstandingDue,
                'opening_balance' => $customer->opening_balance,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'advance_application' => [
                'available_advance' => $manualAdvanceAvailable,
                'applied_to_outstanding' => 0, // Not auto-applied anymore
                'remaining_advance' => $manualAdvanceAvailable,
            ]
        ]);
    }

    /**
     * Apply advance amount to outstanding sales automatically
     */
    private function applyAdvanceToOutstandingSales($customerId, $advanceAmount)
    {
        if ($advanceAmount <= 0) {
            return;
        }

        // Get outstanding sales ordered by date (oldest first)
        $outstandingSales = Sale::withoutGlobalScopes()
            ->where('customer_id', $customerId)
            ->where('total_due', '>', 0)
            ->orderBy('sales_date', 'asc')
            ->get();

        $remainingAdvance = $advanceAmount;

        foreach ($outstandingSales as $sale) {
            if ($remainingAdvance <= 0) {
                break;
            }

            $appliedAmount = min($remainingAdvance, $sale->total_due);
            
            // Update sale with advance payment
            // Only update total_paid - total_due is generated automatically
            $sale->total_paid += $appliedAmount;
            
            // Calculate new total_due for payment status logic
            $newTotalDue = $sale->final_total - $sale->total_paid;
            
            // Update payment status
            if ($newTotalDue <= 0) {
                $sale->payment_status = 'Paid';
            } elseif ($sale->total_paid > 0) {
                $sale->payment_status = 'Partial';
            }
            
            $sale->save();
            
            // Create a payment record for the advance application
            $payment = Payment::create([
                'payment_date' => now()->format('Y-m-d'),
                'amount' => $appliedAmount,
                'payment_method' => 'advance_adjustment',
                'payment_type' => 'sale',
                'reference_id' => $sale->id,
                'reference_no' => 'ADV-' . $sale->invoice_no,
                'customer_id' => $customerId,
                'notes' => 'Advance payment auto-applied to invoice',
            ]);

            // Create ledger entry for the advance application
            $this->createLedgerEntryForPayment($payment, 'customer');

            $remainingAdvance -= $appliedAmount;
        }

        // Update customer's current balance
        $this->updateCustomerBalance($customerId);
    }

    /**
     * Manually apply customer advance payments to outstanding bills
     */
    public function applyCustomerAdvance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid customer ID',
                'errors' => $validator->errors()
            ]);
        }

        try {
            DB::beginTransaction();

            $customerId = $request->customer_id;
            
            // Calculate current advance balance
            $advanceBalance = $this->calculateCustomerAdvanceBalance($customerId);
            
            if ($advanceBalance <= 0) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No advance balance available to apply'
                ]);
            }

            // Apply advance to outstanding sales
            $this->applyAdvanceToOutstandingSales($customerId, $advanceBalance);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Advance payments applied successfully to outstanding bills'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 500,
                'message' => 'Failed to apply advance payments: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate customer's available advance balance for manual application
     */
    private function calculateCustomerAdvanceBalance($customerId)
    {
        // Get customer opening balance
        $customer = Customer::find($customerId);
        $openingBalance = $customer ? floatval($customer->opening_balance) : 0;
        
        // Get total customer payments
        $totalPayments = Payment::where('customer_id', $customerId)->sum('amount');
        
        // Get total sales amount
        $totalSales = Sale::withoutGlobalScopes()
            ->where('customer_id', $customerId)
            ->sum('final_total');
            
        // Get total sales returns
        $totalReturns = SalesReturn::withoutGlobalScopes()
            ->where('customer_id', $customerId)
            ->sum('return_total');
            
        // Manual advance calculation - only available amounts for manual application
        $manualAdvanceAvailable = 0;
        
        // Opening balance advance (if negative, we owe customer)
        if ($openingBalance < 0) {
            $manualAdvanceAvailable += abs($openingBalance);
        }
        
        // Return amounts can be used as advance
        if ($totalReturns > 0) {
            $manualAdvanceAvailable += $totalReturns;
        }
        
        // Check for overpayments
        $overpayment = max(0, $totalPayments - ($totalSales - $totalReturns));
        if ($overpayment > 0) {
            $manualAdvanceAvailable += $overpayment;
        }
        
        return $manualAdvanceAvailable;
    }

    // ==================== SUPPLIER LEDGER METHODS ====================

    public function supplierLedger(Request $request)
    {
        $supplierId = $request->get('supplier_id');
        return view('contact.supplier.supplier_ledger', compact('supplierId'));
    }

    public function getSupplierLedger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'location_id' => 'nullable|exists:locations,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        $supplierId = $request->supplier_id;
        $locationId = $request->location_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Get supplier details
        $supplier = Supplier::find($supplierId);

        // Get purchases data with location filter
        $purchasesQuery = Purchase::withoutGlobalScopes()->where('supplier_id', $supplierId)
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->with(['location', 'user', 'payments']);

        if ($locationId) {
            $purchasesQuery->where('location_id', $locationId);
        }

        $purchases = $purchasesQuery->get();

        // Get payments data (payments TO supplier)
        $paymentsQuery = Payment::where('supplier_id', $supplierId)
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($locationId) {
            $paymentsQuery->where(function($query) use ($locationId) {
                $query->where('payment_type', 'purchase')
                    ->whereIn('reference_id', function($subQuery) use ($locationId) {
                        $subQuery->select('id')
                            ->from('purchases')
                            ->where('location_id', $locationId);
                    })->orWhere(function($subQuery) {
                        $subQuery->whereNull('reference_id');
                    });
            });
        }

        $payments = $paymentsQuery->get();

        // Get purchase returns data
        $returnsQuery = PurchaseReturn::where('supplier_id', $supplierId)
            ->whereBetween('return_date', [$startDate, $endDate])
            ->with(['purchase', 'location', 'user']);

        if ($locationId) {
            $returnsQuery->where('location_id', $locationId);
        }

        $returns = $returnsQuery->get();

        // Combine all transactions and sort by date
        $transactions = collect();

        // Add purchases
        foreach ($purchases as $purchase) {
            $transactions->push([
                'date' => $purchase->purchase_date,
                'reference_no' => $purchase->reference_no,
                'type' => 'Purchase',
                'location' => $purchase->location ? $purchase->location->name : 'N/A',
                'payment_status' => $purchase->payment_status,
                'debit' => $purchase->final_total,
                'credit' => 0,
                'payment_method' => 'N/A',
                'others' => $purchase->discount_amount > 0 ? "Discount: {$purchase->discount_amount}" : '',
                'created_at' => $purchase->created_at,
                'purchase_id' => $purchase->id,
                'transaction_type' => 'purchase'
            ]);
        }

        // Add payments (credit - money paid TO supplier)
        foreach ($payments as $payment) {
            // Safely get location from purchase if relationship exists
            $locationName = 'N/A';
            if ($payment->reference_id && $payment->payment_type === 'purchase') {
                $purchase = Purchase::withoutGlobalScopes()->find($payment->reference_id);
                if ($purchase && $purchase->location) {
                    $locationName = $purchase->location->name;
                }
            }

            $transactions->push([
                'date' => $payment->payment_date,
                'reference_no' => $payment->reference_no,
                'type' => 'Payment',
                'location' => $locationName,
                'payment_status' => 'Paid',
                'debit' => 0,
                'credit' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'others' => $payment->notes,
                'created_at' => $payment->created_at,
                'payment_id' => $payment->id,
                'purchase_id' => $payment->reference_id,
                'transaction_type' => 'payment'
            ]);
        }

        // Add purchase returns (credit - money back from supplier)
        foreach ($returns as $return) {
            $transactions->push([
                'date' => $return->return_date,
                'reference_no' => $return->return_no,
                'type' => 'Return',
                'location' => $return->location ? $return->location->name : 'N/A',
                'payment_status' => 'Returned',
                'debit' => 0,
                'credit' => $return->return_total,
                'payment_method' => 'Return',
                'others' => $return->notes,
                'created_at' => $return->created_at,
                'return_id' => $return->id,
                'transaction_type' => 'return'
            ]);
        }

        // Sort transactions by date
        $transactionsWithBalance = $transactions->sortBy('date')->values()->map(function ($transaction, $index) use ($supplier) {
            // Calculate running balance (for suppliers: positive = we owe them, negative = they owe us/advance from them)
            static $runningBalance = null;
            if ($runningBalance === null) {
                $runningBalance = $supplier->opening_balance;
            }
            
            $runningBalance += $transaction['debit'] - $transaction['credit'];
            $transaction['running_balance'] = $runningBalance;
            
            // For suppliers: positive balance = we owe them, negative = advance from supplier
            if ($runningBalance > 0) {
                $transaction['advance_amount'] = 0;
                $transaction['due_amount'] = $runningBalance;
            } else {
                $transaction['advance_amount'] = abs($runningBalance); // Advance FROM supplier
                $transaction['due_amount'] = 0;
            }
            
            return $transaction;
        });

        // Calculate totals
        $totalPurchases = $purchases->sum('final_total');
        $totalPaid = $payments->sum('amount');
        $totalReturns = $returns->sum('return_total');
        
        // Calculate actual current balance
        $actualCurrentBalance = $supplier->opening_balance + $totalPurchases - $totalPaid - $totalReturns;
        
        // Get current outstanding purchases (all unpaid purchases)
        $outstandingPurchases = Purchase::withoutGlobalScopes()
            ->where('supplier_id', $supplierId)
            ->where('total_due', '>', 0)
            ->get();

        $totalOutstandingDue = $outstandingPurchases->sum('total_due');
        
        // Manual advance calculation for suppliers (reverse logic)
        // For suppliers: positive opening balance = we owe them, negative = they gave us advance
        $manualAdvanceAvailable = 0;
        
        // Supplier advance (if they gave us money in advance - negative opening balance)
        if ($supplier->opening_balance < 0) {
            $manualAdvanceAvailable += abs($supplier->opening_balance);
        }
        
        // Purchase returns can be used as advance
        if ($totalReturns > 0) {
            $manualAdvanceAvailable += $totalReturns;
        }
        
        // Check for overpayments to supplier
        $overpayment = max(0, $totalPaid - ($totalPurchases - $totalReturns));
        if ($overpayment > 0) {
            $manualAdvanceAvailable += $overpayment;
        }
        
        // Calculate effective due (what would be due if advance was applied)
        $effectiveDue = max(0, $totalOutstandingDue - $manualAdvanceAvailable);
        
        return response()->json([
            'status' => 200,
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->first_name . ' ' . $supplier->last_name,
                'mobile' => $supplier->mobile_no,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'opening_balance' => $supplier->opening_balance,
                'current_balance' => $actualCurrentBalance,
            ],
            'transactions' => $transactionsWithBalance,
            'summary' => [
                'total_purchases' => $totalPurchases,
                'total_paid' => $totalPaid,
                'total_returns' => $totalReturns,
                'balance_due' => max(0, $actualCurrentBalance),
                'advance_amount' => $manualAdvanceAvailable,
                'effective_due' => $effectiveDue,
                'outstanding_due' => $totalOutstandingDue,
                'opening_balance' => $supplier->opening_balance,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'advance_application' => [
                'available_advance' => $manualAdvanceAvailable,
                'applied_to_outstanding' => 0,
                'remaining_advance' => $manualAdvanceAvailable,
            ]
        ]);
    }

    public function applySupplierAdvance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid supplier ID',
                'errors' => $validator->errors()
            ]);
        }

        try {
            DB::beginTransaction();

            $supplierId = $request->supplier_id;
            
            // Calculate current advance balance
            $advanceBalance = $this->calculateSupplierAdvanceBalance($supplierId);
            
            if ($advanceBalance <= 0) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No advance balance available to apply'
                ]);
            }

            // Apply advance to outstanding purchases
            $this->applyAdvanceToOutstandingPurchases($supplierId, $advanceBalance);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Supplier advance applied successfully to outstanding purchases'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 500,
                'message' => 'Failed to apply supplier advance: ' . $e->getMessage()
            ]);
        }
    }

    private function calculateSupplierAdvanceBalance($supplierId)
    {
        $supplier = Supplier::find($supplierId);
        $openingBalance = $supplier ? floatval($supplier->opening_balance) : 0;
        
        $totalPayments = Payment::where('supplier_id', $supplierId)->sum('amount');
        $totalPurchases = Purchase::withoutGlobalScopes()->where('supplier_id', $supplierId)->sum('final_total');
        $totalReturns = PurchaseReturn::where('supplier_id', $supplierId)->sum('return_total');
        
        $manualAdvanceAvailable = 0;
        
        // Supplier advance (negative opening balance means they gave us advance)
        if ($openingBalance < 0) {
            $manualAdvanceAvailable += abs($openingBalance);
        }
        
        // Returns can be used as advance
        if ($totalReturns > 0) {
            $manualAdvanceAvailable += $totalReturns;
        }
        
        // Overpayments to supplier
        $overpayment = max(0, $totalPayments - ($totalPurchases - $totalReturns));
        if ($overpayment > 0) {
            $manualAdvanceAvailable += $overpayment;
        }
        
        return $manualAdvanceAvailable;
    }

    private function applyAdvanceToOutstandingPurchases($supplierId, $advanceAmount)
    {
        if ($advanceAmount <= 0) {
            return;
        }

        $outstandingPurchases = Purchase::withoutGlobalScopes()
            ->where('supplier_id', $supplierId)
            ->where('total_due', '>', 0)
            ->orderBy('purchase_date', 'asc')
            ->get();

        $remainingAdvance = $advanceAmount;

        foreach ($outstandingPurchases as $purchase) {
            if ($remainingAdvance <= 0) {
                break;
            }

            $appliedAmount = min($remainingAdvance, $purchase->total_due);
            
            $purchase->total_paid += $appliedAmount;
            // Don't update total_due directly as it might be a generated column
            
            // Calculate new total_due
            $newTotalDue = $purchase->final_total - $purchase->total_paid;
            
            if ($newTotalDue <= 0) {
                $purchase->payment_status = 'Paid';
            } elseif ($purchase->total_paid > 0) {
                $purchase->payment_status = 'Partial';
            }
            
            $purchase->save();
            
            // Create payment record
            $payment = Payment::create([
                'payment_date' => now()->format('Y-m-d'),
                'amount' => $appliedAmount,
                'payment_method' => 'advance_adjustment',
                'payment_type' => 'purchase',
                'reference_id' => $purchase->id,
                'reference_no' => 'ADV-' . $purchase->reference_no,
                'supplier_id' => $supplierId,
                'notes' => 'Supplier advance auto-applied to purchase',
            ]);

            // Create ledger entry for the advance application
            $this->createLedgerEntryForPayment($payment, 'supplier');

            $remainingAdvance -= $appliedAmount;
        }
    }

    public function storeOrUpdate(Request $request, $paymentId = null)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        DB::transaction(function () use ($request, $paymentId) {
            $paymentData = $this->preparePaymentData($request);
            $payment = $this->saveOrUpdatePayment($paymentData, $paymentId);

            if ($payment) {
                $this->processPayment($payment);
            }
        });

        return response()->json(['status' => 200, 'message' => 'Payment processed successfully.']);
    }

    private function getValidationRules()
    {
        return [
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'reference_no' => 'nullable|string',
            'notes' => 'nullable|string',
            'payment_type' => 'required|string|in:purchase,sale,purchase_return,sale_return_with_bill,sale_return_without_bill',
            'reference_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'card_number' => 'nullable|string',
            'card_holder_name' => 'nullable|string',
            'card_expiry_month' => 'nullable|string',
            'card_expiry_year' => 'nullable|string',
            'card_security_code' => 'nullable|string',
            'cheque_number' => 'nullable|string',
            'cheque_bank_branch' => 'nullable|string',
            'cheque_received_date' => 'nullable|date',
            'cheque_valid_date' => 'nullable|date',
            'cheque_given_by' => 'nullable|string',
        ];
    }

    private function preparePaymentData(Request $request)
    {
        return [
            'payment_date' => Carbon::parse($request->payment_date)->format('Y-m-d'),
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'reference_no' => $request->reference_no,
            'notes' => $request->notes,
            'payment_type' => $request->payment_type,
            'reference_id' => $request->reference_id,
            'supplier_id' => $request->supplier_id,
            'customer_id' => $request->customer_id,
            'card_number' => $request->card_number,
            'card_holder_name' => $request->card_holder_name,
            'card_expiry_month' => $request->card_expiry_month,
            'card_expiry_year' => $request->card_expiry_year,
            'card_security_code' => $request->card_security_code,
            'cheque_number' => $request->cheque_number,
            'cheque_bank_branch' => $request->cheque_bank_branch,
            'cheque_received_date' => $request->cheque_received_date ? Carbon::createFromFormat('d-m-Y', $request->cheque_received_date)->format('Y-m-d') : null,
            'cheque_valid_date' => $request->cheque_valid_date ? Carbon::createFromFormat('d-m-Y', $request->cheque_valid_date)->format('Y-m-d') : null,
            'cheque_given_by' => $request->cheque_given_by,
        ];
    }

    private function saveOrUpdatePayment($paymentData, $paymentId = null)
    {
        if ($paymentId) {
            $payment = Payment::find($paymentId);
            if ($payment) {
                $payment->update($paymentData);
                return $payment;
            }
        } else {
            return Payment::create($paymentData);
        }
        return null;
    }

    private function processPayment(Payment $payment)
    {
        // Create ledger entries and update related tables
        if ($payment->payment_type === 'sale' && $payment->customer_id) {
            $this->createLedgerEntryForPayment($payment, 'customer');
            $this->updateSaleTable($payment->reference_id);
            $this->updateCustomerBalance($payment->customer_id);
        } else if ($payment->payment_type === 'purchase' && $payment->supplier_id) {
            $this->createLedgerEntryForPayment($payment, 'supplier');
            $this->updatePurchaseTable($payment->reference_id);
            $this->updateSupplierBalance($payment->supplier_id);
        }
        // Add handling for returns if needed in the future
    }

    private function createLedgerEntryForPayment(Payment $payment, $contactType = 'customer')
    {
        $userId = $contactType === 'supplier' ? $payment->supplier_id : $payment->customer_id;

        $prevLedger = Ledger::where('user_id', $userId)
            ->where('contact_type', $contactType)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $prevBalance = $prevLedger ? $prevLedger->balance : 0;

        // Payment: debit = 0, credit = payment amount
        // For suppliers: credit reduces what we owe them (reduces balance)
        // For customers: credit reduces what they owe us (reduces balance)
        $debit = 0;
        $credit = $payment->amount;
        $newBalance = $prevBalance + $debit - $credit;

        Ledger::create([
            'transaction_date' => $payment->payment_date,
            'reference_no' => $payment->reference_no,
            'transaction_type' => 'payments',
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $newBalance,
            'contact_type' => $contactType,
            'user_id' => $userId,
        ]);
    }

    private function updateSaleTable($saleId)
    {
        $sale = Sale::find($saleId);
        if ($sale) {
            $totalPaid = Payment::where('reference_id', $sale->id)
                ->where('payment_type', 'sale')
                ->sum('amount');
            
            // Only update total_paid and payment_status
            // total_due is a generated column that calculates automatically
            $sale->total_paid = $totalPaid;

            // Calculate payment status based on the generated total_due
            $totalDue = $sale->final_total - $totalPaid;

            if ($totalDue <= 0) {
                $sale->payment_status = 'Paid';
            } elseif ($totalPaid > 0) {
                $sale->payment_status = 'Partial';
            } else {
                $sale->payment_status = 'Due';
            }
            $sale->save();
        }
    }

    private function updateCustomerBalance($customerId)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $totalSales = Sale::where('customer_id', $customerId)->sum('final_total');
            $totalSalesReturn = SalesReturn::where('customer_id', $customerId)->sum('return_total');
            $totalPayments = Payment::where('customer_id', $customerId)->where('payment_type', 'sale')->sum('amount');
            $customer->current_balance = ($customer->opening_balance + $totalSales) - ($totalPayments + $totalSalesReturn);
            $customer->save();
        }
    }

    private function updateSupplierBalance($supplierId)
    {
        $supplier = Supplier::find($supplierId);
        if ($supplier) {
            $totalPurchases = Purchase::where('supplier_id', $supplierId)->sum('final_total');
            $totalPurchasesReturn = PurchaseReturn::where('supplier_id', $supplierId)->sum('return_total');
            $totalPayments = Payment::where('supplier_id', $supplierId)->where('payment_type', 'purchase')->sum('amount');
            $supplier->current_balance = ($supplier->opening_balance + $totalPurchases) - ($totalPayments + $totalPurchasesReturn);
            $supplier->save();
        }
    }

    private function calculateSupplierBalance($supplierId)
    {
        $supplier = Supplier::find($supplierId);
        if (!$supplier) {
            return 0;
        }

        $totalPurchases = Purchase::where('supplier_id', $supplierId)->sum('final_total');
        $totalPurchasesReturn = PurchaseReturn::where('supplier_id', $supplierId)->sum('return_total');
        $totalPayments = Payment::where('supplier_id', $supplierId)->where('payment_type', 'purchase')->sum('amount');
        
        return ($supplier->opening_balance + $totalPurchases) - ($totalPayments + $totalPurchasesReturn);
    }

    public function show(Payment $payment)
    {
        $payment->load($this->getPaymentRelations($payment->payment_type));
        return response()->json(['status' => 200, 'data' => $payment]);
    }

    private function getPaymentRelations($paymentType)
    {
        return match ($paymentType) {
            'sale' => ['customer', 'reference.location'],
            'purchase' => ['supplier', 'reference.location'],
            default => ['customer', 'supplier'],
        };
    }

    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $payment->delete();

            if ($payment->payment_type === 'purchase' || $payment->payment_type === 'purchase_return') {
                $this->updateSupplierBalance($payment->supplier_id);
            } else {
                $this->updateCustomerBalance($payment->customer_id);
                $this->updateSaleTable($payment->reference_id);
            }

            Ledger::where('transaction_date', $payment->payment_date)
                ->where('reference_no', $payment->reference_no)
                ->where('transaction_type', 'payments')
                ->where('user_id', $payment->supplier_id ?? $payment->customer_id)
                ->delete();
        });

        return response()->json(['status' => 200, 'message' => 'Payment deleted and balances restored successfully.']);
    }

    // Bulk payment functions for sales (customer) and purchases (supplier)
    public function submitBulkPayment(Request $request)
    {
        $data = $request->validate($this->getBulkPaymentValidationRules($request));

        $entity = $this->validateEntity($data['entity_type'], $data['entity_id']);
        $totalDueAmount = $this->calculateTotalDueAmount($data['entity_type'], $entity->id, $entity->opening_balance);

        if ($data['global_amount'] > $totalDueAmount) {
            return response()->json(['error' => 'Global amount exceeds total due amount'], 400);
        }

        DB::transaction(function () use ($entity, $data, $request) {
            $remainingAmount = $data['global_amount'] ?? 0;
            $this->reduceEntityOpeningBalance($entity, $remainingAmount);
            $this->applyGlobalAmountToReferences($entity, $remainingAmount, $data, $request);
            $this->handleIndividualPayments($entity, $data, $request);
        });

        return response()->json(['message' => 'Payments submitted successfully.']);
    }

    private function getBulkPaymentValidationRules(Request $request)
    {
        return [
            'entity_type' => 'required|in:supplier,customer',
            'entity_id' => 'required',
            'payment_method' => 'required|string',
            'payment_date' => 'nullable|date',
            'global_amount' => 'nullable|numeric',
            'payments' => 'nullable|array',
            'payments.*.reference_id' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->entity_type === 'supplier' && !Purchase::where('id', $value)->exists()) {
                        $fail('The selected ' . $attribute . ' is invalid.');
                    } elseif ($request->entity_type === 'customer' && !Sale::where('id', $value)->exists()) {
                        $fail('The selected ' . $attribute . ' is invalid.');
                    }
                }
            ],
            'payments.*.amount' => 'required|numeric|min:0',
        ];
    }

    private function validateEntity($entityType, $entityId)
    {
        return match ($entityType) {
            'supplier' => Supplier::findOrFail($entityId),
            'customer' => Customer::findOrFail($entityId),
            default => throw new \Exception('Invalid entity type')
        };
    }

    private function calculateTotalDueAmount($entityType, $entityId, $openingBalance)
    {
        $totalDueFromReferences = match ($entityType) {
            'supplier' => Purchase::where('supplier_id', $entityId)->where('total_due', '>', 0)->sum('total_due'),
            'customer' => Sale::where('customer_id', $entityId)->where('total_due', '>', 0)->sum('total_due'),
            default => 0,
        };

        return $openingBalance + $totalDueFromReferences;
    }

    private function reduceEntityOpeningBalance($entity, &$remainingAmount)
    {
        if ($entity->opening_balance > 0) {
            if ($remainingAmount >= $entity->opening_balance) {
                $remainingAmount -= $entity->opening_balance;
                $entity->opening_balance = 0;
            } else {
                $entity->opening_balance -= $remainingAmount;
                $remainingAmount = 0;
            }
            $entity->save();
        }
    }

    private function applyGlobalAmountToReferences($entity, &$remainingAmount, $data, $request)
    {
        if ($data['global_amount'] > 0) {
            $references = $this->getReferencesByEntityType($data['entity_type'], $entity->id);

            foreach ($references as $reference) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $paidAmount = min($remainingAmount, $reference->total_due);
                $this->createBulkPayment($reference, $paidAmount, $data['payment_method'], $data['entity_type'], $entity->id, 'Bulk payment', $request);
                $remainingAmount -= $paidAmount;
            }
        }
    }

    private function handleIndividualPayments($entity, $data, $request)
    {
        if ($data['global_amount'] == 0 && isset($data['payments']) && count($data['payments']) > 0) {
            foreach ($data['payments'] as $paymentData) {
                $reference = $this->getReferenceForBulk($data['entity_type'], $paymentData['reference_id']);
                $this->createBulkPayment($reference, $paymentData['amount'], $data['payment_method'], $data['entity_type'], $entity->id, 'Individual payment', $request);
            }
        }
    }

    private function getReferencesByEntityType($entityType, $entityId)
    {
        return match ($entityType) {
            'supplier' => Purchase::where('supplier_id', $entityId)->where('total_due', '>', 0)->orderBy('created_at', 'asc')->get(),
            'customer' => Sale::where('customer_id', $entityId)->where('total_due', '>', 0)->orderBy('created_at', 'asc')->get(),
            default => collect(),
        };
    }

    private function getReferenceForBulk($entityType, $refId)
    {
        return $entityType === 'supplier'
            ? Purchase::find($refId)
            : Sale::find($refId);
    }

    private function createBulkPayment($reference, $amount, $paymentMethod, $entityType, $entityId, $notes, $request)
    {
        $paymentData = [
            'payment_date' => Carbon::today()->format('Y-m-d'),
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'payment_type' => $entityType === 'supplier' ? 'purchase' : 'sale',
            'reference_id' => $reference->id,
            'reference_no' => $reference->invoice_no ?? $reference->reference_no,
            $entityType === 'supplier' ? 'supplier_id' : 'customer_id' => $entityId,
            'notes' => $notes,
        ];

        if ($paymentMethod === 'card') {
            $paymentData['card_number'] = $request->card_number;
            $paymentData['card_holder_name'] = $request->card_holder_name;
            $paymentData['card_expiry_month'] = $request->card_expiry_month;
            $paymentData['card_expiry_year'] = $request->card_expiry_year;
            $paymentData['card_security_code'] = $request->card_security_code;
        }

        if ($paymentMethod === 'cheque') {
            $paymentData['cheque_number'] = $request->cheque_number;
            $paymentData['cheque_bank_branch'] = $request->cheque_bank_branch;
            $paymentData['cheque_received_date'] = $request->cheque_received_date ? Carbon::createFromFormat('d-m-Y', $request->cheque_received_date)->format('Y-m-d') : null;
            $paymentData['cheque_valid_date'] = $request->cheque_valid_date ? Carbon::createFromFormat('d-m-Y', $request->cheque_valid_date)->format('Y-m-d') : null;
            $paymentData['cheque_given_by'] = $request->cheque_given_by;
        }

        if ($paymentMethod === 'bank_transfer') {
            $paymentData['bank_account_number'] = $request->bank_account_number;
        }

        $payment = Payment::create($paymentData);

        // Only payment ledger entries!
        $this->createLedgerEntryForPayment($payment, $entityType);

        if ($entityType === 'customer') {
            $this->updateSaleTable($reference->id);
            $this->updateCustomerBalance($entityId);
        } else {
            $this->updatePurchaseTable($reference->id);
            $this->updateSupplierBalance($entityId);
        }
    }

    private function updatePurchaseTable($purchaseId)
    {
        $purchase = Purchase::find($purchaseId);
        if ($purchase) {
            $totalPaid = Payment::where('reference_id', $purchase->id)
                ->where('payment_type', 'purchase')
                ->sum('amount');
            
            // Only update total_paid and payment_status
            // total_due is a generated column that calculates automatically
            $purchase->total_paid = $totalPaid;

            // Calculate payment status based on the generated total_due
            $totalDue = $purchase->final_total - $totalPaid;
            
            if ($totalDue <= 0) {
                $purchase->payment_status = 'Paid';
            } elseif ($totalPaid > 0) {
                $purchase->payment_status = 'Partial';
            } else {
                $purchase->payment_status = 'Due';
            }
            
            $purchase->save();
        }
    }

    /**
     * Get supplier details for ledger
     */
    public function getSupplierDetails(Request $request)
    {
        $supplierId = $request->supplier_id;
        
        if (!$supplierId) {
            return response()->json(['success' => false, 'message' => 'Supplier ID required']);
        }

        $supplier = Supplier::find($supplierId);
        
        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Supplier not found']);
        }

        // Calculate supplier balance (for suppliers, positive means we owe them)
        $balance = $this->calculateSupplierBalance($supplierId);
        
        // Get total purchases
        $totalPurchases = Purchase::where('supplier_id', $supplierId)->sum('final_total');

        $supplierData = [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'mobile' => $supplier->mobile,
            'email' => $supplier->email,
            'supplier_business_name' => $supplier->supplier_business_name,
            'balance' => $balance,
            'total_purchases' => $totalPurchases
        ];

        return response()->json(['success' => true, 'data' => $supplierData]);
    }

    /**
     * Get suppliers list for dropdown
     */
    public function getSuppliersData()
    {
        $suppliers = Supplier::select('id', 'name', 'mobile', 'supplier_business_name')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    /**
     * Get business locations for dropdown
     */
    public function getBusinessLocations()
    {
        // Assuming you have a BusinessLocation model
        $locations = collect([
            ['id' => 1, 'name' => 'Main Store'],
            ['id' => 2, 'name' => 'Warehouse'],
            ['id' => 3, 'name' => 'Branch Office']
        ]);

        return response()->json(['success' => true, 'data' => $locations]);
    }
}