<?php

namespace App\Services\Sale;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\User;

class SaleReceiptService
{
    /** Receipt view names keyed by layout slug */
    private const LAYOUT_MAP = [
        '80mm'             => 'sell.receipt',
        'a4'               => 'sell.receipt_a4',
        'dot_matrix'       => 'sell.receipt_dot_matrix',
        'dot_matrix_full'  => 'sell.receipt_dot_matrix_full',
    ];

    private const DEFAULT_VIEW = 'sell.receipt';

    /**
     * Render the receipt HTML for a given sale.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getHtml(int $saleId, ?string $layout): string
    {
        $sale     = Sale::findOrFail($saleId);
        $customer = Customer::withoutLocationScope()->findOrFail($sale->customer_id);
        $products = SalesProduct::with(['product', 'imeis', 'batch'])->where('sale_id', $saleId)->get();
        $payments = Payment::where('reference_id', $saleId)->where('payment_type', 'sale')->get();
        $user     = User::find($sale->user_id);
        $location = $sale->location;

        $customerOutstandingBalance = 0;
        if ($customer && $customer->id != 1) {
            $customerOutstandingBalance = $customer->calculateBalanceFromLedger();
        }

        $viewData = [
            'sale'                         => $sale,
            'customer'                     => $customer,
            'products'                     => $products,
            'payments'                     => $payments,
            'total_discount'               => 0,
            'amount_given'                 => $sale->amount_given,
            'balance_amount'               => $sale->balance_amount,
            'customer_outstanding_balance' => $customerOutstandingBalance,
            'user'                         => $user,
            'location'                     => $location,
            'receiptConfig'                => $location ? $location->getReceiptConfig() : [],
        ];

        $view = $this->resolveView($layout, $location);

        return view($view, $viewData)->render();
    }

    private function resolveView(?string $layout, $location): string
    {
        if ($layout) {
            return self::LAYOUT_MAP[$layout] ?? self::DEFAULT_VIEW;
        }

        return $location ? $location->getReceiptViewName() : self::DEFAULT_VIEW;
    }
}
