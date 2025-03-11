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

    public function addSaleBulkPayments()
    {
       
        return view('bulk_payments.sales_bulk_payments');
    }

    public function addPurchaseBulkPayments()
    {
       
        return view('bulk_payments.purchases_bulk_payments');
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
                $this->processPayment($request, $payment);
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

    private function processPayment(Request $request, Payment $payment)
    {
        $this->updateReference($request, $payment);

        if (in_array($payment->payment_type, ['purchase', 'purchase_return'])) {
            $this->updateSupplierBalance($request->supplier_id);
            $this->createLedgerEntry($request, $payment, 'supplier');
        } elseif (in_array($payment->payment_type, ['sale', 'sale_return_with_bill', 'sale_return_without_bill'])) {
            $this->updateCustomerBalance($request->customer_id);
            $this->createLedgerEntry($request, $payment, 'customer');
        }
    }

    private function updateReference(Request $request, Payment $payment)
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
        return match ($type) {
            'purchase' => Purchase::find($id),
            'sale' => Sale::find($id),
            'purchase_return' => PurchaseReturn::find($id),
            'sale_return_with_bill', 'sale_return_without_bill' => SalesReturn::find($id),
            default => null,
        };
    }

    private function updatePaymentStatus($reference, $totalPaid, $type)
    {
        $finalTotalField = match ($type) {
            'purchase', 'purchase_return' => $type === 'purchase' ? 'final_total' : 'return_total',
            'sale_return_with_bill', 'sale_return_without_bill' => 'return_total',
            default => 'final_total',
        };

        $reference->total_paid = $totalPaid;
        $reference->payment_status = $this->getPaymentStatus($reference->$finalTotalField, $totalPaid);
        $reference->save();
    }

    private function getPaymentStatus($finalTotal, $totalPaid)
    {
        if ($finalTotal - $totalPaid <= 0) {
            return 'Paid';
        } elseif ($totalPaid < $finalTotal) {
            return 'Partial';
        } else {
            return 'Due';
        }
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
        $ledgerData = [
            'transaction_date' => $payment->payment_date,
            'reference_no' => $payment->reference_no,
            'transaction_type' => $payment->payment_type,
            'debit' => $contactType === 'supplier' ? $payment->amount : 0,
            'credit' => $contactType === 'customer' ? $payment->amount : 0,
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
            $this->restoreReference($payment);

            if (in_array($payment->payment_type, ['purchase', 'purchase_return'])) {
                $this->updateSupplierBalance($payment->supplier_id);
            } else {
                $this->updateCustomerBalance($payment->customer_id);
            }
        });

        return response()->json(['status' => 200, 'message' => 'Payment deleted and balances restored successfully.']);
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
                $this->createPayment($reference, $paidAmount, $data['payment_method'], $data['entity_type'], $entity->id, 'Bulk payment', $request);
                $remainingAmount -= $paidAmount;
            }
        }
    }

    private function handleIndividualPayments($entity, $data, $request)
    {
        if ($data['global_amount'] == 0 && isset($data['payments']) && count($data['payments']) > 0) {
            foreach ($data['payments'] as $paymentData) {
                $reference = $this->findReference($data['entity_type'] === 'supplier' ? 'purchase' : 'sale', $paymentData['reference_id']);
                $this->createPayment($reference, $paymentData['amount'], $data['payment_method'], $data['entity_type'], $entity->id, 'Individual payment', $request);
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

    private function createPayment($reference, $amount, $paymentMethod, $entityType, $entityId, $notes, $request)
    {
        $paymentData = [
            'payment_date' => Carbon::today()->format('Y-m-d'),
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'payment_type' => $entityType === 'supplier' ? 'purchase' : 'sale',
            'reference_id' => $reference->id,
            'reference_no' => $reference->reference_no,
            $entityType === 'supplier' ? 'supplier_id' : 'customer_id' => $entityId,
            'notes' => $notes,
        ];

        // Add card details if payment method is card
        if ($paymentMethod === 'card') {
            $paymentData['card_number'] = $request->card_number;
            $paymentData['card_holder_name'] = $request->card_holder_name;
            $paymentData['card_expiry_month'] = $request->card_expiry_month;
            $paymentData['card_expiry_year'] = $request->card_expiry_year;
            $paymentData['card_security_code'] = $request->card_security_code;
        }

        // Add cheque details if payment method is cheque
        if ($paymentMethod === 'cheque') {
            $paymentData['cheque_number'] = $request->cheque_number;
            $paymentData['cheque_bank_branch'] = $request->cheque_bank_branch;
            $paymentData['cheque_received_date'] = $request->cheque_received_date ? Carbon::createFromFormat('d-m-Y', $request->cheque_received_date)->format('Y-m-d') : null;
            $paymentData['cheque_valid_date'] = $request->cheque_valid_date ? Carbon::createFromFormat('d-m-Y', $request->cheque_valid_date)->format('Y-m-d') : null;
            $paymentData['cheque_given_by'] = $request->cheque_given_by;
        }

        // Add bank transfer details if payment method is bank transfer
        if ($paymentMethod === 'bank_transfer') {
            $paymentData['bank_account_number'] = $request->bank_account_number;
        }

        $payment = Payment::create($paymentData);

        $this->createLedgerEntry($request, $payment, $entityType);

        $reference->increment('total_paid', $amount);
        $reference->payment_status = $this->getPaymentStatus($reference->final_total, $reference->total_paid);
        $reference->save();
    }
}
