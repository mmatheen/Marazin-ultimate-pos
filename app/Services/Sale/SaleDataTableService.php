<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;

class SaleDataTableService
{
    /**
     * MySQL does not allow OFFSET without LIMIT. DataTables "All" uses length=-1 (no user page size);
     * we use a large LIMIT so the full filtered set is returned in one query.
     */
    private const MAX_ROWS_ALL = 2147483647;

    /**
     * Build the DataTable response payload for the All Sales list.
     *
     * @return array{draw:int, recordsTotal:int, recordsFiltered:int, data:array}
     */
    public function getData(Request $request, ?User $user): array
    {
        $start = max(0, (int) $request->input('start', 0));
        $draw  = (int) $request->input('draw', 1);
        $search = $request->input('search.value', '');

        // DataTables sends length=-1 for "Show All". Normalize so -1 is never confused with other types.
        $rawLength = $request->input('length', 10);
        if ($rawLength === '-1' || $rawLength === -1) {
            $lengthInt = -1;
        } elseif (is_numeric($rawLength)) {
            $lengthInt = (int) $rawLength;
        } else {
            $lengthInt = 10;
        }

        // Base query – bypass location global scope so admins see all sales
        $baseQuery = Sale::withoutGlobalScopes()
            ->where('status', 'final')
            ->where('transaction_type', '!=', 'sale_order')
            ->where(function ($q) {
                $q->where('transaction_type', 'invoice')
                  ->orWhereNull('transaction_type');
            });

        // Restrict to own sales when user lacks 'view all sales'
        if ($user && $user->can('view own sales') && ! $user->can('view all sales')) {
            $baseQuery->where('user_id', $user->id);
        }

        $recordsTotal = (clone $baseQuery)->count();

        if ($recordsTotal === 0) {
            return [
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ];
        }

        $query = (clone $baseQuery)->select([
            'id', 'invoice_no', 'sales_date', 'customer_id', 'user_id', 'location_id',
            'final_total', 'total_paid', 'total_due', 'payment_status', 'status',
            'created_at', 'updated_at', 'transaction_type', 'sale_notes',
        ])->with([
            'customer' => fn ($q) => $q->withoutGlobalScopes()
                                       ->select('id', 'first_name', 'last_name', 'mobile_no'),
            'user:id,full_name',
            'location:id,name',
            'payments:id,reference_id,amount,payment_method,payment_date,notes',
        ])->withCount('products as total_items');

        // --- Search ---
        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('final_total', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($c) =>
                      $c->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name',   'like', "%{$search}%")
                        ->orWhere('mobile_no',   'like', "%{$search}%")
                  );
            });
        }

        // --- Filters ---
        if (filled($request->location_id))    $query->where('location_id',    $request->location_id);
        if (filled($request->customer_id))    $query->where('customer_id',    $request->customer_id);
        if (filled($request->user_id))        $query->where('user_id',        $request->user_id);
        if (filled($request->payment_status)) $query->where('payment_status', $request->payment_status);

        if (filled($request->payment_method)) {
            $query->whereHas('payments', fn ($p) =>
                $p->where('payment_method', $request->payment_method)
            );
        }

        if (filled($request->start_date)) $query->whereDate('sales_date', '>=', $request->start_date);
        if (filled($request->end_date))   $query->whereDate('sales_date', '<=', $request->end_date);

        $recordsFiltered = (clone $query)->count();

        if ($recordsFiltered === 0) {
            return [
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => 0,
                'data'            => [],
            ];
        }

        // MySQL does not allow OFFSET without LIMIT. Always set take() to a positive integer.
        if ($lengthInt === -1) {
            $take = min(self::MAX_ROWS_ALL, $recordsFiltered);
        } else {
            $take = max(1, $lengthInt);
        }

        $sales = $query->orderByDesc('created_at')->skip($start)->take($take)->get();

        return [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $this->formatRows($sales),
        ];
    }

    private function formatRows($sales): array
    {
        return $sales->map(function ($sale) {
            $customerName = $sale->customer
                ? trim(($sale->customer->first_name ?? '') . ' ' . ($sale->customer->last_name ?? ''))
                : '';

            $paymentMethods = $sale->payments
                ->map(fn ($p) => [
                    'id'     => $p->id,
                    'method' => ucfirst($p->payment_method),
                    'amount' => (float) $p->amount,
                    'date'   => $p->payment_date,
                    'notes'  => $p->notes,
                ])->all();

            return [
                'id'             => $sale->id,
                'invoice_no'     => $sale->invoice_no,
                'sales_date'     => $sale->sales_date,
                'sale_notes'     => $sale->sale_notes,
                'customer'       => $sale->customer ? [
                    'id'         => $sale->customer->id,
                    'first_name' => $sale->customer->first_name,
                    'last_name'  => $sale->customer->last_name,
                    'name'       => $customerName,
                    'phone'      => $sale->customer->mobile_no,
                ] : null,
                'user'           => $sale->user     ? ['id' => $sale->user->id,     'name' => $sale->user->full_name]   : null,
                'location'       => $sale->location ? ['id' => $sale->location->id, 'name' => $sale->location->name]   : null,
                'payments'       => $paymentMethods,
                'final_total'    => (float) $sale->final_total,
                'total_paid'     => (float) $sale->total_paid,
                'total_due'      => (float) $sale->total_due,
                'payment_status' => $sale->payment_status,
                'status'         => $sale->status,
                'total_items'    => (int) $sale->total_items,
                'created_at'     => $sale->created_at,
                'updated_at'     => $sale->updated_at,
            ];
        })->all();
    }
}
