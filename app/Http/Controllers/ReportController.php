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
use Illuminate\Support\Facades\Cache;

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
        // Get all locations for filter dropdown - cache static data
        $locations = Cache::remember('locations_list', 3600, function() {
            return Location::select('id', 'name')->get();
        });
        $categories = Cache::remember('main_categories_list', 3600, function() {
            return \App\Models\MainCategory::select('id', 'mainCategoryName')->get();
        });
        $subCategories = Cache::remember('sub_categories_list', 3600, function() {
            return \App\Models\SubCategory::select('id', 'subCategoryname', 'main_category_id')->get();
        });
        $brands = Cache::remember('brands_list', 3600, function() {
            return Brand::select('id', 'name')->get();
        });
        $units = Cache::remember('units_list', 3600, function() {
            return \App\Models\Unit::select('id', 'name', 'short_name')->get();
        });

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
        $query = Product::select('products.*')
            ->with([
                'mainCategory',
                'subCategory',
                'brand',
                'unit',
                'batches' => function($q) {
                    $q->with(['locationBatches' => function($lb) {
                        $lb->with('location'); // Remove qty filter to include zero stock
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

                    // Show all stocks including zero
                    $locationName = $locationBatch->location->name ?? 'Unknown Location';
                    $unitCost = floatval($batch->unit_cost ?? 0);
                    $retailPrice = floatval($batch->retail_price ?? 0);

                    $stockByPurchasePrice = $currentStock * $unitCost;
                    $stockBySalePrice = $currentStock * $retailPrice;
                    // Only calculate markup if retail price is set (> 0)
                    $potentialProfit = ($retailPrice > 0) ? ($stockBySalePrice - $stockByPurchasePrice) : 0;

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
                        'product_id' => $product->id,
                    ];
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
                    // Remove qty filter to include zero stock
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
                    // Include zero stock
                    $unitCost = floatval($batch->unit_cost ?? 0);
                    $retailPrice = floatval($batch->retail_price ?? 0);

                    $totalStockByPurchasePrice += ($currentStock * $unitCost);
                    $totalStockBySalePrice += ($currentStock * $retailPrice);
                    // Only add to markup if retail price is set (> 0)
                    $totalPotentialProfit += ($retailPrice > 0) ? (($currentStock * $retailPrice) - ($currentStock * $unitCost)) : 0;
                }
            }
        }

        return [
            'total_stock_by_purchase_price' => $totalStockByPurchasePrice,
            'total_stock_by_sale_price' => $totalStockBySalePrice,
            'total_potential_profit' => $totalPotentialProfit,
            'profit_margin' => $totalStockByPurchasePrice > 0 ? (($totalPotentialProfit / $totalStockByPurchasePrice) * 100) : 0,
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
        $cities = \App\Models\City::orderBy('name')->get();

        // If AJAX request for DataTables
        if ($request->ajax()) {
            return $this->getDueDataForDataTables($request);
        }

        // Initial summary data (empty state)
        $summaryData = [
            'total_due' => 0,
            'total_bills' => 0,
            'total_parties' => 0,
            'max_single_due' => 0,
        ];

        return view('reports.due_report', compact('locations', 'users', 'customers', 'suppliers', 'cities', 'summaryData'));
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
     * Get customer due data as array - LEDGER-BASED APPROACH
     */
    private function getCustomerDueDataArray($request)
    {
        // First, get all customers with outstanding balances from the ledger
        $customerBalances = [];

        // Get all customers who have transactions
        $customerIds = \App\Models\Ledger::where('contact_type', 'customer')
            ->where('status', 'active')
            ->distinct()
            ->pluck('contact_id');

        // Calculate balance for each customer and filter those with due amounts
        foreach ($customerIds as $customerId) {
            $balance = \App\Helpers\BalanceHelper::getCustomerBalance($customerId);
            if ($balance > 0) {
                $customerBalances[$customerId] = $balance;
            }
        }

        // If specific customer filter is applied
        if ($request->has('customer_id') && $request->customer_id != '' && $request->customer_id != null) {
            $filterCustomerId = $request->customer_id;
            if (isset($customerBalances[$filterCustomerId])) {
                $customerBalances = [$filterCustomerId => $customerBalances[$filterCustomerId]];
            } else {
                $customerBalances = [];
            }
        }

        // Now get the sales for these customers to show individual bill details
        // ✅ SIMPLE: Just show invoices (sale orders become invoices when converted)
        $query = Sale::with(['customer', 'location', 'user', 'salesReturns'])
            ->whereIn('customer_id', array_keys($customerBalances))
            ->whereIn('payment_status', ['partial', 'due'])
            ->where('total_due', '>', 0)
            ->whereNotNull('customer_id')
            ->where('status', 'final') // Only show final sales (invoices), not drafts
            ->where('transaction_type', 'invoice'); // Only show invoices

        // Apply city filter
        if ($request->has('city_id') && $request->city_id != '' && $request->city_id != null) {
            $query->whereHas('customer', function($q) use ($request) {
                $q->where('city_id', $request->city_id);
            });
        }

        // Apply location filter
        if ($request->has('location_id') && $request->location_id != '' && $request->location_id != null) {
            $query->where('location_id', $request->location_id);
        }

        // Apply user filter
        if ($request->has('user_id') && $request->user_id != '' && $request->user_id != null) {
            $query->where('user_id', $request->user_id);
        }

        // Predefined date range filter (30, 60, 90 days)
        if ($request->has('date_range_filter') && $request->date_range_filter != '' && $request->date_range_filter != null) {
            $days = (int)$request->date_range_filter;
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $query->whereBetween('sales_date', [$startDate, $endDate]);
        } else {
            // Date range filter - only apply if no specific customer is selected
            if ((!$request->has('customer_id') || $request->customer_id == '') && $request->has('start_date') && $request->start_date != '' && $request->start_date != null) {
                $query->whereDate('sales_date', '>=', $request->start_date);
            }

            if ((!$request->has('customer_id') || $request->customer_id == '') && $request->has('end_date') && $request->end_date != '' && $request->end_date != null) {
                $query->whereDate('sales_date', '<=', $request->end_date);
            }
        }

        $sales = $query->orderBy('sales_date', 'desc')->get();

        // Format data for DataTables
        $data = [];
        foreach ($sales as $sale) {
            // Calculate actual due amount after considering returns
            $totalReturns = $sale->salesReturns()->sum('return_total');
            $originalDue = $sale->total_due;
            $actualDue = $originalDue - $totalReturns;

            // Only include if there's actual due amount after returns
            if ($actualDue <= 0) continue;

            $salesDate = Carbon::parse($sale->sales_date);
            $dueDays = Carbon::now()->diffInDays($salesDate, false);

            $data[] = [
                'id' => $sale->id,
                'customer_id' => $sale->customer_id, // Include customer_id for grouping
                'invoice_no' => $sale->invoice_no ?? 'N/A',
                'customer_name' => $sale->customer ? $sale->customer->full_name : 'N/A',
                'customer_mobile' => $sale->customer ? $sale->customer->mobile_no : 'N/A',
                'sales_date' => $salesDate->format('d-M-Y'),
                'location' => $sale->location ? $sale->location->name : 'N/A',
                'user' => $sale->user ? ($sale->user->full_name ?? $sale->user->name) : 'N/A',
                'final_total' => $sale->final_total,
                'total_paid' => $sale->total_paid,
                'original_due' => $originalDue, // Original due before returns
                'return_amount' => $totalReturns, // Total return amount
                'total_due' => $actualDue, // Final due after returns
                'final_due' => $actualDue, // Same as total_due, for max calculation
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
        // Only show final purchases (not draft) with reference numbers
        $query = \App\Models\Purchase::with(['supplier', 'location', 'user'])
            ->whereIn('payment_status', ['partial', 'due'])
            ->where('total_due', '>', 0)
            ->whereNotNull('supplier_id')
            ->where('status', 'final') // Only show final purchases, not drafts
            ->whereNotNull('reference_no'); // Only show purchases with reference numbers

        // Apply filters
        if ($request->has('supplier_id') && $request->supplier_id != '' && $request->supplier_id != null) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Apply city filter for suppliers
        if ($request->has('city_id') && $request->city_id != '' && $request->city_id != null) {
            $query->whereHas('supplier', function($q) use ($request) {
                $q->where('city_id', $request->city_id);
            });
        }

        if ($request->has('location_id') && $request->location_id != '' && $request->location_id != null) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('user_id') && $request->user_id != '' && $request->user_id != null) {
            $query->where('user_id', $request->user_id);
        }

        // Predefined date range filter (30, 60, 90 days)
        if ($request->has('date_range_filter') && $request->date_range_filter != '' && $request->date_range_filter != null) {
            $days = (int)$request->date_range_filter;
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $query->whereBetween('purchase_date', [$startDate, $endDate]);
        } else {
            // Date range filter
            if ($request->has('start_date') && $request->start_date != '' && $request->start_date != null) {
                $query->whereDate('purchase_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date != '' && $request->end_date != null) {
                $query->whereDate('purchase_date', '<=', $request->end_date);
            }
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
                'user' => $purchase->user ? ($purchase->user->full_name ?? $purchase->user->name) : 'N/A',
                'final_total' => $purchase->final_total,
                'total_paid' => $purchase->total_paid,
                'original_due' => $purchase->total_due,
                'return_amount' => 0,
                'total_due' => $purchase->total_due,
                'final_due' => $purchase->total_due,
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
        $maxSingleDue = 0;

        foreach ($data as $row) {
            $totalDue += $row['total_due'];

            // Track maximum single due
            if (isset($row['final_due']) && $row['final_due'] > $maxSingleDue) {
                $maxSingleDue = $row['final_due'];
            }

            if ($reportType === 'customer') {
                $uniqueParties[$row['customer_name']] = true;
            } else {
                $uniqueParties[$row['supplier_name']] = true;
            }
        }

        $totalParties = count($uniqueParties);
        $avgDuePerBill = $totalBills > 0 ? $totalDue / $totalBills : 0;

        // 🔥 CRITICAL FIX: Use BalanceHelper for actual outstanding balance
        // The total_due calculated above is just from individual bills
        // But the actual outstanding balance includes all ledger transactions
        $actualOutstandingBalance = 0;

        if ($reportType === 'customer') {
            // Get unique customer IDs from the data
            $uniqueCustomerIds = [];
            foreach ($data as $row) {
                if (isset($row['id'])) {
                    // Get the sale to find customer_id
                    $sale = \App\Models\Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)->find($row['id']);
                    if ($sale && $sale->customer_id) {
                        $uniqueCustomerIds[$sale->customer_id] = true;
                    }
                }
            }

            // Calculate total outstanding for all customers with due bills
            foreach (array_keys($uniqueCustomerIds) as $customerId) {
                $balance = \App\Helpers\BalanceHelper::getCustomerBalance($customerId);
                if ($balance > 0) {
                    $actualOutstandingBalance += $balance;
                }
            }
        } else {
            // For suppliers
            $uniqueSupplierIds = [];
            foreach ($data as $row) {
                if (isset($row['id'])) {
                    $purchase = \App\Models\Purchase::find($row['id']);
                    if ($purchase && $purchase->supplier_id) {
                        $uniqueSupplierIds[$purchase->supplier_id] = true;
                    }
                }
            }

            foreach (array_keys($uniqueSupplierIds) as $supplierId) {
                $balance = \App\Helpers\BalanceHelper::getSupplierBalance($supplierId);
                if ($balance > 0) {
                    $actualOutstandingBalance += $balance;
                }
            }
        }

        return [
            'total_due' => $actualOutstandingBalance, // Use ledger-based balance
            'total_bills' => $totalBills,
            'total_parties' => $totalParties,
            'max_single_due' => $maxSingleDue,
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
                ->whereNotNull('customer_id')
                ->where('status', 'final')
                ->where('transaction_type', 'invoice'); // Only count invoices

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

    /**
     * Payment Report
     */
    public function paymentReport(Request $request)
    {
        // Calculate summary data
        $summaryData = $this->calculatePaymentSummary($request);

        // If AJAX request for summary update
        if ($request->has('ajax_summary')) {
            return response()->json(['summaryData' => $summaryData]);
        }

        // Get all locations for filter dropdown
        $locations = Location::all();

        // Get all customers and suppliers for filter dropdown
        $customers = \App\Models\Customer::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        $suppliers = \App\Models\Supplier::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        // Get main/parent location for report header
        $mainLocation = Location::whereNull('parent_id')->first();

        return view('reports.payment_report', compact('locations', 'customers', 'suppliers', 'summaryData', 'mainLocation'));
    }

    /**
     * Get payment data for DataTables
     */
    public function paymentReportData(Request $request)
    {
        $query = \App\Models\Payment::with(['customer', 'supplier', 'sale', 'purchase', 'purchaseReturn'])
            ->select('payments.*');

        // Apply filters
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('supplier_id') && $request->supplier_id != '') {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('location_id') && $request->location_id != '') {
            // Filter by location through sale or purchase
            $query->where(function($q) use ($request) {
                $q->whereHas('sale', function($saleQuery) use ($request) {
                    $saleQuery->where('location_id', $request->location_id);
                })
                ->orWhereHas('purchase', function($purchaseQuery) use ($request) {
                    $purchaseQuery->where('location_id', $request->location_id);
                })
                ->orWhereHas('purchaseReturn', function($returnQuery) use ($request) {
                    $returnQuery->where('location_id', $request->location_id);
                });
            });
        }

        if ($request->has('payment_method') && $request->payment_method != '') {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('payment_type') && $request->payment_type != '') {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $payments = $query->orderBy('payment_date', 'desc')
                          ->orderBy('reference_no', 'desc')
                          ->orderBy('id', 'desc')
                          ->get();

        // Group payments by reference_no (for bulk payments like BLK-S0001)
        $collections = [];
        $groupedPayments = $payments->groupBy('reference_no');

        foreach ($groupedPayments as $referenceNo => $paymentGroup) {
            $firstPayment = $paymentGroup->first();
            $locationName = '';

            if ($firstPayment->sale) {
                $locationName = optional($firstPayment->sale->location)->name ?? '';
            } elseif ($firstPayment->purchase) {
                $locationName = optional($firstPayment->purchase->location)->name ?? '';
            } elseif ($firstPayment->purchaseReturn) {
                $locationName = optional($firstPayment->purchaseReturn->location)->name ?? '';
            }

            $paymentsData = $paymentGroup->map(function($payment) {
                $invoiceNo = '';
                $invoiceValue = 0;
                $invoiceDate = '';
                $deliveryDate = '';

                // Get invoice details based on payment type
                if ($payment->payment_type === 'sale' && $payment->sale) {
                    $invoiceNo = $payment->sale->invoice_no ?? '';
                    $invoiceValue = (float) ($payment->sale->final_total ?? 0); // Use final_total for sales
                    $invoiceDate = $payment->sale->sales_date ? \Carbon\Carbon::parse($payment->sale->sales_date)->format('Y-m-d') : '';
                    $deliveryDate = $payment->sale->expected_delivery_date ? \Carbon\Carbon::parse($payment->sale->expected_delivery_date)->format('Y-m-d') : '';
                } elseif ($payment->payment_type === 'purchase' && $payment->purchase) {
                    $invoiceNo = $payment->purchase->invoice_no ?? '';
                    $invoiceValue = (float) ($payment->purchase->grand_total ?? 0);
                    $invoiceDate = $payment->purchase->purchase_date ? \Carbon\Carbon::parse($payment->purchase->purchase_date)->format('Y-m-d') : '';
                } elseif (($payment->payment_type === 'purchase_return' || $payment->payment_type === 'sale_return_with_bill' || $payment->payment_type === 'sale_return_without_bill') && $payment->purchaseReturn) {
                    $invoiceNo = $payment->purchaseReturn->invoice_no ?? '';
                    $invoiceValue = (float) ($payment->purchaseReturn->grand_total ?? 0);
                    $invoiceDate = $payment->purchaseReturn->return_date ? \Carbon\Carbon::parse($payment->purchaseReturn->return_date)->format('Y-m-d') : '';
                }

                // Fallback: Try to find the related sale/purchase by reference_id
                if ($invoiceValue == 0 && $payment->reference_id) {
                    if ($payment->payment_type === 'sale') {
                        $sale = \App\Models\Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)->find($payment->reference_id);
                        if ($sale) {
                            $invoiceNo = $sale->invoice_no ?? '';
                            $invoiceValue = (float) ($sale->final_total ?? 0); // Use final_total
                            $invoiceDate = $sale->sales_date ? \Carbon\Carbon::parse($sale->sales_date)->format('Y-m-d') : '';
                            $deliveryDate = $sale->expected_delivery_date ? \Carbon\Carbon::parse($sale->expected_delivery_date)->format('Y-m-d') : '';
                        }
                    } elseif ($payment->payment_type === 'purchase') {
                        $purchase = \App\Models\Purchase::find($payment->reference_id);
                        if ($purchase) {
                            $invoiceNo = $purchase->invoice_no ?? '';
                            $invoiceValue = (float) ($purchase->grand_total ?? 0);
                            $invoiceDate = $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : '';
                        }
                    }
                }

                return [
                    'id' => $payment->id,
                    'payment_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : '',
                    'amount' => (float) $payment->amount, // Return raw number for proper calculation
                    'amount_formatted' => number_format($payment->amount, 2), // Formatted version for display if needed
                    'payment_method' => ucfirst($payment->payment_method),
                    'payment_type' => ucfirst($payment->payment_type),
                    'reference_no' => $payment->reference_no ?? '',
                    'invoice_no' => $invoiceNo,
                    'invoice_value' => $invoiceValue,
                    'invoice_date' => $invoiceDate,
                    'delivery_date' => $deliveryDate,
                    'customer_name' => $payment->customer ? $payment->customer->full_name : '',
                    'supplier_name' => $payment->supplier ? $payment->supplier->full_name : '',
                    'cheque_number' => $payment->cheque_number ?? '',
                    'cheque_bank_branch' => $payment->cheque_bank_branch ?? '',
                    'cheque_valid_date' => $payment->cheque_valid_date ? \Carbon\Carbon::parse($payment->cheque_valid_date)->format('Y-m-d') : '',
                    'cheque_status' => $payment->cheque_status ? ucfirst($payment->cheque_status) : '',
                    'notes' => $payment->notes ?? '',
                ];
            });

            $collections[] = [
                'reference_no' => $referenceNo,
                'payment_date' => $firstPayment->payment_date ? \Carbon\Carbon::parse($firstPayment->payment_date)->format('Y-m-d') : '',
                'customer_name' => $firstPayment->customer ? $firstPayment->customer->full_name : ($firstPayment->supplier ? $firstPayment->supplier->full_name : ''),
                'customer_address' => $firstPayment->customer ? ($firstPayment->customer->address ?? '') : ($firstPayment->supplier ? ($firstPayment->supplier->address ?? '') : ''),
                'location' => $locationName,
                'total_amount' => (float) $paymentGroup->sum('amount'), // Return as float for proper calculation
                'payments' => $paymentsData->toArray(),
            ];
        }

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
            'location_name' => $this->getPaymentLocationName($payment),
            'invoice_no' => $this->getPaymentInvoiceNo($payment),
        ]);
    }

    /**
     * Calculate payment summary data
     */
    private function calculatePaymentSummary($request)
    {
        $query = \App\Models\Payment::query();

        // Apply same filters as in paymentReportData
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('supplier_id') && $request->supplier_id != '') {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('location_id') && $request->location_id != '') {
            // Filter by location through sale or purchase
            $query->where(function($q) use ($request) {
                $q->whereHas('sale', function($saleQuery) use ($request) {
                    $saleQuery->where('location_id', $request->location_id);
                })
                ->orWhereHas('purchase', function($purchaseQuery) use ($request) {
                    $purchaseQuery->where('location_id', $request->location_id);
                })
                ->orWhereHas('purchaseReturn', function($returnQuery) use ($request) {
                    $returnQuery->where('location_id', $request->location_id);
                });
            });
        }

        if ($request->has('payment_method') && $request->payment_method != '') {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('payment_type') && $request->payment_type != '') {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        // Calculate totals by payment method
        $cashTotal = (clone $query)->where('payment_method', 'cash')->sum('amount');
        $cardTotal = (clone $query)->where('payment_method', 'card')->sum('amount');
        $chequeTotal = (clone $query)->where('payment_method', 'cheque')->sum('amount');
        $bankTransferTotal = (clone $query)->where('payment_method', 'bank_transfer')->sum('amount');
        $otherTotal = (clone $query)->whereNotIn('payment_method', ['cash', 'card', 'cheque', 'bank_transfer'])->sum('amount');
        $totalAmount = $query->sum('amount');

        // Calculate totals by payment type
        $salePayments = (clone $query)->where('payment_type', 'sale')->sum('amount');
        $purchasePayments = (clone $query)->where('payment_type', 'purchase')->sum('amount');

        return [
            'total_amount' => $totalAmount,
            'cash_total' => $cashTotal,
            'card_total' => $cardTotal,
            'cheque_total' => $chequeTotal,
            'bank_transfer_total' => $bankTransferTotal,
            'other_total' => $otherTotal,
            'sale_payments' => $salePayments,
            'purchase_payments' => $purchasePayments,
        ];
    }

    /**
     * Get payment location name helper
     */
    private function getPaymentLocationName($payment)
    {
        if ($payment->sale && $payment->sale->location) {
            return $payment->sale->location->name;
        } elseif ($payment->purchase && $payment->purchase->location) {
            return $payment->purchase->location->name;
        } elseif ($payment->purchaseReturn && $payment->purchaseReturn->location) {
            return $payment->purchaseReturn->location->name;
        }
        return '';
    }

    /**
     * Get payment invoice number helper
     */
    private function getPaymentInvoiceNo($payment)
    {
        if ($payment->sale) {
            return $payment->sale->invoice_no;
        } elseif ($payment->purchase) {
            return $payment->purchase->invoice_no;
        } elseif ($payment->purchaseReturn) {
            return $payment->purchaseReturn->invoice_no;
        }
        return '';
    }

    /**
     * Export payment report as PDF
     */
    public function paymentReportExportPdf(Request $request)
    {
        try {
            Log::info('PDF Export Request:', $request->all());

            // Use the same data structure as the screen view (collections)
            $collections = $this->getPaymentCollectionsForExport($request);
            $summaryData = $this->calculatePaymentSummary($request);

            // Get main location for header
            $mainLocation = Location::whereNull('parent_id')->first();

            // Calculate payment method counts
            $countQuery = \App\Models\Payment::query();
            if ($request->has('start_date') && $request->start_date != '') {
                $countQuery->whereDate('payment_date', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date != '') {
                $countQuery->whereDate('payment_date', '<=', $request->end_date);
            }
            if ($request->has('customer_id') && $request->customer_id != '') {
                $countQuery->where('customer_id', $request->customer_id);
            }
            if ($request->has('supplier_id') && $request->supplier_id != '') {
                $countQuery->where('supplier_id', $request->supplier_id);
            }
            if ($request->has('payment_method') && $request->payment_method != '') {
                $countQuery->where('payment_method', $request->payment_method);
            }
            if ($request->has('payment_type') && $request->payment_type != '') {
                $countQuery->where('payment_type', $request->payment_type);
            }

            $paymentCounts = [
                'cash' => (clone $countQuery)->where('payment_method', 'cash')->count(),
                'cheque' => (clone $countQuery)->where('payment_method', 'cheque')->count(),
                'card' => (clone $countQuery)->where('payment_method', 'card')->count(),
                'bank_transfer' => (clone $countQuery)->where('payment_method', 'bank_transfer')->count(),
                'total' => (clone $countQuery)->count(),
            ];

            Log::info('PDF Export Collections Count:', ['count' => count($collections)]);

            $pdf = Pdf::loadView('reports.payment_report_pdf', compact('collections', 'summaryData', 'request', 'mainLocation', 'paymentCounts'))
                ->setPaper('a4', 'portrait');

            $filename = 'payment-report-' . date('Y-m-d-H-i-s') . '.pdf';

            // Return with explicit headers to force download
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
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

            $data = $this->getPaymentReportDataArray($request);

            Log::info('Excel Export Data Count:', ['count' => $data->count()]);

            $filename = 'payment-report-' . date('Y-m-d-H-i-s') . '.xlsx';

            return Excel::download(new \App\Exports\PaymentReportExport($data), $filename, \Maatwebsite\Excel\Excel::XLSX, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } catch (\Exception $e) {
            Log::error('Excel Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate Excel: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get payment collections in grouped format for export (same as screen view)
     */
    private function getPaymentCollectionsForExport($request)
    {
        $query = \App\Models\Payment::with(['customer', 'supplier', 'sale', 'purchase', 'purchaseReturn'])
            ->select('payments.*');

        // Apply same filters as paymentReportData
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('supplier_id') && $request->supplier_id != '') {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('location_id') && $request->location_id != '') {
            $query->where(function($q) use ($request) {
                $q->whereHas('sale', function($saleQuery) use ($request) {
                    $saleQuery->where('location_id', $request->location_id);
                })
                ->orWhereHas('purchase', function($purchaseQuery) use ($request) {
                    $purchaseQuery->where('location_id', $request->location_id);
                })
                ->orWhereHas('purchaseReturn', function($returnQuery) use ($request) {
                    $returnQuery->where('location_id', $request->location_id);
                });
            });
        }

        if ($request->has('payment_method') && $request->payment_method != '') {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('payment_type') && $request->payment_type != '') {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $payments = $query->orderBy('payment_date', 'desc')
                          ->orderBy('reference_no', 'desc')
                          ->orderBy('id', 'desc')
                          ->get();

        // Group payments by reference_no
        $collections = [];
        $groupedPayments = $payments->groupBy('reference_no');

        foreach ($groupedPayments as $referenceNo => $paymentGroup) {
            $firstPayment = $paymentGroup->first();
            $locationName = '';

            if ($firstPayment->sale) {
                $locationName = optional($firstPayment->sale->location)->name ?? '';
            } elseif ($firstPayment->purchase) {
                $locationName = optional($firstPayment->purchase->location)->name ?? '';
            } elseif ($firstPayment->purchaseReturn) {
                $locationName = optional($firstPayment->purchaseReturn->location)->name ?? '';
            }

            $paymentsData = $paymentGroup->map(function($payment) {
                $invoiceNo = '';
                $invoiceValue = 0;
                $invoiceDate = '';

                if ($payment->payment_type === 'sale' && $payment->sale) {
                    $invoiceNo = $payment->sale->invoice_no ?? '';
                    $invoiceValue = (float) ($payment->sale->final_total ?? 0);
                    $invoiceDate = $payment->sale->sales_date ? \Carbon\Carbon::parse($payment->sale->sales_date)->format('Y-m-d') : '';
                } elseif ($payment->payment_type === 'purchase' && $payment->purchase) {
                    $invoiceNo = $payment->purchase->invoice_no ?? '';
                    $invoiceValue = (float) ($payment->purchase->grand_total ?? 0);
                    $invoiceDate = $payment->purchase->purchase_date ? \Carbon\Carbon::parse($payment->purchase->purchase_date)->format('Y-m-d') : '';
                } elseif ($payment->purchaseReturn) {
                    $invoiceNo = $payment->purchaseReturn->invoice_no ?? '';
                    $invoiceValue = (float) ($payment->purchaseReturn->grand_total ?? 0);
                    $invoiceDate = $payment->purchaseReturn->return_date ? \Carbon\Carbon::parse($payment->purchaseReturn->return_date)->format('Y-m-d') : '';
                }

                return [
                    'id' => $payment->id,
                    'payment_date' => $payment->payment_date,
                    'invoice_date' => $invoiceDate,
                    'invoice_no' => $invoiceNo,
                    'invoice_value' => $invoiceValue,
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_type' => $payment->payment_type,
                    'cheque_number' => $payment->cheque_number,
                    'cheque_bank_branch' => $payment->cheque_bank_branch,
                    'cheque_valid_date' => $payment->cheque_valid_date,
                    'cheque_status' => $payment->cheque_status,
                    'notes' => $payment->notes,
                ];
            });

            $collections[] = [
                'reference_no' => $referenceNo,
                'payment_date' => $firstPayment->payment_date,
                'customer_name' => $firstPayment->customer ? $firstPayment->customer->full_name : '',
                'customer_address' => $firstPayment->customer ? $firstPayment->customer->address : '',
                'supplier_name' => $firstPayment->supplier ? $firstPayment->supplier->full_name : '',
                'location' => $locationName,
                'total_amount' => (float) $paymentGroup->sum('amount'),
                'payments' => $paymentsData->toArray(),
                'is_bulk' => (strpos($referenceNo, 'BLK-') === 0 || strpos($referenceNo, 'BULK-') === 0) && count($paymentsData) > 1
            ];
        }

        return $collections;
    }

    /**
     * Get payment report data as array for export
     */
    private function getPaymentReportDataArray($request)
    {
        $query = \App\Models\Payment::with(['customer', 'supplier', 'sale', 'purchase', 'purchaseReturn'])
            ->select('payments.*');

        // Apply same filters as paymentReportData method
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('supplier_id') && $request->supplier_id != '') {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('location_id') && $request->location_id != '') {
            $query->where(function($q) use ($request) {
                $q->whereHas('sale', function($saleQuery) use ($request) {
                    $saleQuery->where('location_id', $request->location_id);
                })
                ->orWhereHas('purchase', function($purchaseQuery) use ($request) {
                    $purchaseQuery->where('location_id', $request->location_id);
                })
                ->orWhereHas('purchaseReturn', function($returnQuery) use ($request) {
                    $returnQuery->where('location_id', $request->location_id);
                });
            });
        }

        if ($request->has('payment_method') && $request->payment_method != '') {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('payment_type') && $request->payment_type != '') {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }
}
