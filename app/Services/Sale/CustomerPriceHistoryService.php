<?php

namespace App\Services\Sale;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerPriceHistoryService
{
    /**
     * Retrieve the last N purchase prices for a customer/product pair.
     *
     * @return array{
     *   has_previous_purchases: bool,
     *   previous_prices: Collection,
     *   average_price: float|null,
     *   last_price: float|null,
     *   last_purchase_date: string|null
     * }
     */
    public function get(int $customerId, int $productId, int $limit = 3): array
    {
        $rows = collect(DB::select('
            SELECT sp.price, sp.quantity, s.created_at, s.invoice_no
            FROM sales_products sp
            JOIN sales s ON sp.sale_id = s.id
            WHERE s.customer_id = ? AND sp.product_id = ? AND s.status = \'final\'
            ORDER BY s.created_at DESC
            LIMIT ' . $limit,
            [$customerId, $productId]
        ))->map(fn ($row) => [
            'sale_date'  => Carbon::parse($row->created_at)->format('Y-m-d'),
            'invoice_no' => $row->invoice_no,
            'unit_price' => (float) $row->price,
            'quantity'   => (float) $row->quantity,
            'total'      => (float) $row->price * (float) $row->quantity,
        ]);

        return [
            'has_previous_purchases' => $rows->isNotEmpty(),
            'previous_prices'        => $rows,
            'average_price'          => $rows->isNotEmpty() ? $rows->avg('unit_price') : null,
            'last_price'             => $rows->first()['unit_price'] ?? null,
            'last_purchase_date'     => $rows->first()['sale_date']  ?? null,
        ];
    }
}
