<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Location;
use App\Models\SalesProduct;
use App\Models\Batch;
use App\Models\LocationBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProfitLossService
{
    /**
     * Generate comprehensive profit & loss report
     */
    public function generateReport(array $filters)
    {
        return [
            'overall_summary' => $this->getOverallSummary($filters),
            'product_wise' => $this->getProductWiseReport($filters),
            'batch_wise' => $this->getBatchWiseReport($filters),
            'brand_wise' => $this->getBrandWiseReport($filters),
            'location_wise' => $this->getLocationWiseReport($filters),
            'filters' => $filters,
            'generated_at' => now()
        ];
    }

    /**
     * Get overall sales summary with profit/loss calculations
     */
    public function getOverallSummary(array $filters)
    {
        $salesQuery = $this->buildSalesQuery($filters);

        // Use sales.final_total as single source of truth (backend now ensures this is correct)
        $totalSales = $salesQuery->sum('final_total');

        // Total quantity includes both paid and free quantities
        $totalQuantity = $salesQuery->sum(DB::raw('(SELECT SUM(quantity + COALESCE(free_quantity, 0)) FROM sales_products WHERE sale_id = sales.id)'));
        $totalPaidQuantity = $salesQuery->sum(DB::raw('(SELECT SUM(quantity) FROM sales_products WHERE sale_id = sales.id)'));
        $totalFreeQuantity = $salesQuery->sum(DB::raw('(SELECT SUM(COALESCE(free_quantity, 0)) FROM sales_products WHERE sale_id = sales.id)'));
        $totalTransactions = $salesQuery->count();

        // Calculate total cost using FIFO method
        $totalCost = $this->calculateTotalCost($filters);

        // Calculate returns and adjust metrics
        $returnData = $this->calculateReturns($filters);
        $netSales = $totalSales - $returnData['total_return_amount'];
        $netCost = $totalCost - $returnData['total_return_cost'];
        $netQuantity = $totalQuantity - $returnData['total_return_quantity'];

        // Calculate profit metrics (after returns)
        $grossProfit = $netSales - $netCost;
        $grossProfitMargin = $netSales > 0 ? ($grossProfit / $netSales) * 100 : 0;

        // Additional expenses (can be extended to include other expenses)
        $totalExpenses = 0; // This can be extended to include operational expenses
        $netProfit = $grossProfit - $totalExpenses;
        $netProfitMargin = $netSales > 0 ? ($netProfit / $netSales) * 100 : 0;

        // Average metrics (based on net values)
        $averageOrderValue = $totalTransactions > 0 ? $netSales / $totalTransactions : 0;
        $averageQuantityPerOrder = $totalTransactions > 0 ? $netQuantity / $totalTransactions : 0;
        $averageProfitPerOrder = $totalTransactions > 0 ? $grossProfit / $totalTransactions : 0;

        return [
            'total_sales' => round($netSales, 2), // Net sales after returns
            'total_cost' => round($netCost, 2), // Net cost after returns
            'total_quantity' => $totalQuantity, // GROSS quantity (paid + free combined) - matches paid + free shown separately
            'total_paid_quantity' => $totalPaidQuantity, // Paid quantities (gross)
            'total_free_quantity' => $totalFreeQuantity, // Free quantities (gross)
            'net_quantity' => $netQuantity, // Net quantity after returns deducted
            'total_transactions' => $totalTransactions,
            'gross_profit' => round($grossProfit, 2),
            'profit_margin' => round($grossProfitMargin, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($netProfit, 2),
            'net_profit_margin' => round($netProfitMargin, 2),
            'average_order_value' => round($averageOrderValue, 2),
            'average_quantity_per_order' => round($averageQuantityPerOrder, 2),
            'average_profit_per_order' => round($averageProfitPerOrder, 2),
            // Additional return details
            'total_returns' => $returnData['total_returns'],
            'total_return_amount' => round($returnData['total_return_amount'], 2),
            'total_return_cost' => round($returnData['total_return_cost'], 2),
            'return_percentage' => $totalSales > 0 ? round(($returnData['total_return_amount'] / $totalSales) * 100, 2) : 0,
        ];
    }

    /**
     * Get product-wise profit/loss report
     */
    public function getProductWiseReport(array $filters)
    {
        // Don't pass product_ids or brand_ids to avoid duplicate joins
        $baseFilters = $filters;
        unset($baseFilters['product_ids'], $baseFilters['brand_ids']);

        $salesProductsQuery = $this->buildSalesProductsQuery($baseFilters);

        $productsQuery = $salesProductsQuery
            ->select([
                'products.id',
                'products.product_name',
                'products.sku',
                'products.unit_id',
                DB::raw('SUM(sales_products.quantity) as total_paid_quantity'),
                DB::raw('SUM(COALESCE(sales_products.free_quantity, 0)) as total_free_quantity'),
                DB::raw('SUM(sales_products.quantity + COALESCE(sales_products.free_quantity, 0)) as total_quantity'),
                DB::raw('SUM(sales_products.quantity * sales_products.price) as total_sales'),
                DB::raw('AVG(sales_products.price) as avg_selling_price'),
                'brands.name as brand_name',
                'units.allow_decimal'
            ])
            ->join('products', 'sales_products.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->groupBy('products.id', 'products.product_name', 'products.sku', 'products.unit_id', 'brands.name', 'units.allow_decimal');

        // Apply filters if specified
        if (!empty($filters['product_ids'])) {
            $productsQuery->whereIn('products.id', $filters['product_ids']);
        }

        if (!empty($filters['brand_ids'])) {
            $productsQuery->whereIn('products.brand_id', $filters['brand_ids']);
        }

        $products = $productsQuery->get();

        $productReport = [];
        foreach ($products as $product) {
            $totalCost = $this->calculateProductCost($product->id, $filters);

            // Calculate returns for this product
            $productReturns = $this->calculateProductReturns($product->id, $filters);

            // Net values after returns
            $netSales = $product->total_sales - $productReturns['return_amount'];
            $netCost = $totalCost - $productReturns['return_cost'];
            $netQuantity = $product->total_quantity - $productReturns['return_quantity'];

            $profit = $netSales - $netCost;
            $profitMargin = $netSales > 0 ? ($profit / $netSales) * 100 : 0;

            $productReport[] = [
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'brand_name' => $product->brand_name ?? 'No Brand',
                'quantity_sold' => $product->allow_decimal ?
                    round($netQuantity, 2) :
                    intval($netQuantity),
                'paid_quantity' => $product->allow_decimal ?
                    round($product->total_paid_quantity, 2) :
                    intval($product->total_paid_quantity),
                'free_quantity' => $product->allow_decimal ?
                    round($product->total_free_quantity, 2) :
                    intval($product->total_free_quantity),
                'total_quantity' => $product->allow_decimal ?
                    round($product->total_quantity, 2) :
                    intval($product->total_quantity),
                'total_sales' => round($netSales, 2),
                'total_cost' => round($netCost, 2),
                'gross_profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_selling_price' => $netQuantity > 0 ? round($netSales / $netQuantity, 2) : 0,
                'cost_per_unit' => $netQuantity > 0 ? round($netCost / $netQuantity, 2) : 0,
                'allow_decimal' => $product->allow_decimal,
                // Return details
                'returns_count' => $productReturns['return_count'],
                'return_amount' => round($productReturns['return_amount'], 2),
                'return_quantity' => $product->allow_decimal ?
                    round($productReturns['return_quantity'], 2) :
                    intval($productReturns['return_quantity']),
            ];
        }

        // Sort by profit/loss descending
        usort($productReport, function($a, $b) {
            return $b['gross_profit'] <=> $a['gross_profit'];
        });

        return $productReport;
    }

    /**
     * Get batch-wise profit/loss report
     */
    public function getBatchWiseReport(array $filters)
    {
        // Don't pass product_ids or brand_ids to avoid duplicate joins
        $baseFilters = $filters;
        unset($baseFilters['product_ids'], $baseFilters['brand_ids']);

        $salesProductsQuery = $this->buildSalesProductsQuery($baseFilters);

        $batchesQuery = $salesProductsQuery
            ->select([
                'batches.id as batch_id',
                'batches.batch_no',
                'batches.unit_cost',
                'batches.expiry_date',
                'products.product_name',
                'products.sku',
                'products.unit_id',
                DB::raw('SUM(sales_products.quantity) as total_paid_quantity'),
                DB::raw('SUM(COALESCE(sales_products.free_quantity, 0)) as total_free_quantity'),
                DB::raw('SUM(sales_products.quantity + COALESCE(sales_products.free_quantity, 0)) as total_quantity'),
                DB::raw('SUM(sales_products.quantity * sales_products.price) as total_sales'),
                DB::raw('AVG(sales_products.price) as avg_selling_price'),
                'brands.name as brand_name',
                'units.allow_decimal'
            ])
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->groupBy('batches.id', 'batches.batch_no', 'batches.unit_cost', 'batches.expiry_date', 'products.product_name', 'products.sku', 'products.unit_id', 'brands.name', 'units.allow_decimal');

        // Apply filters if specified
        if (!empty($filters['product_ids'])) {
            $batchesQuery->whereIn('products.id', $filters['product_ids']);
        }

        if (!empty($filters['brand_ids'])) {
            $batchesQuery->whereIn('products.brand_id', $filters['brand_ids']);
        }

        $batches = $batchesQuery->get();

        $batchReport = [];
        foreach ($batches as $batch) {
            $totalCost = $batch->total_quantity * $batch->unit_cost;

            // Calculate returns for this batch
            $batchReturns = $this->calculateBatchReturns($batch->batch_id, $filters);

            // Net values after returns
            $netSales = $batch->total_sales - $batchReturns['return_amount'];
            $netCost = $totalCost - $batchReturns['return_cost'];
            $netQuantity = $batch->total_quantity - $batchReturns['return_quantity'];

            $profit = $netSales - $netCost;
            $profitMargin = $netSales > 0 ? ($profit / $netSales) * 100 : 0;

            $batchReport[] = [
                'batch_id' => $batch->batch_id,
                'batch_number' => $batch->batch_no,
                'product_name' => $batch->product_name,
                'sku' => $batch->sku,
                'brand_name' => $batch->brand_name ?? 'No Brand',
                'expiry_date' => $batch->expiry_date ? date('Y-m-d', strtotime($batch->expiry_date)) : 'N/A',
                'paid_quantity' => $batch->allow_decimal ?
                    round($batch->total_paid_quantity, 2) :
                    intval($batch->total_paid_quantity),
                'free_quantity' => $batch->allow_decimal ?
                    round($batch->total_free_quantity, 2) :
                    intval($batch->total_free_quantity),
                'total_quantity' => $batch->allow_decimal ?
                    round($batch->total_quantity, 2) :
                    intval($batch->total_quantity),
                'purchase_price' => round($batch->unit_cost, 2),
                'quantity_sold' => $batch->allow_decimal ?
                    round($netQuantity, 2) :
                    intval($netQuantity),
                'total_sales' => round($netSales, 2),
                'total_cost' => round($netCost, 2),
                'gross_profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_selling_price' => $netQuantity > 0 ? round($netSales / $netQuantity, 2) : 0,
                'profit_per_unit' => $netQuantity > 0 ? round($profit / $netQuantity, 2) : 0,
                'allow_decimal' => $batch->allow_decimal,
                // Return details
                'return_amount' => round($batchReturns['return_amount'], 2),
                'return_quantity' => $batch->allow_decimal ?
                    round($batchReturns['return_quantity'], 2) :
                    intval($batchReturns['return_quantity']),
            ];
        }

        // Sort by profit/loss descending
        usort($batchReport, function($a, $b) {
            return $b['gross_profit'] <=> $a['gross_profit'];
        });

        return $batchReport;
    }

    /**
     * Get brand-wise profit/loss report
     */
    public function getBrandWiseReport(array $filters)
    {
        // Don't pass brand_ids to avoid duplicate joins
        $baseFilters = $filters;
        unset($baseFilters['brand_ids']);

        $salesProductsQuery = $this->buildSalesProductsQuery($baseFilters);

        $brandsQuery = $salesProductsQuery
            ->select([
                'brands.id as brand_id',
                'brands.name as brand_name',
                DB::raw('SUM(sales_products.quantity) as total_quantity'),
                DB::raw('SUM(sales_products.quantity * sales_products.price) as total_sales'),
                DB::raw('COUNT(DISTINCT products.id) as product_count'),
                DB::raw('AVG(sales_products.price) as avg_selling_price')
            ])
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->groupBy('brands.id', 'brands.name');

        // Apply brand filter if specified
        if (!empty($filters['brand_ids'])) {
            $brandsQuery->whereIn('products.brand_id', $filters['brand_ids']);
        }

        $brands = $brandsQuery->get();

        $brandReport = [];
        foreach ($brands as $brand) {
            $totalCost = $this->calculateBrandCost($brand->brand_id, $filters);

            // Calculate returns for this brand
            $brandReturns = $this->calculateBrandReturns($brand->brand_id, $filters);

            // Net values after returns
            $netSales = $brand->total_sales - $brandReturns['return_amount'];
            $netCost = $totalCost - $brandReturns['return_cost'];
            $netQuantity = $brand->total_quantity - $brandReturns['return_quantity'];

            $profit = $netSales - $netCost;
            $profitMargin = $netSales > 0 ? ($profit / $netSales) * 100 : 0;

            $brandReport[] = [
                'brand_id' => $brand->brand_id,
                'brand_name' => $brand->brand_name ?? 'No Brand',
                'product_count' => $brand->product_count,
                'quantity_sold' => round($netQuantity, 2), // Always show decimal for mixed units
                'total_sales' => round($netSales, 2),
                'total_cost' => round($netCost, 2),
                'gross_profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_selling_price' => $netQuantity > 0 ? round($netSales / $netQuantity, 2) : 0,
                'sales_per_product' => $brand->product_count > 0 ? round($netSales / $brand->product_count, 2) : 0,
                'return_amount' => round($brandReturns['return_amount'], 2),
            ];
        }

        // Sort by profit/loss descending
        usort($brandReport, function($a, $b) {
            return $b['gross_profit'] <=> $a['gross_profit'];
        });

        return $brandReport;
    }

    /**
     * Get location-wise profit/loss report
     */
    public function getLocationWiseReport(array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        // Build a completely isolated query to avoid any ambiguity - only include finalized sales
        $locationsQuery = DB::table('sales')
            ->join('locations', 'sales.location_id', '=', 'locations.id')
            ->whereBetween('sales.sales_date', [$startDateTime, $endDateTime])
            ->where('sales.status', 'final')
            ->where(function($q) {
                // Include either invoice transactions or legacy sales without transaction_type
                $q->where('sales.transaction_type', 'invoice')
                  ->orWhereNull('sales.transaction_type');
            });

        // Apply location filter explicitly on sales table only
        if (!empty($filters['location_ids'])) {
            $locationsQuery->whereIn('sales.location_id', $filters['location_ids']);
        }

        $locations = $locationsQuery
            ->select([
                'locations.id as location_id',
                'locations.name as location_name',
                DB::raw('COUNT(DISTINCT sales.id) as total_transactions'),
                DB::raw('SUM(sales.final_total) as total_sales'),
                DB::raw('(SELECT SUM(sp.quantity) FROM sales_products sp JOIN sales s2 ON sp.sale_id = s2.id WHERE s2.location_id = locations.id AND s2.sales_date BETWEEN ? AND ? AND s2.status = \'final\' AND (s2.transaction_type = \'invoice\' OR s2.transaction_type IS NULL)) as total_quantity')
            ])
            ->addBinding([$startDateTime, $endDateTime], 'select')
            ->groupBy('locations.id', 'locations.name')
            ->get();

        $locationReport = [];
        foreach ($locations as $location) {
            // Create isolated filters for this specific location
            $locationFilters = [
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
                'location_ids' => [$location->location_id]
            ];

            // Calculate total cost for this location using isolated query
            $totalCost = $this->calculateLocationSpecificCost($location->location_id, $filters);

            // Calculate returns for this location
            $locationReturns = $this->calculateLocationReturns($location->location_id, $filters);

            // Net values after returns
            $netSales = $location->total_sales - $locationReturns['return_amount'];
            $netCost = $totalCost - $locationReturns['return_cost'];
            $netQuantity = $location->total_quantity - $locationReturns['return_quantity'];

            $profit = $netSales - $netCost;
            $profitMargin = $netSales > 0 ? ($profit / $netSales) * 100 : 0;

            // Get product count for this location using explicit query
            $startDateTime = $filters['start_date'] . ' 00:00:00';
            $endDateTime = $filters['end_date'] . ' 23:59:59';

            $productCount = DB::table('sales_products')
                ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
                ->where('sales.location_id', $location->location_id)
                ->whereBetween('sales.sales_date', [$startDateTime, $endDateTime])
                ->where('sales.status', 'final')
                ->where(function($q) {
                    $q->where('sales.transaction_type', 'invoice')
                      ->orWhereNull('sales.transaction_type');
                })
                ->distinct('sales_products.product_id')
                ->count('sales_products.product_id');

            $locationReport[] = [
                'location_id' => $location->location_id,
                'location_name' => $location->location_name,
                'product_count' => $productCount,
                'total_transactions' => $location->total_transactions,
                'quantity_sold' => round($netQuantity, 2), // Always show decimal for mixed units
                'total_sales' => round($netSales, 2),
                'total_cost' => round($netCost, 2),
                'gross_profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_transaction' => $location->total_transactions > 0 ? round($netSales / $location->total_transactions, 2) : 0,
                'return_amount' => round($locationReturns['return_amount'], 2),
            ];
        }

        // Sort by profit/loss descending
        usort($locationReport, function($a, $b) {
            return $b['gross_profit'] <=> $a['gross_profit'];
        });

        return $locationReport;
    }

    /**
     * Calculate returns for a specific brand
     */
    private function calculateBrandReturns($brandId, array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        $returnData = DB::table('sales_return_products')
            ->join('sales_returns', 'sales_return_products.sales_return_id', '=', 'sales_returns.id')
            ->join('products', 'sales_return_products.product_id', '=', 'products.id')
            ->join('batches', 'sales_return_products.batch_id', '=', 'batches.id')
            ->where('products.brand_id', $brandId)
            ->whereBetween('sales_returns.return_date', [$startDateTime, $endDateTime])
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales_returns.location_id', $filters['location_ids']);
            })
            ->selectRaw('
                SUM(sales_return_products.quantity * sales_return_products.return_price) as return_amount,
                SUM(sales_return_products.quantity) as return_quantity,
                SUM(sales_return_products.quantity * batches.unit_cost) as return_cost
            ')
            ->first();

        return [
            'return_amount' => $returnData->return_amount ?? 0,
            'return_quantity' => $returnData->return_quantity ?? 0,
            'return_cost' => $returnData->return_cost ?? 0,
        ];
    }

    /**
     * Calculate returns for a specific location
     */
    private function calculateLocationReturns($locationId, array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        $returnData = DB::table('sales_return_products')
            ->join('sales_returns', 'sales_return_products.sales_return_id', '=', 'sales_returns.id')
            ->join('batches', 'sales_return_products.batch_id', '=', 'batches.id')
            ->where('sales_returns.location_id', $locationId)
            ->whereBetween('sales_returns.return_date', [$startDateTime, $endDateTime])
            ->selectRaw('
                SUM(sales_return_products.quantity * sales_return_products.return_price) as return_amount,
                SUM(sales_return_products.quantity) as return_quantity,
                SUM(sales_return_products.quantity * batches.unit_cost) as return_cost
            ')
            ->first();

        return [
            'return_amount' => $returnData->return_amount ?? 0,
            'return_quantity' => $returnData->return_quantity ?? 0,
            'return_cost' => $returnData->return_cost ?? 0,
        ];
    }

    /**
     * Calculate cost for a specific location using isolated query to avoid ambiguity
     */
    private function calculateLocationSpecificCost($locationId, array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        $totalCost = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->where('sales.location_id', $locationId)
            ->whereBetween('sales.sales_date', [$startDateTime, $endDateTime])
            ->where('sales.status', 'final')
            ->where(function($q) {
                $q->where('sales.transaction_type', 'invoice')
                  ->orWhereNull('sales.transaction_type');
            })
            ->sum(DB::raw('sales_products.quantity * batches.unit_cost'));

        return $totalCost;
    }

    /**
     * Calculate returns for given filters
     */
    private function calculateReturns(array $filters)
    {
        $returnsQuery = $this->buildReturnsQuery($filters);

        $totalReturns = $returnsQuery->count();
        $totalReturnAmount = $returnsQuery->sum('return_total');

        // Calculate total return cost using the same FIFO method
        $totalReturnCost = $this->calculateReturnCost($filters);

        // Calculate total return quantity
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        $totalReturnQuantity = DB::table('sales_return_products')
            ->join('sales_returns', 'sales_return_products.sales_return_id', '=', 'sales_returns.id')
            ->whereBetween('sales_returns.return_date', [$startDateTime, $endDateTime])
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales_returns.location_id', $filters['location_ids']);
            })
            ->sum('sales_return_products.quantity');

        return [
            'total_returns' => $totalReturns,
            'total_return_amount' => $totalReturnAmount,
            'total_return_cost' => $totalReturnCost,
            'total_return_quantity' => $totalReturnQuantity,
        ];
    }

    /**
     * Calculate total return cost using FIFO method based on actual return costs
     */
    private function calculateReturnCost(array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        $totalReturnCost = DB::table('sales_return_products')
            ->join('sales_returns', 'sales_return_products.sales_return_id', '=', 'sales_returns.id')
            ->join('batches', 'sales_return_products.batch_id', '=', 'batches.id')
            ->whereBetween('sales_returns.return_date', [$startDateTime, $endDateTime])
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales_returns.location_id', $filters['location_ids']);
            })
            ->when(!empty($filters['product_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales_return_products.product_id', $filters['product_ids']);
            })
            ->sum(DB::raw('sales_return_products.quantity * batches.unit_cost'));

        return $totalReturnCost;
    }

    /**
     * Build base returns query with filters
     */
    private function buildReturnsQuery(array $filters)
    {
        $user = auth()->user();

        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        // Use the SalesReturn model with proper scoping
        $query = \App\Models\SalesReturn::whereBetween('return_date', [$startDateTime, $endDateTime]);

        // Only Master Super Admin or users with bypass permission can override location scope
        if ($user && ($this->isMasterSuperAdmin($user) || $this->hasLocationBypassPermission($user))) {
            // Apply requested location filter if provided
            if (!empty($filters['location_ids'])) {
                $query->whereIn('location_id', $filters['location_ids']);
            }
        } else {
            // For regular users, validate they have access to requested locations
            if (!empty($filters['location_ids'])) {
                $userLocationIds = $user ? $user->locations->pluck('id')->toArray() : [];
                $validLocationIds = array_intersect($filters['location_ids'], $userLocationIds);

                if (!empty($validLocationIds)) {
                    $query->whereIn('location_id', $validLocationIds);
                }
            }
        }

        return $query;
    }

    /**
     * Calculate total cost using FIFO method based on actual batch unit costs
     */
    public function calculateTotalCost(array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        // Use direct DB query to avoid any potential join conflicts - only include finalized sales
        $totalCost = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->whereBetween('sales.sales_date', [$startDateTime, $endDateTime])
            ->where('sales.status', 'final')
            ->where(function($q) {
                $q->where('sales.transaction_type', 'invoice')
                  ->orWhereNull('sales.transaction_type');
            })
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales.location_id', $filters['location_ids']);
            })
            ->when(!empty($filters['product_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales_products.product_id', $filters['product_ids']);
            })
            ->sum(DB::raw('sales_products.quantity * batches.unit_cost'));

        return $totalCost;
    }

    /**
     * 🔧 NEW: Calculate total sales amount from sales_products (quantity × price)
     * This is more accurate than using sales.final_total which may be corrupted
     */
    public function calculateTotalSalesFromProducts(array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        $totalSales = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sales_date', [$startDateTime, $endDateTime])
            ->where('sales.status', 'final')
            ->where(function($q) {
                $q->where('sales.transaction_type', 'invoice')
                  ->orWhereNull('sales.transaction_type');
            })
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales.location_id', $filters['location_ids']);
            })
            ->when(!empty($filters['product_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales_products.product_id', $filters['product_ids']);
            })
            ->sum(DB::raw('sales_products.quantity * sales_products.price'));

        return $totalSales;
    }

    /**
     * Calculate returns for a specific batch
     */
    private function calculateBatchReturns($batchId, array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        $returnData = DB::table('sales_return_products')
            ->join('sales_returns', 'sales_return_products.sales_return_id', '=', 'sales_returns.id')
            ->join('batches', 'sales_return_products.batch_id', '=', 'batches.id')
            ->where('sales_return_products.batch_id', $batchId)
            ->whereBetween('sales_returns.return_date', [$startDateTime, $endDateTime])
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales_returns.location_id', $filters['location_ids']);
            })
            ->selectRaw('
                SUM(sales_return_products.quantity * sales_return_products.return_price) as return_amount,
                SUM(sales_return_products.quantity) as return_quantity,
                SUM(sales_return_products.quantity * batches.unit_cost) as return_cost
            ')
            ->first();

        return [
            'return_amount' => $returnData->return_amount ?? 0,
            'return_quantity' => $returnData->return_quantity ?? 0,
            'return_cost' => $returnData->return_cost ?? 0,
        ];
    }

    /**
     * Calculate returns for a specific product
     */
    private function calculateProductReturns($productId, array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        // Get return amount and quantity
        $returnData = DB::table('sales_return_products')
            ->join('sales_returns', 'sales_return_products.sales_return_id', '=', 'sales_returns.id')
            ->where('sales_return_products.product_id', $productId)
            ->whereBetween('sales_returns.return_date', [$startDateTime, $endDateTime])
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales_returns.location_id', $filters['location_ids']);
            })
            ->selectRaw('
                COUNT(DISTINCT sales_returns.id) as return_count,
                SUM(sales_return_products.quantity * sales_return_products.return_price) as return_amount,
                SUM(sales_return_products.quantity) as return_quantity
            ')
            ->first();

        // Get return cost using batch unit costs
        $returnCost = DB::table('sales_return_products')
            ->join('sales_returns', 'sales_return_products.sales_return_id', '=', 'sales_returns.id')
            ->join('batches', 'sales_return_products.batch_id', '=', 'batches.id')
            ->where('sales_return_products.product_id', $productId)
            ->whereBetween('sales_returns.return_date', [$startDateTime, $endDateTime])
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales_returns.location_id', $filters['location_ids']);
            })
            ->sum(DB::raw('sales_return_products.quantity * batches.unit_cost'));

        return [
            'return_count' => $returnData->return_count ?? 0,
            'return_amount' => $returnData->return_amount ?? 0,
            'return_quantity' => $returnData->return_quantity ?? 0,
            'return_cost' => $returnCost ?? 0,
        ];
    }

    /**
     * Calculate cost for a specific product using FIFO based on actual sales
     */
    public function calculateProductCost($productId, array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        // Use direct DB query to avoid any potential join conflicts - only include finalized sales
        $totalCost = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->where('products.id', $productId)
            ->whereBetween('sales.sales_date', [$startDateTime, $endDateTime])
            ->where('sales.status', 'final')
            ->where(function($q) {
                $q->where('sales.transaction_type', 'invoice')
                  ->orWhereNull('sales.transaction_type');
            })
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales.location_id', $filters['location_ids']);
            })
            ->sum(DB::raw('sales_products.quantity * batches.unit_cost'));

        return $totalCost;
    }

    /**
     * Calculate cost for a specific brand using FIFO based on actual sales
     */
    public function calculateBrandCost($brandId, array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        // Use direct DB query to avoid any potential join conflicts - only include finalized sales
        $totalCost = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->where('products.brand_id', $brandId)
            ->whereBetween('sales.sales_date', [$startDateTime, $endDateTime])
            ->where('sales.status', 'final')
            ->where(function($q) {
                $q->where('sales.transaction_type', 'invoice')
                  ->orWhereNull('sales.transaction_type');
            })
            ->when(!empty($filters['location_ids']), function ($query) use ($filters) {
                return $query->whereIn('sales.location_id', $filters['location_ids']);
            })
            ->sum(DB::raw('sales_products.quantity * batches.unit_cost'));

        return $totalCost;
    }

    /**
     * Get FIFO cost breakdown for a specific product with detailed profit analysis
     */
    public function getFifoCostBreakdown($productId, array $filters)
    {
        $salesProducts = $this->buildSalesProductsQuery($filters)
            ->select([
                'sales_products.id',
                'sales_products.quantity',
                'sales_products.price as selling_price',
                'batches.batch_no',
                'batches.unit_cost as cost_price',
                'batches.expiry_date',
                'sales.sales_date',
                'sales.invoice_no',
                'products.product_name'
            ])
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->where('products.id', $productId)
            ->orderBy('sales.sales_date')
            ->orderBy('sales_products.id') // FIFO ordering
            ->get();

        $breakdown = [];
        $cumulativeQuantity = 0;
        $cumulativeCost = 0;
        $cumulativeSales = 0;

        foreach ($salesProducts as $salesProduct) {
            $totalCost = $salesProduct->quantity * $salesProduct->cost_price;
            $totalSales = $salesProduct->quantity * $salesProduct->selling_price;
            $profit = $totalSales - $totalCost;
            $profitMargin = $totalSales > 0 ? ($profit / $totalSales) * 100 : 0;

            $cumulativeQuantity += $salesProduct->quantity;
            $cumulativeCost += $totalCost;
            $cumulativeSales += $totalSales;

            $breakdown[] = [
                'sale_date' => $salesProduct->sales_date,
                'invoice_no' => $salesProduct->invoice_no,
                'batch_no' => $salesProduct->batch_no,
                'expiry_date' => $salesProduct->expiry_date,
                'quantity' => $salesProduct->quantity,
                'unit_cost' => round($salesProduct->cost_price, 2),
                'selling_price' => round($salesProduct->selling_price, 2),
                'total_cost' => round($totalCost, 2),
                'total_sales' => round($totalSales, 2),
                'profit_loss' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'cumulative_quantity' => $cumulativeQuantity,
                'cumulative_cost' => round($cumulativeCost, 2),
                'cumulative_sales' => round($cumulativeSales, 2),
                'cumulative_profit' => round($cumulativeSales - $cumulativeCost, 2)
            ];
        }

        return $breakdown;
    }

    /**
     * Get detailed product report with batch breakdown
     */
    public function getProductDetailedReport(array $filters)
    {
        $productId = $filters['product_id'];

        $product = Product::with('brand')->find($productId);
        if (!$product) {
            return null;
        }

        $productSummary = $this->getProductWiseReport($filters);
        $productData = collect($productSummary)->firstWhere('product_id', $productId);

        $batchBreakdown = $this->getFifoCostBreakdown($productId, $filters);

        return [
            'product' => $product,
            'summary' => $productData,
            'batch_breakdown' => $batchBreakdown
        ];
    }

    /**
     * Get monthly comparison report
     */
    public function getMonthlyComparison($year, array $locationIds = [])
    {
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $filters = [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'location_ids' => $locationIds
            ];

            $summary = $this->getOverallSummary($filters);

            $monthlyData[] = [
                'month' => $month,
                'month_name' => $startDate->format('F'),
                'year' => $year,
                'summary' => $summary
            ];
        }

        return $monthlyData;
    }

    /**
     * Get top performing products
     */
    public function getTopPerformingProducts(array $filters, $limit = 10, $sortBy = 'profit')
    {
        $productReport = $this->getProductWiseReport($filters);

        // Sort based on the specified criteria
        switch ($sortBy) {
            case 'quantity':
                usort($productReport, function($a, $b) {
                    return $b['total_quantity'] <=> $a['total_quantity'];
                });
                break;
            case 'revenue':
                usort($productReport, function($a, $b) {
                    return $b['total_sales'] <=> $a['total_sales'];
                });
                break;
            case 'margin':
                usort($productReport, function($a, $b) {
                    return $b['profit_margin'] <=> $a['profit_margin'];
                });
                break;
            default: // profit
                usort($productReport, function($a, $b) {
                    return $b['profit_loss'] <=> $a['profit_loss'];
                });
        }

        return array_slice($productReport, 0, $limit);
    }

    /**
     * Get profit margin analysis
     */
    public function getProfitMarginAnalysis(array $filters)
    {
        $productReport = $this->getProductWiseReport($filters);

        $margins = array_column($productReport, 'profit_margin');

        return [
            'highest_margin' => !empty($margins) ? max($margins) : 0,
            'lowest_margin' => !empty($margins) ? min($margins) : 0,
            'average_margin' => !empty($margins) ? array_sum($margins) / count($margins) : 0,
            'products_with_loss' => count(array_filter($productReport, function($p) {
                return $p['profit_loss'] < 0;
            })),
            'products_with_profit' => count(array_filter($productReport, function($p) {
                return $p['profit_loss'] > 0;
            })),
            'total_products' => count($productReport)
        ];
    }

    /**
     * Build base sales query with filters - respecting user location access
     */
    private function buildSalesQuery(array $filters)
    {
        $user = auth()->user();

        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        // Start with location-scoped query - only include finalized sales
        $query = Sale::whereBetween('sales_date', [$startDateTime, $endDateTime])
            ->where('status', 'final')
            ->where(function($q) {
                // Include either invoice transactions or legacy sales without transaction_type
                $q->where('transaction_type', 'invoice')
                  ->orWhereNull('transaction_type');
            });

        // Only Master Super Admin or users with bypass permission can override location scope
        if ($user && ($this->isMasterSuperAdmin($user) || $this->hasLocationBypassPermission($user))) {
            $query = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                ->whereBetween('sales_date', [$startDateTime, $endDateTime])
                ->where('status', 'final')
                ->where(function($q) {
                    // Include either invoice transactions or legacy sales without transaction_type
                    $q->where('transaction_type', 'invoice')
                      ->orWhereNull('transaction_type');
                });

            // Apply requested location filter if provided
            if (!empty($filters['location_ids'])) {
                $query->whereIn('location_id', $filters['location_ids']);
            }
        } else {
            // For regular users, validate they have access to requested locations
            if (!empty($filters['location_ids'])) {
                $userLocationIds = $user ? $user->locations->pluck('id')->toArray() : [];
                $validLocationIds = array_intersect($filters['location_ids'], $userLocationIds);

                if (!empty($validLocationIds)) {
                    $query->whereIn('location_id', $validLocationIds);
                }
            }
        }

        return $query;
    }

    /**
     * Check if user is Master Super Admin
     */
    private function isMasterSuperAdmin($user): bool
    {
        if (!$user || !$user->relationLoaded('roles')) {
            $user?->load('roles');
        }

        return $user && ($user->roles->pluck('name')->contains('Master Super Admin') ||
               $user->roles->pluck('key')->contains('master_super_admin'));
    }

    /**
     * Check if user has location bypass permission
     */
    private function hasLocationBypassPermission($user): bool
    {
        if (!$user || !$user->relationLoaded('roles')) {
            $user?->load('roles');
        }

        if (!$user) return false;

        // Check if any role has bypass_location_scope flag
        foreach ($user->roles as $role) {
            if ($role->bypass_location_scope ?? false) {
                return true;
            }
        }

        // Check for specific permissions
        return $user->hasPermissionTo('override location scope');
    }

    /**
     * Build sales products query with filters
     */
    private function buildSalesProductsQuery(array $filters)
    {
        // Convert date strings to proper date range for datetime columns
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';

        $query = SalesProduct::join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->whereExists(function($query) use ($startDateTime, $endDateTime) {
                $query->select(DB::raw(1))
                      ->from('sales as s2')
                      ->whereRaw('s2.id = sales.id')
                      ->whereBetween('s2.sales_date', [$startDateTime, $endDateTime])
                      ->where('s2.status', 'final')
                      ->where(function($q) {
                          // Include either invoice transactions or legacy sales without transaction_type
                          $q->where('s2.transaction_type', 'invoice')
                            ->orWhereNull('s2.transaction_type');
                      });
            });

        if (!empty($filters['location_ids'])) {
            $query->whereIn('sales.location_id', $filters['location_ids']);
        }

        if (!empty($filters['product_ids'])) {
            $query->whereIn('sales_products.product_id', $filters['product_ids']);
        }

        if (!empty($filters['brand_ids'])) {
            $query->join('products', 'sales_products.product_id', '=', 'products.id')
                  ->whereIn('products.brand_id', $filters['brand_ids']);
        }

        return $query;
    }
}
