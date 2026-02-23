<?php
namespace App\Http\Controllers;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use App\Services\ProfitLossService;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProfitLossExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Services\Report\StockHistoryService;
use App\Services\Report\DueReportService;
use App\Services\Report\PaymentReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $profitLossService;
    protected StockHistoryService $stockHistoryService;
    protected DueReportService $dueReportService;
    protected PaymentReportService $paymentReportService;

    function __construct(
        ProfitLossService $profitLossService,
        StockHistoryService $stockHistoryService,
        DueReportService $dueReportService,
        PaymentReportService $paymentReportService
    ) {
        $this->profitLossService    = $profitLossService;
        $this->stockHistoryService  = $stockHistoryService;
        $this->dueReportService     = $dueReportService;
        $this->paymentReportService = $paymentReportService;
        $this->middleware('permission:view daily-report', ['only' => ['saleDailyReport', 'dailyReport']]);
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
        if ($request->ajax()) {
            return $this->stockHistoryService->getDataForDataTables($request);
        }

        $filters     = $this->stockHistoryService->getFilters();
        $summaryData = $this->stockHistoryService->calculateSummary($request);

        return view('reports.stock_report', array_merge($filters, compact('summaryData')));
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
        if ($request->ajax()) {
            return $this->dueReportService->getDataForDataTables($request);
        }

        $locations = Location::all();
        $users     = \App\Models\User::all();
        $customers = \App\Models\Customer::orderBy('first_name')->get();
        $suppliers = \App\Models\Supplier::orderBy('first_name')->get();
        $cities    = \App\Models\City::orderBy('name')->get();

        $summaryData = ['total_due' => 0, 'total_bills' => 0, 'total_parties' => 0, 'max_single_due' => 0];

        return view('reports.due_report', compact('locations', 'users', 'customers', 'suppliers', 'cities', 'summaryData'));
    }

    /**
     * Export due report as PDF
     */
    public function dueReportExportPdf(Request $request)
    {
        $reportType  = $request->input('report_type', 'customer');
        $data        = $reportType === 'customer'
            ? $this->dueReportService->getCustomerData($request)
            : $this->dueReportService->getSupplierData($request);
        $summaryData = $this->dueReportService->calculateSummaryFromData($data, $reportType);
        $pdf         = Pdf::loadView('reports.due_report_pdf', compact('data', 'summaryData', 'reportType'));
        return $pdf->download('due-report-' . $reportType . '-' . date('Y-m-d-H-i-s') . '.pdf');
    }

    /**
     * Export due report as Excel
     */
    public function dueReportExportExcel(Request $request)
    {
        $reportType = $request->input('report_type', 'customer');
        $data = $reportType === 'customer'
            ? $this->dueReportService->getCustomerData($request)
            : $this->dueReportService->getSupplierData($request);
        return Excel::download(new \App\Exports\DueReportExport($data, $reportType),
            'due-report-' . $reportType . '-' . date('Y-m-d-H-i-s') . '.xlsx');
    }

    /**
     * Export due report as CSV
     */
    public function dueReportExportCsv(Request $request)
    {
        $reportType = $request->input('report_type', 'customer');
        $data = $reportType === 'customer'
            ? $this->dueReportService->getCustomerData($request)
            : $this->dueReportService->getSupplierData($request);
        return Excel::download(new \App\Exports\DueReportExport($data, $reportType),
            'due-report-' . $reportType . '-' . date('Y-m-d-H-i-s') . '.csv',
            \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Payment Report
     */
    public function paymentReport(Request $request)
    {
        $summaryData = $this->paymentReportService->getSummary($request);

        if ($request->has('ajax_summary')) {
            return response()->json(['summaryData' => $summaryData]);
        }

        $locations    = Location::all();
        $customers    = \App\Models\Customer::select('id','first_name','last_name')->orderBy('first_name')->get();
        $suppliers    = \App\Models\Supplier::select('id','first_name','last_name')->orderBy('first_name')->get();
        $mainLocation = Location::whereNull('parent_id')->first();

        return view('reports.payment_report', compact('locations','customers','suppliers','summaryData','mainLocation'));
    }

    /**
     * Get payment data for DataTables
     */
    public function paymentReportData(Request $request)
    {
        $collections = $this->paymentReportService->getCollections($request);
        return response()->json(['collections' => $collections]);
    }

    /**
     * Get payment detail
     */
    public function paymentDetail($id)
    {
        $payment = \App\Models\Payment::with([
            'customer',
            'supplier',
            'sale.location',
            'purchase.location',
            'purchaseReturn.location',
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);

        return response()->json([
            'payment' => $payment,
            'formatted_amount' => number_format($payment->amount, 2),
            'formatted_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '',
            'location_name' => $this->paymentReportService->getLocationName($payment),
            'invoice_no' => $this->paymentReportService->getInvoiceNo($payment),
        ]);
    }

    /**
     * Export payment report as PDF
     */
    public function paymentReportExportPdf(Request $request)
    {
        try {
            Log::info('PDF Export Request:', $request->all());

            $collections   = $this->paymentReportService->getCollectionsForExport($request);
            $summaryData   = $this->paymentReportService->getSummary($request);
            $paymentCounts = $this->paymentReportService->getPaymentCounts($request);
            $mainLocation  = Location::whereNull('parent_id')->first();

            Log::info('PDF Export Collections Count:', ['count' => count($collections)]);

            $pdf      = Pdf::loadView('reports.payment_report_pdf',
                compact('collections', 'summaryData', 'request', 'mainLocation', 'paymentCounts'))
                ->setPaper('a4', 'portrait');
            $filename = 'payment-report-' . date('Y-m-d-H-i-s') . '.pdf';

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control'       => 'no-cache, no-store, must-revalidate',
                'Pragma'              => 'no-cache',
                'Expires'             => '0',
            ]);
        } catch (\Exception $e) {
            Log::error('PDF Export Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export payment report as Excel
     */
    public function paymentReportExportExcel(Request $request)
    {
        try {
            Log::info('Excel Export Request:', $request->all());

            $data     = $this->paymentReportService->getDataForExport($request);
            $filename = 'payment-report-' . date('Y-m-d-H-i-s') . '.xlsx';

            Log::info('Excel Export Data Count:', ['count' => $data->count()]);

            return Excel::download(new \App\Exports\PaymentReportExport($data), $filename,
                \Maatwebsite\Excel\Excel::XLSX, [
                    'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    'Cache-Control'       => 'no-cache, no-store, must-revalidate',
                    'Pragma'              => 'no-cache',
                    'Expires'             => '0',
                ]);
        } catch (\Exception $e) {
            Log::error('Excel Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate Excel: ' . $e->getMessage()], 500);
        }
    }

    // ----- Daily Sales Report (moved from SaleController) --------------------

    public function saleDailyReport()
    {
        return view('reports.daily_sales_report');
    }

    public function dailyReport(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->input('start_date', Carbon::today()->startOfDay()))->startOfDay();
            $endDate   = Carbon::parse($request->input('end_date',   Carbon::today()->endOfDay()))->endOfDay();

            // Final invoices within the date range
            $salesQuery = Sale::with(['customer', 'location', 'user', 'payments', 'products'])
                ->where('status', 'final')
                ->where('transaction_type', 'invoice')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('sales_date', [$startDate, $endDate])
                      ->orWhereBetween(
                          DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30')"),
                          [$startDate, $endDate]
                      );
                });

            if ($request->filled('customer_id')) $salesQuery->where('customer_id', $request->customer_id);
            if ($request->filled('user_id'))     $salesQuery->where('user_id', $request->user_id);
            if ($request->filled('location_id')) $salesQuery->where('location_id', $request->location_id);

            $sales = $salesQuery->get();

            // Payment totals by method
            $cashPayments = $chequePayments = $bankTransferPayments = $cardPayments = $creditTotal = 0;
            foreach ($sales as $sale) {
                foreach ($sale->payments as $payment) {
                    match ($payment->payment_method) {
                        'cash'          => $cashPayments          += $payment->amount,
                        'cheque'        => $chequePayments        += $payment->amount,
                        'bank_transfer' => $bankTransferPayments  += $payment->amount,
                        'card'          => $cardPayments          += $payment->amount,
                        default         => null,
                    };
                }
                $creditTotal += $sale->total_due;
            }

            // Returns within the same date range
            $allReturnsQuery = SalesReturn::with(['customer', 'location', 'returnProducts', 'sale'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('return_date', [$startDate, $endDate])
                      ->orWhereBetween(
                          DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30')"),
                          [$startDate, $endDate]
                      );
                });

            if ($request->filled('customer_id')) $allReturnsQuery->where('customer_id', $request->customer_id);
            if ($request->filled('location_id')) $allReturnsQuery->where('location_id', $request->location_id);

            $allReturns = $allReturnsQuery->get();
            $salesIds   = $sales->pluck('id')->all();

            $todaySalesReturns = $allReturns->filter(fn($r) => in_array($r->sale_id, $salesIds))->values();
            $oldSaleReturns    = $allReturns->filter(fn($r) => !in_array($r->sale_id, $salesIds))->values();

            // Summaries
            $billTotal         = $sales->sum('final_total');
            $totalSalesReturns = $todaySalesReturns->sum('return_total');
            $paymentTotal      = $cashPayments + $chequePayments + $bankTransferPayments + $cardPayments;

            $discounts = $sales->sum(function ($sale) {
                return $sale->discount_type === 'percentage'
                    ? ($sale->subtotal * $sale->discount_amount / 100)
                    : $sale->discount_amount;
            });

            return response()->json([
                'sales'             => $sales,
                'summaries'         => [
                    'billTotal'          => $billTotal,
                    'discounts'          => $discounts,
                    'cashPayments'       => $cashPayments,
                    'chequePayments'     => $chequePayments,
                    'bankTransfer'       => $bankTransferPayments,
                    'cardPayments'       => $cardPayments,
                    'salesReturns'       => $totalSalesReturns,
                    'paymentTotal'       => $paymentTotal,
                    'creditTotal'        => $creditTotal,
                    'netIncome'          => $billTotal - $totalSalesReturns,
                    'cashInHand'         => $paymentTotal,
                    'totalFreeQuantity'  => $sales->sum(fn($s) => $s->products->sum('free_quantity')),
                    'totalPaidQuantity'  => $sales->sum(fn($s) => $s->products->sum('quantity')),
                ],
                'todaySalesReturns' => $todaySalesReturns,
                'oldSaleReturns'    => $oldSaleReturns,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'An error occurred while fetching sales data.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
