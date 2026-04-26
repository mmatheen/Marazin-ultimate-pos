<?php

namespace App\Services\Sale;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\User;
use App\Scopes\LocationScope;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    public function getHtml(int $saleId, ?string $layout, bool $bypassLocationScope = false): string
    {
        $saleQuery = $bypassLocationScope
            ? Sale::withoutLocationScope()
            : Sale::query();

        $sale = $saleQuery->findOrFail($saleId);

        $authUser = auth()->user();

        // When bypassing the session-selected location scope (used by POS recent-transactions),
        // still restrict the receipt to locations the current user can access.
        if ($bypassLocationScope && ! LocationScope::userBypassesLocationScope($authUser)) {
            if (! $authUser instanceof User) {
                throw (new ModelNotFoundException)->setModel(Sale::class, [$saleId]);
            }

            $authUser->loadMissing('locations');
            $locationIds = $authUser->locations->pluck('id')->all();
            if ($locationIds === [] || !in_array((int) $sale->location_id, array_map('intval', $locationIds), true)) {
                throw (new ModelNotFoundException)->setModel(Sale::class, [$saleId]);
            }
        }

        if (
            $authUser instanceof AuthorizableContract
            && $authUser->can('view own sales')
            && ! $authUser->can('view all sales')
        ) {
            if ((int) $sale->user_id !== (int) $authUser->id) {
                throw (new ModelNotFoundException)->setModel(Sale::class, [$saleId]);
            }
        }

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
