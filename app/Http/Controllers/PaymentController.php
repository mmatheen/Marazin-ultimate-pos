<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function storeOrUpdatePayment(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'payable_type' => 'required|string|in:Purchase,Sale,PurchaseReturn,SalesReturn',
            'payable_id' => 'required|integer',
            'entity_id' => 'required|integer',
            'entity_type' => 'required|string|in:Supplier,Customer',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_account' => 'nullable|string',
            'payment_note' => 'nullable|string',
            'paid_date' => 'nullable|date_format:d-m-Y', // Expecting d-m-Y format from the request
            // Additional fields for credit card payments
            'card_number' => 'nullable|string',
            'card_holder_name' => 'nullable|string',
            'card_type' => 'nullable|string',
            'expiry_month' => 'nullable|string',
            'expiry_year' => 'nullable|string',
            'security_code' => 'nullable|string',
            // Additional fields for cheque payments
            'cheque_number' => 'nullable|string',
            'bank_branch' => 'nullable|string',
            // Additional fields for bank transfer payments
            'bank_account_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        // Find the payable entity
        $payable = match ($request->payable_type) {
            'Purchase' => Purchase::find($request->payable_id),
            'Sale' => Sale::find($request->payable_id),
            'PurchaseReturn' => PurchaseReturn::find($request->payable_id),
            'SalesReturn' => SalesReturn::find($request->payable_id),
            default => null,
        };

        if (!$payable) {
            return response()->json(['status' => 400, 'message' => 'Invalid payable entity.']);
        }

        // Convert the date to MySQL format (Y-m-d) if provided
        $paymentDate = $request->paid_date ? Carbon::createFromFormat('d-m-Y', $request->paid_date)->format('Y-m-d') : null;

        // Calculate due amount after payment
        $dueAmount = $payable->final_total - $payable->payments()->sum('amount') - $request->amount;

        // Create the payment record
        Payment::create([
            'payable_type' => $request->payable_type,
            'payable_id' => $request->payable_id,
            'entity_id' => $request->entity_id,
            'entity_type' => $request->entity_type,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_account' => $request->payment_account,
            'payment_date' => $paymentDate, // Fixed date format
            'payment_note' => $request->payment_note,
            'due_amount' => $dueAmount,
            'card_number' => $request->card_number,
            'card_holder_name' => $request->card_holder_name,
            'card_type' => $request->card_type,
            'expiry_month' => $request->expiry_month,
            'expiry_year' => $request->expiry_year,
            'security_code' => $request->security_code,
            'cheque_number' => $request->cheque_number,
            'bank_branch' => $request->bank_branch,
            'bank_account_number' => $request->bank_account_number,
        ]);

        // Update the entity's balance
        $entity = $request->entity_type === 'Supplier'
            ? Supplier::find($request->entity_id)
            : Customer::find($request->entity_id);

        if ($entity) {
            $entity->updateBalance(-$request->amount);
        }

        // Update the payable entity's payment status
        $payable->updatePaymentStatus();

        return response()->json(['status' => 200, 'message' => 'Payment recorded successfully!']);
    }
}
