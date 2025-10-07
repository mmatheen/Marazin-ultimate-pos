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

    public function stockHistory()
    {
        // Dummy data for stock history
        $stockHistory = [
            [
                'Action' => 'Sold',
                'SKU' => 'SKU001',
                'Product' => 'Product A',
               
                'Category' => 'Clothing',
                'Location' => 'Store 1',
                'Unit Selling Price' => 50.00,
                'Current stock' => 100,
                'Current Stock Value (By purchase price)' => 4000.00,
                'Current Stock Value (By sale price)' => 5000.00,
                'Potential profit' => 1000.00,
                'Total unit sold' => 200,
                'Total Unit Transferred' => 50,
                'Total Unit Adjusted' => 10,
            ],
            [
                'Action' => 'Purchased',
                'SKU' => 'SKU002',
                'Product' => 'Product B',
                
                'Category' => 'Electronics',
                'Location' => 'Store 2',
                'Unit Selling Price' => 120.00,
                'Current stock' => 50,
                'Current Stock Value (By purchase price)' => 4500.00,
                'Current Stock Value (By sale price)' => 6000.00,
                'Potential profit' => 1500.00,
                'Total unit sold' => 80,
                'Total Unit Transferred' => 20,
                'Total Unit Adjusted' => 5,
            ],
            [
                'Action' => 'Adjusted',
                'SKU' => 'SKU003',
                'Product' => 'Product C',
                
                'Category' => 'Stationery',
                'Location' => 'Store 3',
                'Unit Selling Price' => 10.00,
                'Current stock' => 300,
                'Current Stock Value (By purchase price)' => 2000.00,
                'Current Stock Value (By sale price)' => 3000.00,
                'Potential profit' => 1000.00,
                'Total unit sold' => 150,
                'Total Unit Transferred' => 30,
                'Total Unit Adjusted' => 15,
            ],
            [
                'Action' => 'Transferred',
                'SKU' => 'SKU004',
                'Product' => 'Product D',
              
                'Category' => 'Apparel',
                'Location' => 'Warehouse',
                'Unit Selling Price' => 80.00,
                'Current stock' => 200,
                'Current Stock Value (By purchase price)' => 8000.00,
                'Current Stock Value (By sale price)' => 16000.00,
                'Potential profit' => 8000.00,
                'Total unit sold' => 100,
                'Total Unit Transferred' => 75,
                'Total Unit Adjusted' => 20,
            ],
            [
                'Action' => 'Sold',
                'SKU' => 'SKU005',
                'Product' => 'Product E',
             
                'Category' => 'Home Decor',
                'Location' => 'Store 4',
                'Unit Selling Price' => 25.00,
                'Current stock' => 150,
                'Current Stock Value (By purchase price)' => 3000.00,
                'Current Stock Value (By sale price)' => 3750.00,
                'Potential profit' => 750.00,
                'Total unit sold' => 250,
                'Total Unit Transferred' => 40,
                'Total Unit Adjusted' => 10,

                
            ],
        ];

        // Return the view with stock history data
        return view('reports.stock_report', compact('stockHistory'));
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
