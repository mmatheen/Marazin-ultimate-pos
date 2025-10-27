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
use App\Models\BulkPaymentLog;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentService;
use App\Services\UnifiedLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $unifiedLedgerService;

    function __construct(PaymentService $paymentService, UnifiedLedgerService $unifiedLedgerService)
    {
        $this->paymentService = $paymentService;
        $this->unifiedLedgerService = $unifiedLedgerService;
        // Temporarily disable middleware to test data loading
        // Fixed middleware - remove duplicate permissions
        $this->middleware('permission:view payments', ['only' => ['index', 'show']]);
        $this->middleware('permission:create payment', ['only' => ['store']]);
        $this->middleware('permission:edit payment', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete payment', ['only' => ['destroy']]);
        $this->middleware('permission:bulk sale payment', ['only' => ['addSaleBulkPayments', 'storeSaleBulkPayments', 'getBulkPaymentsList', 'editBulkPayment', 'updateBulkPayment', 'deleteBulkPayment', 'getBulkPaymentLogs']]);
        $this->middleware('permission:bulk purchase payment', ['only' => ['addPurchaseBulkPayments', 'storePurchaseBulkPayments', 'getBulkPaymentsList', 'editBulkPayment', 'updateBulkPayment', 'deleteBulkPayment', 'getBulkPaymentLogs']]);
    }

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

    /**
     * Get customer ledger data using the unified ledger system
     * This method uses the centralized ledger system with proper debit/credit logic
     */
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

        try {
            $ledgerData = $this->unifiedLedgerService->getCustomerLedger(
                $request->customer_id,
                $request->start_date,
                $request->end_date,
                $request->location_id
            );

            return response()->json([
                'status' => 200,
                'customer' => $ledgerData['customer'],
                'transactions' => $ledgerData['transactions'],
                'summary' => $ledgerData['summary'],
                'period' => $ledgerData['period'],
                'advance_application' => $ledgerData['advance_application']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve customer ledger: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Format transaction type for display (kept for backward compatibility)
     */
    private function formatTransactionType($type)
    {
        return Ledger::formatTransactionType($type);
    }

    /**
     * Apply advance amount to outstanding sales automatically
     */
    private function applyAdvanceToOutstandingSales($customerId, $advanceAmount)
    {
        if ($advanceAmount <= 0) {
            return;
        }

        // Get outstanding sales ordered by date (oldest first) - respecting location scope
        $outstandingSales = Sale::where('customer_id', $customerId)
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
                'payment_date' => now()->format('Y-m-d H:i:s'),
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
        
        // Get total sales amount - respecting location scope
        $totalSales = Sale::where('customer_id', $customerId)
            ->sum('final_total');
            
        // Get total sales returns - respecting location scope
        $totalReturns = SalesReturn::where('customer_id', $customerId)
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

    // ==================== UNIFIED LEDGER METHODS ====================

    /**
     * Get unified ledger view showing both customers and suppliers
     */
    public function getUnifiedLedger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'contact_type' => 'nullable|in:customer,supplier',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $transactions = $this->unifiedLedgerService->getUnifiedLedgerView(
                $request->start_date,
                $request->end_date,
                $request->contact_type
            );

            // Calculate running balance for the entire unified ledger
            $runningBalance = 0;
            $transactionsWithBalance = $transactions->map(function ($transaction) use (&$runningBalance) {
                $runningBalance += $transaction['debit'] - $transaction['credit'];
                $transaction['running_balance'] = $runningBalance;
                return $transaction;
            });

            // Calculate summary totals
            $totalDebits = $transactions->sum('debit');
            $totalCredits = $transactions->sum('credit');
            $netBalance = $totalDebits - $totalCredits;

            // Separate customer and supplier balances
            $customerTransactions = $transactions->where('contact_type', 'customer');
            $supplierTransactions = $transactions->where('contact_type', 'supplier');

            $customerDebits = $customerTransactions->sum('debit');
            $customerCredits = $customerTransactions->sum('credit');
            $supplierDebits = $supplierTransactions->sum('debit');
            $supplierCredits = $supplierTransactions->sum('credit');

            return response()->json([
                'status' => 200,
                'transactions' => $transactionsWithBalance,
                'summary' => [
                    'total_debits' => $totalDebits,
                    'total_credits' => $totalCredits,
                    'net_balance' => $netBalance,
                    'customer_summary' => [
                        'total_debits' => $customerDebits,
                        'total_credits' => $customerCredits,
                        'net_balance' => $customerDebits - $customerCredits,
                    ],
                    'supplier_summary' => [
                        'total_debits' => $supplierDebits,
                        'total_credits' => $supplierCredits,
                        'net_balance' => $supplierCredits - $supplierDebits,
                    ]
                ],
                'period' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'contact_type' => $request->contact_type ?: 'all'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve unified ledger: ' . $e->getMessage()
            ]);
        }
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

        try {
            $ledgerData = $this->unifiedLedgerService->getSupplierLedger(
                $request->supplier_id,
                $request->start_date,
                $request->end_date,
                $request->location_id
            );

            return response()->json([
                'status' => 200,
                'supplier' => $ledgerData['supplier'],
                'transactions' => $ledgerData['transactions'],
                'summary' => $ledgerData['summary'],
                'period' => $ledgerData['period'],
                'advance_application' => $ledgerData['advance_application']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve supplier ledger: ' . $e->getMessage()
            ]);
        }
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
        $totalPurchases = Purchase::where('supplier_id', $supplierId)->sum('final_total');
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

        $outstandingPurchases = Purchase::where('supplier_id', $supplierId)
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
                'payment_date' => now()->format('Y-m-d H:i:s'),
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
        // If only date is provided, use current time. If datetime is provided, use as-is.
        $paymentDate = $request->payment_date;
        if (strlen($paymentDate) <= 10) { // Only date provided (Y-m-d format)
            $paymentDate = Carbon::parse($paymentDate)->setTimeFromTimeString(Carbon::now()->format('H:i:s'));
        } else {
            $paymentDate = Carbon::parse($paymentDate);
        }
        
        return [
            'payment_date' => $paymentDate->format('Y-m-d H:i:s'),
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
        // Use unified ledger service for all payment processing
        if ($payment->payment_type === 'sale' && $payment->customer_id) {
            $this->unifiedLedgerService->recordSalePayment($payment);
            $this->updateSaleTable($payment->reference_id);
            $this->updateCustomerBalance($payment->customer_id);
        } else if ($payment->payment_type === 'purchase' && $payment->supplier_id) {
            $this->unifiedLedgerService->recordPurchasePayment($payment);
            $this->updatePurchaseTable($payment->reference_id);
        } else if ($payment->payment_type === 'purchase_return' && $payment->supplier_id) {
            $this->unifiedLedgerService->recordReturnPayment($payment, 'supplier');
            $this->updatePurchaseReturnTable($payment->reference_id);
        } else if (in_array($payment->payment_type, ['sale_return_with_bill', 'sale_return_without_bill']) && $payment->customer_id) {
            $this->unifiedLedgerService->recordReturnPayment($payment, 'customer');
            $this->updateSaleReturnTable($payment->reference_id);
            $this->updateCustomerBalance($payment->customer_id);
        } else if ($payment->payment_type === 'opening_balance') {
            $contactType = $payment->customer_id ? 'customer' : 'supplier';
            $this->unifiedLedgerService->recordOpeningBalancePayment($payment, $contactType);
            
            if ($payment->customer_id) {
                $this->updateCustomerBalance($payment->customer_id);
            } else {
                $this->updateSupplierBalance($payment->supplier_id);
            }
        }
    }

    private function updateSaleTable($saleId)
    {
        $sale = Sale::find($saleId);
        if ($sale) {
            $totalPaid = Payment::where('reference_id', $sale->id)
                ->where('payment_type', 'sale')
                ->sum('amount');
            
            // Update total_paid first
            $sale->total_paid = $totalPaid;
            $sale->save();
            
            // Refresh the model to get the updated generated total_due column
            $sale->refresh();

            // Calculate payment status based on the database generated total_due
            if ($sale->total_due <= 0) {
                $sale->payment_status = 'Paid';
            } elseif ($sale->total_paid > 0) {
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
            // Use the ledger system for consistent balance calculation
            // This ensures current_balance field matches the ledger balance
            $customer->recalculateCurrentBalance();
        }
    }

    private function updateSupplierBalance($supplierId)
    {
        $supplier = Supplier::find($supplierId);
        if ($supplier) {
            // Use ledger system for consistent balance calculation
            // Get the latest balance from ledger entries
            $latestEntry = \App\Models\Ledger::where('user_id', $supplierId)
                ->where('contact_type', 'supplier')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            
            if ($latestEntry) {
                $supplier->current_balance = $latestEntry->balance;
                $supplier->saveQuietly(); // Use saveQuietly to prevent triggering observers
                
                Log::info("Supplier balance updated from ledger", [
                    'supplier_id' => $supplierId,
                    'new_balance' => $supplier->current_balance,
                    'ledger_entry_id' => $latestEntry->id
                ]);
            }
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
            'purchase_return' => ['supplier', 'reference.location'],
            default => ['customer', 'supplier'],
        };
    }

    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $payment->delete();

            if ($payment->payment_type === 'purchase') {
                $this->updateSupplierBalance($payment->supplier_id);
                $this->updatePurchaseTable($payment->reference_id);
            } elseif ($payment->payment_type === 'purchase_return') {
                $this->updateSupplierBalance($payment->supplier_id);
                $this->updatePurchaseReturnTable($payment->reference_id);
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
        $paymentType = $data['payment_type'] ?? 'both';
        
        // Calculate the appropriate maximum amount based on payment type
        $maxAmount = $this->calculateMaxPaymentAmount($data['entity_type'], $entity->id, $entity->opening_balance, $paymentType);

        if ($data['global_amount'] > $maxAmount) {
            return response()->json(['error' => 'Global amount exceeds total due amount'], 400);
        }

        DB::transaction(function () use ($entity, $data, $request) {
            $paymentType = $data['payment_type'] ?? 'both';
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $remainingAmount = $data['global_amount'] ?? 0;

            if ($paymentType === 'opening_balance') {
                // Only settle opening balance
                $this->reduceEntityOpeningBalance($entity, $remainingAmount, $paymentMethod);
            } elseif ($paymentType === 'sale_dues') {
                // Only pay against sales/purchases
                $this->applyGlobalAmountToReferences($entity, $remainingAmount, $data, $request);
                $this->handleIndividualPayments($entity, $data, $request);
            } else {
                // Both - opening balance first, then sales
                $this->reduceEntityOpeningBalance($entity, $remainingAmount, $paymentMethod);
                $this->applyGlobalAmountToReferences($entity, $remainingAmount, $data, $request);
                $this->handleIndividualPayments($entity, $data, $request);
            }
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
            'payment_type' => 'nullable|in:opening_balance,sale_dues,both',
            'global_amount' => 'nullable|numeric|min:0',
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
            'customer' => Customer::withoutGlobalScopes()->findOrFail($entityId),
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

    private function calculateMaxPaymentAmount($entityType, $entityId, $openingBalance, $paymentType)
    {
        $totalDueFromReferences = match ($entityType) {
            'supplier' => Purchase::where('supplier_id', $entityId)->where('total_due', '>', 0)->sum('total_due'),
            'customer' => Sale::where('customer_id', $entityId)->where('total_due', '>', 0)->sum('total_due'),
            default => 0,
        };

        return match ($paymentType) {
            'opening_balance' => max(0, $openingBalance), // Only opening balance
            'sale_dues' => $totalDueFromReferences, // Only sale/purchase dues
            'both' => max(0, $openingBalance) + $totalDueFromReferences, // Both combined
            default => max(0, $openingBalance) + $totalDueFromReferences, // Default to both
        };
    }

    private function reduceEntityOpeningBalance($entity, &$remainingAmount, $paymentMethod = 'cash')
    {
        if ($entity->opening_balance > 0 && $remainingAmount > 0) {
            $openingBalancePayment = min($remainingAmount, $entity->opening_balance);
            
            // Create opening balance settlement payment
            $this->createOpeningBalancePayment($entity, $openingBalancePayment, $paymentMethod);
            
            // DO NOT update opening balance - it should remain historical
            // The opening balance is historical data and should never change
            // Payment entries will handle the balance reduction
            
            // Reduce remaining amount
            $remainingAmount -= $openingBalancePayment;
        }
    }

    /**
     * Create payment record for opening balance settlement using unified ledger
     */
    private function createOpeningBalancePayment($entity, $amount, $paymentMethod = 'cash')
    {
        $entityType = $entity instanceof Customer ? 'customer' : 'supplier';
        $referenceNo = 'OB-PAYMENT-' . $entity->id . '-' . time();

        $paymentData = [
            'payment_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'payment_type' => 'opening_balance',
            'reference_id' => null, // No specific sale/purchase reference
            'reference_no' => $referenceNo,
            'notes' => 'Opening Balance Settlement for ' . ($entity->first_name ?? $entity->business_name) . ' ' . ($entity->last_name ?? ''),
        ];

        if ($entityType === 'customer') {
            $paymentData['customer_id'] = $entity->id;
        } else {
            $paymentData['supplier_id'] = $entity->id;
        }

        $payment = Payment::create($paymentData);

        // Use unified ledger service for opening balance payment
        $this->unifiedLedgerService->recordOpeningBalancePayment($payment, $entityType);

        // Update entity balance
        if ($entityType === 'customer') {
            $this->updateCustomerBalance($entity->id);
        } else {
            $this->updateSupplierBalance($entity->id);
        }

        return $payment;
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
            'payment_date' => Carbon::now()->format('Y-m-d H:i:s'),
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

        // Use unified ledger service for bulk payment ledger entries
        if ($entityType === 'customer') {
            $this->unifiedLedgerService->recordSalePayment($payment);
            $this->updateSaleTable($reference->id);
            $this->updateCustomerBalance($entityId);
        } else {
            $this->unifiedLedgerService->recordPurchasePayment($payment);
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
            
            // Update total_paid first
            $purchase->total_paid = $totalPaid;
            $purchase->save();
            
            // Refresh the model to get the updated generated total_due column
            $purchase->refresh();

            // Calculate payment status based on the database generated total_due
            if ($purchase->total_due <= 0) {
                $purchase->payment_status = 'Paid';
            } elseif ($purchase->total_paid > 0) {
                $purchase->payment_status = 'Partial';
            } else {
                $purchase->payment_status = 'Due';
            }
            
            $purchase->save();
        }
    }

    private function updatePurchaseReturnTable($purchaseReturnId)
    {
        $purchaseReturn = PurchaseReturn::find($purchaseReturnId);
        if ($purchaseReturn) {
            $totalPaid = Payment::where('reference_id', $purchaseReturn->id)
                ->where('payment_type', 'purchase_return')
                ->sum('amount');
            
            // Update total_paid first
            $purchaseReturn->total_paid = $totalPaid;
            $purchaseReturn->save();
            
            // Refresh the model to get the updated generated total_due column
            $purchaseReturn->refresh();

            // Calculate payment status based on the database generated total_due
            if ($purchaseReturn->total_due <= 0) {
                $purchaseReturn->payment_status = 'Paid';
            } elseif ($purchaseReturn->total_paid > 0) {
                $purchaseReturn->payment_status = 'Partial';
            } else {
                $purchaseReturn->payment_status = 'Due';
            }
            
            $purchaseReturn->save();
        }
    }

    private function updateSaleReturnTable($salesReturnId)
    {
        $salesReturn = SalesReturn::find($salesReturnId);
        if ($salesReturn) {
            $totalPaid = Payment::where('reference_id', $salesReturn->id)
                ->whereIn('payment_type', ['sale_return_with_bill', 'sale_return_without_bill'])
                ->sum('amount');
            
            // Update total_paid first
            $salesReturn->total_paid = $totalPaid;
            $salesReturn->save();
            
            // Refresh the model to get the updated generated total_due column
            $salesReturn->refresh();

            // Calculate payment status based on the database generated total_due
            if ($salesReturn->total_due <= 0) {
                $salesReturn->payment_status = 'Paid';
            } elseif ($salesReturn->total_paid > 0) {
                $salesReturn->payment_status = 'Partial';
            } else {
                $salesReturn->payment_status = 'Due';
            }
            
            $salesReturn->save();
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

    /**
     * Get bulk payments list for sales or purchases with edit/delete options
     */
    public function getBulkPaymentsList(Request $request)
    {
        try {
            // Make entity_type optional, default to sale
            $validator = Validator::make($request->all(), [
                'entity_type' => 'nullable|in:sale,purchase',
                'entity_id' => 'nullable|integer',
                'customer_id' => 'nullable|integer',
                'supplier_id' => 'nullable|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 400, 'errors' => $validator->messages()]);
            }

            $entityType = $request->get('entity_type', 'sale'); // Default to sale
            $query = Payment::query();

            // Filter by entity type with more flexible conditions
            if ($entityType === 'sale') {
                $query->where('payment_type', 'sale')
                      ->with(['customer']);
                // Don't require customer_id to be not null as it might be optional
                
                // Filter by specific customer if provided
                if ($request->customer_id) {
                    $query->where('customer_id', $request->customer_id);
                }
            } else {
                $query->where('payment_type', 'purchase')
                      ->with(['supplier']);
                // Don't require supplier_id to be not null as it might be optional
                
                // Filter by specific supplier if provided
                if ($request->supplier_id) {
                    $query->where('supplier_id', $request->supplier_id);
                }
            }

            // Filter by specific entity ID if provided
            if ($request->entity_id) {
                $query->where('reference_id', $request->entity_id);
            }

            // Default to today's payments if no date range provided
            $startDate = $request->get('start_date', date('Y-m-d'));
            $endDate = $request->get('end_date', date('Y-m-d'));

            // Filter by date range (be more flexible with date filtering)
            if ($startDate) {
                $query->whereDate('payment_date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('payment_date', '<=', $endDate);
            }

            // **IMPORTANT: Filter out zero-amount payments**
            // Zero-amount payments are typically from credit sales where no actual payment was made
            // They create ledger entries for sales but shouldn't appear in payment lists
            $query->where('amount', '>', 0);

            // Get payments ordered by date descending (latest first)
            $payments = $query->orderBy('payment_date', 'desc')
                             ->orderBy('created_at', 'desc')
                             ->get();

            // Debug: Log the query and results
            Log::info('Bulk payments query', [
                'entity_type' => $entityType,
                'customer_id' => $request->customer_id,
                'supplier_id' => $request->supplier_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sql' => $query->toSql(),
                'count' => $payments->count()
            ]);

            // Add related entity information
            foreach ($payments as $payment) {
                if ($entityType === 'sale') {
                    $payment->sale = Sale::find($payment->reference_id);
                } else {
                    $payment->purchase = Purchase::find($payment->reference_id);
                }
            }

            return response()->json([
                'status' => 200,
                'message' => 'Bulk payments retrieved successfully',
                'data' => $payments,
                'entity_type' => $entityType,
                'count' => $payments->count(),
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'entity_type' => $entityType,
                    'customer_id' => $request->customer_id,
                    'supplier_id' => $request->supplier_id
                ]
            ]);

        } catch (\Exception $e) {
            // Log::error('Error getting bulk payments list: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while retrieving payments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bulk payment details for editing
     */
    public function editBulkPayment($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            // Determine entity type
            $entityType = $payment->payment_type === 'sale' ? 'sale' : 'purchase';
            
            // Get related entity
            $entity = null;
            if ($entityType === 'sale') {
                $entity = Sale::find($payment->reference_id);
                $contact = Customer::find($payment->customer_id);
            } else {
                $entity = Purchase::find($payment->reference_id);
                $contact = Supplier::find($payment->supplier_id);
            }

            return response()->json([
                'status' => 200,
                'payment' => $payment,
                'entity' => $entity,
                'contact' => $contact,
                'entity_type' => $entityType
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Payment not found or error occurred: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update bulk payment
     */
    public function updateBulkPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
            'reason' => 'required|string|max:500',
            'allow_advance' => 'nullable|boolean',
            // Card fields
            'card_number' => 'nullable|string',
            'card_holder_name' => 'nullable|string',
            'card_expiry_month' => 'nullable|string',
            'card_expiry_year' => 'nullable|string',
            'card_security_code' => 'nullable|string',
            // Cheque fields
            'cheque_number' => 'nullable|string',
            'cheque_bank_branch' => 'nullable|string',
            'cheque_received_date' => 'nullable|date',
            'cheque_valid_date' => 'nullable|date',
            'cheque_given_by' => 'nullable|string',
            // Bank transfer fields
            'bank_account_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $payment = Payment::findOrFail($id);
            
            // CRITICAL: Only allow editing TODAY's payments to maintain ledger integrity
            $paymentDate = Carbon::parse($payment->payment_date);
            $today = Carbon::today();
            
            if (!$paymentDate->isSameDay($today)) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Cannot edit past payments. Only TODAY\'s payments can be edited to maintain ledger integrity and prevent accounting corruption.'
                ]);
            }
            
            $entityType = $payment->payment_type === 'sale' ? 'sale' : 'purchase';
            $advanceAmount = 0; // Initialize outside transaction
            
            DB::transaction(function () use ($request, $id, &$advanceAmount) {
                $payment = Payment::findOrFail($id);
                
                // Store old payment data for logging and ledger management
                $oldPaymentData = $payment->toArray();
                $oldAmount = $payment->amount;
                
                // Determine entity type and get related info
                $entityType = $payment->payment_type === 'sale' ? 'sale' : 'purchase';
                $entityId = $payment->reference_id;
                $customerId = $payment->customer_id;
                $supplierId = $payment->supplier_id;

                // CRITICAL VALIDATION: Prevent overpayment for bulk payments
                // For bulk payments, we need to check against the original sale/purchase amount
                // to ensure total payments don't exceed the invoice total
                
                // Skip validation if this is an opening balance payment or payment without reference
                if (!$entityId) {
                    Log::info("Payment edit without reference_id - skipping overpayment validation", [
                        'payment_id' => $id,
                        'payment_type' => $payment->payment_type,
                        'amount' => $request->amount
                    ]);
                    // Allow edit for payments without entity reference (opening balance, etc.)
                } elseif ($entityType === 'sale') {
                    $entity = Sale::find($entityId);
                    if (!$entity) {
                        throw new \Exception('Related sale not found');
                    }
                    
                    // Skip validation if sale has 0 amount (opening balance or special case)
                    if ($entity->total_amount <= 0) {
                        Log::info("Payment edit for sale with zero amount - skipping overpayment validation", [
                            'payment_id' => $id,
                            'sale_id' => $entityId,
                            'sale_amount' => $entity->total_amount,
                            'payment_amount' => $request->amount
                        ]);
                    } else {
                        // Calculate total payments for this sale (excluding current payment being edited)
                        $totalOtherPayments = Payment::where('reference_id', $entityId)
                            ->where('payment_type', 'sale')
                            ->where('id', '!=', $payment->id)
                            ->sum('amount');
                        
                        // Maximum allowed for this payment = Total Sale Amount - Other Payments
                        $maxAmount = $entity->total_amount - $totalOtherPayments;
                        
                        // Also check against available due (current due + old payment amount)
                        // This handles cases where there might be returns or adjustments
                        $maxAmountByDue = $entity->total_due + $oldAmount;
                        
                        // Use the smaller of the two to be safe
                        $maxAmount = min($maxAmount, $maxAmountByDue);
                    }
                    
                } else {
                    $entity = Purchase::find($entityId);
                    if (!$entity) {
                        throw new \Exception('Related purchase not found');
                    }
                    
                    // Skip validation if purchase has 0 amount (opening balance or special case)
                    if ($entity->total_amount <= 0) {
                        Log::info("Payment edit for purchase with zero amount - skipping overpayment validation", [
                            'payment_id' => $id,
                            'purchase_id' => $entityId,
                            'purchase_amount' => $entity->total_amount,
                            'payment_amount' => $request->amount
                        ]);
                    } else {
                        // Calculate total payments for this purchase (excluding current payment being edited)
                        $totalOtherPayments = Payment::where('reference_id', $entityId)
                            ->where('payment_type', 'purchase')
                            ->where('id', '!=', $payment->id)
                            ->sum('amount');
                        
                        // Maximum allowed for this payment = Total Purchase Amount - Other Payments
                        $maxAmount = $entity->total_amount - $totalOtherPayments;
                        
                        // Also check against available due (current due + old payment amount)
                        $maxAmountByDue = $entity->total_due + $oldAmount;
                        
                        // Use the smaller of the two to be safe
                        $maxAmount = min($maxAmount, $maxAmountByDue);
                    }
                }

                // ADVANCE PAYMENT HANDLING
                // Check if user explicitly allowed advance payment (for customer credit)
                $allowAdvance = $request->input('allow_advance', false);
                $advanceAmount = 0;
                
                // Only validate overpayment if there's a linked entity (sale/purchase)
                if ($entityId && isset($maxAmount) && $request->amount > $maxAmount) {
                    if (!$allowAdvance) {
                        // Overpayment not allowed without advance flag
                        $totalPaid = ($entityType === 'sale' ? 
                            Payment::where('reference_id', $entityId)->where('payment_type', 'sale')->where('id', '!=', $payment->id)->sum('amount') :
                            Payment::where('reference_id', $entityId)->where('payment_type', 'purchase')->where('id', '!=', $payment->id)->sum('amount')
                        );
                        
                        // Create a structured error message for better display
                        $errorData = [
                            'title' => 'Overpayment Not Allowed!',
                            'message' => 'The payment amount exceeds the maximum allowed for this ' . $entityType . '.',
                            'details' => [
                                'Total ' . ucfirst($entityType) . ' Amount' => 'Rs. ' . number_format($entity->total_amount, 2),
                                'Already Paid (other payments)' => 'Rs. ' . number_format($totalPaid, 2),
                                'Maximum allowed for this payment' => 'Rs. ' . number_format($maxAmount, 2),
                                'You tried to pay' => 'Rs. ' . number_format($request->amount, 2)
                            ],
                            'tip' => 'Check "Allow Advance Payment" if customer is paying extra as advance credit.'
                        ];
                        
                        // Throw exception with JSON encoded data for frontend parsing
                        throw new \Exception(json_encode($errorData));
                    } else {
                        // Advance payment is allowed - calculate excess amount
                        $advanceAmount = $request->amount - $maxAmount;
                        Log::info("Advance payment detected", [
                            'payment_id' => $id,
                            'total_payment' => $request->amount,
                            'applied_to_bill' => $maxAmount,
                            'advance_amount' => $advanceAmount,
                            'entity_type' => $entityType,
                            'entity_id' => $entityId
                        ]);
                    }
                }

                // Update payment data
                $updateData = [
                    'amount' => $request->amount,
                    'payment_method' => $request->payment_method,
                    'payment_date' => $request->payment_date,
                    'notes' => $request->notes,
                ];

                // Handle payment method specific fields
                if ($request->payment_method === 'card') {
                    $updateData = array_merge($updateData, [
                        'card_number' => $request->card_number,
                        'card_holder_name' => $request->card_holder_name,
                        'card_expiry_month' => $request->card_expiry_month,
                        'card_expiry_year' => $request->card_expiry_year,
                        'card_security_code' => $request->card_security_code,
                    ]);
                } elseif ($request->payment_method === 'cheque') {
                    $updateData = array_merge($updateData, [
                        'cheque_number' => $request->cheque_number,
                        'cheque_bank_branch' => $request->cheque_bank_branch,
                        'cheque_received_date' => $request->cheque_received_date,
                        'cheque_valid_date' => $request->cheque_valid_date,
                        'cheque_given_by' => $request->cheque_given_by,
                    ]);
                } elseif ($request->payment_method === 'bank_transfer') {
                    $updateData['bank_account_number'] = $request->bank_account_number;
                }

                // Update the payment
                $payment->update($updateData);

                // LEDGER MANAGEMENT: Handle unified ledger update - this is crucial for accurate accounting
                try {
                    // Create old payment object for ledger cleanup
                    $oldPayment = new Payment($oldPaymentData);
                    $oldPayment->id = $payment->id;
                    
                    // Step 1: Delete old ledger entry
                    // Step 2: Create new ledger entry with updated amount
                    // This ensures the ledger is always in sync with the payment
                    $this->unifiedLedgerService->updatePayment($payment->fresh(), $oldPayment);
                    
                    Log::info('Ledger updated successfully for payment edit', [
                        'payment_id' => $payment->id,
                        'old_amount' => $oldAmount,
                        'new_amount' => $request->amount,
                        'customer_id' => $customerId,
                        'supplier_id' => $supplierId,
                        'entity_type' => $entityType
                    ]);
                } catch (\Exception $e) {
                    Log::error('CRITICAL: Failed to update unified ledger for payment', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw new \Exception('Failed to update payment ledger. Transaction rolled back to prevent data corruption: ' . $e->getMessage());
                }

                // Log the edit action with proper reference information
                $referenceInfo = '';
                if ($entityType === 'sale' && isset($entity)) {
                    $referenceInfo = $entity->invoice_no ?? 'Sale #' . $entityId;
                } elseif ($entityType === 'purchase' && isset($entity)) {
                    $referenceInfo = $entity->reference_no ?? 'Purchase #' . $entityId;
                }
                
                BulkPaymentLog::create([
                    'action' => 'edit',
                    'entity_type' => $entityType,
                    'payment_id' => $payment->id,
                    'entity_id' => $entityId,
                    'customer_id' => $customerId,
                    'supplier_id' => $supplierId,
                    'old_data' => $oldPaymentData,
                    'new_data' => $payment->fresh()->toArray(),
                    'old_amount' => $oldAmount,
                    'new_amount' => $request->amount,
                    'reference_no' => $referenceInfo ?: $payment->reference_no,
                    'reason' => $request->reason,
                    'performed_by' => auth()->id() ?? Auth::id(),
                    'performed_at' => Carbon::now(),
                ]);

                // HANDLE ADVANCE PAYMENT (Customer Credit)
                if ($advanceAmount > 0 && $entityType === 'sale') {
                    // Add advance amount to customer's credit balance
                    $customer = Customer::find($customerId);
                    if ($customer) {
                        $oldCreditBalance = $customer->current_balance;
                        $customer->current_balance = ($customer->current_balance ?? 0) - $advanceAmount; // Negative means customer has credit
                        $customer->save();
                        
                        Log::info("Customer credit updated for advance payment", [
                            'customer_id' => $customerId,
                            'customer_name' => $customer->first_name . ' ' . $customer->last_name,
                            'old_credit_balance' => $oldCreditBalance,
                            'new_credit_balance' => $customer->current_balance,
                            'advance_amount_added' => $advanceAmount,
                            'payment_id' => $payment->id
                        ]);
                        
                        // Add note to payment about advance
                        $advanceNote = "\n[Advance Payment: Rs. " . number_format($advanceAmount, 2) . " added to customer credit. Applied to bill: Rs. " . number_format($maxAmount, 2) . "]";
                        $payment->notes = ($payment->notes ?? '') . $advanceNote;
                        $payment->save();
                    }
                }

                // Update related tables and balances
                if ($entityType === 'sale') {
                    $this->updateSaleTable($entityId);
                    $this->updateCustomerBalance($customerId);
                } else {
                    $this->updatePurchaseTable($entityId);
                    $this->updateSupplierBalance($supplierId);
                }
            });

            // Prepare success message
            $successMessage = 'Payment updated successfully! The ' . $entityType . ' balance has been recalculated.';
            if ($advanceAmount > 0) {
                $successMessage .= ' Advance amount of Rs. ' . number_format($advanceAmount, 2) . ' has been added to customer credit.';
            }

            return response()->json([
                'status' => 200,
                'message' => $successMessage,
                'payment' => $payment->fresh(),
                'advance_amount' => $advanceAmount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update payment: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete bulk payment
     */
    public function deleteBulkPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $payment = Payment::findOrFail($id);
            
            // CRITICAL: Only allow deleting TODAY's payments to maintain ledger integrity
            $paymentDate = Carbon::parse($payment->payment_date);
            $today = Carbon::today();
            
            if (!$paymentDate->isSameDay($today)) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Cannot delete past payments. Only TODAY\'s payments can be deleted to maintain ledger integrity and prevent accounting corruption.'
                ]);
            }
            
            $entityType = '';
            $amount = 0;
            
            DB::transaction(function () use ($request, $id, &$entityType, &$amount) {
                $payment = Payment::findOrFail($id);
                
                // Store data for logging before deletion
                $paymentData = $payment->toArray();
                $entityType = $payment->payment_type === 'sale' ? 'sale' : 'purchase';
                $entityId = $payment->reference_id;
                $customerId = $payment->customer_id;
                $supplierId = $payment->supplier_id;
                $amount = $payment->amount;

                // LEDGER MANAGEMENT: Handle unified ledger cleanup - this is crucial for accurate accounting
                try {
                    // Delete the payment ledger entries from unified ledger
                    // This removes the debit/credit entries for this payment
                    $this->unifiedLedgerService->deletePaymentLedger($payment);
                    
                    Log::info('Ledger payment entries deleted successfully', [
                        'payment_id' => $payment->id,
                        'amount' => $amount,
                        'customer_id' => $customerId,
                        'supplier_id' => $supplierId,
                        'entity_type' => $entityType
                    ]);
                } catch (\Exception $e) {
                    Log::error('CRITICAL: Failed to delete unified ledger entries for payment', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw new \Exception('Failed to delete payment ledger entries. Transaction rolled back to prevent data corruption: ' . $e->getMessage());
                }

                // Log the delete action
                BulkPaymentLog::create([
                    'action' => 'delete',
                    'entity_type' => $entityType,
                    'payment_id' => $payment->id,
                    'entity_id' => $entityId,
                    'customer_id' => $customerId,
                    'supplier_id' => $supplierId,
                    'old_data' => $paymentData,
                    'new_data' => null,
                    'old_amount' => $amount,
                    'new_amount' => null,
                    'reference_no' => $payment->reference_no,
                    'reason' => $request->reason,
                    'performed_by' => Auth::id(),
                    'performed_at' => Carbon::now(),
                ]);

                // Delete the payment
                $payment->delete();

                // Update related tables and balances
                if ($entityType === 'sale') {
                    $this->updateSaleTable($entityId);
                    $this->updateCustomerBalance($customerId);
                } else {
                    $this->updatePurchaseTable($entityId);
                    $this->updateSupplierBalance($supplierId);
                }
            });

            return response()->json([
                'status' => 200,
                'message' => 'Payment of Rs. ' . number_format($amount, 2) . ' deleted successfully! The ' . $entityType . ' balance has been updated.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete payment: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get bulk payment logs
     */
    public function getBulkPaymentLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'nullable|in:sale,purchase',
            'action' => 'nullable|in:edit,delete',
            'payment_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        $query = BulkPaymentLog::with(['performedBy', 'customer', 'supplier']);

        // Apply filters
        if ($request->entity_type) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->action) {
            $query->where('action', $request->action);
        }
        
        // Filter by specific payment ID
        if ($request->payment_id) {
            $query->where('payment_id', $request->payment_id);
        }

        if ($request->start_date) {
            $query->whereDate('performed_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('performed_at', '<=', $request->end_date);
        }

        $perPage = $request->per_page ?? 20;
        $logs = $query->orderBy('performed_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 200,
            'data' => $logs
        ]);
    }
}