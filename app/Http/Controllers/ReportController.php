<?php
namespace App\Http\Controllers;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

use Illuminate\Http\Request;

class ReportController extends Controller
{
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
}
