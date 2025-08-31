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

    public function customerLedger()
    {
        return view('customer.customer_ledger');
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

        // Build base query for ledger transactions
        $ledgerQuery = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->whereBetween('transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('transaction_date', 'asc');

        // Get all ledger entries
        $ledgerEntries = $ledgerQuery->get();

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
                    // For payments without sales, you might want to include them or exclude them
                    // For now, let's include payments that don't have sales
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

        // Combine all transactions
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
            ]);
        }

        // Sort transactions by date
        $transactions = $transactions->sortBy('date')->values();

        // Calculate running balance
        $runningBalance = $customer->opening_balance;
        $transactionsWithBalance = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += $transaction['debit'] - $transaction['credit'];
            $transaction['running_balance'] = $runningBalance;
            return $transaction;
        });

        // Calculate totals
        $totalInvoices = $sales->sum('final_total');
        $totalPaid = $payments->sum('amount');
        $totalReturns = $returns->sum('return_total');
        $balanceDue = $runningBalance;

        return response()->json([
            'status' => 200,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'mobile' => $customer->mobile_no,
                'email' => $customer->email,
                'address' => $customer->address,
                'opening_balance' => $customer->opening_balance,
            ],
            'transactions' => $transactionsWithBalance,
            'summary' => [
                'total_invoices' => $totalInvoices,
                'total_paid' => $totalPaid,
                'total_returns' => $totalReturns,
                'balance_due' => $balanceDue,
                'opening_balance' => $customer->opening_balance,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
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
        // Only payment ledger entries!
        if ($payment->payment_type === 'sale' && $payment->customer_id) {
            $this->updateCustomerBalance($payment->customer_id);
            $this->createLedgerEntryForPayment($payment);
            $this->updateSaleTable($payment->reference_id);
        } else if ($payment->payment_type === 'purchase' && $payment->supplier_id) {
            $this->updateSupplierBalance($payment->supplier_id);
            $this->createLedgerEntryForPayment($payment, 'supplier');
            // update purchase table if needed...
        }
        // handle returns if needed (not shown here)
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
        $debit = 0;
        $credit = $payment->amount;
        $newBalance = $prevBalance + $debit - $credit;

        Ledger::create([
            'transaction_date' => $payment->payment_date,
            'reference_no' => $payment->reference_no,
            'transaction_type' => 'payments', // Always payments for PaymentController!
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $newBalance,
            'contact_type' => $contactType,
            'user_id' => $userId,
            'payment_method' => $payment->payment_method,
        ]);
    }

    private function updateSaleTable($saleId)
    {
        $sale = Sale::find($saleId);
        if ($sale) {
            $totalPaid = Payment::where('reference_id', $sale->id)
                ->where('payment_type', 'sale')
                ->sum('amount');
            $sale->total_paid = $totalPaid;
            $sale->total_due = max($sale->final_total - $totalPaid, 0);

            if ($sale->total_due <= 0) {
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
            $this->updateSupplierBalance($entityId);
        }
    }
}