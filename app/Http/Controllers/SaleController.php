<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\LocationBatch;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\SalesReturn;
use App\Models\StockHistory;
use App\Models\SaleImei;
use App\Models\ImeiNumber;
use App\Services\UnifiedLedgerService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\JobTicket;
use Barryvdh\DomPDF\Facade\Pdf;


class SaleController extends Controller
{
    protected $unifiedLedgerService;
    protected $paymentService;

    function __construct(UnifiedLedgerService $unifiedLedgerService, PaymentService $paymentService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        $this->paymentService = $paymentService;
        $this->middleware('permission:view all sales|view own sales', ['only' => ['listSale', 'index', 'show', 'getDataTableSales', 'salesDetails']]);
        $this->middleware('permission:create sale', ['only' => ['addSale', 'storeOrUpdate']]);
        $this->middleware('permission:access pos', ['only' => ['pos']]);
        $this->middleware('permission:edit sale', ['only' => ['editSale']]);
        $this->middleware('permission:delete sale', ['only' => ['destroy']]);
        $this->middleware('permission:print sale invoice', ['only' => ['printInvoice']]);

        // Middleware for sale permissions
        // If user has 'view own sales', restrict to their own sales; otherwise, allow all sales
        $this->middleware(function ($request, $next) {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            if ($user && $user->can('view own sales') && !$user->can('view all sales')) {
                // Only allow access to own sales
                Sale::addGlobalScope('own_sale', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            }
            return $next($request);
        })->only(['index', 'listSale', 'getDataTableSales', 'salesDetails']);
    }

 
    private function validateCreditLimit($customer, $finalTotal, $payments, $saleStatus)
    {
        // Skip validation for walk-in customers
        if ($customer->id == 1) {
            return true;
        }

        // Skip validation if customer has no credit limit
        if ($customer->credit_limit <= 0) {
            return true;
        }

        // Skip validation for non-final sales (draft, quotation, suspend, jobticket)
        if (!in_array($saleStatus, ['final'])) {
            return true;
        }

        // Calculate actual payment amount and check payment methods
        $actualPaymentAmount = 0;
        $hasCreditPayment = false;
        $hasChequePayment = false;

        if (!empty($payments)) {
            $actualPaymentAmount = array_sum(array_column($payments, 'amount'));
            
            // Check payment methods
            foreach ($payments as $payment) {
                $paymentMethod = $payment['payment_method'] ?? '';
                
                if ($paymentMethod === 'credit') {
                    $hasCreditPayment = true;
                }
                
                // Skip credit limit validation for cheque payments
                if ($paymentMethod === 'cheque') {
                    $hasChequePayment = true;
                }
            }
        }

        // Skip credit limit validation if cheque payment is used
        if ($hasChequePayment) {
            return true; // Cheque payments don't check credit limit
        }

        // Calculate remaining balance after payment (amount that goes to credit)
        $remainingBalance = max(0, $finalTotal - $actualPaymentAmount);

        // Only validate credit limit if there's remaining balance OR explicit credit payment
        if ($remainingBalance <= 0 && !$hasCreditPayment) {
            return true; // Full payment made and no explicit credit sale
        }

        // Get customer's current outstanding balance (calculate fresh from ledger)
        $currentBalance = $customer->calculateBalanceFromLedger();
        
        // Calculate available credit remaining
        $availableCredit = max(0, $customer->credit_limit - $currentBalance);
        
        // Check if the credit amount exceeds available credit
        if ($remainingBalance > $availableCredit) {
            // Format error message with clear breakdown
            $errorMessage = "Credit limit exceeded for {$customer->full_name}.\n\n";
            $errorMessage .= "Credit Details:\n";
            $errorMessage .= "• Credit Limit: Rs " . number_format($customer->credit_limit, 2) . "\n";
            $errorMessage .= "• Current Outstanding: Rs " . number_format($currentBalance, 2) . "\n";
            $errorMessage .= "• Available Credit: Rs " . number_format($availableCredit, 2) . "\n\n";
            $errorMessage .= "Sale Details:\n";
            $errorMessage .= "• Total Sale Amount: Rs " . number_format($finalTotal, 2) . "\n";
            $errorMessage .= "• Payment Received: Rs " . number_format($actualPaymentAmount, 2) . "\n";
            $errorMessage .= "• Credit Amount Required: Rs " . number_format($remainingBalance, 2) . "\n\n";
            
            if ($availableCredit > 0) {
                $errorMessage .= "Maximum credit sale allowed: Rs " . number_format($availableCredit, 2) . "\n";
                $errorMessage .= "Exceeds limit by: Rs " . number_format($remainingBalance - $availableCredit, 2);
            } else {
                $errorMessage .= "No credit available. Please settle previous outstanding amount or pay full amount.";
            }
            
            throw new \Exception($errorMessage);
        }

        return true;
    }

    public function listSale()
    {
        // Get filter data for dropdowns
        $locations = \App\Models\Location::select('id', 'name')->get();
        $customers = \App\Models\Customer::select('id', 'first_name', 'last_name')->get();
        $users = \App\Models\User::select('id', 'full_name')->get();
        
        return view('sell.sale', compact('locations', 'customers', 'users'));
    }

    public function addSale()
    {
        return view('sell.add_sale');
    }

    public function pos()
    {
        // No need to pass sales reps - we use logged in user
        return view('sell.pos');
    }

    public function draft()
    {
        return view('sell.draft_list');
    }
    
    public function quotation()
    {
        return view('sell.quotation_list');
    }

    public function saleOrdersList()
    {
        return view('sell.sale_orders_list');
    }

    /**
     * Convert Sale Order to Invoice
     */
    public function convertToInvoice($id)
    {
        try {
            $saleOrder = Sale::findOrFail($id);
            
            // Validate it's a sale order
            if ($saleOrder->transaction_type !== 'sale_order') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This is not a Sale Order'
                ], 400);
            }
            
            // Validate not already converted
            if ($saleOrder->order_status === 'completed') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This Sale Order has already been converted to an invoice'
                ], 400);
            }
            
            // Convert using model method
            $invoice = $saleOrder->convertToInvoice();
            
            // Redirect to POS edit page for payment
            return response()->json([
                'status' => 200,
                'message' => 'Sale Order converted to Invoice successfully!',
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                ],
                'redirect_url' => route('sales.edit', $invoice->id)
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update Sale Order status and notes
     */
    public function updateSaleOrder(Request $request, $id)
    {
        try {
            $saleOrder = Sale::findOrFail($id);
            
            // Validate it's a sale order
            if ($saleOrder->transaction_type !== 'sale_order') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This is not a Sale Order'
                ], 400);
            }
            
            // Get the JSON data
            $data = $request->all();
            
            // Update allowed fields
            if (isset($data['order_status'])) {
                $saleOrder->order_status = $data['order_status'];
            }
            
            if (isset($data['order_notes'])) {
                $saleOrder->order_notes = $data['order_notes'];
            }
            
            if (isset($data['expected_delivery_date'])) {
                $saleOrder->expected_delivery_date = $data['expected_delivery_date'];
            }
            
            $saleOrder->save();
            
            return response()->json([
                'status' => 200,
                'message' => 'Sale Order updated successfully!',
                'sale_order' => $saleOrder
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel invoice and revert back to Sale Order
     * Used when user clicks Cancel on payment page after conversion
     */
    public function cancelConvertedInvoice($invoiceId)
    {
        try {
            $invoice = Sale::findOrFail($invoiceId);
            
            // Validate it's an invoice
            if ($invoice->transaction_type !== 'invoice') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This is not an invoice'
                ], 400);
            }
            
            // Find the original sale order
            $saleOrder = Sale::where('converted_to_sale_id', $invoiceId)
                ->where('transaction_type', 'sale_order')
                ->first();
            
            if (!$saleOrder) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Original Sale Order not found'
                ], 400);
            }
            
            // Revert the conversion
            $saleOrder->revertInvoiceConversion($invoiceId);
            
            return response()->json([
                'status' => 200,
                'message' => 'Invoice cancelled successfully. Sale Order restored to confirmed status.',
                'sale_order' => [
                    'id' => $saleOrder->id,
                    'order_number' => $saleOrder->order_number,
                    'status' => 'confirmed'
                ],
                'redirect_url' => route('sale-orders-list')
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    //draft sales

    public function index(Request $request)
    {
        // Check if this is a DataTable request
        if ($request->has('draw') || $request->has('length')) {
            return $this->getDataTableSales($request);
        }

        // Check if this is a request for Recent Transactions (includes all statuses)
        if ($request->has('recent_transactions') && $request->get('recent_transactions') == 'true') {
            // For Recent Transactions in POS, we need all statuses so frontend can filter by tabs
            // ✅ Exclude Sale Orders and cancelled invoices - only show actual active invoices
            $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')
                ->where(function($query) {
                    $query->where('transaction_type', 'invoice')
                          ->orWhereNull('transaction_type');
                })
                ->whereIn('status', ['final', 'quotation', 'draft', 'jobticket', 'suspended'])
                ->where('payment_status', '!=', 'Cancelled') // Exclude cancelled invoices
                ->orderBy('created_at', 'desc')
                ->limit(200) // Increased limit for Recent Transactions
                ->get();
        } else {
            // Original simple format for backward compatibility (only final sales)
            $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')
                ->where('status', 'final')
                ->orderBy('created_at', 'desc')
                ->limit(100) // Limit to prevent timeout
                ->get();
        }

        return response()->json(['sales' => $sales], 200);
    }

    /**
     * Get sales data for DataTable with server-side processing
     * 
     * This method fetches sales data from the database and returns it
     * in a format that DataTable can understand. It includes pagination,
     * search, and proper relationship loading.
     */
    public function getDataTableSales(Request $request)
    {
        try {
            // 1. Get basic parameters from DataTable request
            $perPage = (int) $request->input('length', 10);  // How many records per page
            $start = (int) $request->input('start', 0);      // Starting record number
            $draw = (int) $request->input('draw', 1);        // DataTable draw counter
            $search = $request->input('search.value', '');    // Search term
            
            // Make sure parameters are valid
            if ($perPage <= 0) $perPage = 10;
            if ($start < 0) $start = 0;
            
            // Get authenticated user
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            
            // 2. Check if we have any sales (bypassing location scopes to get all sales)
            $totalSalesQuery = Sale::withoutGlobalScopes();
            
            // If user can only view own sales, apply filter for total count too
            if ($user && $user->can('view own sales') && !$user->can('view all sales')) {
                $totalSalesQuery->where('user_id', $user->id);
            }
            
            $totalSales = $totalSalesQuery->count();
            
            // If no sales exist in database, return helpful message
            if ($totalSales === 0) {
                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'debug' => [
                        'message' => 'No sales found in database',
                        'suggestion' => 'Create some sales first using POS or Add Sale'
                    ]
                ]);
            }
            
            // 3. Build the main query (bypass location scopes to show all user's sales)
            $query = Sale::withoutGlobalScopes()->select([
                'id', 'invoice_no', 'sales_date', 'customer_id', 'user_id', 'location_id',
                'final_total', 'total_paid', 'total_due', 'payment_status', 'status', 
                'created_at', 'updated_at'
            ])->where('status', 'final');
            
            // Apply user-based filtering - if user can only view own sales, filter by user_id
            if ($user && $user->can('view own sales') && !$user->can('view all sales')) {
                $query->where('user_id', $user->id);
            }
            
            // Load related data (customer, user, location, payments) with correct column names
            // Note: We only load the columns we need to make the query faster
            // Load customer without global scopes to avoid location filtering
            $query->with([
                'customer' => function($q) {
                    $q->withoutGlobalScopes()->select('id', 'first_name', 'last_name', 'mobile_no');
                },
                'user:id,full_name',                           // User who made the sale
                'location:id,name',                            // Location where sale was made
                'payments:id,reference_id,amount,payment_method,payment_date,notes' // Payment info
            ]);
            
            // Count how many products are in each sale
            $query->withCount('products as total_items');

            // 4. Add search functionality if user is searching for something
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    // Search in invoice number
                    $q->where('invoice_no', 'like', "%{$search}%")
                      // Search in total amount
                      ->orWhere('final_total', 'like', "%{$search}%")
                      // Search in customer information
                      ->orWhereHas('customer', function ($customerQ) use ($search) {
                          $customerQ->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhere('mobile_no', 'like', "%{$search}%");
                      });
                });
            }

            // 5. Apply custom filters
            // Location filter
            if ($request->has('location_id') && !empty($request->location_id)) {
                $query->where('location_id', $request->location_id);
            }

            // Customer filter
            if ($request->has('customer_id') && !empty($request->customer_id)) {
                $query->where('customer_id', $request->customer_id);
            }

            // User filter
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Payment status filter
            if ($request->has('payment_status') && !empty($request->payment_status)) {
                $query->where('payment_status', $request->payment_status);
            }

            // Payment method filter
            if ($request->has('payment_method') && !empty($request->payment_method)) {
                $query->whereHas('payments', function ($paymentQ) use ($request) {
                    $paymentQ->where('payment_method', $request->payment_method);
                });
            }

            // Date range filter
            if ($request->has('start_date') && !empty($request->start_date)) {
                $query->whereDate('sales_date', '>=', $request->start_date);
            }
            if ($request->has('end_date') && !empty($request->end_date)) {
                $query->whereDate('sales_date', '<=', $request->end_date);
            }

            // 6. Get total count for pagination (after filters)
            $totalCount = $query->count();
            
            // 7. Get the actual sales data with pagination
            $sales = $query->orderBy('created_at', 'desc')  // Newest first
                          ->skip($start)                      // Skip records for pagination
                          ->take($perPage)                    // Take only what we need
                          ->get();
            
            // 8. Format the data for DataTable
            $salesData = [];
            foreach ($sales as $sale) {
                // Build customer name from first_name + last_name
                $customerName = '';
                if ($sale->customer) {
                    $customerName = trim(($sale->customer->first_name ?? '') . ' ' . ($sale->customer->last_name ?? ''));
                }
                
                // Use existing payment status from database (don't recalculate)
                $paymentStatus = $sale->payment_status;
                
                // Format payment methods for display
                $paymentMethods = [];
                if ($sale->payments && $sale->payments->count() > 0) {
                    foreach ($sale->payments as $payment) {
                        $paymentMethods[] = [
                            'id' => $payment->id,
                            'method' => ucfirst($payment->payment_method),
                            'amount' => (float) $payment->amount,
                            'date' => $payment->payment_date,
                            'notes' => $payment->notes
                        ];
                    }
                }
                
                // Create the data array for this sale
                $salesData[] = [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'sales_date' => $sale->sales_date,
                    'customer' => $sale->customer ? [
                        'id' => $sale->customer->id,
                        'first_name' => $sale->customer->first_name,
                        'last_name' => $sale->customer->last_name,
                        'name' => $customerName,
                        'phone' => $sale->customer->mobile_no
                    ] : null,
                    'user' => $sale->user ? [
                        'id' => $sale->user->id,
                        'name' => $sale->user->full_name
                    ] : null,
                    'location' => $sale->location ? [
                        'id' => $sale->location->id,
                        'name' => $sale->location->name
                    ] : null,
                    'payments' => $paymentMethods,  // Add payment methods
                    'final_total' => (float) $sale->final_total,
                    'total_paid' => (float) $sale->total_paid,
                    'total_due' => (float) $sale->total_due,
                    'payment_status' => $paymentStatus,
                    'status' => $sale->status,
                    'total_items' => (int) $sale->total_items,
                    'created_at' => $sale->created_at,
                    'updated_at' => $sale->updated_at
                ];
            }

            // 9. Return the data in DataTable format
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalCount,
                'recordsFiltered' => $totalCount,
                'data' => $salesData
            ]);

        } catch (\Exception $e) {
            // If something goes wrong, log the error and return error response
            Log::error('Sales DataTable Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to fetch sales data',
                'message' => $e->getMessage(),
                'draw' => $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ], 500);
        }
    }

    /**
     * Clear sales cache to force refresh (called after new sales)
     */
    public function clearSalesCache()
    {
        Cache::forget('sales_final_count');
        return response()->json(['message' => 'Sales cache cleared'], 200);
    }

    
    public function salesDetails($id)
    {
        try {
            $salesDetails = Sale::with('products.product', 'customer', 'location', 'payments')->findOrFail($id);
            return response()->json(['salesDetails' => $salesDetails], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sale not found'], 404);
        }
    }


    public function saleDailyReport()
    {
        return view('reports.daily_sales_report');
    }


    public function dailyReport(Request $request)
    {
        try {
            // Get start and end date from request or default to today
            $startDate = $request->input('start_date', Carbon::today()->startOfDay());
            $endDate = $request->input('end_date', Carbon::today()->endOfDay());

            // Convert inputs to Carbon instances if they are strings
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

            // 1. Fetch sales filtered by sales_date, created_at (Asia/Colombo), or both
            $salesQuery = Sale::with(['customer', 'location', 'user', 'payments', 'products'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('sales_date', [$startDate, $endDate])
                        ->orWhereBetween(
                            DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30')"),
                            [$startDate, $endDate]
                        );
                });

            // Apply filters
            if ($request->has('customer_id') && $request->customer_id) {
                $salesQuery->where('customer_id', $request->customer_id);
            }
            if ($request->has('user_id') && $request->user_id) {
                $salesQuery->where('user_id', $request->user_id);
            }
            if ($request->has('location_id') && $request->location_id) {
                $salesQuery->where('location_id', $request->location_id);
            }

            $sales = $salesQuery->get();

            // 2. Initialize totals
            $cashPayments = 0;
            $chequePayments = 0;
            $bankTransferPayments = 0;
            $cardPayments = 0;
            $creditTotal = 0;

            foreach ($sales as $sale) {
                foreach ($sale->payments as $payment) {
                    switch ($payment->payment_method) {
                        case 'cash':
                            $cashPayments += $payment->amount;
                            break;
                        case 'cheque':
                            $chequePayments += $payment->amount;
                            break;
                        case 'bank_transfer':
                            $bankTransferPayments += $payment->amount;
                            break;
                        case 'card':
                            $cardPayments += $payment->amount;
                            break;
                    }
                }
                $creditTotal += $sale->total_due;
            }

            // 3. Get all returns filtered by return_date, created_at (Asia/Colombo), or both
            $allReturnsQuery = SalesReturn::with(['customer', 'location', 'returnProducts', 'sale'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('return_date', [$startDate, $endDate])
                        ->orWhereBetween(
                            DB::raw("CONVERT_TZ(created_at, '+00:00', '+05:30')"),
                            [$startDate, $endDate]
                        );
                });

            if ($request->has('customer_id') && $request->customer_id) {
                $allReturnsQuery->where('customer_id', $request->customer_id);
            }
            if ($request->has('location_id') && $request->location_id) {
                $allReturnsQuery->where('location_id', $request->location_id);
            }

            $allReturns = $allReturnsQuery->get();

            // 4. Split returns into two groups:
            // - Returns for sales made today (today's sales returns)
            // - Returns for sales made before today (old sales, but returned today)
            $salesIds = $sales->pluck('id')->toArray();

            $todaySalesReturns = $allReturns->filter(function ($r) use ($salesIds) {
                return in_array($r->sale_id, $salesIds);
            })->values();

            $oldSaleReturns = $allReturns->filter(function ($r) use ($salesIds) {
                return !in_array($r->sale_id, $salesIds);
            })->values();

            // 5. Summaries
            $billTotal = $sales->sum('final_total'); // Only sales within the selected date range

            // Correctly sum discount values (fixed and percentage)
            $discounts = $sales->sum(function ($sale) {
                if ($sale->discount_type === 'percentage') {
                    return ($sale->subtotal * $sale->discount_amount / 100);
                }
                return $sale->discount_amount;
            });

            // Calculate total sales returns (only for sales in the current period)
            $totalSalesReturns = $todaySalesReturns->sum('return_total');

            // Net Income: Bill Total - Total Sales Returns
            $netIncome = $billTotal - $totalSalesReturns;

            // Cash in Hand: Cash Payments (actual cash received)
            $cashInHand = $cashPayments;

            // Payment Total: Sum of all payment methods
            $paymentTotal = $cashPayments + $chequePayments + $bankTransferPayments + $cardPayments;

            $summaries = [
                'billTotal' => $billTotal,
                'discounts' => $discounts,
                'cashPayments' => $cashPayments,
                'chequePayments' => $chequePayments,
                'bankTransfer' => $bankTransferPayments,
                'cardPayments' => $cardPayments,
                'salesReturns' => $totalSalesReturns,
                'paymentTotal' => $paymentTotal,
                'creditTotal' => $creditTotal,
                'netIncome' => $netIncome,
                'cashInHand' => $cashInHand,
            ];

            return response()->json([
                'sales' => $sales,
                'summaries' => $summaries,
                'todaySalesReturns' => $todaySalesReturns->values(),
                'oldSaleReturns' => $oldSaleReturns->values()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching sales data.',
                'details' => $e->getMessage()
            ], 500);
        }
    }



    public function edit($id)
    {
        try {
            $sale = Sale::with('products.product.batches', 'customer', 'location', 'payments')->findOrFail($id);

            foreach ($sale->products as $product) {
                // Use the Sale model's method to get batch quantity plus sold
                $product->batch_quantity_plus_sold = $sale->getBatchQuantityPlusSold(
                    $product->batch_id,
                    $sale->location_id,
                    $product->product_id
                );
            }

            if (request()->ajax() || request()->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'sales' => $sale,
                ]);
            }

            return view('sell.add_sale', compact('sale'));
        } catch (ModelNotFoundException $e) {
            if (request()->ajax() || request()->is('api/*')) {
                return response()->json(['message' => 'Sale not found'], 404);
            }
            return redirect()->route('list-sale')->with('error', 'Sale not found.');
        }
    }


    public function storeOrUpdate(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'location_id' => 'required|integer|exists:locations,id',
            'sales_date' => 'required|date',
            'status' => 'required|string',
            'invoice_no' => 'nullable|string|unique:sales,invoice_no',
            //  NEW: Sale Order fields (no sales_rep_id - using user_id)
            'transaction_type' => 'nullable|string|in:invoice,sale_order',
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
            'order_notes' => 'nullable|string|max:1000',
            // Rest of existing validations
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.quantity' => [
                'required',
                'numeric',
                'min:0.0001',
                function ($attribute, $value, $fail) use ($request) {
                    // Extract the index from the attribute, e.g., products.0.quantity => 0
                    if (preg_match('/products\.(\d+)\.quantity/', $attribute, $matches)) {
                        $index = $matches[1];
                        $productData = $request->input("products.$index");
                        if ($productData && isset($productData['product_id'])) {
                            $product = Product::find($productData['product_id']);
                            if ($product && $product->unit && !$product->unit->allow_decimal && floor($value) != $value) {
                                $fail("The quantity must be an integer for this unit.");
                            }
                        }
                    }
                },
            ],
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.subtotal' => 'required|numeric|min:0',
            'products.*.batch_id' => 'nullable|string|max:255',
            'products.*.price_type' => 'required|string|in:retail,wholesale,special',
            'products.*.discount' => 'nullable|numeric|min:0',
            'products.*.tax' => 'nullable|numeric|min:0',
            'products.*.imei_numbers' => 'nullable|array',
            'products.*.imei_numbers.*' => 'string|max:255',
            'payments' => 'nullable|array',
            'payments.*.payment_method' => 'required_with:payments|string',
            'payments.*.payment_date' => 'required_with:payments|date',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'total_paid' => 'nullable|numeric|min:0',
            'payment_mode' => 'nullable|string',
            'payment_status' => 'nullable|string',
            'payment_reference' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'total_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'required|string|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric|min:0',
            'amount_given' => 'nullable|numeric|min:0',
            'balance_amount' => 'nullable|numeric',
            'advance_amount' => 'nullable|numeric|min:0', // add this
            'jobticket_description' => 'nullable|string', // add this
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        // Optimized Walk-In Customer validation (customer_id == 1)
        if ($request->customer_id == 1) {
            // Quick cheque payment check without heavy iteration
            if (!empty($request->payments)) {
                $hasCheque = collect($request->payments)->contains('payment_method', 'cheque');
                if ($hasCheque) {
                    return response()->json([
                        'status' => 400, 
                        'message' => 'Cheque payment is not allowed for Walk-In Customer. Please choose another payment method or select a different customer.',
                        'errors' => ['payment_method' => ['Cheque payment is not allowed for Walk-In Customer.']]
                    ]);
                }

                // Quick credit sales validation (skip for suspended sales)
                if ($request->status !== 'suspend') {
                    $finalTotal = $request->final_total ?? $request->total_amount ?? 0;
                    $totalPayments = collect($request->payments)->sum('amount');
                    
                    // Only block if there's insufficient payment (credit sale)
                    if ($totalPayments < $finalTotal) {
                        return response()->json([
                            'status' => 400,
                            'message' => 'Credit sales are not allowed for Walk-In Customer. Please collect full payment or select a different customer.',
                            'errors' => ['amount_given' => ['Full payment required for Walk-In Customer.']]
                        ]);
                    }
                }
            }
        }

        try {
            // Pre-validation: Skip expensive credit limit check for Walk-In Customer
            if ($request->customer_id != 1) {
                // Only do credit limit validation for non Walk-In customers
                // Use withoutGlobalScopes to avoid location/route filtering
                $customer = Customer::withoutGlobalScopes()->findOrFail($request->customer_id);
                $subtotal = array_reduce($request->products, fn($carry, $p) => $carry + $p['subtotal'], 0);
                $discount = $request->discount_amount ?? 0;
                $finalTotal = $request->discount_type === 'percentage'
                    ? $subtotal - ($subtotal * $discount / 100)
                    : $subtotal - $discount;

                // Validate credit limit using centralized method
                try {
                    $this->validateCreditLimit($customer, $finalTotal, $request->payments ?? [], $request->status);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 400,
                        'message' => $e->getMessage(),
                        'errors' => ['credit_limit' => ['Credit limit would be exceeded by this sale.']]
                    ]);
                }
            }

            $sale = DB::transaction(function () use ($request, $id) {
                $isUpdate = $id !== null;
                $sale = $isUpdate ? Sale::with(['products'])->findOrFail($id) : new Sale();
                $referenceNo = $isUpdate ? $sale->reference_no : $this->generateReferenceNo();

                $oldStatus = $isUpdate ? $sale->getOriginal('status') : null;
                $newStatus = $request->status;

                // ✨ NEW: Determine transaction type
                $transactionType = $request->transaction_type ?? 'invoice';
                $orderNumber = null;
                $orderStatus = null;

                // ----- Invoice/Order No Generation -----
                if ($transactionType === 'sale_order') {
                    // Sale Order: Generate order number, no invoice yet
                    $orderNumber = Sale::generateOrderNumber($request->location_id);
                    $orderStatus = $request->order_status ?? 'pending';
                    $invoiceNo = null; // No invoice for sale order
                } elseif (
                    $isUpdate &&
                    $oldStatus === 'jobticket' &&
                    in_array($newStatus, ['final', 'suspend'])
                ) {
                    $invoiceNo = Sale::generateInvoiceNo($request->location_id);
                } elseif ($newStatus === 'jobticket') {
                    $prefix = 'J/';
                    $year = now()->format('Y');
                    $lastJobTicketSale = Sale::whereYear('created_at', now())
                        ->where('invoice_no', 'like', "$prefix$year/%")
                        ->latest()
                        ->first();
                    $number = $lastJobTicketSale ? ((int)substr($lastJobTicketSale->invoice_no, -4)) + 1 : 1;
                    $invoiceNo = "$prefix$year/" . str_pad($number, 4, '0', STR_PAD_LEFT);
                } elseif (!$isUpdate) {
                    if (in_array($newStatus, ['quotation', 'draft'])) {
                        $prefix = $newStatus === 'quotation' ? 'Q/' : 'D/';
                        $year = now()->format('Y');
                        $lastSale = Sale::whereYear('created_at', now())
                            ->where('invoice_no', 'like', "$prefix$year/%")
                            ->latest()
                            ->first();
                        $number = $lastSale ? ((int)substr($lastSale->invoice_no, -4)) + 1 : 1;
                        $invoiceNo = "$prefix$year/" . str_pad($number, 4, '0', STR_PAD_LEFT);
                    } else {
                        $invoiceNo = Sale::generateInvoiceNo($request->location_id);
                    }
                } else {
                    if (
                        in_array($oldStatus, ['draft', 'quotation']) &&
                        in_array($newStatus, ['final', 'suspend']) &&
                        !preg_match('/^\d+$/', $sale->invoice_no)
                    ) {
                        $invoiceNo = Sale::generateInvoiceNo($request->location_id);
                    } else {
                        $invoiceNo = $sale->invoice_no;
                    }
                }

                // ----- Amount Calculation -----
                $subtotal = array_reduce($request->products, fn($carry, $p) => $carry + $p['subtotal'], 0);
                $discount = $request->discount_amount ?? 0;
                $finalTotal = $request->discount_type === 'percentage'
                    ? $subtotal - ($subtotal * $discount / 100)
                    : $subtotal - $discount;

                // ----- Jobticket Payment Logic -----
                $advanceAmount = floatval($request->advance_amount ?? 0);

                if ($newStatus === 'jobticket') {
                    if ($advanceAmount >= $finalTotal) {
                        $totalPaid = $finalTotal;
                        $totalDue = 0;
                        $amountGiven = $advanceAmount;
                        $balanceAmount = $advanceAmount - $finalTotal;
                    } else {
                        $totalPaid = $advanceAmount;
                        $totalDue = $finalTotal - $advanceAmount;
                        $amountGiven = $advanceAmount;
                        $balanceAmount = 0;
                    }
                } else {
                    // Normal sale logic, default values
                    $amountGiven = $request->amount_given ?? $finalTotal;
                    // --- FIX: total_paid should be min(amount_given, final_total) ---
                    $totalPaid = min($amountGiven, $finalTotal);
                    $totalDue = max(0, $finalTotal - $totalPaid);
                    $balanceAmount = max(0, $amountGiven - $finalTotal);
                }

                // Credit limit validation using centralized method (skip for Walk-In)
                if ($request->customer_id != 1) {
                    // Use withoutGlobalScopes to avoid location/route filtering
                    $customer = Customer::withoutGlobalScopes()->findOrFail($request->customer_id);
                    $this->validateCreditLimit($customer, $finalTotal, $request->payments ?? [], $newStatus);
                }

                // ----- Save Sale -----
                $sale->fill([
                    'customer_id' => $request->customer_id,
                    'location_id' => $request->location_id,
                    'sales_date' => Carbon::parse($sale->created_at)
                        ->setTimezone('Asia/Colombo')
                        ->format('Y-m-d H:i:s'),
                    'status' => $newStatus,
                    'invoice_no' => $invoiceNo,
                    'reference_no' => $referenceNo,
                    'subtotal' => $subtotal,
                    'final_total' => $finalTotal,
                    'discount_type' => $request->discount_type,
                    'discount_amount' => $discount,
                    'user_id' => auth()->id(),
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                    'amount_given' => $amountGiven,
                    'balance_amount' => $balanceAmount,
                    // ✨ NEW: Sale Order fields (user_id is already set above)
                    'transaction_type' => $transactionType,
                    'order_number' => $orderNumber,
                    'order_date' => $transactionType === 'sale_order' ? now() : null,
                    'expected_delivery_date' => $request->expected_delivery_date,
                    'order_status' => $orderStatus,
                    'order_notes' => $request->order_notes,
                ])->save();

                // ----- Job Ticket Logic -----
                if ($sale->status === 'jobticket') {
                    JobTicket::updateOrCreate(
                        ['sale_id' => $sale->id],
                        [
                            'customer_id'      => $sale->customer_id,
                            'description'      => $request->jobticket_description ?? null,
                            'job_ticket_date'  => Carbon::now('Asia/Colombo'),
                            'status'           => 'open',
                            'advance_amount'   => $advanceAmount,
                            'balance_amount'   => $balanceAmount,
                        ]
                    );
                    $sale->update([
                        'total_paid' => $totalPaid,
                        'total_due' => $totalDue,
                        'amount_given' => $amountGiven,
                        'balance_amount' => $balanceAmount,
                    ]);
                    if ($advanceAmount > 0) {
                        $paymentAmount = min($advanceAmount, $finalTotal);
                        $paymentData = [
                            'payment_date' => $request->sales_date ?? Carbon::now('Asia/Colombo')->format('Y-m-d'),
                            'amount' => $paymentAmount,
                            'payment_method' => 'cash',
                            'reference_no' => $referenceNo,
                            'notes' => 'Advance payment for job ticket',
                        ];
                        
                        $payment = $this->paymentService->recordSalePayment($paymentData, $sale);
                    }
                }

                // ----- Handle Payments (if not jobticket and not sale_order) -----
                if ($sale->status !== 'jobticket' && $transactionType !== 'sale_order') {
                    $totalPaid = 0;
                    if (!empty($request->payments)) {
                        $totalPaid = array_sum(array_column($request->payments, 'amount'));

                        if ($isUpdate) {
                            // Batch delete for better performance
                            Payment::where('reference_id', $sale->id)->delete();
                        }

                        // Create payments individually using PaymentService
                        foreach ($request->payments as $paymentData) {
                            // Skip creating payments with zero amounts to prevent unnecessary ledger entries
                            if (empty($paymentData['amount']) || $paymentData['amount'] <= 0) {
                                continue;
                            }
                            
                            // Prepare payment data with enhanced cheque handling
                            $servicePaymentData = [
                                'payment_date' => $paymentData['payment_date'],
                                'amount' => $paymentData['amount'],
                                'payment_method' => $paymentData['payment_method'],
                                'reference_no' => $referenceNo,
                                'notes' => $paymentData['notes'] ?? '',
                            ];

                            // Add payment method specific fields
                            if ($paymentData['payment_method'] === 'card') {
                                $servicePaymentData = array_merge($servicePaymentData, [
                                    'card_number' => $paymentData['card_number'] ?? null,
                                    'card_holder_name' => $paymentData['card_holder_name'] ?? null,
                                    'card_expiry_month' => $paymentData['card_expiry_month'] ?? null,
                                    'card_expiry_year' => $paymentData['card_expiry_year'] ?? null,
                                    'card_security_code' => $paymentData['card_security_code'] ?? null,
                                    'payment_status' => 'completed',
                                ]);
                            } elseif ($paymentData['payment_method'] === 'cheque') {
                                $servicePaymentData = array_merge($servicePaymentData, [
                                    'cheque_number' => $paymentData['cheque_number'] ?? null,
                                    'cheque_bank_branch' => $paymentData['cheque_bank_branch'] ?? null,
                                    'cheque_received_date' => $paymentData['cheque_received_date'] ?? null,
                                    'cheque_valid_date' => $paymentData['cheque_valid_date'] ?? null,
                                    'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
                                    'payment_status' => $paymentData['cheque_status'] === 'cleared' ? 'completed' : 'pending',
                                ]);
                            } else {
                                // For cash, bank_transfer, etc.
                                $servicePaymentData['payment_status'] = 'completed';
                            }

                            // Create individual payment record using PaymentService
                            $payment = $this->paymentService->recordSalePayment($servicePaymentData, $sale);
                        }

                        // Get the created payments for verification
                        $createdPayments = Payment::where('reference_id', $sale->id)
                            ->where('payment_type', 'sale')
                            ->orderBy('id', 'desc')
                            ->limit(count($request->payments))
                            ->get();

                        // Create cheque reminders for cheque payments (move outside transaction later)
                        foreach ($createdPayments as $payment) {
                            if ($payment->payment_method === 'cheque' && $payment->cheque_valid_date) {
                                // Store for later processing outside transaction
                                $payment->scheduleReminderCreation = true;
                            }
                        }

                        // Calculate total paid for sale completion (include all payments except bounced)
                        $totalPaid = $sale->payments()
                            ->where(function($query) {
                                $query->where('payment_method', '!=', 'cheque')
                                      ->orWhere(function($subQuery) {
                                          $subQuery->where('payment_method', 'cheque')
                                                   ->where('cheque_status', '!=', 'bounced');
                                      });
                            })
                            ->sum('amount');

                        // Update sale totals
                        $sale->update([
                            'total_paid' => $totalPaid,
                        ]);
                        
                        // Set payment status based on calculated amounts (only for invoices)
                        if ($transactionType === 'invoice') {
                            $totalDue = max(0, $sale->final_total - $totalPaid);
                            if ($totalDue <= 0) {
                                $sale->payment_status = 'Paid';
                            } elseif ($sale->total_paid > 0) {
                                $sale->payment_status = 'Partial';
                            } else {
                                $sale->payment_status = 'Due';
                            }
                            $sale->save();
                        }
                        
                    } elseif ($isUpdate) {
                        $totalPaid = $sale->total_paid;
                    }

                    // --- FIX: total_paid should be min(amount_given, final_total) ---
                    $amountGiven = $request->amount_given ?? $sale->final_total;
                    $totalPaid = min($amountGiven, $finalTotal);

                    $sale->update([
                        'total_paid' => $totalPaid,
                        'amount_given' => $amountGiven,
                        'balance_amount' => max(0, $amountGiven - $sale->final_total),
                    ]);
                } elseif ($transactionType === 'sale_order') {
                    // Sale Order: No payment required
                    $sale->update([
                        'payment_status' => 'Due',
                        'total_paid' => 0,
                        'amount_given' => 0,
                        'balance_amount' => 0,
                    ]);
                }

                // Check for partial payments for Walk-In Customer (optimized) - Skip for sale orders
                if ($transactionType !== 'sale_order' && $request->customer_id == 1 && $amountGiven < $sale->final_total) {
                    throw new \Exception("Partial payment is not allowed for Walk-In Customer.");
                }

                // ----- Products Logic (allow multiple for jobticket) -----
                if ($isUpdate) {
                    // Store original quantities for stock validation during update
                    $originalProducts = [];
                    foreach ($sale->products as $product) {
                        $originalProducts[$product->product_id][$product->batch_id] = ($originalProducts[$product->product_id][$product->batch_id] ?? 0) + $product->quantity;

                        if (in_array($oldStatus, ['final', 'suspend'])) {
                            $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                        } else {
                            // For non-final statuses, still need to restore IMEI numbers
                            $this->restoreImeiNumbers($product);
                        }
                        $product->delete();
                    }
                }

                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    if ($product->stock_alert === 0) {
                        $this->processUnlimitedStockProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE);
                    } else {
                        // For updates, check stock availability considering the original sale quantities
                        if ($isUpdate && in_array($newStatus, ['final', 'suspend'])) {
                            $this->validateStockForUpdate($productData, $request->location_id, $originalProducts ?? []);
                        }
                        
                        // ✨ For Sale Orders: Don't reduce stock, just save items
                        if ($transactionType === 'sale_order') {
                            $this->simulateBatchSelection($productData, $sale->id, $request->location_id, 'draft');
                        }
                        // Always process sale for final/suspend status
                        elseif (in_array($newStatus, ['final', 'suspend'])) {
                            $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE, $newStatus);
                        } else {
                            // For non-final statuses, just simulate batch selection
                            $this->simulateBatchSelection($productData, $sale->id, $request->location_id, $newStatus);
                        }
                    }
                }

                // ----- Ledger (optimized) - Skip for Walk-In customers -----
                if ($request->customer_id != 1) {
                    if ($isUpdate) {
                        // For updates, use updateSale method to handle proper cleanup and recreation
                        $this->unifiedLedgerService->updateSale($sale, $referenceNo);
                    } else {
                        // For new sales, use regular recordSale method
                        $this->unifiedLedgerService->recordSale($sale);
                    }
                    // Note: Payment ledger processing is handled in payment creation loop above
                }

                return $sale;
            });

            // POST-TRANSACTION OPERATIONS (for better performance)
            
            // Create cheque reminders outside transaction - Skip for Walk-In customers
            if (!empty($request->payments) && $request->customer_id != 1) {
                $payments = Payment::where('reference_id', $sale->id)
                    ->where('payment_type', 'sale')
                    ->where('payment_method', 'cheque')
                    ->whereNotNull('cheque_valid_date')
                    ->get();
                    
                foreach ($payments as $payment) {
                    $payment->createReminders();
                }
            }

            // Load related data with eager loading for better performance
            $sale->load(['location', 'user']);
            
            // Optimized customer loading (avoid re-loading for Walk-In Customer)
            if ($sale->customer_id == 1) {
                // Create a simple Walk-In Customer object to avoid database call
                $customer = (object) [
                    'id' => 1,
                    'first_name' => 'Walk-In',
                    'last_name' => 'Customer',
                    'full_name' => 'Walk-In Customer',
                    'mobile_no' => '',
                    'email' => '',
                ];
            } else {
                // Use withoutGlobalScopes for receipt generation
                $customer = Customer::withoutGlobalScopes()->findOrFail($sale->customer_id);
            }
            
            $products = SalesProduct::with(['product', 'imeis'])->where('sale_id', $sale->id)->get();
            $payments = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->get();
            $user = $sale->user;
            $location = $sale->location;

            $viewData = [
                'sale' => $sale,
                'customer' => $customer,
                'products' => $products,
                'payments' => $payments,
                'total_discount' => $request->discount_amount ?? 0,
                'amount_given' => $sale->amount_given,
                'balance_amount' => $sale->balance_amount,
                'user' => $user,
                'location' => $location,
            ];

            // Get location-specific receipt view
            $receiptView = $location ? $location->getReceiptViewName() : 'sell.receipt';
            $html = view($receiptView, $viewData)->render();

            // SEND WHATSAPP MESSAGE ASYNCHRONOUSLY (NON-BLOCKING) - Skip for Walk-In customers
            if ($sale->customer_id != 1) {
                $this->sendWhatsAppAsync($customer, $sale, $viewData);
            }

            // Customize success message based on sale status and type
            $message = '';
            if ($id) {
                $message = 'Sale updated successfully.';
            } else {
                if ($sale->transaction_type === 'sale_order') {
                    $message = 'Sale Order created successfully!';
                } elseif ($sale->status === 'suspended') {
                    $message = 'Sale suspended successfully.';
                } else {
                    $message = 'Sale recorded successfully.';
                }
            }

            return response()->json([
                'message' => $message,
                'invoice_html' => $html,
                'data' => $viewData,
                'sale' => [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'order_number' => $sale->order_number, // ✨ NEW
                    'transaction_type' => $sale->transaction_type, // ✨ NEW
                    'order_status' => $sale->order_status, // ✨ NEW
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
            
        }
    }

    /**
     * Send WhatsApp message asynchronously (non-blocking)
     */
    private function sendWhatsAppAsync($customer, $sale, $viewData)
    {
        // Run in background to avoid blocking the response
        dispatch(function () use ($customer, $sale, $viewData) {
            try {
                $mobileNo = ltrim($customer->mobile_no, '0');
                $whatsAppApiUrl = env('WHATSAPP_API_URL');

                if (!empty($mobileNo) && !empty($whatsAppApiUrl)) {
                    // Get location from sale for receipt template selection
                    $location = $sale->location;
                    $receiptView = $location ? $location->getReceiptViewName() : 'sell.receipt';
                    
                    // Render the location-specific receipt view to HTML
                    $receiptHtml = view($receiptView, $viewData)->render();

                    // Generate PDF with appropriate paper size based on layout
                    $paperSize = [0, 0, 226.77, 842]; // Default 80mm x 297mm
                    $layoutType = $location ? $location->invoice_layout_pos : '80mm';
                    
                    if ($layoutType === 'a4') {
                        $paperSize = 'A4';
                    } elseif ($layoutType === 'dot_matrix') {
                        $paperSize = [0, 0, 612, 792]; // 8.5" x 11"
                    }
                    
                    $pdf = Pdf::loadHTML($receiptHtml)->setPaper($paperSize, 'portrait');
                    $pdfContent = $pdf->output();

                    // Send to WhatsApp API
                    $response = Http::timeout(30)->withHeaders([])
                        ->attach(
                            'files',
                            $pdfContent,
                            "invoice_{$sale->invoice_no}_{$layoutType}.pdf"
                        )
                        ->post($whatsAppApiUrl, [
                            'number' => "+94" . $mobileNo,
                            'message' => "Dear {$customer->first_name}, your invoice #{$sale->invoice_no} has been generated successfully. Total amount: Rs {$sale->final_total}. Thank you for your business!",
                        ]);

                    if ($response->successful()) {
                        Log::info('WhatsApp message sent successfully to: ' . $mobileNo);
                    } else {
                        Log::error('WhatsApp send failed: ' . $response->body());
                    }
                } else {
                    Log::info("WhatsApp skipped: API URL not set or mobile number missing.");
                }
            } catch (\Exception $ex) {
                Log::error('WhatsApp send error: ' . $ex->getMessage());
            }
        })->afterResponse();
    }

 

    private function processProductSale($productData, $saleId, $locationId, $stockType, $newStatus)
    {
        $totalQuantity = $productData['quantity'];
        $remainingQuantity = $totalQuantity;

        // We'll store info about each batch deduction
        $batchDeductions = [];

        if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
            // Specific batch selected
            $batch = Batch::findOrFail($productData['batch_id']);
            $locationBatch = LocationBatch::where('batch_id', $batch->id)
                ->where('location_id', $locationId)
                ->firstOrFail();

            if ($locationBatch->qty < $remainingQuantity) {
                throw new \Exception("Batch ID {$productData['batch_id']} does not have enough stock.");
            }

            $this->deductBatchStock($productData['batch_id'], $locationId, $remainingQuantity, $stockType);
            $batchDeductions[] = [
                'batch_id' => $batch->id,
                'quantity' => $remainingQuantity
            ];
        } else {
            // All batches selected — apply FIFO
            $batches = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productData['product_id'])
                ->where('location_batches.location_id', $locationId)
                ->where('location_batches.qty', '>', 0)
                ->orderBy('batches.created_at')
                ->select('location_batches.batch_id', 'location_batches.qty')
                ->get();

            foreach ($batches as $batch) {
                if ($remainingQuantity <= 0) break;

                $deductQuantity = min($batch->qty, $remainingQuantity);

                $this->deductBatchStock($batch->batch_id, $locationId, $deductQuantity, $stockType);
                $batchDeductions[] = [
                    'batch_id' => $batch->batch_id,
                    'quantity' => $deductQuantity
                ];

                $remainingQuantity -= $deductQuantity;
            }

            // Only validate stock if the sale status is final/suspend
            if (in_array($newStatus, ['final', 'suspend'])) {
                if ($remainingQuantity > 0) {
                    throw new \Exception("Not enough stock across all batches to fulfill the sale.");
                }
            }
        }

        // Loop through batch deductions
        foreach ($batchDeductions as $deduction) {
            // Create sales_product record for this batch
            $saleProduct = SalesProduct::create([
                'sale_id' => $saleId,
                'product_id' => $productData['product_id'],
                'quantity' => $deduction['quantity'],
                'price' => $productData['unit_price'],
                'unit_price' => $productData['unit_price'],
                'subtotal' => $productData['subtotal'] * ($deduction['quantity'] / $totalQuantity),
                'batch_id' => $deduction['batch_id'],
                'location_id' => $locationId,
                'price_type' => $productData['price_type'],
                'discount_amount' => $productData['discount_amount'] ?? 0,
                'discount_type' => $productData['discount_type'] ?? 'fixed',
                'tax' => $productData['tax'] ?? 0,
            ]);

            // Handle IMEI insertion if available (optimized batch processing)
            if (!empty($productData['imei_numbers']) && is_array($productData['imei_numbers'])) {
                $imeiUpdates = [];
                $saleImeiInserts = [];
                $count = 0;

                foreach ($productData['imei_numbers'] as $imei) {
                    if ($count >= $deduction['quantity']) break;

                    // Prepare batch updates and inserts
                    $imeiUpdates[] = $imei;
                    $saleImeiInserts[] = [
                        'sale_id' => $saleId,
                        'sale_product_id' => $saleProduct->id,
                        'product_id' => $productData['product_id'],
                        'batch_id' => $deduction['batch_id'],
                        'location_id' => $locationId,
                        'imei_number' => $imei,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $count++;
                }

                // Batch update IMEI statuses
                if (!empty($imeiUpdates)) {
                    ImeiNumber::whereIn('imei_number', $imeiUpdates)
                        ->where('product_id', $productData['product_id'])
                        ->where('batch_id', $deduction['batch_id'])
                        ->where('location_id', $locationId)
                        ->update(['status' => 'sold']);

                    // Batch insert sale IMEIs
                    SaleImei::insert($saleImeiInserts);
                }
            }
        }
    }
    private function deductBatchStock($batchId, $locationId, $quantity, $stockType)
    {
        Log::info("Deducting $quantity from batch ID $batchId at location $locationId");

        // Optimized: Single update query with error handling
        $affected = DB::table('location_batches')
            ->where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->where('qty', '>=', $quantity) // Ensure sufficient stock
            ->update(['qty' => DB::raw("qty - $quantity")]);

        if ($affected === 0) {
            throw new \Exception("Insufficient stock in batch ID $batchId at location $locationId");
        }

        // Get the location batch for stock history (single query)
        $locationBatch = LocationBatch::where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->first();

        if ($locationBatch) {
            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity' => -$quantity,
                'stock_type' => $stockType,
            ]);
        }
    }

    private function processUnlimitedStockProductSale($productData, $saleId, $locationId, $stockType)
    {
        // Record the sales product for unlimited stock product
        SalesProduct::create([
            'sale_id' => $saleId,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'price' => $productData['unit_price'],
            'unit_price' => $productData['unit_price'],
            'subtotal' => $productData['subtotal'],
            'batch_id' => null,
            'location_id' => $locationId,
            'price_type' => $productData['price_type'],
            'discount_amount' => $productData['discount_amount'],
            'discount_type' => $productData['discount_type'],
            'tax' => $productData['tax'],
        ]);

        // Add stock history for unlimited stock product
        StockHistory::create([
            'loc_batch_id' => null,
            'quantity' => -$productData['quantity'],
            'stock_type' => $stockType,

        ]);
    }

    private function simulateBatchSelection($productData, $saleId, $locationId, $newStatus)
    {
        $totalQuantity = $productData['quantity'];
        $remainingQuantity = $totalQuantity;

        $batchDeductions = [];

        if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
            $batch = Batch::findOrFail($productData['batch_id']);
            $locationBatch = LocationBatch::where('batch_id', $batch->id)
                ->where('location_id', $locationId)
                ->first();

            // For draft/quotation, allow any quantity, even if it exceeds stock
            if (in_array($newStatus, ['draft', 'quotation', 'jobticket'])) {
                $batchDeductions[] = [
                    'batch_id' => $batch->id,
                    'quantity' => $remainingQuantity,
                ];
            } 
            else {
                // Only check stock for final/suspend status
                if ($locationBatch && $locationBatch->qty >= $remainingQuantity) {
                    $batchDeductions[] = [
                        'batch_id' => $batch->id,
                        'quantity' => $remainingQuantity
                    ];
                } else {
                    throw new \Exception("Not enough stock in selected batch.");
                }
            }
        } else {
            // For "all" batches, allow any quantity for draft/quotation
            if (in_array($newStatus, ['draft', 'quotation', 'jobticket'])) {
                // Just assign all to a pseudo batch (or null)
                $batchDeductions[] = [
                    'batch_id' => null,
                    'quantity' => $remainingQuantity
                ];
            } else {
                $batches = DB::table('location_batches')
                    ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                    ->where('batches.product_id', $productData['product_id'])
                    ->where('location_batches.location_id', $locationId)
                    ->where('location_batches.qty', '>', 0)
                    ->orderBy('batches.created_at')
                    ->select('location_batches.batch_id', 'location_batches.qty')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingQuantity <= 0) break;
                    $deductQuantity = min($batch->qty, $remainingQuantity);
                    $batchDeductions[] = [
                        'batch_id' => $batch->batch_id,
                        'quantity' => $deductQuantity
                    ];
                    $remainingQuantity -= $deductQuantity;
                }

                // Only validate stock if the sale status is final/suspend
                if (in_array($newStatus, ['final', 'suspend'])) {
                    if ($remainingQuantity > 0) {
                        throw new \Exception("Not enough stock across all batches to fulfill the sale.");
                    }
                }
            }
        }

        foreach ($batchDeductions as $deduction) {
            SalesProduct::create([
                'sale_id' => $saleId,
                'product_id' => $productData['product_id'],
                'quantity' => $deduction['quantity'],
                'price' => $productData['unit_price'],
                'unit_price' => $productData['unit_price'],
                'subtotal' => $productData['subtotal'] * ($deduction['quantity'] / $totalQuantity),
                'batch_id' => $deduction['batch_id'],
                'location_id' => $locationId,
                'price_type' => $productData['price_type'],
                'discount_amount' => $productData['discount_amount'] ?? 0,
                'discount_type' => $productData['discount_type'] ?? 'fixed',
                'tax' => $productData['tax'] ?? 0,
            ]);
        }
    }

    private function validateStockForUpdate($productData, $locationId, $originalProducts)
    {
        $totalQuantity = $productData['quantity'];
        $productId = $productData['product_id'];
        $batchId = $productData['batch_id'];
        
        // Get original quantity sold for this product/batch combination
        $originalQuantity = 0;
        if (isset($originalProducts[$productId])) {
            if ($batchId === 'all') {
                // For 'all' batches, sum all original quantities for this product
                $originalQuantity = array_sum($originalProducts[$productId]);
            } else {
                // For specific batch, get original quantity for this batch
                $originalQuantity = $originalProducts[$productId][$batchId] ?? 0;
            }
        }

        if (!empty($batchId) && $batchId != 'all') {
            // Specific batch selected
            $currentStock = Sale::getAvailableStock($batchId, $locationId);
            $availableStock = $currentStock + $originalQuantity;
            
            if ($totalQuantity > $availableStock) {
                throw new \Exception("Batch ID {$batchId} does not have enough stock. Available: {$availableStock}, Requested: {$totalQuantity}");
            }
        } else {
            // All batches selected - check total available stock
            $currentTotalStock = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productId)
                ->where('location_batches.location_id', $locationId)
                ->sum('location_batches.qty');
                
            $availableStock = $currentTotalStock + $originalQuantity;
            
            if ($totalQuantity > $availableStock) {
                throw new \Exception("Not enough stock available. Available: {$availableStock}, Requested: {$totalQuantity}");
            }
        }
    }

    private function restoreStock($product, $stockType)
    {
        Log::info("Restoring stock for product ID {$product->product_id} from batch ID {$product->batch_id} at location {$product->location_id}");

        // Optimized: Single update query
        $affected = DB::table('location_batches')
            ->where('batch_id', $product->batch_id)
            ->where('location_id', $product->location_id)
            ->update(['qty' => DB::raw("qty + {$product->quantity}")]);

        if ($affected > 0) {
            // Get location batch for stock history
            $locationBatch = LocationBatch::where('batch_id', $product->batch_id)
                ->where('location_id', $product->location_id)
                ->first();

            if ($locationBatch) {
                StockHistory::create([
                    'loc_batch_id' => $locationBatch->id,
                    'quantity' => $product->quantity,
                    'stock_type' => $stockType,
                ]);
            }
        }

        // Restore IMEI numbers to 'available' status
        $this->restoreImeiNumbers($product);
    }

    private function restoreImeiNumbers($salesProduct)
    {
        Log::info("Restoring IMEI numbers for sale product ID {$salesProduct->id}");
        
        // Get all IMEI numbers associated with this sale product
        $saleImeis = SaleImei::where('sale_product_id', $salesProduct->id)->get();
        
        if ($saleImeis->isNotEmpty()) {
            // Batch update IMEI statuses
            $imeiNumbers = $saleImeis->pluck('imei_number')->toArray();
            
            $updated = ImeiNumber::whereIn('imei_number', $imeiNumbers)
                ->where('product_id', $salesProduct->product_id)
                ->where('batch_id', $salesProduct->batch_id)
                ->where('location_id', $salesProduct->location_id)
                ->update(['status' => 'available']);
                
            Log::info("Updated {$updated} IMEI numbers to available status");
            
            // Batch delete sale IMEI records
            SaleImei::where('sale_product_id', $salesProduct->id)->delete();
        }
        
        Log::info("Completed IMEI restoration for sale product ID {$salesProduct->id}");
    }

    private function generateReferenceNo()
    {
        return 'SALE-' . now()->format('Ymd');
    }

    public function getSaleByInvoiceNo($invoiceNo)
    {
        $sale = Sale::with([
            'products.product.unit', // eager load product and its unit
            'salesReturns' // load existing returns
        ])->where('invoice_no', $invoiceNo)->first();

        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        // Check if this sale has already been returned
        if ($sale->salesReturns->count() > 0) {
            return response()->json([
                'error' => 'This sale has already been returned. Multiple returns for the same invoice are not allowed.',
                'returned_count' => $sale->salesReturns->count(),
                'return_details' => $sale->salesReturns->map(function($return) {
                    return [
                        'return_date' => $return->return_date,
                        'return_total' => $return->return_total,
                        'notes' => $return->notes
                    ];
                })
            ], 409); // 409 Conflict status code
        }

        $products = $sale->products->map(function ($product) use ($sale) {
            $currentQuantity = $sale->getCurrentSaleQuantity($product->product_id);
            $product->current_quantity = $currentQuantity;

            // Use the actual stored price from sales_products table
            // This already includes all discounts applied during the sale
            $actualPrice = $product->price; // This is the final price customer paid per unit
            
            // Add unit details with better null handling
            $productModel = $product->product;
            if ($productModel && $productModel->unit) {
                $product->unit = [
                    'id' => $productModel->unit->id,
                    'name' => $productModel->unit->name,
                    'short_name' => $productModel->unit->short_name,
                    'allow_decimal' => $productModel->unit->allow_decimal
                ];
            } else {
                $product->unit = [
                    'id' => null,
                    'name' => 'Pieces',
                    'short_name' => 'Pc(s)',
                    'allow_decimal' => false
                ];
            }

            // Set the return price (same as the price customer actually paid)
            $product->return_price = $actualPrice;

            return $product;
        })->filter(function ($product) {
            // Only include products with current quantity > 0
            return $product->current_quantity > 0;
        })->values(); // Reset array keys after filtering

        return response()->json([
            'sale_id' => $sale->id,
            'invoice_no' => $invoiceNo,
            'customer_id' => $sale->customer_id,
            'location_id' => $sale->location_id,
            'products' => $products,
            // Include original sale discount information for proportional calculation
            'original_discount' => [
                'discount_type' => $sale->discount_type, // 'percentage' or 'fixed'
                'discount_amount' => $sale->discount_amount ?? 0,
                'subtotal' => $sale->subtotal ?? 0,
                'final_total' => $sale->final_total ?? 0,
                'total_original_quantity' => $sale->products->sum('quantity') // Total quantity in original sale
            ]
        ], 200);
    }

    public function searchSales(Request $request)
    {
        $term = $request->get('term');
        $sales = Sale::where('invoice_no', 'LIKE', '%' . $term . '%')
            ->orWhere('id', 'LIKE', '%' . $term . '%')
            ->get(['invoice_no as value', 'id']);

        return response()->json($sales);
    }

    public function fetchSuspendedSales()
    {
        try {
            $suspendedSales = Sale::where('status', 'suspend')
                ->with(['customer', 'products.product'])
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'invoice_no' => $sale->invoice_no, // Changed from reference_no
                        'sales_date' => $sale->created_at, // Changed to full date object
                        'customer' => $sale->customer ? ['name' => trim($sale->customer->first_name . ' ' . $sale->customer->last_name)] : ['name' => 'Walk-In Customer'], // Nested object
                        'products' => $sale->products->toArray(), // Full products array for .length
                        'final_total' => $sale->final_total, // Raw number, not formatted
                    ];
                });
            
            return response()->json($suspendedSales->values(), 200); // Ensure it returns an array
        } catch (\Exception $e) {
            logger()->error('Error fetching suspended sales: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch suspended sales'], 500);
        }
    }

    public function editSale($id)
    {
        try {
            // Fetch sale details with related models, including product.unit
            $sale = Sale::with([
                'products.product.unit', // eager load unit relation
                'products.product.batches.locationBatches.location', // eager load all batches for the product
                'products.batch',
                'products.imeis', // Load IMEI numbers for each product
                'customer',
                'location'
            ])->findOrFail($id);

            $saleDetails = [
                'sale' => $sale->only([
                    'id',
                    'customer_id',
                    'location_id',
                    'sales_date',
                    'sale_type',
                    'status',
                    'invoice_no',
                    'subtotal',
                    'discount_type',
                    'discount_amount',
                    'final_total',
                    'total_paid',
                    'total_due',
                    'payment_status',
                    'created_at',
                    'updated_at'
                ]),
                'sale_products' => $sale->products->map(function ($product) use ($sale) {
                    // IMEI Numbers from SaleImei model
                    $imeiDetails = $product->imeis->map(function ($imei) {
                        return [
                            'id' => $imei->id,
                            'imei_number' => $imei->imei_number,
                            'batch_id' => $imei->batch_id,
                            'location_id' => $imei->location_id,
                            'created_at' => $imei->created_at,
                            'updated_at' => $imei->updated_at,
                        ];
                    });

                    // Get unit details if available
                    $unit = optional(optional($product->product)->unit);
                    $unitDetails = $unit ? $unit->only([
                        'id',
                        'name',
                        'short_name',
                        'allow_decimal'
                    ]) : null;

                    if ($product->product && $product->product->stock_alert === 0) {
                        return [
                            'id' => $product->id,
                            'sale_id' => $product->sale_id,
                            'product_id' => $product->product_id,
                            'batch_id' => 'all',
                            'location_id' => $product->location_id,
                            'quantity' => $product->quantity,
                            'price_type' => $product->price_type,
                            'price' => $product->price,
                            'discount_type' => $product->discount_type,
                            'discount_amount' => $product->discount_amount,
                            'tax' => $product->tax,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'total_quantity' => 'Unlimited',
                            'current_stock' => 'Unlimited',
                            'product' => array_merge(
                                optional($product->product)->only([
                                    'id',
                                    'product_name',
                                    'sku',
                                    'unit_id',
                                    'brand_id',
                                    'main_category_id',
                                    'sub_category_id',
                                    'stock_alert',
                                    'alert_quantity',
                                    'product_image',
                                    'description',
                                    'is_imei_or_serial_no',
                                    'is_for_selling',
                                    'product_type',
                                    'pax',
                                    'original_price',
                                    'retail_price',
                                    'whole_sale_price',
                                    'special_price',
                                    'max_retail_price'
                                ]) ?? [],
                                ['batches' => []] // Ensure batches is always an array
                            ),
                            'unit' => $unitDetails,
                            'batch' => null,
                            'imei_numbers' => $product->imeis->pluck('imei_number')->toArray(),
                            'imeis' => $imeiDetails,
                        ];
                    }

                    $batchId = $product->batch_id ?? 'all';
                    
                    // Get current available stock
                    $currentStock = $batchId === 'all'
                        ? DB::table('location_batches')
                        ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                        ->where('batches.product_id', $product->product_id)
                        ->where('location_batches.location_id', $product->location_id)
                        ->sum('location_batches.qty')
                        : Sale::getAvailableStock($batchId, $product->location_id);
                    
                    // For editing: max allowed = current stock + quantity from this sale
                    // This represents what would be available if we "undo" this sale
                    $totalAllowedQuantity = $currentStock + $product->quantity;

                    // Get product batches with location data for frontend compatibility
                    $productBatches = [];
                    if ($product->product && $product->product->batches) {
                        $productBatches = $product->product->batches->map(function ($batch) {
                            return [
                                'id' => $batch->id,
                                'batch_no' => $batch->batch_no,
                                'product_id' => $batch->product_id,
                                'unit_cost' => $batch->unit_cost,
                                'wholesale_price' => $batch->wholesale_price,
                                'special_price' => $batch->special_price,
                                'retail_price' => $batch->retail_price,
                                'max_retail_price' => $batch->max_retail_price,
                                'expiry_date' => $batch->expiry_date,
                                'location_batches' => $batch->locationBatches ? $batch->locationBatches->map(function ($lb) {
                                    return [
                                        'batch_id' => $lb->batch_id,
                                        'location_id' => $lb->location_id,
                                        'location_name' => optional($lb->location)->name ?? 'N/A',
                                        'quantity' => $lb->qty
                                    ];
                                })->toArray() : []
                            ];
                        })->toArray();
                    }

                    return [
                        'id' => $product->id,
                        'sale_id' => $product->sale_id,
                        'product_id' => $product->product_id,
                        'batch_id' => $product->batch_id,
                        'location_id' => $product->location_id,
                        'quantity' => $product->quantity,
                        'price_type' => $product->price_type,
                        'price' => $product->price,
                        'discount_type' => $product->discount_type,
                        'discount_amount' => $product->discount_amount,
                        'tax' => $product->tax,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'total_quantity' => $totalAllowedQuantity,
                        'current_stock' => $currentStock,
                        'product' => array_merge(
                            optional($product->product)->only([
                                'id',
                                'product_name',
                                'sku',
                                'unit_id',
                                'brand_id',
                                'main_category_id',
                                'sub_category_id',
                                'stock_alert',
                                'alert_quantity',
                                'product_image',
                                'description',
                                'is_imei_or_serial_no',
                                'is_for_selling',
                                'product_type',
                                'pax',
                                'original_price',
                                'retail_price',
                                'whole_sale_price',
                                'special_price',
                                'max_retail_price'
                            ]) ?? [],
                            ['batches' => $productBatches] // Ensure batches is always an array
                        ),
                        'unit' => $unitDetails,
                        'batch' => optional($product->batch)->only([
                            'id',
                            'batch_no',
                            'product_id',
                            'qty',
                            'unit_cost',
                            'wholesale_price',
                            'special_price',
                            'retail_price',
                            'max_retail_price',
                            'expiry_date'
                        ]),
                        'imei_numbers' => $product->imeis->pluck('imei_number')->toArray(),
                        'imeis' => $imeiDetails,
                    ];
                }),
                'customer' => optional($sale->customer)->only([
                    'id',
                    'prefix',
                    'first_name',
                    'last_name',
                    'mobile_no',
                    'email',
                    'address',
                    'opening_balance',
                    'current_balance',
                    'location_id'
                ]),
                'location' => optional($sale->location)->only([
                    'id',
                    'name',
                    'location_id',
                    'address',
                    'province',
                    'district',
                    'city',
                    'email',
                    'mobile',
                    'telephone_no'
                ])
            ];

            if (request()->ajax() || request()->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'sale_details' => $saleDetails,
                ]);
            }
            return view('sell.pos', ['saleDetails' => $saleDetails]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 404, 'message' => 'Sale not found.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    public function deleteSuspendedSale($id)
    {
        $sale = Sale::findOrFail($id);
        if ($sale->status !== 'suspend') {
            return response()->json(['message' => 'Sale is not suspended.'], 400);
        }

        DB::transaction(function () use ($sale) {
            foreach ($sale->products as $product) {
                $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                $product->delete();
            }
            $sale->delete();
        });

        return response()->json(['message' => 'Suspended sale deleted and stock restored successfully.'], 200);
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);

        DB::transaction(function () use ($sale) {
            foreach ($sale->products as $product) {
                $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                $product->delete();
            }
            $sale->delete();
        });

        return response()->json([
            'status' => 200,
            'message' => 'Sale deleted and stock restored successfully.'
        ], 200);
    }

    public function printRecentTransaction($id)
    {
        try {
            $sale = Sale::findOrFail($id);
            $customer = Customer::withoutLocationScope()->findOrFail($sale->customer_id);
            $products = SalesProduct::with(['product', 'imeis'])->where('sale_id', $sale->id)->get();
            $payments = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->get();
            $totalDiscount = array_reduce($products->toArray(), function ($carry, $product) {
                return $carry + ($product['discount'] ?? 0);
            }, 0);

            // Fetch the user associated with the sale
            $user = User::find($sale->user_id);

            // Use the location from the sale, not from user's first location
            $location = $sale->location;

            $viewData = [
                'sale' => $sale,
                'customer' => $customer,
                'products' => $products,
                'payments' => $payments,
                'total_discount' => 0, // Fix: use 0 instead of undefined $request variable
                'amount_given' => $sale->amount_given,
                'balance_amount' => $sale->balance_amount,
                'user' => $user,
                'location' => $location,
            ];

            // Get location-specific receipt view
            $receiptView = $location ? $location->getReceiptViewName() : 'sell.receipt';
            $html = view($receiptView, $viewData)->render();

            return response()->json(['invoice_html' => $html], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function logPricingError(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'product_name' => 'required|string',
                'customer_type' => 'required|string',
                'batch_id' => 'nullable|integer',
                'batch_no' => 'nullable|string',
                'timestamp' => 'required|string',
                'location_id' => 'required|integer'
            ]);

            // Log to Laravel log file with structured data
            Log::warning('POS Pricing Error', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'Unknown',
                'product_id' => $validated['product_id'],
                'product_name' => $validated['product_name'],
                'customer_type' => $validated['customer_type'],
                'batch_id' => $validated['batch_id'],
                'batch_no' => $validated['batch_no'],
                'location_id' => $validated['location_id'],
                'timestamp' => $validated['timestamp'],
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Pricing error logged successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log pricing error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to log pricing error'
            ], 500);
        }
    }

}
