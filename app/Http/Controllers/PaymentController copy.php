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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::all();
        return response()->json(['status' => 200, 'data' => $payments]);
    }

    /**
     * Store or update a payment in storage.
     */
    public function storeOrUpdate(Request $request, $paymentId = null)
    {
        $validator = Validator::make($request->all(), [
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'reference_no' => 'nullable|string',
            'notes' => 'nullable|string',
            'payment_type' => 'nullable|string|in:purchase,sale,purchase_return,sale_return_with_bill,sale_return_without_bill',
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
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        DB::transaction(function () use ($request, $paymentId) {
            $paymentData = $this->preparePaymentData($request);
            $payment = null;

            if ($paymentId) {
                $payment = Payment::find($paymentId);
                $oldAmount = $payment ? $payment->amount : 0;
                if ($payment) {
                    $payment->update($paymentData);
                    $this->updateReference($request, $payment, $oldAmount);
                }
            } else {
                if (!$request->payment_type) {
                    $payment = $this->allocateOverallBalancePayment($request);
                    if ($payment instanceof \Illuminate\Http\JsonResponse) {
                        return $payment; // Return error response if any
                    }
                } else {
                    $payment = Payment::create($paymentData);
                    $this->updateReference($request, $payment);
                }
            }

            if ($payment) {
                if (in_array($payment->payment_type, ['purchase', 'purchase_return'])) {
                    $this->updateSupplierBalance($request->supplier_id);
                    $this->createLedgerEntry($request, $payment, 'supplier');
                } elseif (in_array($payment->payment_type, ['sale', 'sale_return_with_bill', 'sale_return_without_bill'])) {
                    $this->updateCustomerBalance($request->customer_id);
                    $this->createLedgerEntry($request, $payment, 'customer');
                }
            }
        });

        return response()->json(['status' => 200, 'message' => 'Payment processed successfully.']);
    }


    private function updateReference(Request $request, Payment $payment, $oldAmount = 0)
    {
        if ($request->reference_id) {
            $reference = $this->findReference($request->payment_type, $request->reference_id);
            if ($reference) {
                $totalPaid = $reference->payments()->where('id', '!=', $payment->id)->sum('amount') + $payment->amount;
                $reference->total_paid = $totalPaid;
                $this->updatePaymentStatus($reference, $totalPaid, $request->payment_type);
            }
        }
    }

    private function restoreReference(Payment $payment)
    {
        $reference = $this->findReference($payment->payment_type, $payment->reference_id);
        if ($reference) {
            $totalPaid = $reference->payments()->where('id', '!=', $payment->id)->sum('amount');
            $reference->total_paid = $totalPaid;
            $this->updatePaymentStatus($reference, $totalPaid, $payment->payment_type);
        }
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

    private function findReference($type, $id)
    {
        switch ($type) {
            case 'purchase':
                return Purchase::find($id);
            case 'sale':
                return Sale::find($id);
            case 'purchase_return':
                return PurchaseReturn::find($id);
            case 'sale_return_with_bill':
            case 'sale_return_without_bill':
                return SalesReturn::find($id);
            default:
                return null;
        }
    }

    private function updatePaymentStatus($reference, $totalPaid, $type)
    {
        if (in_array($type, ['purchase', 'purchase_return'])) {
            $finalTotalField = $type === 'purchase' ? 'final_total' : 'return_total';
        } elseif (in_array($type, ['sale_return_with_bill', 'sale_return_without_bill'])) {
            $finalTotalField = 'return_total';
        } else {
            $finalTotalField = 'final_total';
        }

        $reference->total_paid = $totalPaid;

        if ($reference->$finalTotalField - $totalPaid <= 0) {
            $reference->payment_status = 'Paid';
        } elseif ($totalPaid < $reference->$finalTotalField) {
            $reference->payment_status = 'Partial';
        } else {
            $reference->payment_status = 'Due';
        }

        $reference->save();
    }

    private function updateSupplierBalance($supplierId)
    {
        $supplier = Supplier::find($supplierId);

        if ($supplier) {
            $totalPurchases = Purchase::where('supplier_id', $supplierId)->sum('final_total');
            $totalPurchasesReturn = PurchaseReturn::where('supplier_id', $supplierId)->sum('return_total');
            $totalPayments = Payment::where('supplier_id', $supplierId)->whereIn('payment_type', ['purchase', 'purchase_return'])->sum('amount');

            $supplier->current_balance = ($supplier->opening_balance + $totalPurchases) - ($totalPayments + $totalPurchasesReturn);
            $supplier->save();
        }
    }

    private function updateCustomerBalance($customerId)
    {
        $customer = Customer::find($customerId);

        if ($customer) {
            $totalSales = Sale::where('customer_id', $customerId)->sum('final_total');
            $totalSalesReturn = SalesReturn::where('customer_id', $customerId)->sum('return_total');
            $totalPayments = Payment::where('customer_id', $customerId)->whereIn('payment_type', ['sale', 'sale_return_with_bill', 'sale_return_without_bill'])->sum('amount');

            $customer->current_balance = ($customer->opening_balance + $totalSales) - ($totalPayments + $totalSalesReturn);
            $customer->save();
        }
    }

    private function createLedgerEntry(Request $request, Payment $payment, $contactType)
    {
        $transactionType = $payment->payment_type;

        $ledgerData = [
            'transaction_date' => $payment->payment_date,
            'reference_no' => $payment->reference_no,
            'transaction_type' => $transactionType,
            'debit' => in_array($payment->payment_type, ['purchase', 'purchase_return']) ? $payment->amount : 0,
            'credit' => in_array($payment->payment_type, ['sale', 'sale_return_with_bill', 'sale_return_without_bill']) ? $payment->amount : 0,
            'balance' => 0,
            'payment_method' => $payment->payment_method,
            'contact_type' => $contactType,
            'user_id' => $contactType === 'supplier' ? $request->supplier_id : $request->customer_id,
        ];

        $ledger = Ledger::create($ledgerData);
        Ledger::calculateBalance($ledger->user_id, $ledger->contact_type);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        if ($payment->payment_type === 'sale') {
            $payment->load('customer', 'reference.location');
        } elseif ($payment->payment_type === 'purchase') {
            $payment->load('supplier', 'reference.location');
        } else {
            $payment->load('customer', 'supplier');
        }
        return response()->json(['status' => 200, 'data' => $payment]);
    }

    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $oldAmount = $payment->amount;

            $payment->delete();

            $this->restoreReference($payment);

            if (in_array($payment->payment_type, ['purchase', 'purchase_return'])) {
                $this->updateSupplierBalance($payment->supplier_id);
            } else {
                $this->updateCustomerBalance($payment->customer_id);
            }
        });

        return response()->json(['status' => 200, 'message' => 'Payment deleted and balances restored successfully.']);
    }


    public function submitBulkPayment(Request $request)
    {
        $data = $request->validate([
            'entity_type' => 'required|in:supplier,customer', // Determine if the entity is a supplier or customer
            'entity_id' => 'required', // entity_id can be supplier_id or customer_id
            'payment_method' => 'required|string',
            'payment_date' => 'nullable|date',
            'global_amount' => 'nullable|numeric',
            'payments' => 'nullable|array',
            'payments.*.reference_id' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->entity_type === 'supplier') {
                        if (!Purchase::where('id', $value)->exists()) {
                            $fail('The selected ' . $attribute . ' is invalid.');
                        }
                    } else {
                        if (!Sale::where('id', $value)->exists()) {
                            $fail('The selected ' . $attribute . ' is invalid.');
                        }
                    }
                }
            ],
            'payments.*.amount' => 'required|numeric|min:0',
        ]);

        // Validate entity_id based on entity_type
        if ($data['entity_type'] === 'supplier') {
            $entity = Supplier::findOrFail($data['entity_id']);
        } else {
            $entity = Customer::findOrFail($data['entity_id']);
        }

        $entityId = $data['entity_id'];
        $globalAmount = $data['global_amount'] ?? 0;
        $remainingAmount = $globalAmount;

        if ($data['entity_type'] === 'supplier') {
            $totalDueFromReferences = Purchase::where('supplier_id', $entityId)
                ->where('total_due', '>', 0)
                ->sum('total_due');
        } else {
            $totalDueFromReferences = Sale::where('customer_id', $entityId)
                ->where('total_due', '>', 0)
                ->sum('total_due');
        }

        $totalDueAmount = $entity->opening_balance + $totalDueFromReferences;

        // Validate that the global amount does not exceed total due amount
        if ($globalAmount > $totalDueAmount) {
            return response()->json(['error' => 'Global amount exceeds total due amount'], 400);
        }

        DB::transaction(function () use ($entity, &$remainingAmount, $globalAmount, $data, $request) {
            // Reduce entity opening balance
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

            // Apply remaining amount to references using FIFO method if global amount is provided
            if ($globalAmount > 0) {
                if ($data['entity_type'] === 'supplier') {
                    $references = Purchase::where('supplier_id', $entity->id)
                        ->where('total_due', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->get();
                } else {
                    $references = Sale::where('customer_id', $entity->id)
                        ->where('total_due', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->get();
                }

                foreach ($references as $reference) {
                    if ($remainingAmount <= 0) {
                        break;
                    }

                    $dueAmount = $reference->total_due;
                    $paidAmount = min($remainingAmount, $dueAmount);

                    $payment = Payment::create([
                        'payment_date' => Carbon::today()->format('Y-m-d'),
                        'amount' => $paidAmount,
                        'payment_method' => $data['payment_method'],
                        'payment_type' => $data['entity_type'] === 'supplier' ? "purchase" : "sale",
                        'reference_id' => $reference->id,
                        'reference_no' => $reference->reference_no,
                        $data['entity_type'] === 'supplier' ? 'supplier_id' : 'customer_id' => $entity->id,
                        'notes' => 'Bulk payment',
                        
                    ]);

                    $this->createLedgerEntry($request, $payment, $data['entity_type']);

                    // Update total_paid and ensure it is correctly incremented
                    $reference->increment('total_paid', $paidAmount);
                    $reference->payment_status = $reference->total_paid >= $reference->final_total ? 'Paid' : 'Partial';
                    $reference->save();

                    $remainingAmount -= $paidAmount;
                }
            }

            // Handle individual payments if global amount is not provided
            if ($globalAmount == 0 && isset($data['payments']) && count($data['payments']) > 0) {
                foreach ($data['payments'] as $paymentData) {
                    if ($data['entity_type'] === 'supplier') {
                        $reference = Purchase::findOrFail($paymentData['reference_id']);
                    } else {
                        $reference = Sale::findOrFail($paymentData['reference_id']);
                    }
                    $paidAmount = $paymentData['amount'];

                    $payment = Payment::create([
                        'payment_date' => $data['payment_date'] ?? Carbon::today()->format('Y-m-d'),
                        'amount' => $paidAmount,
                        'payment_method' => $data['payment_method'],
                        'payment_type' => $data['entity_type'] === 'supplier' ? "purchase" : "sale",
                        'reference_id' => $reference->id,
                        'reference_no' => $reference->reference_no,
                        $data['entity_type'] === 'supplier' ? 'supplier_id' : 'customer_id' => $entity->id,
                        'notes' => 'Individual payment',
                    ]);

                    $this->createLedgerEntry($request, $payment, $data['entity_type']);

                    // Update total_paid and ensure it is correctly incremented
                    $reference->increment('total_paid', $paidAmount);
                    $reference->payment_status = $reference->total_paid >= $reference->final_total ? 'Paid' : 'Partial';
                    $reference->save();
                }
            }
        });

        return response()->json(['message' => 'Payments submitted successfully.']);
    }
}
