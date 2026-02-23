<?php

namespace App\Services\Sale;

use App\Models\Customer;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaleSaveService
{
    /**
     * Capture pre-save change flags, fill all fields onto the Sale model,
     * persist it (with error handling), and refresh it from the DB.
     *
     * @param  Sale    $sale           New Sale() for create, existing for update
     * @param  Request $request        Incoming HTTP request
     * @param  bool    $isUpdate       True when editing an existing sale
     * @param  array   $amounts        Output of SaleAmountCalculator::calculate()
     * @param  array   $numberData     Output of SaleInvoiceNumberService::resolve()
     * @param  string  $newStatus      Target sale status
     * @param  string  $transactionType 'invoice' | 'sale_order' | …
     * @param  string  $referenceNo    Reference number for this sale
     * @return array{
     *   old_customer_id: int|null,
     *   old_final_total: float|null,
     *   customer_changed: bool,
     *   financial_data_changed: bool
     * }
     */
    public function fillAndSave(
        Sale    $sale,
        Request $request,
        bool    $isUpdate,
        array   $amounts,
        array   $numberData,
        string  $newStatus,
        string  $transactionType,
        string  $referenceNo
    ): array {
        // ----- Capture pre-save state (needed by SaleLedgerManager) -----
        $oldCustomerId = $isUpdate ? $sale->getOriginal('customer_id') : null;
        $oldFinalTotal = $isUpdate ? $sale->getOriginal('final_total')  : null;
        $oldSubtotal   = $isUpdate ? $sale->getOriginal('subtotal')     : null;
        $oldDiscount   = $isUpdate ? $sale->getOriginal('discount_amount') : null;

        $customerChanged = $isUpdate && ($oldCustomerId != $request->customer_id);

        // Check if financial data changed (for ledger update decision)
        $financialDataChanged = $isUpdate && (
            abs($oldFinalTotal - $amounts['final_total']) > 0.01 ||
            abs($oldSubtotal   - $amounts['subtotal'])    > 0.01 ||
            abs($oldDiscount   - $amounts['discount'])    > 0.01
        );

        // ----- Fill all sale fields -----
        $sale->fill([
            'customer_id'            => $request->customer_id,
            'location_id'            => $request->location_id,
            'sales_date'             => Carbon::now('Asia/Colombo')->format('Y-m-d H:i:s'),
            'status'                 => $newStatus,
            'invoice_no'             => $numberData['invoice_no'],
            'reference_no'           => $referenceNo,
            'subtotal'               => $amounts['subtotal'],
            'final_total'            => $amounts['final_total'],
            'discount_type'          => $request->discount_type,
            'discount_amount'        => $amounts['discount'],
            'user_id'                => $isUpdate ? $sale->user_id : auth()->id(), // Keep original creator
            'updated_by'             => $isUpdate ? auth()->id() : null,           // Track who edited
            'total_paid'             => $amounts['total_paid'],
            'total_due'              => $amounts['total_due'],
            'amount_given'           => $amounts['amount_given'],
            'balance_amount'         => $amounts['balance_amount'],
            'sale_notes'             => $request->sale_notes,
            // Sale Order fields
            'transaction_type'       => $transactionType,
            'order_number'           => $numberData['order_number'],
            'order_date'             => $transactionType === 'sale_order' ? now() : null,
            'expected_delivery_date' => $request->expected_delivery_date,
            'order_status'           => $numberData['order_status'],
            'order_notes'            => $request->order_notes,
            // Shipping fields
            'shipping_details'       => $request->shipping_details,
            'shipping_address'       => $request->shipping_address,
            'shipping_charges'       => $request->shipping_charges ?? 0,
            'shipping_status'        => $request->shipping_status ?? 'pending',
            'delivered_to'           => $request->delivered_to,
            'delivery_person'        => $request->delivery_person,
        ]);

        // ----- Persist with error handling -----
        try {
            $saveResult = $sale->save();
            if (!$saveResult) {
                throw new \Exception("Sale save operation returned false");
            }
        } catch (\Exception $e) {
            Log::error('CRITICAL: Sale save operation failed', [
                'sale_id'          => $sale->id ?? 'NEW',
                'customer_id'      => $request->customer_id,
                'error'            => $e->getMessage(),
                'error_code'       => $e->getCode(),
                'sale_attributes'  => $sale->getAttributes(),
            ]);

            // Return specific error for database constraint violations
            if (str_contains($e->getMessage(), 'foreign key constraint') ||
                str_contains($e->getMessage(), 'Integrity constraint violation')) {
                throw new \Exception("Invalid customer ID: Customer with ID {$request->customer_id} does not exist.");
            }

            throw $e; // Re-throw for other errors
        }


        return [
            'old_customer_id'      => $oldCustomerId,
            'old_final_total'      => $oldFinalTotal,
            'customer_changed'     => $customerChanged,
            'financial_data_changed' => $financialDataChanged,
        ];
    }
}
