<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Location;
use App\Models\SalesProduct;
use App\Models\Batch;
use App\Services\ProfitLossService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProfitLossExport;

class ProfitLossController extends Controller
{
    protected $profitLossService;

    public function __construct(ProfitLossService $profitLossService)
    {
        $this->profitLossService = $profitLossService;
    }

    /**
     * Display the main Profit & Loss report page
     */
    public function index(Request $request)
    {
        $locations = Location::all();
        $brands = Brand::all();
        
        // Default date range - current month
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        $locationIds = $request->location_ids ?? [];
        $reportType = $request->report_type ?? 'overall';

        // Generate the report data
        $reportData = $this->profitLossService->generateReport([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location_ids' => $locationIds,
            'report_type' => $reportType
        ]);

        return view('reports.profit-loss.index', compact(
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
     * Get overall sales summary
     */
    public function getOverallSummary(Request $request)
    {
        $filters = $this->getFilters($request);
        $summary = $this->profitLossService->getOverallSummary($filters);
        
        return response()->json($summary);
    }

    /**
     * Get product-wise profit/loss report
     */
    public function getProductWiseReport(Request $request)
    {
        $filters = $this->getFilters($request);
        $productReport = $this->profitLossService->getProductWiseReport($filters);
        
        return response()->json($productReport);
    }

    /**
     * Get batch-wise profit/loss report
     */
    public function getBatchWiseReport(Request $request)
    {
        $filters = $this->getFilters($request);
        $batchReport = $this->profitLossService->getBatchWiseReport($filters);
        
        return response()->json($batchReport);
    }

    /**
     * Get brand-wise profit/loss report
     */
    public function getBrandWiseReport(Request $request)
    {
        $filters = $this->getFilters($request);
        $brandReport = $this->profitLossService->getBrandWiseReport($filters);
        
        return response()->json($brandReport);
    }

    /**
     * Export report as PDF
     */
    public function exportPdf(Request $request)
    {
        $filters = $this->getFilters($request);
        $reportData = $this->profitLossService->generateReport($filters);
        
        $pdf = Pdf::loadView('reports.profit-loss.pdf', compact('reportData', 'filters'));
        
        $filename = 'profit-loss-report-' . date('Y-m-d-H-i-s') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export report as Excel
     */
    public function exportExcel(Request $request)
    {
        $filters = $this->getFilters($request);
        $filename = 'profit-loss-report-' . date('Y-m-d-H-i-s') . '.xlsx';
        
        return Excel::download(new ProfitLossExport($filters), $filename);
    }

    /**
     * Export report as CSV
     */
    public function exportCsv(Request $request)
    {
        $filters = $this->getFilters($request);
        $filename = 'profit-loss-report-' . date('Y-m-d-H-i-s') . '.csv';
        
        return Excel::download(new ProfitLossExport($filters), $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Get detailed product report with batch breakdown
     */
    public function getProductDetails(Request $request, $productId)
    {
        $filters = $this->getFilters($request);
        $filters['product_id'] = $productId;
        
        $productDetails = $this->profitLossService->getProductDetailedReport($filters);
        
        return response()->json($productDetails);
    }

    /**
     * Get FIFO cost calculation for a specific product
     */
    public function getFifoCostBreakdown(Request $request, $productId)
    {
        $filters = $this->getFilters($request);
        $fifoBreakdown = $this->profitLossService->getFifoCostBreakdown($productId, $filters);
        
        return response()->json($fifoBreakdown);
    }

    /**
     * Get date range presets (today, yesterday, this week, last week, etc.)
     */
    public function getDateRangePresets()
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
     * Get location-wise summary
     */
    public function getLocationWiseReport(Request $request)
    {
        $filters = $this->getFilters($request);
        $locationReport = $this->profitLossService->getLocationWiseReport($filters);
        
        return response()->json($locationReport);
    }

    /**
     * Get monthly comparison report
     */
    public function getMonthlyComparison(Request $request)
    {
        $year = $request->year ?? Carbon::now()->year;
        $locationIds = $request->location_ids ?? [];
        
        $monthlyData = $this->profitLossService->getMonthlyComparison($year, $locationIds);
        
        return response()->json($monthlyData);
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

    /**
     * Get top performing products
     */
    public function getTopPerformingProducts(Request $request)
    {
        $filters = $this->getFilters($request);
        $limit = $request->limit ?? 10;
        $sortBy = $request->sort_by ?? 'profit'; // profit, quantity, revenue
        
        $topProducts = $this->profitLossService->getTopPerformingProducts($filters, $limit, $sortBy);
        
        return response()->json($topProducts);
    }

    /**
     * Get profit margin analysis
     */
    public function getProfitMarginAnalysis(Request $request)
    {
        $filters = $this->getFilters($request);
        $marginAnalysis = $this->profitLossService->getProfitMarginAnalysis($filters);
        
        return response()->json($marginAnalysis);
    }
}