<?php
namespace App\Http\Controllers;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use App\Services\ProfitLossService;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProfitLossExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $profitLossService;

    function __construct(ProfitLossService $profitLossService)
    {
        $this->profitLossService = $profitLossService;
        $this->middleware('permission:view daily-report', ['only' => ['dailyReport']]);
        $this->middleware('permission:view sales-report', ['only' => ['salesReport']]);
        $this->middleware('permission:view purchase-report', ['only' => ['purchaseReport']]);
        $this->middleware('permission:view stock-report', ['only' => ['stockHistory', 'stockReport']]);
        $this->middleware('permission:view profit-loss-report', ['only' => ['profitLossReport', 'profitLossData', 'profitLossExport']]);
        $this->middleware('permission:view payment-report', ['only' => ['paymentReport']]);
        $this->middleware('permission:view customer-report', ['only' => ['customerReport']]);
        $this->middleware('permission:view supplier-report', ['only' => ['supplierReport']]);
        $this->middleware('permission:export reports', ['only' => ['exportReport', 'profitLossExportPdf', 'profitLossExportExcel', 'profitLossExportCsv']]);
    }

    public function stockHistory(Request $request)
    {
        // Get all locations for filter dropdown
        $locations = Location::all();
        $categories = \App\Models\MainCategory::all();
        $subCategories = \App\Models\SubCategory::all();
        $brands = Brand::all();
        $units = \App\Models\Unit::all();

        // If AJAX request for DataTables
        if ($request->ajax()) {
            return $this->getStockDataForDataTables($request);
        }

        // Calculate summary data
        $summaryData = $this->calculateStockSummary($request);

        return view('reports.stock_report', compact('locations', 'categories', 'subCategories', 'brands', 'units', 'summaryData'));
    }

    private function getStockDataForDataTables($request)
    {
        // Build query for products with all necessary relationships
        $query = Product::select('products.*') // Explicitly select all product columns
            ->with([
            'mainCategory', 
            'subCategory', 
            'brand', 
            'unit', 
            'batches' => function($q) {
                $q->with(['locationBatches' => function($lb) {
                    $lb->with('location')->where('qty', '>', 0);
                }]);
            }
        ]);

        // Apply filters
        if ($request->has('location_id') && $request->location_id != '' && $request->location_id != null) {
            $query->whereHas('batches.locationBatches', function($q) use ($request) {
                $q->where('location_id', $request->location_id);
            });
        }

        if ($request->has('category_id') && $request->category_id != '' && $request->category_id != null) {
            $query->where('main_category_id', $request->category_id);
        }

        if ($request->has('sub_category_id') && $request->sub_category_id != '' && $request->sub_category_id != null) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->has('brand_id') && $request->brand_id != '' && $request->brand_id != null) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('unit_id') && $request->unit_id != '' && $request->unit_id != null) {
            $query->where('unit_id', $request->unit_id);
        }

        $products = $query->get();

        // Process stock data
        $stockData = [];
        foreach ($products as $product) {
            if ($product->batches->isEmpty()) continue;

            foreach ($product->batches as $batch) {
                $locationBatches = $batch->locationBatches;

                if ($request->has('location_id') && $request->location_id != '' && $request->location_id != null) {
                    $locationBatches = $locationBatches->where('location_id', $request->location_id);
                }

                foreach ($locationBatches as $locationBatch) {
                    $currentStock = floatval($locationBatch->qty ?? 0);

                    if ($currentStock > 0) {
                        $locationName = $locationBatch->location->name ?? 'Unknown Location';
                        // Use ONLY batch prices, not product table prices
                        $unitCost = floatval($batch->unit_cost ?? 0);
                        $retailPrice = floatval($batch->retail_price ?? 0);
                        
                        $stockByPurchasePrice = $currentStock * $unitCost;
                        $stockBySalePrice = $currentStock * $retailPrice;
                        $potentialProfit = $stockBySalePrice - $stockByPurchasePrice;

                        $stockData[] = [
                            'sku' => $product->sku ?? 'N/A',
                            'product_name' => $product->product_name ?? 'Unknown Product',
                            'batch_no' => $batch->batch_no ?? 'N/A',
                            'category' => optional($product->mainCategory)->mainCategoryName ?? 'N/A',
                            'location' => $locationName,
                            'unit_selling_price' => $retailPrice,
                            'unit_cost' => $unitCost,
                            'current_stock' => $currentStock,
                            'stock_value_purchase' => $stockByPurchasePrice,
                            'stock_value_sale' => $stockBySalePrice,
                            'potential_profit' => $potentialProfit,
                            'expiry_date' => $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->format('Y-m-d') : null,
                            'product_id' => $product->id, // Add for debugging
                        ];
                    }
                }
            }
        }

        return response()->json(['data' => $stockData]);
    }

    private function calculateStockSummary($request)
    {
        $query = Product::with([
            'batches' => function($q) {
                $q->with(['locationBatches' => function($lb) {
                    $lb->where('qty', '>', 0);
                }]);
            }
        ]);

        // Apply same filters
        if ($request->has('location_id') && $request->location_id != '' && $request->location_id != null) {
            $query->whereHas('batches.locationBatches', function($q) use ($request) {
                $q->where('location_id', $request->location_id);
            });
        }

        if ($request->has('category_id') && $request->category_id != '' && $request->category_id != null) {
            $query->where('main_category_id', $request->category_id);
        }

        if ($request->has('sub_category_id') && $request->sub_category_id != '' && $request->sub_category_id != null) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->has('brand_id') && $request->brand_id != '' && $request->brand_id != null) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('unit_id') && $request->unit_id != '' && $request->unit_id != null) {
            $query->where('unit_id', $request->unit_id);
        }

        $products = $query->get();

        $totalStockByPurchasePrice = 0;
        $totalStockBySalePrice = 0;
        $totalPotentialProfit = 0;

        foreach ($products as $product) {
            foreach ($product->batches as $batch) {
                $locationBatches = $batch->locationBatches;

                if ($request->has('location_id') && $request->location_id != '' && $request->location_id != null) {
                    $locationBatches = $locationBatches->where('location_id', $request->location_id);
                }

                foreach ($locationBatches as $locationBatch) {
                    $currentStock = floatval($locationBatch->qty ?? 0);
                    if ($currentStock > 0) {
                        // Use ONLY batch prices, not product table prices
                        $unitCost = floatval($batch->unit_cost ?? 0);
                        $retailPrice = floatval($batch->retail_price ?? 0);
                        
                        $totalStockByPurchasePrice += ($currentStock * $unitCost);
                        $totalStockBySalePrice += ($currentStock * $retailPrice);
                        $totalPotentialProfit += (($currentStock * $retailPrice) - ($currentStock * $unitCost));
                    }
                }
            }
        }

        return [
            'total_stock_by_purchase_price' => $totalStockByPurchasePrice,
            'total_stock_by_sale_price' => $totalStockBySalePrice,
            'total_potential_profit' => $totalPotentialProfit,
            // Profit Margin = (Profit / Sale Price) × 100 - Maximum 100%
            'profit_margin' => $totalStockBySalePrice > 0 ? (($totalPotentialProfit / $totalStockBySalePrice) * 100) : 0,
        ];
    }

    /**
     * Display the activity log page.
     */
    public function activityLogPage()
    {
        
        return view('reports.activity_log');
    }

    /**
     * Display account ledger page for both customers and suppliers
     */
    public function accountLedger()
    {
        return view('reports.account_ledger');
    }

    /**
     * Display unified ledger page (backward compatibility)
     */
    public function unifiedLedger()
    {
        return $this->accountLedger();
    }

    /**
     * Fetch activity logs for DataTables via AJAX.
     */
public function fetchActivityLog(Request $request)
{
    // Get date range from request or use today as default
    $from = $request->input('start_date');
    $to = $request->input('end_date');
    $subjectType = $request->input('subject_type');
    $userId = $request->input('causer_id');

    // Default date range: today (Asia/Colombo)
    $timezone = 'Asia/Colombo';
    $now = now()->setTimezone($timezone);
    if (!$from) {
        $from = $now->copy()->startOfDay()->toDateTimeString();
    } else {
        $from = \Carbon\Carbon::parse($from, $timezone)->startOfDay()->toDateTimeString();
    }
    if (!$to) {
        $to = $now->copy()->endOfDay()->toDateTimeString();
    } else {
        $to = \Carbon\Carbon::parse($to, $timezone)->endOfDay()->toDateTimeString();
    }

    // Build query (convert input dates to UTC for DB query)
    $query = Activity::query()
        ->whereBetween('created_at', [
            \Carbon\Carbon::parse($from, $timezone)->setTimezone('UTC'),
            \Carbon\Carbon::parse($to, $timezone)->setTimezone('UTC')
        ]);

    if ($subjectType) {
        $query->where('subject_type', $subjectType);
    }

    if ($userId) {
        $query->where('causer_id', $userId);
    }

    // Fetch all data
    $logs = $query->orderBy('created_at', 'desc')->get();

    // Get all unique causer_ids from logs
    $causerIds = $logs->pluck('causer_id')->unique()->filter()->all();

    // Fetch all related users
    $users = \App\Models\User::whereIn('id', $causerIds)->get()->keyBy('id');

    // Convert created_at to Asia/Colombo and add user details
    $logs->transform(function ($item) use ($timezone, $users) {
        $item->created_at_colombo = \Carbon\Carbon::parse($item->created_at)->setTimezone($timezone)->format('Y-m-d H:i:s');
        $item->user = $users[$item->causer_id] ?? null;
        return $item;
    });

    return response()->json([
        'success' => true,
        'data' => $logs
    ]);
}

    // ==================== PROFIT & LOSS REPORT METHODS ====================

    /**
     * Display the main Profit & Loss report page
     */
    public function profitLossReport(Request $request)
    {
        $locations = Location::all();
        $brands = Brand::all();
        
        // Default date range - current month
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        $locationIds = $request->location_ids ?? [];
        $reportType = $request->report_type ?? 'overall';

        // If it's an AJAX request for data, return JSON
        if ($request->ajax()) {
            return $this->profitLossData($request);
        }

        // Generate the report data for initial load
        $reportData = $this->profitLossService->generateReport([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location_ids' => $locationIds,
            'report_type' => $reportType
        ]);

        return view('reports.profit_loss_report', compact(
            'reportData', 
            'locations', 
            'brands',
            'startDate',
            'endDate',
            'locationIds',
            'reportType'
        ));
    }

    /**
     * Get profit/loss data via AJAX
     */
    public function profitLossData(Request $request)
    {
        try {
            $filters = $this->getFilters($request);
            
            // Always get summary data
            $summary = $this->profitLossService->getOverallSummary($filters);
            
            // Get specific report data based on type
            switch ($filters['report_type']) {
                case 'product':
                    $reportData = $this->profitLossService->getProductWiseReport($filters);
                    break;
                case 'batch':
                    $reportData = $this->profitLossService->getBatchWiseReport($filters);
                    break;
                case 'brand':
                    $reportData = $this->profitLossService->getBrandWiseReport($filters);
                    break;
                case 'location':
                    $reportData = $this->profitLossService->getLocationWiseReport($filters);
                    break;
                default:
                    // For overall summary, convert summary to table format
                    $reportData = [
                        ['description' => 'Total Sales', 'amount' => $summary['total_sales']],
                        ['description' => 'Total Cost', 'amount' => $summary['total_cost']],
                        ['description' => 'Gross Profit', 'amount' => $summary['gross_profit']],
                        ['description' => 'Gross Profit Margin', 'amount' => $summary['profit_margin'] . '%'],
                        ['description' => 'Total Transactions', 'amount' => $summary['total_transactions']],
                        ['description' => 'Average Order Value', 'amount' => $summary['average_order_value']],
                    ];
            }
            
            return response()->json([
                'summary' => $summary,
                'data' => $reportData
            ]);
            
        } catch (\Exception $e) {
            Log::error('P&L Report Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to generate report: ' . $e->getMessage(),
                'summary' => ['total_sales' => 0, 'total_cost' => 0, 'gross_profit' => 0, 'profit_margin' => 0],
                'data' => []
            ], 500);
        }
    }

    /**
     * Export P&L report as PDF
     */
    public function profitLossExportPdf(Request $request)
    {
        $filters = $this->getFilters($request);
        $reportData = $this->profitLossService->generateReport($filters);
        
        $pdf = Pdf::loadView('reports.profit_loss_pdf', compact('reportData', 'filters'));
        
        $filename = 'profit-loss-report-' . date('Y-m-d-H-i-s') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export P&L report as Excel
     */
    public function profitLossExportExcel(Request $request)
    {
        $filters = $this->getFilters($request);
        $filename = 'profit-loss-report-' . date('Y-m-d-H-i-s') . '.xlsx';
        
        return Excel::download(new ProfitLossExport($filters), $filename);
    }

    /**
     * Export P&L report as CSV
     */
    public function profitLossExportCsv(Request $request)
    {
        $filters = $this->getFilters($request);
        $filename = 'profit-loss-report-' . date('Y-m-d-H-i-s') . '.csv';
        
        return Excel::download(new ProfitLossExport($filters), $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Get detailed product report with batch breakdown
     */
    public function profitLossProductDetails(Request $request, $productId = null)
    {
        if (!$productId) {
            return response()->json(['error' => 'Product ID is required'], 400);
        }
        
        $filters = $this->getFilters($request);
        $filters['product_id'] = $productId;
        
        $productDetails = $this->profitLossService->getProductDetailedReport($filters);
        
        return response()->json($productDetails);
    }

    /**
     * Get FIFO cost calculation for a specific product
     */
    public function profitLossFifoBreakdown(Request $request, $productId)
    {
        $filters = $this->getFilters($request);
        $fifoBreakdown = $this->profitLossService->getFifoCostBreakdown($productId, $filters);
        
        return response()->json($fifoBreakdown);
    }

    /**
     * Get date range presets (today, yesterday, this week, last week, etc.)
     */
    public function profitLossDatePresets()
    {
        return response()->json([
            'today' => [
                'start' => Carbon::today()->format('Y-m-d'),
                'end' => Carbon::today()->format('Y-m-d')
            ],
            'yesterday' => [
                'start' => Carbon::yesterday()->format('Y-m-d'),
                'end' => Carbon::yesterday()->format('Y-m-d')
            ],
            'this_week' => [
                'start' => Carbon::now()->startOfWeek()->format('Y-m-d'),
                'end' => Carbon::now()->endOfWeek()->format('Y-m-d')
            ],
            'last_week' => [
                'start' => Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d'),
                'end' => Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d')
            ],
            'this_month' => [
                'start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'end' => Carbon::now()->endOfMonth()->format('Y-m-d')
            ],
            'last_month' => [
                'start' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'end' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d')
            ],
            'this_quarter' => [
                'start' => Carbon::now()->startOfQuarter()->format('Y-m-d'),
                'end' => Carbon::now()->endOfQuarter()->format('Y-m-d')
            ],
            'last_quarter' => [
                'start' => Carbon::now()->subQuarter()->startOfQuarter()->format('Y-m-d'),
                'end' => Carbon::now()->subQuarter()->endOfQuarter()->format('Y-m-d')
            ],
            'this_year' => [
                'start' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'end' => Carbon::now()->endOfYear()->format('Y-m-d')
            ],
            'last_year' => [
                'start' => Carbon::now()->subYear()->startOfYear()->format('Y-m-d'),
                'end' => Carbon::now()->subYear()->endOfYear()->format('Y-m-d')
            ]
        ]);
    }

    /**
     * Extract and validate filters from request
     */
    private function getFilters(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location_ids' => 'array',
            'location_ids.*' => 'exists:locations,id',
            'brand_ids' => 'array',
            'brand_ids.*' => 'exists:brands,id',
            'product_ids' => 'array',
            'product_ids.*' => 'exists:products,id',
            'report_type' => 'in:overall,product,batch,brand,location'
        ]);

        return [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location_ids' => $request->location_ids ?? [],
            'brand_ids' => $request->brand_ids ?? [],
            'product_ids' => $request->product_ids ?? [],
            'report_type' => $request->report_type ?? 'overall',
            'costing_method' => $request->costing_method ?? 'fifo' // fifo or batch
        ];
    }
}
