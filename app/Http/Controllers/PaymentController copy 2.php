<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\Supplier;
use App\Models\Customer;
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
            'payment_type' => 'required|string|in:purchase,sale,purchase_return,sale_return_with_bill,sale_return_without_bill',
            'reference_id' => 'required|integer',
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

        // Validate that the reference ID matches the supplier ID or customer ID
        if (!$this->validateReference($request)) {
            return response()->json(['status' => 400, 'message' => 'Reference ID does not match the specified supplier or customer.']);
        }

        $totalDue = $this->getTotalDue($request, $paymentId);
        if ($request->amount > $totalDue) {
            return response()->json(['status' => 400, 'message' => 'Payment amount exceeds the total due.']);
        }

        DB::transaction(function () use ($request, $paymentId) {
            $paymentData = $this->preparePaymentData($request);

            if ($paymentId) {
                $payment = Payment::find($paymentId);
                $oldAmount = $payment ? $payment->amount : 0;
                if ($payment) {
                    $payment->update($paymentData);
                    $this->updateReference($request, $payment, $oldAmount);
                }
            } else {
                $payment = Payment::create($paymentData);
                $this->updateReference($request, $payment);
            }

            if (in_array($request->payment_type, ['purchase', 'purchase_return'])) {
                $this->updateSupplierBalance($request->supplier_id);
            } else {
                $this->updateCustomerBalance($request->customer_id);
            }
        });

        return response()->json(['status' => 200, 'message' => 'Payment ' . ($paymentId ? 'updated' : 'added') . ' successfully.']);
    }

    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $oldAmount = $payment->amount;

            $transaction = Transaction::where('reference_id', $payment->id)->where('transaction_type', $payment->payment_type)->first();
            $transaction?->delete();

            $payment->delete();

            $this->restoreReference($payment, $oldAmount);

            if (in_array($payment->payment_type, ['purchase', 'purchase_return'])) {
                $this->updateSupplierBalance($payment->supplier_id);
            } else {
                $this->updateCustomerBalance($payment->customer_id);
            }
        });

        return response()->json(['status' => 200, 'message' => 'Payment deleted and balances restored successfully.']);
    }

    private function validateReference($request)
    {
        $reference = $this->findReference($request->payment_type, $request->reference_id);
        if (!$reference) {
            return false;
        }

        if (in_array($request->payment_type, ['purchase', 'purchase_return'])) {
            return $reference->supplier_id == $request->supplier_id;
        }

        return $reference->customer_id == $request->customer_id;
    }

    private function updateReference($request, $payment, $oldAmount = 0)
    {
        $reference = $this->findReference($request->payment_type, $request->reference_id);
        if ($reference) {
            $totalPaid = $reference->payments ? $reference->payments->sum('amount') - $oldAmount + $request->amount : $request->amount;
            $reference->total_paid = $totalPaid;
            $reference->save(); // Save the total_paid without updating the generated column
            $this->updatePaymentStatus($reference, $totalPaid, $request->payment_type);
        }
    }

    private function restoreReference($payment, $oldAmount)
    {
        $reference = $this->findReference($payment->payment_type, $payment->reference_id);
        if ($reference) {
            $totalPaid = $reference->payments ? $reference->payments->sum('amount') - $oldAmount : 0;
            $reference->total_paid = $totalPaid;
            $reference->save(); // Save the total_paid without updating the generated column
            $this->updatePaymentStatus($reference, $totalPaid, $payment->payment_type);
        }
    }

    private function preparePaymentData($request)
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
            'cheque_received_date' => $request->cheque_received_date,
            'cheque_valid_date' => $request->cheque_valid_date,
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
                throw new \Exception("Unknown payment type: $type");
        }
    }


    private function updatePaymentStatus($reference, $totalPaid, $type)
    {
        if (in_array($type, ['purchase', 'purchase_return'])) {
            $finalTotalField = $type === 'purchase' ? 'final_total' : 'return_total';
        } else {
            $finalTotalField = 'final_total';
        }

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
            $totalPayments = Payment::where('supplier_id', $supplierId)->whereIn('payment_type', ['purchase', 'purchase_return'])->sum('amount');

            $supplier->current_balance = $supplier->opening_balance + $totalPurchases - $totalPayments;
            $supplier->save();
        }
    }

    private function updateCustomerBalance($customerId)
    {
        $customer = Customer::find($customerId);

        if ($customer) {
            $totalSales = Sale::where('customer_id', $customerId)->sum('final_total');
            $totalPayments = Payment::where('customer_id', $customerId)->whereIn('payment_type', ['sale', 'sale_return_with_bill', 'sale_return_without_bill'])->sum('amount');

            $customer->current_balance = $customer->opening_balance + $totalSales - $totalPayments;
            $customer->save();
        }
    }

    private function getTotalDue($request, $paymentId = null)
    {
        $totalPaid = $paymentId ? Payment::where('id', '!=', $paymentId)->where('reference_id', $request->reference_id)->where('payment_type', $request->payment_type)->sum('amount') : 0;

        $reference = $this->findReference($request->payment_type, $request->reference_id);
        if (!$reference) {
            return 0;
        }

        $finalTotalField = in_array($request->payment_type, ['purchase', 'purchase_return']) ? 'return_total' : 'final_total';
        return $reference->$finalTotalField - $totalPaid;
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
}
