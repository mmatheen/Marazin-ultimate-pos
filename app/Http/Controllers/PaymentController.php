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
            $paymentData = [
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

            if ($paymentId) {
                $payment = Payment::find($paymentId);
                $oldAmount = $payment ? $payment->amount : 0;
                if ($payment) {
                    $payment->update($paymentData);
                    $this->updatePurchaseOrSale($request, $payment, $oldAmount);
                }
            } else {
                $payment = Payment::create($paymentData);
                $this->updatePurchaseOrSale($request, $payment);
            }

            Transaction::updateOrCreate(
                ['reference_id' => $payment->id, 'transaction_type' => $payment->payment_type],
                ['transaction_date' => $payment->payment_date, 'amount' => $payment->amount]
            );

            if ($request->payment_type === 'purchase' || $request->payment_type === 'purchase_return') {
                $this->updateSupplierBalance($request->supplier_id);
            } else if ($request->payment_type === 'sale' || $request->payment_type === 'sale_return_with_bill' || $request->payment_type === 'sale_return_without_bill') {
                $this->updateCustomerBalance($request->customer_id);
            }
        });

        return response()->json(['status' => 200, 'message' => 'Payment ' . ($paymentId ? 'updated' : 'added') . ' successfully.']);
    }

    private function validateReference($request)
    {
        if ($request->payment_type === 'purchase' || $request->payment_type === 'purchase_return') {
            $purchase = Purchase::find($request->reference_id);
            return $purchase && $purchase->supplier_id == $request->supplier_id;
        } elseif ($request->payment_type === 'sale' || $request->payment_type === 'sale_return_with_bill' || $request->payment_type === 'sale_return_without_bill') {
            $sale = Sale::find($request->reference_id);
            return $sale && $sale->customer_id == $request->customer_id;
        }
        return false;
    }

    private function updatePurchaseOrSale($request, $payment, $oldAmount = 0)
    {
        if ($request->payment_type === 'purchase') {
            $purchase = Purchase::find($request->reference_id);
            if ($purchase) {
                $totalPaid = $purchase->payments ? $purchase->payments->sum('amount') - $oldAmount + $request->amount : $request->amount;
                $purchase->total_paid = $totalPaid;
                $purchase->updateTotalDue();
                $this->updatePurchasePaymentStatus($purchase, $totalPaid);
            }
        } elseif ($request->payment_type === 'sale') {
            $sale = Sale::find($request->reference_id);
            if ($sale) {
                $totalPaid = $sale->payments ? $sale->payments->sum('amount') - $oldAmount + $request->amount : $request->amount;
                $sale->total_paid = $totalPaid;
                $sale->updateTotalDue();
                $this->updateSalePaymentStatus($sale, $totalPaid);
            }
        } elseif ($request->payment_type === 'purchase_return') {
            $purchaseReturn = PurchaseReturn::find($request->reference_id);
            if ($purchaseReturn) {
                $totalPaid = $purchaseReturn->payments ? $purchaseReturn->payments->sum('amount') - $oldAmount + $request->amount : $request->amount;
                $purchaseReturn->total_paid = $totalPaid;
                $purchaseReturn->updateTotalDue();
                $this->updatePurchaseReturnPaymentStatus($purchaseReturn, $totalPaid);
            }
        } elseif ($request->payment_type === 'sale_return_with_bill' || $request->payment_type === 'sale_return_without_bill') {
            $saleReturn = SalesReturn::find($request->reference_id);
            if ($saleReturn) {
                $totalPaid = $saleReturn->payments ? $saleReturn->payments->sum('amount') - $oldAmount + $request->amount : $request->amount;
                $saleReturn->total_paid = $totalPaid;
                $saleReturn->updateTotalDue();
                $this->updateSaleReturnPaymentStatus($saleReturn, $totalPaid);
            }
        }
    }

    private function updatePurchasePaymentStatus($purchase, $totalPaid)
    {
        if ($purchase->final_total - $totalPaid <= 0) {
            $purchase->payment_status = 'Paid';
        } elseif ($totalPaid < $purchase->final_total) {
            $purchase->payment_status = 'Partial';
        } else {
            $purchase->payment_status = 'Due';
        }

        $purchase->save();
    }

    private function updateSalePaymentStatus($sale, $totalPaid)
    {
        if ($sale->final_total - $totalPaid <= 0) {
            $sale->payment_status = 'Paid';
        } elseif ($totalPaid < $sale->final_total) {
            $sale->payment_status = 'Partial';
        } else {
            $sale->payment_status = 'Due';
        }

        $sale->save();
    }

    private function updatePurchaseReturnPaymentStatus($purchaseReturn, $totalPaid)
    {
        if ($purchaseReturn->final_total - $totalPaid <= 0) {
            $purchaseReturn->payment_status = 'Paid';
        } elseif ($totalPaid < $purchaseReturn->final_total) {
            $purchaseReturn->payment_status = 'Partial';
        } else {
            $purchaseReturn->payment_status = 'Due';
        }

        $purchaseReturn->save();
    }

    private function updateSaleReturnPaymentStatus($saleReturn, $totalPaid)
    {
        if ($saleReturn->final_total - $totalPaid <= 0) {
            $saleReturn->payment_status = 'Paid';
        } elseif ($totalPaid < $saleReturn->final_total) {
            $saleReturn->payment_status = 'Partial';
        } else {
            $saleReturn->payment_status = 'Due';
        }

        $saleReturn->save();
    }

    private function updateSupplierBalance($supplierId)
    {
        $supplier = Supplier::find($supplierId);

        if ($supplier) {
            $totalPurchases = Purchase::where('supplier_id', $supplierId)->sum('final_total');
            $totalPayments = Payment::where('supplier_id', $supplierId)->where('payment_type', 'purchase')->sum('amount');

            $supplier->current_balance = $supplier->opening_balance + $totalPurchases - $totalPayments;
            $supplier->save();
        }
    }

    private function updateCustomerBalance($customerId)
    {
        $customer = Customer::find($customerId);

        if ($customer) {
            $totalSales = Sale::where('customer_id', $customerId)->sum('final_total');
            $totalPayments = Payment::where('customer_id', $customerId)->where('payment_type', 'sale')->sum('amount');

            $customer->current_balance = $customer->opening_balance + $totalSales - $totalPayments;
            $customer->save();
        }
    }

    private function getTotalDue($request, $paymentId = null)
    {
        $totalPaid = $paymentId ? Payment::where('id', '!=', $paymentId)->where('reference_id', $request->reference_id)->where('payment_type', $request->payment_type)->sum('amount') : 0;

        if ($request->payment_type === 'purchase') {
            $purchase = Purchase::find($request->reference_id);
            return $purchase ? $purchase->final_total - $totalPaid : 0;
        } elseif ($request->payment_type === 'sale') {
            $sale = Sale::find($request->reference_id);
            return $sale ? $sale->final_total - $totalPaid : 0;
        } elseif ($request->payment_type === 'purchase_return') {
            $purchaseReturn = PurchaseReturn::find($request->reference_id);
            return $purchaseReturn ? $purchaseReturn->final_total - $totalPaid : 0;
        } elseif ($request->payment_type === 'sale_return_with_bill' || $request->payment_type === 'sale_return_without_bill') {
            $saleReturn = SalesReturn::find($request->reference_id);
            return $saleReturn ? $saleReturn->final_total - $totalPaid : 0;
        }
        return 0;
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        return response()->json(['status' => 200, 'data' => $payment]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $oldAmount = $payment->amount;

            $transaction = Transaction::where('reference_id', $payment->id)->where('transaction_type', $payment->payment_type)->first();
            $transaction?->delete();

            $payment->delete();

            if ($payment->payment_type === 'purchase' || $payment->payment_type === 'purchase_return') {
                $this->restorePurchaseOrSale($payment, $oldAmount, 'purchase');
            } else if ($payment->payment_type === 'sale' || $payment->payment_type === 'sale_return_with_bill' || $payment->payment_type === 'sale_return_without_bill') {
                $this->restorePurchaseOrSale($payment, $oldAmount, 'sale');
            }
        });

        return response()->json(['status' => 200, 'message' => 'Payment deleted and balances restored successfully.']);
    }

    private function restorePurchaseOrSale($payment, $oldAmount, $type)
    {
        if ($type === 'purchase') {
            $purchase = Purchase::find($payment->reference_id);
            if ($purchase) {
                $totalPaid = $purchase->payments ? $purchase->payments->sum('amount') - $oldAmount : 0;
                $purchase->total_paid = $totalPaid;
                $purchase->updateTotalDue();
                $this->updatePurchasePaymentStatus($purchase, $totalPaid);
            }
        } elseif ($type === 'sale') {
            $sale = Sale::find($payment->reference_id);
            if ($sale) {
                $totalPaid = $sale->payments ? $sale->payments->sum('amount') - $oldAmount : 0;
                $sale->total_paid = $totalPaid;
                $sale->updateTotalDue();
                $this->updateSalePaymentStatus($sale, $totalPaid);
            }
        }
    }
}
