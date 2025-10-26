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
        $this->middleware('permission:export reports', ['only' => ['exportReport', 'profitLossExportPdf', 'profitLossExportExcel', 'profitLossExportCsv', 'dueReportExportPdf', 'dueReportExportExcel', 'dueReportExportCsv']]);
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

    // ==================== DUE REPORT METHODS ====================

    /**
     * Display the Due Report page for customers and suppliers
     */
    public function dueReport(Request $request)
    {
        $locations = Location::all();
        $users = \App\Models\User::all();
        $customers = \App\Models\Customer::orderBy('first_name')->get();
        $suppliers = \App\Models\Supplier::orderBy('first_name')->get();

        // If AJAX request for DataTables
        if ($request->ajax()) {
            return $this->getDueDataForDataTables($request);
        }

        // Initial summary data (empty state)
        $summaryData = [
            'total_due' => 0,
            'total_bills' => 0,
            'total_parties' => 0,
            'avg_due_per_bill' => 0,
        ];

        return view('reports.due_report', compact('locations', 'users', 'customers', 'suppliers', 'summaryData'));
    }

    /**
     * Get due data for DataTables via AJAX
     */
    private function getDueDataForDataTables($request)
    {
        $reportType = $request->input('report_type', 'customer'); // customer or supplier
        
        Log::info('Due Report Request', [
            'report_type' => $reportType,
            'customer_id' => $request->customer_id,
            'supplier_id' => $request->supplier_id,
            'location_id' => $request->location_id,
            'user_id' => $request->user_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);
        
        // Get data and summary
        if ($reportType === 'customer') {
            $data = $this->getCustomerDueDataArray($request);
        } else {
            $data = $this->getSupplierDueDataArray($request);
        }
        
        Log::info('Due Report Data Count', ['count' => count($data)]);
        
        // Calculate summary
        $summary = $this->calculateDueSummaryFromData($data, $reportType);
        
        Log::info('Due Report Summary', $summary);
        
        return response()->json([
            'data' => $data,
            'summary' => $summary
        ]);
    }

    /**
     * Get customer due data as array
     */
    private function getCustomerDueDataArray($request)
    {
        $query = Sale::with(['customer', 'location', 'user'])
            ->whereIn('payment_status', ['partial', 'due'])
            ->where('total_due', '>', 0)
            ->whereNotNull('customer_id');

        // Apply filters
        if ($request->has('customer_id') && $request->customer_id != '' && $request->customer_id != null) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('location_id') && $request->location_id != '' && $request->location_id != null) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('user_id') && $request->user_id != '' && $request->user_id != null) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->has('start_date') && $request->start_date != '' && $request->start_date != null) {
            $query->whereDate('sales_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '' && $request->end_date != null) {
            $query->whereDate('sales_date', '<=', $request->end_date);
        }

        // Due days range filter
        if ($request->has('due_days_from') && $request->due_days_from != '' && $request->due_days_from != null) {
            $dueDaysFrom = (int)$request->due_days_from;
            $dateFrom = Carbon::now()->subDays($dueDaysFrom)->format('Y-m-d');
            $query->whereDate('sales_date', '<=', $dateFrom);
        }

        if ($request->has('due_days_to') && $request->due_days_to != '' && $request->due_days_to != null) {
            $dueDaysTo = (int)$request->due_days_to;
            $dateTo = Carbon::now()->subDays($dueDaysTo)->format('Y-m-d');
            $query->whereDate('sales_date', '>=', $dateTo);
        }

        $sales = $query->orderBy('sales_date', 'desc')->get();

        // Format data for DataTables
        $data = [];
        foreach ($sales as $sale) {
            // Only include if there's actual due amount and not paid
            if ($sale->total_due <= 0 || $sale->payment_status === 'paid') continue;
            
            $salesDate = Carbon::parse($sale->sales_date);
            $dueDate = $salesDate; // You can modify this if you have a separate due_date field
            $dueDays = Carbon::now()->diffInDays($salesDate, false);
            
            $data[] = [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no ?? 'N/A',
                'customer_name' => $sale->customer ? $sale->customer->full_name : 'N/A',
                'customer_mobile' => $sale->customer ? $sale->customer->mobile_no : 'N/A',
                'sales_date' => $salesDate->format('d-M-Y'),
                'location' => $sale->location ? $sale->location->name : 'N/A',
                'user' => $sale->user ? $sale->user->name : 'N/A',
                'final_total' => $sale->final_total,
                'total_paid' => $sale->total_paid,
                'total_due' => $sale->total_due,
                'payment_status' => $sale->payment_status,
                'due_days' => abs($dueDays),
                'due_status' => $this->getDueStatus($dueDays),
            ];
        }

        return $data;
    }

    /**
     * Get supplier due data as array
     */
    private function getSupplierDueDataArray($request)
    {
        $query = \App\Models\Purchase::with(['supplier', 'location', 'user'])
            ->whereIn('payment_status', ['partial', 'due'])
            ->where('total_due', '>', 0)
            ->whereNotNull('supplier_id');

        // Apply filters
        if ($request->has('supplier_id') && $request->supplier_id != '' && $request->supplier_id != null) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('location_id') && $request->location_id != '' && $request->location_id != null) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('user_id') && $request->user_id != '' && $request->user_id != null) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->has('start_date') && $request->start_date != '' && $request->start_date != null) {
            $query->whereDate('purchase_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '' && $request->end_date != null) {
            $query->whereDate('purchase_date', '<=', $request->end_date);
        }

        // Due days range filter
        if ($request->has('due_days_from') && $request->due_days_from != '' && $request->due_days_from != null) {
            $dueDaysFrom = (int)$request->due_days_from;
            $dateFrom = Carbon::now()->subDays($dueDaysFrom)->format('Y-m-d');
            $query->whereDate('purchase_date', '<=', $dateFrom);
        }

        if ($request->has('due_days_to') && $request->due_days_to != '' && $request->due_days_to != null) {
            $dueDaysTo = (int)$request->due_days_to;
            $dateTo = Carbon::now()->subDays($dueDaysTo)->format('Y-m-d');
            $query->whereDate('purchase_date', '>=', $dateTo);
        }

        $purchases = $query->orderBy('purchase_date', 'desc')->get();

        // Format data for DataTables
        $data = [];
        foreach ($purchases as $purchase) {
            // Only include if there's actual due amount and not paid
            if ($purchase->total_due <= 0 || $purchase->payment_status === 'paid') continue;
            
            $purchaseDate = Carbon::parse($purchase->purchase_date);
            $dueDate = $purchaseDate; // You can modify this if you have a separate due_date field
            $dueDays = Carbon::now()->diffInDays($purchaseDate, false);
            
            $data[] = [
                'id' => $purchase->id,
                'reference_no' => $purchase->reference_no ?? 'N/A',
                'supplier_name' => $purchase->supplier ? $purchase->supplier->full_name : 'N/A',
                'supplier_mobile' => $purchase->supplier ? $purchase->supplier->mobile_no : 'N/A',
                'purchase_date' => $purchaseDate->format('d-M-Y'),
                'location' => $purchase->location ? $purchase->location->name : 'N/A',
                'user' => $purchase->user ? $purchase->user->name : 'N/A',
                'final_total' => $purchase->final_total,
                'total_paid' => $purchase->total_paid,
                'total_due' => $purchase->total_due,
                'payment_status' => $purchase->payment_status,
                'due_days' => abs($dueDays),
                'due_status' => $this->getDueStatus($dueDays),
            ];
        }

        return $data;
    }

    /**
     * Calculate summary from data array
     */
    private function calculateDueSummaryFromData($data, $reportType)
    {
        $totalDue = 0;
        $totalBills = count($data);
        $uniqueParties = [];

        foreach ($data as $row) {
            $totalDue += $row['total_due'];
            
            if ($reportType === 'customer') {
                $uniqueParties[$row['customer_name']] = true;
            } else {
                $uniqueParties[$row['supplier_name']] = true;
            }
        }

        $totalParties = count($uniqueParties);
        $avgDuePerBill = $totalBills > 0 ? $totalDue / $totalBills : 0;

        return [
            'total_due' => $totalDue,
            'total_bills' => $totalBills,
            'total_parties' => $totalParties,
            'avg_due_per_bill' => $avgDuePerBill,
        ];
    }

    /**
     * Get due status based on days
     */
    private function getDueStatus($dueDays)
    {
        $days = abs($dueDays);
        
        if ($days <= 7) {
            return 'recent';
        } elseif ($days <= 30) {
            return 'medium';
        } elseif ($days <= 90) {
            return 'old';
        } else {
            return 'critical';
        }
    }

    /**
     * Calculate summary data for due report
     */
    private function calculateDueSummary($request)
    {
        $reportType = $request->input('report_type', 'customer');
        
        if ($reportType === 'customer') {
            $query = Sale::whereIn('payment_status', ['partial', 'due'])
                ->where('total_due', '>', 0)
                ->whereNotNull('customer_id');

            // Apply same filters as main query
            if ($request->has('customer_id') && $request->customer_id != '') {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->has('location_id') && $request->location_id != '') {
                $query->where('location_id', $request->location_id);
            }
            if ($request->has('user_id') && $request->user_id != '') {
                $query->where('user_id', $request->user_id);
            }
            if ($request->has('start_date') && $request->start_date != '') {
                $query->whereDate('sales_date', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date != '') {
                $query->whereDate('sales_date', '<=', $request->end_date);
            }

            $totalDue = $query->sum('total_due');
            $totalBills = $query->count();
            $totalCustomers = $query->distinct('customer_id')->count('customer_id');
            $avgDuePerBill = $totalBills > 0 ? $totalDue / $totalBills : 0;

        } else {
            $query = \App\Models\Purchase::whereIn('payment_status', ['partial', 'due'])
                ->where('total_due', '>', 0)
                ->whereNotNull('supplier_id');

            // Apply same filters as main query
            if ($request->has('supplier_id') && $request->supplier_id != '') {
                $query->where('supplier_id', $request->supplier_id);
            }
            if ($request->has('location_id') && $request->location_id != '') {
                $query->where('location_id', $request->location_id);
            }
            if ($request->has('user_id') && $request->user_id != '') {
                $query->where('user_id', $request->user_id);
            }
            if ($request->has('start_date') && $request->start_date != '') {
                $query->whereDate('purchase_date', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date != '') {
                $query->whereDate('purchase_date', '<=', $request->end_date);
            }

            $totalDue = $query->sum('total_due');
            $totalBills = $query->count();
            $totalSuppliers = $query->distinct('supplier_id')->count('supplier_id');
            $avgDuePerBill = $totalBills > 0 ? $totalDue / $totalBills : 0;
            $totalCustomers = $totalSuppliers; // For consistency in blade template
        }

        return [
            'total_due' => $totalDue,
            'total_bills' => $totalBills,
            'total_parties' => $totalCustomers,
            'avg_due_per_bill' => $avgDuePerBill,
        ];
    }

    /**
     * Export due report as PDF
     */
    public function dueReportExportPdf(Request $request)
    {
        $reportType = $request->input('report_type', 'customer');
        $data = $reportType === 'customer' 
            ? $this->getCustomerDueDataArray($request)
            : $this->getSupplierDueDataArray($request);
        
        $summaryData = $this->calculateDueSummaryFromData($data, $reportType);
        
        $pdf = Pdf::loadView('reports.due_report_pdf', compact('data', 'summaryData', 'reportType'));
        
        $filename = 'due-report-' . $reportType . '-' . date('Y-m-d-H-i-s') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export due report as Excel
     */
    public function dueReportExportExcel(Request $request)
    {
        $reportType = $request->input('report_type', 'customer');
        $data = $reportType === 'customer' 
            ? $this->getCustomerDueDataArray($request)
            : $this->getSupplierDueDataArray($request);
        
        $filename = 'due-report-' . $reportType . '-' . date('Y-m-d-H-i-s') . '.xlsx';
        
        return Excel::download(new \App\Exports\DueReportExport($data, $reportType), $filename);
    }

    /**
     * Export due report as CSV
     */
    public function dueReportExportCsv(Request $request)
    {
        $reportType = $request->input('report_type', 'customer');
        $data = $reportType === 'customer' 
            ? $this->getCustomerDueDataArray($request)
            : $this->getSupplierDueDataArray($request);
        
        $filename = 'due-report-' . $reportType . '-' . date('Y-m-d-H-i-s') . '.csv';
        
        return Excel::download(new \App\Exports\DueReportExport($data, $reportType), $filename, \Maatwebsite\Excel\Excel::CSV);
    }
}
