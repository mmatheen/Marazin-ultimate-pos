<?php
namespace App\Services\Report;

use App\Models\Brand;
use App\Models\Location;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * StockHistoryService  uses a single JOIN query instead of loading
 * the full Product  Batch  LocationBatch  Location chain.
 *
 * Before: ~5 nested ORM calls per product row, all PHP-side filtered.
 * After:  1 SQL query for data, 1 SQL query for summary.
 */
class StockHistoryService
{
    //  Dropdown filters (cached) 

    public function getFilters(): array
    {
        return [
            'locations'     => Cache::remember('locations_list',       3600, fn() => Location::select('id', 'name')->get()),
            'categories'    => Cache::remember('main_categories_list', 3600, fn() => \App\Models\MainCategory::select('id', 'mainCategoryName')->get()),
            'subCategories' => Cache::remember('sub_categories_list',  3600, fn() => \App\Models\SubCategory::select('id', 'subCategoryname', 'main_category_id')->get()),
            'brands'        => Cache::remember('brands_list',          3600, fn() => Brand::select('id', 'name')->get()),
            'units'         => Cache::remember('units_list',           3600, fn() => \App\Models\Unit::select('id', 'name', 'short_name')->get()),
        ];
    }

    //  DataTables AJAX response 

    public function getDataForDataTables(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = DB::table('products as p')
            ->join('batches as b',          'b.product_id',  '=', 'p.id')
            ->join('location_batches as lb', 'lb.batch_id',  '=', 'b.id')
            ->join('locations as l',        'l.id',          '=', 'lb.location_id')
            ->leftJoin('main_categories as mc', 'mc.id',     '=', 'p.main_category_id')
            ->select([
                'p.id         as product_id',
                'p.sku',
                'p.product_name',
                'b.batch_no',
                'b.unit_cost',
                'b.retail_price',
                'b.expiry_date',
                DB::raw('(lb.qty + lb.free_qty) as current_stock'),
                'l.name       as location_name',
                'mc.mainCategoryName as category',
            ]);

        $this->applyJoinFilters($query, $request);

        $rows      = $query->orderBy('p.product_name')->get();
        $stockData = $rows->map(function ($row) {
            $currentStock = (float) $row->current_stock;
            $unitCost     = (float) $row->unit_cost;
            $retailPrice  = (float) $row->retail_price;
            $byPurchase   = $currentStock * $unitCost;
            $bySale       = $currentStock * $retailPrice;

            return [
                'product_id'           => $row->product_id,
                'sku'                  => $row->sku     ?? 'N/A',
                'product_name'         => $row->product_name ?? 'Unknown Product',
                'batch_no'             => $row->batch_no ?? 'N/A',
                'category'             => $row->category  ?? 'N/A',
                'location'             => $row->location_name ?? 'Unknown Location',
                'unit_cost'            => $unitCost,
                'unit_selling_price'   => $retailPrice,
                'current_stock'        => $currentStock,
                'stock_value_purchase' => $byPurchase,
                'stock_value_sale'     => $bySale,
                'potential_profit'     => $retailPrice > 0 ? $bySale - $byPurchase : 0,
                'expiry_date'          => $row->expiry_date
                    ? \Carbon\Carbon::parse($row->expiry_date)->format('Y-m-d')
                    : null,
            ];
        })->values()->all();

        return response()->json(['data' => $stockData]);
    }

    //  Summary (single SQL aggregate) 

    public function calculateSummary(Request $request): array
    {
        $query = DB::table('products as p')
            ->join('batches as b',           'b.product_id', '=', 'p.id')
            ->join('location_batches as lb',  'lb.batch_id', '=', 'b.id')
            ->select([
                DB::raw('SUM((lb.qty + lb.free_qty) * b.unit_cost)    AS total_purchase'),
                DB::raw('SUM((lb.qty + lb.free_qty) * b.retail_price) AS total_sale'),
                DB::raw('SUM(CASE WHEN b.retail_price > 0
                              THEN (lb.qty + lb.free_qty) * (b.retail_price - b.unit_cost)
                              ELSE 0 END)                             AS total_profit'),
            ]);

        $this->applyJoinFilters($query, $request);

        $row        = $query->first();
        $byPurchase = (float) ($row->total_purchase ?? 0);
        $bySale     = (float) ($row->total_sale     ?? 0);
        $profit     = (float) ($row->total_profit   ?? 0);

        return [
            'total_stock_by_purchase_price' => $byPurchase,
            'total_stock_by_sale_price'     => $bySale,
            'total_potential_profit'        => $profit,
            'profit_margin'                 => $byPurchase > 0 ? ($profit / $byPurchase * 100) : 0,
        ];
    }

    //  Shared filter builder 

    /**
     * Applies all WHERE conditions directly to the DB query builder.
     * All filtering is done in SQL  zero PHP-side filtering.
     */
    private function applyJoinFilters($query, Request $request): void
    {
        if (filled($request->location_id)) {
            $query->where('lb.location_id', $request->location_id);
        }
        if (filled($request->category_id)) {
            $query->where('p.main_category_id', $request->category_id);
        }
        if (filled($request->sub_category_id)) {
            $query->where('p.sub_category_id', $request->sub_category_id);
        }
        if (filled($request->brand_id)) {
            $query->where('p.brand_id', $request->brand_id);
        }
        if (filled($request->unit_id)) {
            $query->where('p.unit_id', $request->unit_id);
        }
    }
}