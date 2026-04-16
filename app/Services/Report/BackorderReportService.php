<?php

namespace App\Services\Report;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BackorderReportService
{
    public function getFilters(): array
    {
        return [
            'locations' => Cache::remember('backorder_report_locations', 3600, fn () => Location::select('id', 'name')->orderBy('name')->get()),
        ];
    }

    public function getRequestFilters(Request $request): array
    {
        return [
            'start_date' => $request->input('start_date', now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->input('end_date', now()->endOfMonth()->format('Y-m-d')),
            'location_id' => $request->input('location_id'),
            'status' => $request->input('status'),
        ];
    }

    public function calculateSummary(Request $request): array
    {
        $filters = $this->getRequestFilters($request);
        $query = $this->baseQuery($filters);

        $row = DB::query()
            ->fromSub($query, 'backorders')
            ->selectRaw('COUNT(*) as total_backorders')
            ->selectRaw('SUM(ordered_paid_qty) as ordered_paid_qty')
            ->selectRaw('SUM(ordered_free_qty) as ordered_free_qty')
            ->selectRaw('SUM(fulfilled_paid_qty) as fulfilled_paid_qty')
            ->selectRaw('SUM(fulfilled_free_qty) as fulfilled_free_qty')
            ->selectRaw('SUM(remaining_paid_qty) as remaining_paid_qty')
            ->selectRaw('SUM(remaining_free_qty) as remaining_free_qty')
            ->selectRaw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count")
            ->selectRaw("SUM(CASE WHEN status = 'partially_allocated' THEN 1 ELSE 0 END) as partial_count")
            ->selectRaw("SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_count")
            ->first();

        return [
            'total_backorders' => (int) ($row->total_backorders ?? 0),
            'ordered_paid_qty' => (float) ($row->ordered_paid_qty ?? 0),
            'ordered_free_qty' => (float) ($row->ordered_free_qty ?? 0),
            'fulfilled_paid_qty' => (float) ($row->fulfilled_paid_qty ?? 0),
            'fulfilled_free_qty' => (float) ($row->fulfilled_free_qty ?? 0),
            'remaining_paid_qty' => (float) ($row->remaining_paid_qty ?? 0),
            'remaining_free_qty' => (float) ($row->remaining_free_qty ?? 0),
            'open_count' => (int) ($row->open_count ?? 0),
            'partial_count' => (int) ($row->partial_count ?? 0),
            'fulfilled_count' => (int) ($row->fulfilled_count ?? 0),
        ];
    }

    public function getDataForDataTables(Request $request): array
    {
        $filters = $this->getRequestFilters($request);

        return $this->baseQuery($filters)
            ->orderByDesc('sales_date')
            ->orderByDesc('backorder_id')
            ->get()
            ->map(function ($row) {
                return [
                    'backorder_id' => $row->backorder_id,
                    'sale_id' => $row->sale_id,
                    'invoice_no' => $row->invoice_no,
                    'sales_date' => $row->sales_date,
                    'location_name' => $row->location_name,
                    'product_name' => $row->product_name,
                    'sku' => $row->sku,
                    'brand_name' => $row->brand_name ?? 'N/A',
                    'status' => $row->status,
                    'ordered_paid_qty' => (float) $row->ordered_paid_qty,
                    'ordered_free_qty' => (float) $row->ordered_free_qty,
                    'fulfilled_paid_qty' => (float) $row->fulfilled_paid_qty,
                    'fulfilled_free_qty' => (float) $row->fulfilled_free_qty,
                    'remaining_paid_qty' => (float) $row->remaining_paid_qty,
                    'remaining_free_qty' => (float) $row->remaining_free_qty,
                    'reserved_paid_qty' => (float) $row->reserved_paid_qty,
                    'reserved_free_qty' => (float) $row->reserved_free_qty,
                    'released_paid_qty' => (float) $row->released_paid_qty,
                    'released_free_qty' => (float) $row->released_free_qty,
                    'allocation_count' => (int) $row->allocation_count,
                ];
            })
            ->values()
            ->all();
    }

    private function baseQuery(array $filters)
    {
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        $query = DB::table('stock_backorders as sb')
            ->join('sales_products as sp', 'sb.sale_product_id', '=', 'sp.id')
            ->join('sales as s', 'sp.sale_id', '=', 's.id')
            ->join('products as p', 'sp.product_id', '=', 'p.id')
            ->join('locations as l', 'sb.location_id', '=', 'l.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin('stock_backorder_allocations as sba', 'sba.stock_backorder_id', '=', 'sb.id')
            ->whereBetween('s.sales_date', [$startDateTime, $endDateTime])
            ->select([
                'sb.id as backorder_id',
                'sb.status',
                'sb.ordered_paid_qty',
                'sb.ordered_free_qty',
                'sb.fulfilled_paid_qty',
                'sb.fulfilled_free_qty',
                DB::raw('(sb.ordered_paid_qty - sb.fulfilled_paid_qty) as remaining_paid_qty'),
                DB::raw('(sb.ordered_free_qty - sb.fulfilled_free_qty) as remaining_free_qty'),
                'sp.id as sale_product_id',
                's.id as sale_id',
                's.invoice_no',
                's.sales_date',
                'l.name as location_name',
                'p.product_name',
                'p.sku',
                'b.name as brand_name',
                DB::raw("SUM(CASE WHEN sba.allocation_type = 'purchase_reservation' THEN sba.allocated_paid_qty ELSE 0 END) as reserved_paid_qty"),
                DB::raw("SUM(CASE WHEN sba.allocation_type = 'purchase_reservation' THEN sba.allocated_free_qty ELSE 0 END) as reserved_free_qty"),
                DB::raw("SUM(CASE WHEN sba.allocation_type = 'reservation_release' THEN ABS(sba.allocated_paid_qty) ELSE 0 END) as released_paid_qty"),
                DB::raw("SUM(CASE WHEN sba.allocation_type = 'reservation_release' THEN ABS(sba.allocated_free_qty) ELSE 0 END) as released_free_qty"),
                DB::raw('COUNT(DISTINCT sba.id) as allocation_count'),
            ])
            ->groupBy(
                'sb.id',
                'sb.status',
                'sb.ordered_paid_qty',
                'sb.ordered_free_qty',
                'sb.fulfilled_paid_qty',
                'sb.fulfilled_free_qty',
                'sp.id',
                's.id',
                's.invoice_no',
                's.sales_date',
                'l.name',
                'p.product_name',
                'p.sku',
                'b.name'
            );

        if (!empty($filters['location_id'])) {
            $query->where('sb.location_id', $filters['location_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('sb.status', $filters['status']);
        }

        return $query;
    }
}
