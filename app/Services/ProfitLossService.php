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
        
        // Basic sales metrics
        $totalSales = $salesQuery->sum('final_total');
        $totalQuantity = $salesQuery->sum(DB::raw('(SELECT SUM(quantity) FROM sales_products WHERE sale_id = sales.id)'));
        $totalTransactions = $salesQuery->count();
        
        // Calculate total cost using FIFO method
        $totalCost = $this->calculateTotalCost($filters);
        
        // Calculate profit metrics
        $grossProfit = $totalSales - $totalCost;
        $grossProfitMargin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;
        
        // Additional expenses (can be extended to include other expenses)
        $totalExpenses = 0; // This can be extended to include operational expenses
        $netProfit = $grossProfit - $totalExpenses;
        $netProfitMargin = $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0;
        
        // Average metrics
        $averageOrderValue = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
        $averageQuantityPerOrder = $totalTransactions > 0 ? $totalQuantity / $totalTransactions : 0;
        $averageProfitPerOrder = $totalTransactions > 0 ? $grossProfit / $totalTransactions : 0;

        return [
            'total_sales' => round($totalSales, 2),
            'total_cost' => round($totalCost, 2),
            'total_quantity' => $totalQuantity,
            'total_transactions' => $totalTransactions,
            'gross_profit' => round($grossProfit, 2),
            'profit_margin' => round($grossProfitMargin, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($netProfit, 2),
            'net_profit_margin' => round($netProfitMargin, 2),
            'average_order_value' => round($averageOrderValue, 2),
            'average_quantity_per_order' => round($averageQuantityPerOrder, 2),
            'average_profit_per_order' => round($averageProfitPerOrder, 2),
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
                DB::raw('SUM(sales_products.quantity) as total_quantity'),
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
            $profit = $product->total_sales - $totalCost;
            $profitMargin = $product->total_sales > 0 ? ($profit / $product->total_sales) * 100 : 0;

            $productReport[] = [
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'brand_name' => $product->brand_name ?? 'No Brand',
                'quantity_sold' => $product->allow_decimal ? 
                    round($product->total_quantity, 2) : 
                    intval($product->total_quantity),
                'total_sales' => round($product->total_sales, 2),
                'total_cost' => round($totalCost, 2),
                'gross_profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_selling_price' => round($product->avg_selling_price, 2),
                'cost_per_unit' => $product->total_quantity > 0 ? round($totalCost / $product->total_quantity, 2) : 0,
                'allow_decimal' => $product->allow_decimal
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
                DB::raw('SUM(sales_products.quantity) as total_quantity'),
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
            $profit = $batch->total_sales - $totalCost;
            $profitMargin = $batch->total_sales > 0 ? ($profit / $batch->total_sales) * 100 : 0;

            $batchReport[] = [
                'batch_id' => $batch->batch_id,
                'batch_number' => $batch->batch_no,
                'product_name' => $batch->product_name,
                'sku' => $batch->sku,
                'brand_name' => $batch->brand_name ?? 'No Brand',
                'expiry_date' => $batch->expiry_date ? date('Y-m-d', strtotime($batch->expiry_date)) : 'N/A',
                'purchase_price' => round($batch->unit_cost, 2),
                'quantity_sold' => $batch->allow_decimal ? 
                    round($batch->total_quantity, 2) : 
                    intval($batch->total_quantity),
                'total_sales' => round($batch->total_sales, 2),
                'total_cost' => round($totalCost, 2),
                'gross_profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_selling_price' => round($batch->avg_selling_price, 2),
                'profit_per_unit' => $batch->total_quantity > 0 ? round($profit / $batch->total_quantity, 2) : 0,
                'allow_decimal' => $batch->allow_decimal
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
            $profit = $brand->total_sales - $totalCost;
            $profitMargin = $brand->total_sales > 0 ? ($profit / $brand->total_sales) * 100 : 0;

            $brandReport[] = [
                'brand_id' => $brand->brand_id,
                'brand_name' => $brand->brand_name ?? 'No Brand',
                'product_count' => $brand->product_count,
                'quantity_sold' => round($brand->total_quantity, 2), // Always show decimal for mixed units
                'total_sales' => round($brand->total_sales, 2),
                'total_cost' => round($totalCost, 2),
                'gross_profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_selling_price' => round($brand->avg_selling_price, 2),
                'sales_per_product' => $brand->product_count > 0 ? round($brand->total_sales / $brand->product_count, 2) : 0,
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
        // Build a completely isolated query to avoid any ambiguity
        $locationsQuery = DB::table('sales')
            ->join('locations', 'sales.location_id', '=', 'locations.id')
            ->whereBetween('sales.sales_date', [$filters['start_date'], $filters['end_date']]);
            
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
                DB::raw('(SELECT SUM(sp.quantity) FROM sales_products sp JOIN sales s2 ON sp.sale_id = s2.id WHERE s2.location_id = locations.id AND s2.sales_date BETWEEN ? AND ?) as total_quantity')
            ])
            ->addBinding([$filters['start_date'], $filters['end_date']], 'select')
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
            $profit = $location->total_sales - $totalCost;
            $profitMargin = $location->total_sales > 0 ? ($profit / $location->total_sales) * 100 : 0;

            // Get product count for this location using explicit query
            $productCount = DB::table('sales_products')
                ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
                ->where('sales.location_id', $location->location_id)
                ->whereBetween('sales.sales_date', [$filters['start_date'], $filters['end_date']])
                ->distinct('sales_products.product_id')
                ->count('sales_products.product_id');

            $locationReport[] = [
                'location_id' => $location->location_id,
                'location_name' => $location->location_name,
                'product_count' => $productCount,
                'total_transactions' => $location->total_transactions,
                'quantity_sold' => round($location->total_quantity, 2), // Always show decimal for mixed units
                'total_sales' => round($location->total_sales, 2),
                'total_cost' => round($totalCost, 2),
                'gross_profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_transaction' => $location->total_transactions > 0 ? round($location->total_sales / $location->total_transactions, 2) : 0,
            ];
        }

        // Sort by profit/loss descending
        usort($locationReport, function($a, $b) {
            return $b['gross_profit'] <=> $a['gross_profit'];
        });

        return $locationReport;
    }

    /**
     * Calculate cost for a specific location using isolated query to avoid ambiguity
     */
    private function calculateLocationSpecificCost($locationId, array $filters)
    {
        $totalCost = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->where('sales.location_id', $locationId)
            ->whereBetween('sales.sales_date', [$filters['start_date'], $filters['end_date']])
            ->sum(DB::raw('sales_products.quantity * batches.unit_cost'));
            
        return $totalCost;
    }

    /**
     * Calculate total cost using FIFO method based on actual batch unit costs
     */
    public function calculateTotalCost(array $filters)
    {
        // Use direct DB query to avoid any potential join conflicts
        $totalCost = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->whereBetween('sales.sales_date', [$filters['start_date'], $filters['end_date']])
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
     * Calculate cost for a specific product using FIFO based on actual sales
     */
    public function calculateProductCost($productId, array $filters)
    {
        // Use direct DB query to avoid any potential join conflicts
        $totalCost = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->where('products.id', $productId)
            ->whereBetween('sales.sales_date', [$filters['start_date'], $filters['end_date']])
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
        // Use direct DB query to avoid any potential join conflicts
        $totalCost = DB::table('sales_products')
            ->join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->join('batches', 'sales_products.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->where('products.brand_id', $brandId)
            ->whereBetween('sales.sales_date', [$filters['start_date'], $filters['end_date']])
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
     * Build base sales query with filters
     */
    private function buildSalesQuery(array $filters)
    {
        $query = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
            ->whereBetween('sales_date', [$filters['start_date'], $filters['end_date']]);
        
        if (!empty($filters['location_ids'])) {
            $query->whereIn('location_id', $filters['location_ids']);
        }
        
        return $query;
    }

    /**
     * Build sales products query with filters
     */
    private function buildSalesProductsQuery(array $filters)
    {
        $query = SalesProduct::join('sales', 'sales_products.sale_id', '=', 'sales.id')
            ->whereExists(function($query) use ($filters) {
                $query->select(DB::raw(1))
                      ->from('sales as s2')
                      ->whereRaw('s2.id = sales.id')
                      ->whereBetween('s2.sales_date', [$filters['start_date'], $filters['end_date']]);
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