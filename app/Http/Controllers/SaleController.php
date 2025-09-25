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

    function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        $this->middleware('permission:view all sales|view own sales', ['only' => ['listSale', 'index', 'show']]);
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
        })->only(['index', 'listSale']);
    }

    /**
     * Validate credit limit for customer sales
     * Only checks credit limit when:
     * 1. Customer has credit sales (partial payment or credit payment method)
     * 2. Sale is finalized (not draft, quotation, suspend, jobticket)
     * 3. Customer is not walk-in and has credit limit set
     */
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

        // Calculate actual payment amount
        $actualPaymentAmount = 0;
        $hasCreditPayment = false;

        if (!empty($payments)) {
            $actualPaymentAmount = array_sum(array_column($payments, 'amount'));
            // Check if any payment method is 'credit'
            foreach ($payments as $payment) {
                if (isset($payment['payment_method']) && $payment['payment_method'] === 'credit') {
                    $hasCreditPayment = true;
                    break;
                }
            }
        }

        // Calculate remaining balance after payment
        $remainingBalance = max(0, $finalTotal - $actualPaymentAmount);

        // Only validate credit limit if:
        // 1. There's remaining balance (partial payment), OR
        // 2. Payment method explicitly includes 'credit'
        if ($remainingBalance <= 0 && !$hasCreditPayment) {
            return true; // Full payment made and no explicit credit sale
        }

        // Calculate projected new balance
        $currentBalance = $customer->current_balance;
        $projectedNewBalance = $currentBalance + $remainingBalance;

        // Check if projected balance exceeds credit limit
        if ($projectedNewBalance > $customer->credit_limit) {
            $availableCredit = max(0, $customer->credit_limit - $currentBalance);
            throw new \Exception(
                "Credit limit exceeded for {$customer->full_name}.\n" .
                "Current balance: Rs. " . number_format($currentBalance, 2) . "\n" .
                "Credit limit: Rs. " . number_format($customer->credit_limit, 2) . "\n" .
                "Available credit: Rs. " . number_format($availableCredit, 2) . "\n" .
                "Sale amount due: Rs. " . number_format($remainingBalance, 2) . "\n" .
                "This sale would exceed credit limit by Rs. " . number_format($projectedNewBalance - $customer->credit_limit, 2)
            );
        }

        return true;
    }

    public function listSale()
    {
        return view('sell.sale');
    }

    public function addSale()
    {
        return view('sell.add_sale');
    }

    public function pos()
    {
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

    //draft sales

    public function index(Request $request)
    {
        // Check if this is a DataTable request
        if ($request->has('draw') || $request->has('length')) {
            return $this->getDataTableSales($request);
        }

        // Original simple format for backward compatibility
        $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')
            ->where('status', 'final')
            ->orderBy('created_at', 'desc')
            ->limit(100) // Limit to prevent timeout
            ->get();

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
            
            // 2. Check if we have any sales (bypassing location scopes to get all sales)
            $totalSales = Sale::withoutGlobalScopes()->count();
            
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
            
            // Load related data (customer, user, location, payments) with correct column names
            // Note: We only load the columns we need to make the query faster
            $query->with([
                'customer:id,first_name,last_name,mobile_no',  // Customer info
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

            // 5. Get total count for pagination
            $totalCount = $query->count();
            
            // 6. Get the actual sales data with pagination
            $sales = $query->orderBy('created_at', 'desc')  // Newest first
                          ->skip($start)                      // Skip records for pagination
                          ->take($perPage)                    // Take only what we need
                          ->get();
            
            // 7. Format the data for DataTable
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

            // 8. Return the data in DataTable format
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

            // Cash in Hand: Cash Payments - Total Sales Returns
            $cashInHand = $cashPayments - $totalSalesReturns;

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

        // Validate walk-in customer cheque payment restriction
        if ($request->customer_id == 1 && !empty($request->payments)) {
            foreach ($request->payments as $payment) {
                if (isset($payment['payment_method']) && $payment['payment_method'] === 'cheque') {
                    return response()->json([
                        'status' => 400, 
                        'message' => 'Cheque payment is not allowed for Walk-In Customer. Please choose another payment method or select a different customer.',
                        'errors' => ['payment_method' => ['Cheque payment is not allowed for Walk-In Customer.']]
                    ]);
                }
            }
        }

        // Validate walk-in customer credit sales restriction (skip for suspended sales)
        if ($request->customer_id == 1 && $request->status !== 'suspend') {
            $finalTotal = $request->final_total ?? $request->total_amount ?? 0;
            $totalPayments = 0;
            
            // Calculate total payments made
            if (!empty($request->payments)) {
                foreach ($request->payments as $payment) {
                    $totalPayments += $payment['amount'] ?? 0;
                }
            }
            
            // Only block if there's insufficient payment (credit sale)
            if ($totalPayments < $finalTotal) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Credit sales are not allowed for Walk-In Customer. Please collect full payment or select a different customer.',
                    'errors' => ['amount_given' => ['Full payment required for Walk-In Customer.']]
                ]);
            }
        }

        try {
            // Pre-validation: Check credit limit BEFORE starting transaction
            $customer = Customer::findOrFail($request->customer_id);
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

            $sale = DB::transaction(function () use ($request, $id) {
                $isUpdate = $id !== null;
                $sale = $isUpdate ? Sale::findOrFail($id) : new Sale();
                $referenceNo = $isUpdate ? $sale->reference_no : $this->generateReferenceNo();

                $oldStatus = $isUpdate ? $sale->getOriginal('status') : null;
                $newStatus = $request->status;

                // ----- Invoice No Generation -----
                if (
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

                // Credit limit validation using centralized method
                $customer = Customer::findOrFail($request->customer_id);
                $this->validateCreditLimit($customer, $finalTotal, $request->payments ?? [], $newStatus);

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
                        $payment = Payment::create([
                            'payment_date' => $request->sales_date ?? Carbon::now('Asia/Colombo')->format('Y-m-d'),
                            'amount' => $paymentAmount,
                            'payment_method' => 'cash',
                            'reference_no' => $referenceNo,
                            'notes' => 'Advance payment for job ticket',
                            'payment_type' => 'sale',
                            'reference_id' => $sale->id,
                            'customer_id' => $sale->customer_id,
                        ]);
                        
                        // Use unified ledger service for payment
                        $this->unifiedLedgerService->recordSalePayment($payment, $sale);
                    }
                }

                // ----- Handle Payments (if not jobticket) -----
                if ($sale->status !== 'jobticket') {
                    $totalPaid = 0;
                    if (!empty($request->payments)) {
                        $totalPaid = $request->has('payments')
                            ? array_sum(array_column($request->payments, 'amount'))
                            : $sale->final_total;

                        if ($isUpdate) {
                            Payment::where('reference_id', $sale->id)->delete();
                            // Note: Ledger entries will be managed by UnifiedLedgerService
                        }

                        foreach ($request->payments as $paymentData) {
                            // Prepare payment data with enhanced cheque handling
                            $paymentCreateData = [
                                'payment_date' => Carbon::parse($paymentData['payment_date'])->format('Y-m-d'),
                                'amount' => $paymentData['amount'],
                                'payment_method' => $paymentData['payment_method'],
                                'reference_no' => $referenceNo,
                                'notes' => $paymentData['notes'] ?? '',
                                'payment_type' => 'sale',
                                'reference_id' => $sale->id,
                                'customer_id' => $request->customer_id,
                            ];

                            // Add payment method specific fields
                            if ($paymentData['payment_method'] === 'card') {
                                $paymentCreateData = array_merge($paymentCreateData, [
                                    'card_number' => $paymentData['card_number'] ?? null,
                                    'card_holder_name' => $paymentData['card_holder_name'] ?? null,
                                    'card_expiry_month' => $paymentData['card_expiry_month'] ?? null,
                                    'card_expiry_year' => $paymentData['card_expiry_year'] ?? null,
                                    'card_security_code' => $paymentData['card_security_code'] ?? null,
                                ]);
                            } elseif ($paymentData['payment_method'] === 'cheque') {
                                $paymentCreateData = array_merge($paymentCreateData, [
                                    'cheque_number' => $paymentData['cheque_number'] ?? null,
                                    'cheque_bank_branch' => $paymentData['cheque_bank_branch'] ?? null,
                                    'cheque_received_date' => isset($paymentData['cheque_received_date']) ? 
                                        Carbon::parse($paymentData['cheque_received_date'])->format('Y-m-d') : null,
                                    'cheque_valid_date' => isset($paymentData['cheque_valid_date']) ? 
                                        Carbon::parse($paymentData['cheque_valid_date'])->format('Y-m-d') : null,
                                    'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
                                    // Enhanced cheque fields
                                    'cheque_status' => $paymentData['cheque_status'] ?? 'pending',
                                    'payment_status' => $paymentData['cheque_status'] === 'cleared' ? 'completed' : 'pending',
                                ]);
                            } else {
                                // For cash, bank_transfer, etc.
                                $paymentCreateData['payment_status'] = 'completed';
                            }

                            $payment = Payment::create($paymentCreateData);

                            // Create cheque reminders if it's a cheque payment
                            if ($paymentData['payment_method'] === 'cheque' && isset($paymentData['cheque_valid_date'])) {
                                $payment->createReminders();
                            }

                            // Use unified ledger service for payment recording
                            $this->unifiedLedgerService->recordSalePayment($payment, $sale);
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
                            // total_due is auto-calculated by the database
                        ]);
                        
                        // Refresh to get updated generated total_due column
                        $sale->refresh();
                        
                        // Set payment status based on database calculated total_due
                        if ($sale->total_due <= 0) {
                            $sale->payment_status = 'Paid';
                        } elseif ($sale->total_paid > 0) {
                            $sale->payment_status = 'Partial';
                        } else {
                            $sale->payment_status = 'Due';
                        }
                        $sale->save();
                    } elseif ($isUpdate) {
                        $totalPaid = $sale->total_paid;
                    }

                    // --- FIX: total_paid should be min(amount_given, final_total) ---
                    $amountGiven = $request->amount_given ?? $sale->final_total;
                    $totalPaid = min($amountGiven, $finalTotal);

                    $sale->update([
                        'total_paid' => $totalPaid,
                        // Removed total_due - it's auto-calculated by the database
                        'amount_given' => $amountGiven,
                        'balance_amount' => max(0, $amountGiven - $sale->final_total),
                    ]);
                }

                // Check for partial payments for Walk-In Customer
                if ($request->customer_id == 1 && $amountGiven < $sale->final_total) {
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
                        
                        // Always process sale for final/suspend status
                        if (in_array($newStatus, ['final', 'suspend'])) {
                            $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE, $newStatus);
                        } else {
                            // For non-final statuses, just simulate batch selection
                            $this->simulateBatchSelection($productData, $sale->id, $request->location_id, $newStatus);
                        }
                    }
                }

                // ----- Ledger -----
                if ($isUpdate) {
                    // For updates, use updateSale method to handle proper cleanup and recreation
                    $this->unifiedLedgerService->updateSale($sale, $referenceNo);
                } else {
                    // For new sales, use regular recordSale method
                    $this->unifiedLedgerService->recordSale($sale);
                }

                $this->updatePaymentStatus($sale);
                return $sale;
            });

            $customer = Customer::findOrFail($sale->customer_id);
            $products = SalesProduct::where('sale_id', $sale->id)->get();
            $payments = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->get();
            $user = User::find($sale->user_id);
            // Use the location from the sale, not from user's first location
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

        $html = view('sell.receipt', $viewData)->render();

          


            try {
                $mobileNo = ltrim($customer->mobile_no, '0');
                $whatsAppApiUrl = env('WHATSAPP_API_URL'); // load from .env

                if (!empty($mobileNo) && !empty($whatsAppApiUrl)) {



                    // Render the 80mm thermal receipt view to HTML
                    $thermalHtml = view('sell.receipt', $viewData)->render();

                    // Generate PDF (no saving to disk)
                    $pdf = Pdf::loadHTML($thermalHtml)
                        ->setPaper([0, 0, 226.77, 842], 'portrait'); // 80mm x 297mm
                    $pdfContent = $pdf->output();

                    // Send to WhatsApp API
                    $response = Http::withHeaders([])
                        ->attach(
                            'files',
                            $pdfContent, // Directly attach binary content
                            "invoice_{$sale->invoice_no}_80mm.pdf"
                        )
                        ->post($whatsAppApiUrl, [
                            'number' => "+94" . $mobileNo,
                            'message' => "Dear {$customer->first_name}, your invoice #{$sale->invoice_no} has been generated successfully. Total amount: Rs. {$sale->final_total}. Thank you for your business!",
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




            return response()->json([
                'message' => $id ? 'Sale updated successfully.' : 'Sale recorded successfully.',
                'invoice_html' => $html,
                'data' => $viewData
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
            
        }
    }

    private function updatePaymentStatus($sale)
    {
        $totalPaid = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->sum('amount');

        $sale->total_paid = $totalPaid;
        $sale->save();
        
        // Refresh to get updated generated total_due column
        $sale->refresh();

        // Set payment status based on database calculated total_due
        if ($sale->total_due <= 0) {
            $sale->payment_status = 'Paid';
        } elseif ($sale->total_paid > 0) {
            $sale->payment_status = 'Partial';
        } else {
            $sale->payment_status = 'Due';
        }

        $sale->save();
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

            // Handle IMEI insertion if available
            if (!empty($productData['imei_numbers']) && is_array($productData['imei_numbers'])) {

                // Counter to limit IMEIs to current batch deduction quantity
                $count = 0;

                foreach ($productData['imei_numbers'] as $imei) {
                    if ($count >= $deduction['quantity']) break;

                    // Update IMEI status to 'sold'
                    ImeiNumber::where('imei_number', $imei)
                        ->where('product_id', $productData['product_id'])
                        ->where('batch_id', $deduction['batch_id']) // Use same batch ID as deduction
                        ->where('location_id', $locationId)
                        ->update(['status' => 'sold']);

                    // Record IMEI in sale_imeis table
                    SaleImei::create([
                        'sale_id' => $saleId,
                        'sale_product_id' => $saleProduct->id,
                        'product_id' => $productData['product_id'],
                        'batch_id' => $deduction['batch_id'],
                        'location_id' => $locationId,
                        'imei_number' => $imei,
                    ]);

                    $count++;
                }
            }
        }
    }
    private function deductBatchStock($batchId, $locationId, $quantity, $stockType)
    {
        Log::info("Deducting $quantity from batch ID $batchId at location $locationId");

        $batch = Batch::findOrFail($batchId);
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->firstOrFail();

        Log::info("Before deduction: LocationBatch qty: {$locationBatch->qty}, Batch qty: {$batch->qty}");

        DB::table('location_batches')
            ->where('id', $locationBatch->id)
            ->update(['qty' => DB::raw("GREATEST(qty - $quantity, 0)")]);

        // DB::table('batches')
        //     ->where('id', $batch->id)
        //     ->update(['qty' => DB::raw("GREATEST(qty - $quantity, 0)")]);

        $locationBatch->refresh();
        $batch->refresh();

        Log::info("After deduction: LocationBatch qty: {$locationBatch->qty}, Batch qty: {$batch->qty}");

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => -$quantity,
            'stock_type' => $stockType,
        ]);
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

        $locationBatch = LocationBatch::where('batch_id', $product->batch_id)
            ->where('location_id', $product->location_id)
            ->firstOrFail();

        DB::table('location_batches')
            ->where('id', $locationBatch->id)
            ->update(['qty' => DB::raw("qty + {$product->quantity}")]);

        // DB::table('batches')
        //     ->where('id', $product->batch_id)
        //     ->update(['qty' => DB::raw("qty + {$product->quantity}")]);

        $locationBatch->refresh();

        Log::info("After restoration: LocationBatch qty: {$locationBatch->qty}, Batch qty: {$product->batch->qty}");

        // Restore IMEI numbers to 'available' status
        $this->restoreImeiNumbers($product);

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $product->quantity,
            'stock_type' => $stockType,
        ]);
    }

    private function restoreImeiNumbers($salesProduct)
    {
        Log::info("Restoring IMEI numbers for sale product ID {$salesProduct->id}");
        
        // Get all IMEI numbers associated with this sale product
        $saleImeis = SaleImei::where('sale_product_id', $salesProduct->id)->get();
        
        foreach ($saleImeis as $saleImei) {
            // Update IMEI status back to 'available'
            $updated = ImeiNumber::where('imei_number', $saleImei->imei_number)
                ->where('product_id', $saleImei->product_id)
                ->where('batch_id', $saleImei->batch_id)
                ->where('location_id', $saleImei->location_id)
                ->update(['status' => 'available']);
                
            if ($updated) {
                Log::info("IMEI {$saleImei->imei_number} restored to available status");
            } else {
                Log::warning("Failed to restore IMEI {$saleImei->imei_number} to available status");
            }
            
            // Delete the sale IMEI record
            $saleImei->delete();
        }
        
        Log::info("Completed IMEI restoration for sale product ID {$salesProduct->id}");
    }

    private function generateReferenceNo()
    {
        return 'SALE-' . now()->format('YmdHis') . '-' . strtoupper(uniqid());
    }


    public function getSaleByInvoiceNo($invoiceNo)
    {
        $sale = Sale::with([
            'products.product.unit' // eager load product and its unit
        ])->where('invoice_no', $invoiceNo)->first();

        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        $products = $sale->products->map(function ($product) use ($sale) {
            $currentQuantity = $sale->getCurrentSaleQuantity($product->product_id);
            $product->current_quantity = $currentQuantity;

            // Add unit details if available
            $unit = optional(optional($product->product)->unit);
            $product->unit = $unit ? $unit->only([
                'id',
                'name',
                'short_name',
                'allow_decimal'
            ]) : null;

            return $product;
        });

        return response()->json([
            'sale_id' => $sale->id,
            'invoice_no' => $invoiceNo,
            'customer_id' => $sale->customer_id,
            'location_id' => $sale->location_id,
            'products' => $products,
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
                'products.batch',
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
                            'product' => optional($product->product)->only([
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
                            ]),
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
                        'product' => optional($product->product)->only([
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
                        ]),
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
                        'imei_numbers' => $imeiDetails,
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
            $customer = Customer::findOrFail($sale->customer_id);
            $products = SalesProduct::where('sale_id', $sale->id)->get();
            $payments = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->get();
            $totalDiscount = array_reduce($products->toArray(), function ($carry, $product) {
                return $carry + ($product['discount'] ?? 0);
            }, 0);

            // Fetch the user associated with the sale
            $user = User::find($sale->user_id);

            // Use the location from the sale, not from user's first location
            $location = $sale->location;

            $html = view('sell.receipt', [
                'sale' => $sale,
                'customer' => $customer,
                'products' => $products,
                'payments' => $payments,
                'total_discount' => $request->discount_amount ?? 0,
                'amount_given' => $sale->amount_given,
                'balance_amount' => $sale->balance_amount,
                'user' => $user,
                'location' => $location,
            ])->render();

            return response()->json(['invoice_html' => $html], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Log pricing errors for admin review
     */
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

    /**
     * Update cheque status
     */
    public function updateChequeStatus(Request $request, $paymentId)
    {
        $request->validate([
            'status' => 'required|in:pending,deposited,cleared,bounced,cancelled',
            'remarks' => 'nullable|string',
            'bank_charges' => 'nullable|numeric|min:0',
        ]);

        try {
            $payment = Payment::where('id', $paymentId)
                             ->where('payment_method', 'cheque')
                             ->firstOrFail();

            $payment->updateChequeStatus(
                $request->status,
                $request->remarks,
                $request->bank_charges ?? 0,
                auth()->id()
            );

            return response()->json([
                'status' => 200,
                'message' => 'Cheque status updated successfully',
                'payment' => $payment->fresh(['sale', 'chequeStatusHistory'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update cheque status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cheque management dashboard data
     */
    public function chequeManagement(Request $request)
    {
        $query = Payment::chequePayments()->with(['customer', 'sale']);

        // Filter by status
        if ($request->filled('status') && $request->status !== '' && $request->status !== 'all') {
            $query->where('cheque_status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date') && $request->from_date !== '') {
            $query->where('cheque_valid_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date') && $request->to_date !== '') {
            $query->where('cheque_valid_date', '<=', $request->to_date);
        }

        // Filter by customer
        if ($request->filled('customer_id') && $request->customer_id !== '' && $request->customer_id > 0) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by cheque number
        if ($request->filled('cheque_number') && $request->cheque_number !== '') {
            $query->where('cheque_number', 'like', '%' . $request->cheque_number . '%');
        }

        $cheques = $query->orderBy('cheque_valid_date', 'asc')->paginate(50);

        // Get summary stats with proper null handling
        $stats = [
            'total_pending' => Payment::pendingCheques()->sum('amount') ?? 0,
            'total_cleared' => Payment::clearedCheques()->sum('amount') ?? 0,
            'total_bounced' => Payment::bouncedCheques()->sum('amount') ?? 0,
            'due_soon_count' => Payment::dueSoon(7)->count() ?? 0,
            'overdue_count' => Payment::overdue()->count() ?? 0,
        ];

        if ($request->ajax()) {
            return response()->json([
                'cheques' => $cheques,
                'stats' => $stats
            ]);
        }

        return view('sell.cheque-management', compact('cheques', 'stats'));
    }

    /**
     * Get cheque status history
     */
    public function chequeStatusHistory($paymentId)
    {
        try {
            $payment = Payment::with(['chequeStatusHistory.user', 'customer', 'sale'])
                             ->where('payment_method', 'cheque')
                             ->findOrFail($paymentId);

            return response()->json([
                'status' => 200,
                'payment' => $payment,
                'history' => $payment->chequeStatusHistory()->with('user')->orderBy('created_at', 'desc')->get()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Cheque payment not found'
            ], 404);
        }
    }

    /**
     * Get pending reminders for cheques
     */
    public function pendingChequeReminders()
    {
        $reminders = \App\Models\ChequeReminder::pending()
                    ->with(['payment.customer', 'payment.sale'])
                    ->orderBy('reminder_date', 'asc')
                    ->get();

        return response()->json([
            'status' => 200,
            'reminders' => $reminders
        ]);
    }

    /**
     * Mark reminder as sent
     */
    public function markReminderSent(Request $request, $reminderId)
    {
        try {
            $reminder = \App\Models\ChequeReminder::findOrFail($reminderId);
            $reminder->markAsSent($request->sent_to);

            return response()->json([
                'status' => 200,
                'message' => 'Reminder marked as sent'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update reminder'
            ], 500);
        }
    }

    /**
     * Bulk update cheque status
     */
    public function bulkUpdateChequeStatus(Request $request)
    {
        $request->validate([
            'payment_ids' => 'required|array',
            'payment_ids.*' => 'integer|exists:payments,id',
            'status' => 'required|in:deposited,cleared,bounced,cancelled',
            'remarks' => 'nullable|string',
            'bank_charges' => 'nullable|numeric|min:0',
        ]);

        try {
            $successCount = 0;
            $errors = [];

            foreach ($request->payment_ids as $paymentId) {
                try {
                    $payment = Payment::where('id', $paymentId)
                                     ->where('payment_method', 'cheque')
                                     ->first();

                    if ($payment) {
                        $payment->updateChequeStatus(
                            $request->status,
                            $request->remarks,
                            $request->bank_charges ?? 0,
                            auth()->id()
                        );
                        $successCount++;
                    } else {
                        $errors[] = "Payment ID {$paymentId} not found or not a cheque payment";
                    }

                } catch (\Exception $e) {
                    $errors[] = "Failed to update payment ID {$paymentId}: " . $e->getMessage();
                }
            }

            return response()->json([
                'status' => 200,
                'message' => "{$successCount} cheques updated successfully",
                'success_count' => $successCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
