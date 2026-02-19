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
        $currentUser = auth()->user();

        // Get filter data for dropdowns
        $locations = \App\Models\Location::select('id', 'name')->get();
        $customers = \App\Models\Customer::select('id', 'first_name', 'last_name')->get();

        // Apply same user filtering logic as UserController
        $isMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;

        $usersQuery = \App\Models\User::select('id', 'full_name');

        if (!$isMasterSuperAdmin) {
            // Non-Master Super Admin users cannot see Master Super Admin users
            $usersQuery->whereDoesntHave('roles', function($roleQuery) {
                $roleQuery->where('name', 'Master Super Admin');
            });
        }

        $users = $usersQuery->get();

        return view('sell.sale', compact('locations', 'customers', 'users'));
    }

    public function addSale()
    {
        return view('sell.add_sale');
    }

    public function pos()
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        // Determine which batch price types the user can access based on permissions
        $allowedPriceTypes = [];

        if ($user && $user->can('select retail price')) {
            $allowedPriceTypes[] = 'retail';
        }
        if ($user && $user->can('select wholesale price')) {
            $allowedPriceTypes[] = 'wholesale';
        }
        if ($user && $user->can('select special price')) {
            $allowedPriceTypes[] = 'special';
        }
        if ($user && $user->can('select max retail price')) {
            $allowedPriceTypes[] = 'max_retail';
        }

        // Check editing permissions
        $canEditUnitPrice = $user && $user->can('edit unit price in pos');
        $canEditDiscount = $user && $user->can('edit discount in pos');

        // Get price validation setting from database
        // 1 = STRICT mode (enforce permissions - only users with permission can edit)
        // 0 = FLEXIBLE mode (free editing - all users can edit regardless of permissions)
        $priceValidationEnabled = (int)(\App\Models\Setting::value('enable_price_validation') ?? 1);

        // Pass feature flag to view
        $useModularPOS = env('USE_MODULAR_POS', false); // Default to false (old system) for safety

        return view('sell.pos', compact('allowedPriceTypes', 'canEditUnitPrice', 'canEditDiscount', 'priceValidationEnabled', 'useModularPOS'));
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

            // ✨ Create ledger entry for the invoice (skip for Walk-In customers)
            if ($invoice->customer_id != 1) {
                Log::info('Creating ledger entry for converted invoice', [
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'invoice_no' => $invoice->invoice_no,
                    'final_total' => $invoice->final_total
                ]);

                $this->unifiedLedgerService->recordSale($invoice);

                Log::info('Ledger entry created for converted invoice', [
                    'invoice_id' => $invoice->id
                ]);
            }

            // Return success without redirecting to edit (stock already deducted)
            return response()->json([
                'status' => 200,
                'message' => 'Sale Order converted to Invoice successfully! Invoice created with proper stock allocation.',
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'final_total' => $invoice->final_total,
                    'customer_id' => $invoice->customer_id
                ],
                'print_url' => "/sales/print-recent-transaction/{$invoice->id}",
                'success' => true
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

            // Store original status to check for cancellation
            $originalStatus = $saleOrder->order_status;

            // Get the JSON data
            $data = $request->all();

            // Check if this is a cancellation request
            $isCancellation = isset($data['order_status']) &&
                             $data['order_status'] === 'cancelled' &&
                             $originalStatus !== 'cancelled';

            // If cancelling, use database transaction to restore stock
            if ($isCancellation) {
                DB::transaction(function () use ($saleOrder, $data) {
                    // Get all products from the sale order
                    $products = $saleOrder->products;

                    Log::info('Starting sale order cancellation via updateSaleOrder', [
                        'sale_order_id' => $saleOrder->id,
                        'products_count' => $products->count(),
                        'original_status' => $saleOrder->order_status
                    ]);

                    // Restore stock for each product
                    foreach ($products as $product) {
                        Log::info("Processing product for stock restoration", [
                            'product_id' => $product->product_id,
                            'batch_id' => $product->batch_id,
                            'quantity' => $product->quantity,
                            'location_id' => $product->location_id
                        ]);

                        // Use existing restoreStock function with sale_order_reversal type
                        $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_ORDER_REVERSAL);
                    }

                    // Update sale order status and other fields
                    $saleOrder->order_status = $data['order_status'];
                    $saleOrder->status = 'cancelled'; // Also update main status field

                    if (isset($data['order_notes'])) {
                        $saleOrder->order_notes = $data['order_notes'];
                    }

                    if (isset($data['expected_delivery_date'])) {
                        $saleOrder->expected_delivery_date = $data['expected_delivery_date'];
                    }

                    $saleOrder->save();

                    Log::info('Sale Order cancelled and stock restoration completed via updateSaleOrder', [
                        'sale_order_id' => $saleOrder->id,
                        'order_number' => $saleOrder->order_number,
                        'products_restored' => $products->count()
                    ]);
                });

                return response()->json([
                    'status' => 200,
                    'message' => 'Sale Order cancelled successfully and stock restored!',
                    'sale_order' => $saleOrder->fresh()
                ], 200);
            } else {
                // Normal update (not cancellation)
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
            }

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

            // ✅ SIMPLE: Just revert THIS invoice back to sale order
            // No need to find original sale order - it's the same record!
            $invoice->revertToSaleOrder();

            return response()->json([
                'status' => 200,
                'message' => 'Invoice cancelled successfully. Reverted back to Sale Order.',
                'sale_order' => [
                    'id' => $invoice->id,
                    'order_number' => $invoice->order_number,
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
                ->whereIn('status', ['final', 'quotation', 'draft', 'jobticket', 'suspend'])
                ->where('payment_status', '!=', 'Cancelled') // Exclude cancelled invoices
                ->orderBy('created_at', 'desc')
                ->limit(200) // Increased limit for Recent Transactions
                ->get();
        } else {
            // Check if this is specifically for sale orders list page
            if ($request->has('sale_orders') && $request->get('sale_orders') == 'true') {
                // Only return sale orders for sale orders list page
                $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')
                    ->where('transaction_type', 'sale_order')
                    ->orderBy('created_at', 'desc')
                    ->limit(200)
                    ->get();
            }
            // Check if this is a request for draft, quotation, or suspend sales
            elseif ($request->has('status') && in_array($request->get('status'), ['draft', 'quotation', 'suspend'])) {
                // Return sales with the specified status
                $status = $request->get('status');
                $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')
                    ->where('status', $status)
                    ->orderBy('created_at', 'desc')
                    ->limit(200)
                    ->get();
            }
            else {
                // Original logic for All Sales page - only final invoices
                // ✅ When customer_id is passed (e.g., from bulk payments), bypass LocationScope
                // so all customer sales across locations are visible
                $query = $request->has('customer_id')
                    ? Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                        ->with('products.product', 'customer', 'location', 'payments', 'user')
                        ->where('customer_id', $request->customer_id)
                    : Sale::with('products.product', 'customer', 'location', 'payments', 'user');

                $sales = $query
                    ->where('status', 'final')
                    ->where('transaction_type', '!=', 'sale_order') // Explicitly exclude sale orders
                    ->where(function($query) {
                        // Only include actual invoices
                        $query->where('transaction_type', 'invoice')
                              ->orWhereNull('transaction_type'); // Legacy records without transaction_type
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get();
            }
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
                'created_at', 'updated_at', 'transaction_type', 'sale_notes'
            ])->where('status', 'final')
            ->where('transaction_type', '!=', 'sale_order') // Exclude sale orders from All Sales
            ->where(function($q) {
                // Only include actual invoices
                $q->where('transaction_type', 'invoice')
                  ->orWhereNull('transaction_type'); // Legacy records without transaction_type
            });

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
                    'sale_notes' => $sale->sale_notes,
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
            // ✅ SIMPLE: Just show final invoices (sale orders become invoices when converted)
            $salesQuery = Sale::with(['customer', 'location', 'user', 'payments', 'products'])
                ->where('status', 'final')
                ->where('transaction_type', 'invoice') // Only show invoices
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

            // Payment Total: Sum of all payment methods (excluding credit)
            $paymentTotal = $cashPayments + $chequePayments + $bankTransferPayments + $cardPayments;

            // Net Income: Bill Total - Total Sales Returns
            $netIncome = $billTotal - $totalSalesReturns;

            // Cash in Hand: Total of all actual payments received (Cash + Card + Cheque + Bank Transfer)
            // Note: This shows money collected, not accounting for pending return refunds
            $cashInHand = $paymentTotal;

            // Calculate total free quantity from all sales
            $totalFreeQuantity = $sales->sum(function ($sale) {
                return $sale->products->sum('free_quantity');
            });

            // Calculate total paid quantity from all sales
            $totalPaidQuantity = $sales->sum(function ($sale) {
                return $sale->products->sum('quantity');
            });

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
                'totalFreeQuantity' => $totalFreeQuantity,
                'totalPaidQuantity' => $totalPaidQuantity,
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
        // ✨ PERFORMANCE FIX: Set database query timeout for faster failure
        DB::statement('SET SESSION wait_timeout=30');
        DB::statement('SET SESSION interactive_timeout=30');

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'location_id' => 'required|integer|exists:locations,id',
            'sales_date' => 'required|date',
            'status' => 'required|string',
            'invoice_no' => 'nullable|string|unique:sales,invoice_no',
            'sale_notes' => 'nullable|string|max:2000',
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
            'products.*.free_quantity' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value !== null && $value > 0) {
                        // Extract the index from the attribute
                        if (preg_match('/products\.(\d+)\.free_quantity/', $attribute, $matches)) {
                            $index = $matches[1];
                            $productData = $request->input("products.$index");
                            if ($productData && isset($productData['product_id'])) {
                                $product = Product::find($productData['product_id']);
                                // Validate unit type (integer vs decimal)
                                if ($product && $product->unit && !$product->unit->allow_decimal && floor($value) != $value) {
                                    $fail("The free quantity must be an integer for this unit.");
                                }
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
            // Floating balance fields
            'use_floating_balance' => 'nullable|boolean',
            'floating_balance_amount' => 'nullable|numeric|min:0',
            // Shipping validation rules
            'shipping_details' => 'nullable|string|max:2000',
            'shipping_address' => 'nullable|string|max:1000',
            'shipping_charges' => 'nullable|numeric|min:0|max:999999.99',
            'shipping_status' => 'nullable|string|in:pending,ordered,shipped,delivered,cancelled',
            'delivered_to' => 'nullable|string|max:255',
            'delivery_person' => 'nullable|string|max:255',
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
            $startTime = microtime(true);
            Log::info('Sale processing started', ['customer_id' => $request->customer_id, 'start_time' => $startTime]);

            // Pre-validation: Skip expensive credit limit check for Walk-In Customer
            if ($request->customer_id != 1) {
                // Only do credit limit validation for non Walk-In customers
                // Use withoutGlobalScopes to avoid location/route filtering
                $customer = Customer::withoutGlobalScopes()->findOrFail($request->customer_id);

                // 🔧 FIX: Calculate subtotal correctly from quantity × unit_price (don't trust frontend)
                $subtotal = 0;
                if (!empty($request->products)) {
                    foreach ($request->products as $productData) {
                        $quantity = floatval($productData['quantity'] ?? 0);
                        $unitPrice = floatval($productData['unit_price'] ?? 0);
                        $subtotal += ($quantity * $unitPrice);
                    }
                }

                $discount = $request->discount_amount ?? 0;
                $shippingCharges = $request->shipping_charges ?? 0; // Add shipping here too

                $finalTotal = $request->discount_type === 'percentage'
                    ? $subtotal - ($subtotal * $discount / 100) + $shippingCharges
                    : $subtotal - $discount + $shippingCharges;

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

            $transactionStartTime = microtime(true);
            $sale = DB::transaction(function () use ($request, $id, $transactionStartTime) {
                Log::info('Transaction started', ['time_since_start' => microtime(true) - $transactionStartTime]);
                $isUpdate = $id !== null;
                $sale = $isUpdate ? Sale::with(['products'])->findOrFail($id) : new Sale();
                $referenceNo = $isUpdate ? $sale->reference_no : $this->generateReferenceNo();

                $oldStatus = $isUpdate ? $sale->getOriginal('status') : null;
                $newStatus = $request->status;

                // 🔧 CRITICAL FIX: Recalculate and validate all product subtotals from server-side
                // This prevents incorrect subtotals from frontend calculation errors
                if (!empty($request->products)) {
                    $correctedProducts = [];
                    foreach ($request->products as $productData) {
                        $quantity = floatval($productData['quantity'] ?? 0);
                        $unitPrice = floatval($productData['unit_price'] ?? 0);
                        $frontendSubtotal = floatval($productData['subtotal'] ?? 0);

                        // Recalculate the correct subtotal
                        $correctSubtotal = $quantity * $unitPrice;

                        // Log any discrepancies for debugging
                        if (abs($correctSubtotal - $frontendSubtotal) > 0.01) {
                            Log::warning('⚠️ SUBTOTAL MISMATCH DETECTED AND FIXED', [
                                'product_id' => $productData['product_id'] ?? 'unknown',
                                'quantity' => $quantity,
                                'unit_price' => $unitPrice,
                                'frontend_subtotal' => $frontendSubtotal,
                                'corrected_subtotal' => $correctSubtotal,
                                'difference' => $correctSubtotal - $frontendSubtotal
                            ]);
                        }

                        // Override with correct value
                        $productData['subtotal'] = $correctSubtotal;
                        $correctedProducts[] = $productData;
                    }
                    // Merge corrected products back to request
                    $request->merge(['products' => $correctedProducts]);
                }

                // ✨ NEW: Determine transaction type
                $transactionType = $request->transaction_type ?? 'invoice';
                $orderNumber = null;
                $orderStatus = null;

                // ----- Invoice/Order No Generation -----
                if ($transactionType === 'sale_order') {
                    // 🔄 Sale Order: Preserve existing order number during update, generate new for create
                    if ($isUpdate && $sale->order_number) {
                        // Keep existing order number during update
                        $orderNumber = $sale->order_number;
                    } else {
                        // Generate new order number for new sale order
                        $orderNumber = Sale::generateOrderNumber($request->location_id);
                    }
                    $orderStatus = $request->order_status ?? 'pending';
                    $invoiceNo = null; // No invoice for sale order
                } elseif (
                    $isUpdate &&
                    $sale->transaction_type === 'sale_order' &&
                    $transactionType === 'invoice' &&
                    $newStatus === 'final'
                ) {
                    // 🔧 FIX: Converting sale_order to invoice (credit sale after editing sale order)
                    // Keep the order number, but generate new invoice number
                    $orderNumber = $sale->order_number;
                    $orderStatus = 'completed'; // Mark order as completed when converted to invoice
                    $invoiceNo = Sale::generateInvoiceNo($request->location_id);
                    Log::info('🔄 Converting Sale Order to Invoice', [
                        'sale_id' => $sale->id,
                        'order_number' => $orderNumber,
                        'new_invoice_no' => $invoiceNo,
                        'status' => $newStatus
                    ]);
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
                // 🔧 CRITICAL: Calculate subtotal from corrected product subtotals
                $subtotal = array_reduce($request->products, fn($carry, $p) => $carry + $p['subtotal'], 0);
                $discount = $request->discount_amount ?? 0;
                $shippingCharges = $request->shipping_charges ?? 0;

                // 🔍 VALIDATION: Double-check subtotal calculation
                $validationSubtotal = 0;
                foreach ($request->products as $p) {
                    $validationSubtotal += floatval($p['quantity'] ?? 0) * floatval($p['unit_price'] ?? 0);
                }

                if (abs($subtotal - $validationSubtotal) > 0.01) {
                    Log::error('🚨 CRITICAL: Subtotal calculation mismatch after correction!', [
                        'calculated_subtotal' => $subtotal,
                        'validation_subtotal' => $validationSubtotal,
                        'products' => $request->products,
                        'sale_id' => $sale->id ?? 'NEW'
                    ]);
                    // Use the validation subtotal as it's calculated fresh
                    $subtotal = $validationSubtotal;
                }

                // Debug the incoming request data
                Log::info('🔍 SHIPPING REQUEST DATA:', [
                    'request_shipping_charges' => $request->shipping_charges,
                    'final_shipping_charges_used' => $shippingCharges,
                    'request_has_shipping' => isset($request->shipping_charges),
                    'corrected_subtotal' => $subtotal
                ]);

                // Calculate total after discount
                $totalAfterDiscount = $request->discount_type === 'percentage'
                    ? $subtotal - ($subtotal * $discount / 100)
                    : $subtotal - $discount;

                // Add shipping charges to get final total
                $finalTotal = $totalAfterDiscount + $shippingCharges;

                // Critical debug logging
                Log::info('� CRITICAL SERVER CALCULATION DEBUG:', [
                    'request_final_total' => $request->final_total,
                    'calculated_subtotal' => $subtotal,
                    'calculated_discount' => $discount,
                    'calculated_afterDiscount' => $totalAfterDiscount,
                    'calculated_shippingCharges' => $shippingCharges,
                    'CALCULATED_FINAL_TOTAL' => $finalTotal,
                    'formula' => "{$subtotal} - {$discount} + {$shippingCharges} = {$finalTotal}",
                    'will_save_to_db' => $finalTotal
                ]);

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
                Log::info('🚨 RIGHT BEFORE SAVE:', [
                    'finalTotal_variable' => $finalTotal,
                    'finalTotal_type' => gettype($finalTotal),
                    'subtotal' => $subtotal,
                    'shippingCharges' => $shippingCharges,
                    'about_to_save_final_total' => $finalTotal
                ]);

                // ----- Store customer change information before updating sale -----
                $oldCustomerId = $isUpdate ? $sale->getOriginal('customer_id') : null;
                $oldFinalTotal = $isUpdate ? $sale->getOriginal('final_total') : null;
                $oldSubtotal = $isUpdate ? $sale->getOriginal('subtotal') : null;
                $oldDiscount = $isUpdate ? $sale->getOriginal('discount_amount') : null;
                $customerChanged = $isUpdate && ($oldCustomerId != $request->customer_id);

                // Check if financial data changed (for ledger update decision)
                $financialDataChanged = $isUpdate && (
                    abs($oldFinalTotal - $finalTotal) > 0.01 || // final_total changed
                    abs($oldSubtotal - $subtotal) > 0.01 || // subtotal changed
                    abs($oldDiscount - $discount) > 0.01 // discount changed
                );

                // ✅ CRITICAL FIX: Double-check customer exists before saving sale (use withoutGlobalScopes)
                if (!Customer::withoutGlobalScopes()->where('id', $request->customer_id)->exists()) {
                    throw new \Exception("Customer with ID {$request->customer_id} does not exist.");
                }

                $sale->fill([
                    'customer_id' => $request->customer_id,
                    'location_id' => $request->location_id,
                    'sales_date' => Carbon::now('Asia/Colombo')->format('Y-m-d H:i:s'),
                    'status' => $newStatus,
                    'invoice_no' => $invoiceNo,
                    'reference_no' => $referenceNo,
                    'subtotal' => $subtotal,
                    'final_total' => $finalTotal,
                    'discount_type' => $request->discount_type,
                    'discount_amount' => $discount,
                    'user_id' => $isUpdate ? $sale->user_id : auth()->id(), // Keep original creator
                    'updated_by' => $isUpdate ? auth()->id() : null, // Track who edited
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                    'amount_given' => $amountGiven,
                    'balance_amount' => $balanceAmount,
                    'sale_notes' => $request->sale_notes,
                    // ✨ NEW: Sale Order fields (user_id is already set above)
                    'transaction_type' => $transactionType,
                    'order_number' => $orderNumber,
                    'order_date' => $transactionType === 'sale_order' ? now() : null,
                    'expected_delivery_date' => $request->expected_delivery_date,
                    'order_status' => $orderStatus,
                    'order_notes' => $request->order_notes,
                    // ✨ NEW: Shipping fields
                    'shipping_details' => $request->shipping_details,
                    'shipping_address' => $request->shipping_address,
                    'shipping_charges' => $request->shipping_charges ?? 0,
                    'shipping_status' => $request->shipping_status ?? 'pending',
                    'delivered_to' => $request->delivered_to,
                    'delivery_person' => $request->delivery_person,
                ]);

                // ✅ CRITICAL FIX: Add exception handling around sale save operation
                try {
                    $saveResult = $sale->save();
                    if (!$saveResult) {
                        throw new \Exception("Sale save operation returned false");
                    }
                } catch (\Exception $e) {
                    Log::error('CRITICAL: Sale save operation failed', [
                        'sale_id' => $sale->id ?? 'NEW',
                        'customer_id' => $request->customer_id,
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'sale_attributes' => $sale->getAttributes()
                    ]);

                    // Return specific error for database constraint violations
                    if (str_contains($e->getMessage(), 'foreign key constraint') ||
                        str_contains($e->getMessage(), 'Integrity constraint violation')) {
                        throw new \Exception("Invalid customer ID: Customer with ID {$request->customer_id} does not exist.");
                    }

                    throw $e; // Re-throw for other errors
                }

                // Debug: Check if final_total and customer_id were saved correctly
                $sale->refresh();


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
                            'reference_no' => $sale->invoice_no, // Use invoice number for consistency
                            'notes' => 'Advance payment for job ticket',
                        ];

                        $payment = $this->paymentService->recordSalePayment($paymentData, $sale);
                    }
                }

                // ----- Ledger - Record Sale FIRST (before payments) -----
                // ✨ PERFORMANCE FIX: Skip ledger operations for Walk-In customers (no credit tracking needed)
                // ✨ ACCOUNTING FIX: Only create ledger entries for final sales, not drafts/quotations/sale_orders
                // ✨ SALE ORDER FIX: Skip ledger for sale orders - ledger created only on conversion to invoice
                if ($request->customer_id != 1 && !$isUpdate && !in_array($sale->status, ['draft', 'quotation']) && $transactionType !== 'sale_order') {
                    // ✅ CRITICAL: Record sale in unified ledger INSIDE DB transaction
                    // If ledger creation fails, entire transaction rolls back (sale won't be saved)
                    // This ensures accounting integrity - no sale without ledger entry
                    $this->unifiedLedgerService->recordSale($sale);
                }

// ✅ CORRECT ORDER: Step 1 - Reverse old SALE entries only (not payments yet)
                // Accounting order: Sale reversal → Payment reversal → New sale → New payment
                // ✨ CRITICAL FIX: Skip ledger updates for Sale Orders - they don't create ledger entries
                // 🔧 DRAFT TO FINAL FIX: Check OLD status to detect draft/quotation conversions
                $isDraftToFinalConversion = $isUpdate &&
                    in_array($oldStatus, ['draft', 'quotation']) &&
                    in_array($newStatus, ['final', 'suspend']);

                // 🔧 FIX: Check for sale_order to invoice conversion
                $isSaleOrderToInvoiceConversion = $isUpdate &&
                    $sale->getOriginal('transaction_type') === 'sale_order' &&
                    $transactionType === 'invoice' &&
                    $newStatus === 'final';

                if ($isUpdate && $request->customer_id != 1 && $transactionType !== 'sale_order' && !in_array($newStatus, ['draft', 'quotation'])) {
                    // 🆕 SALE ORDER CONVERSION: If converting from sale_order to invoice, create ledger entry
                    if ($isSaleOrderToInvoiceConversion) {
                        Log::info('🔄 Sale Order to Invoice conversion detected', [
                            'sale_id' => $sale->id,
                            'order_number' => $sale->order_number,
                            'invoice_no' => $sale->invoice_no,
                            'old_transaction_type' => 'sale_order',
                            'new_transaction_type' => $transactionType,
                            'customer_id' => $sale->customer_id,
                            'final_total' => $sale->final_total
                        ]);

                        // Create new ledger entry (sale orders don't have ledger entries)
                        $this->unifiedLedgerService->recordNewSaleEntry($sale);
                    }
                    // 🆕 DRAFT CONVERSION: If converting from draft/quotation to final, force create ledger entry
                    elseif ($isDraftToFinalConversion) {
                        Log::info('🔄 Draft/Quotation to Final conversion detected', [
                            'sale_id' => $sale->id,
                            'invoice_no' => $sale->invoice_no,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'customer_id' => $sale->customer_id,
                            'final_total' => $sale->final_total
                        ]);

                        // Force create new ledger entry (no old entry to reverse)
                        $this->unifiedLedgerService->recordNewSaleEntry($sale);
                    }
                    // Check if customer has changed during edit (use pre-stored values)
                    elseif ($customerChanged) {
                        // Customer changed - use special method to handle ledger transfer
                        Log::info('Sale edit with customer change detected', [
                            'sale_id' => $sale->id,
                            'old_customer_id' => $oldCustomerId,
                            'new_customer_id' => $request->customer_id,
                            'old_amount' => $oldFinalTotal,
                            'new_amount' => $sale->final_total
                        ]);

                        $this->unifiedLedgerService->editSaleWithCustomerChange(
                            $sale,
                            $oldCustomerId,
                            $request->customer_id,
                            $oldFinalTotal,
                            'Customer changed during sale edit'
                        );
                    } elseif ($financialDataChanged) {
                        // Same customer but financial data changed
                        // STEP 1: Reverse old sale entries (new sale created after payment reversals)
                        Log::info('Sale edit with financial data change detected', [
                            'sale_id' => $sale->id,
                            'customer_id' => $sale->customer_id,
                            'old_final_total' => $oldFinalTotal,
                            'new_final_total' => $sale->final_total
                        ]);

                        // ✅ CRITICAL FIX: Validate sale object before ledger operations
                        if (empty($sale->customer_id)) {
                            Log::error('CRITICAL: Sale customer_id is empty before ledger operations', [
                                'sale_id' => $sale->id,
                                'request_customer_id' => $request->customer_id,
                                'sale_customer_id' => $sale->customer_id,
                                'sale_attributes' => $sale->getAttributes(),
                            ]);

                            // Try to reload the sale from database
                            $sale->refresh();
                            if (empty($sale->customer_id)) {
                                throw new \Exception("CRITICAL ERROR: Sale customer_id is missing after database refresh. Sale ID: {$sale->id}. This indicates a database constraint violation or data corruption.");
                            }
                        }

                        // ✅ Let UnifiedLedgerService handle both reversal and new entry
                        $this->unifiedLedgerService->updateSale($sale, $referenceNo);
                    }
                    // Note: If only non-financial fields changed (e.g., sale_notes), ledger is not updated
                }

                // ----- Handle Payments (if not jobticket and not sale_order) -----
                // Payments are handled AFTER sale ledger updates for correct accounting order
                if ($sale->status !== 'jobticket' && $transactionType !== 'sale_order') {
                    $totalPaid = 0;

// ✨ UNIFIED PATH: Use PaymentService for all customers (including Walk-In)
                    // PaymentService now automatically skips ledger entries for Walk-In customers
                    if ($request->customer_id == 1 && !empty($request->payments)) {
                        // ✅ CRITICAL FIX: Delete old payments during edit to prevent duplicates
                        if ($isUpdate) {
                            $oldPayments = Payment::where('reference_id', $sale->id)
                                ->where('payment_type', 'sale')
                                ->where('status', '!=', 'deleted')
                                ->get();

                            foreach ($oldPayments as $oldPayment) {
                                // Mark as deleted (soft delete for audit trail)
                                $oldPayment->update([
                                    'status' => 'deleted',
                                    'payment_status' => 'cancelled',
                                    'notes' => ($oldPayment->notes ?? '') . ' | DELETED: Sale edited - payment recreated'
                                ]);
                            }
                        }

                        // Process Walk-In payments using PaymentService for consistency
                        $totalPaid = 0;
                        foreach ($request->payments as $paymentData) {
                            if (!empty($paymentData['amount']) && $paymentData['amount'] > 0) {
                                // Ensure payment_date is in proper format
                                $paymentDate = $paymentData['payment_date'] ?? now();
                                if (is_string($paymentDate)) {
                                    try {
                                        $paymentDate = Carbon::parse($paymentDate);
                                    } catch (\Exception $e) {
                                        $paymentDate = now();
                                    }
                                }

                                // Use PaymentService for consistency (automatically skips ledger for Walk-In)
                                $servicePaymentData = [
                                    'payment_date' => $paymentDate,
                                    'amount' => $paymentData['amount'],
                                    'payment_method' => $paymentData['payment_method'] ?? 'cash',
                                    'reference_no' => $sale->invoice_no,
                                    'payment_status' => 'completed',
                                    'notes' => $paymentData['notes'] ?? ''
                                ];

                                $payment = $this->paymentService->recordSalePayment($servicePaymentData, $sale);

                                // Count only completed payments
                                if ($paymentData['payment_method'] === 'cheque') {
                                    if (($paymentData['cheque_status'] ?? 'pending') === 'cleared') {
                                        $totalPaid += $paymentData['amount'];
                                    }
                                } else {
                                    $totalPaid += $paymentData['amount'];
                                }
                            }
                        }

                        $sale->update([
                            'total_paid' => $totalPaid,
                            'payment_status' => $totalPaid >= $sale->final_total ? 'Paid' : 'Partial',
                        ]);

                    } elseif (!empty($request->payments)) {
                        // FIXED: Calculate total paid excluding pending cheques
                        $totalPaid = 0;
                        foreach ($request->payments as $payment) {
                            // Only count completed payments or cleared cheques
                            if ($payment['payment_method'] === 'cheque') {
                                if (($payment['cheque_status'] ?? 'pending') === 'cleared') {
                                    $totalPaid += $payment['amount'];
                                }
                            } else {
                                $totalPaid += $payment['amount'];
                            }
                        }

                        if ($isUpdate) {
                            // Handle payment updates properly for customer changes
                            $oldPayments = Payment::where('reference_id', $sale->id)
                                ->where('status', '!=', 'deleted')
                                ->get();

                            // ✅ CRITICAL FIX: Always delete old payments during edit for non-Walk-In customers
                            // Since payments are always re-sent from frontend, we need to clean up first
                            if ($oldPayments->count() > 0) {
                                if ($customerChanged) {
                                    // Customer changed - payment ledger entries already handled by editSaleWithCustomerChange
                                    // ✅ CRITICAL FIX: Mark payments as deleted instead of hard delete
                                    Payment::where('reference_id', $sale->id)
                                        ->where('status', '!=', 'deleted')
                                        ->update([
                                            'status' => 'deleted',
                                            'payment_status' => 'cancelled',
                                            'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' | DELETED: Customer changed during sale edit')")
                                        ]);
                                } else {
                                    // Same customer - delete old payments and use payment service to properly handle ledger reversal
                                    foreach ($oldPayments as $oldPayment) {
                                        $this->paymentService->deleteSalePayment($oldPayment, 'Payment updated during sale edit');
                                    }
                                }
                            }
                        }

                        // ✨ PERFORMANCE FIX: Optimized payment processing
                        $paymentsToCreate = collect($request->payments)->filter(function($paymentData) {
                            return !empty($paymentData['amount']) && $paymentData['amount'] > 0;
                        });

                        // STEP 4: Create new payment entries (after new sale entry)
                        foreach ($paymentsToCreate as $paymentData) {
                            // Ensure payment_date is in proper format
                            $paymentDate = $paymentData['payment_date'] ?? now();
                            if (is_string($paymentDate)) {
                                try {
                                    $paymentDate = Carbon::parse($paymentDate);
                                } catch (\Exception $e) {
                                    $paymentDate = now();
                                }
                            }

                            // Prepare payment data with enhanced cheque handling
                            $servicePaymentData = [
                                'payment_date' => $paymentDate,
                                'amount' => $paymentData['amount'],
                                'payment_method' => $paymentData['payment_method'],
                                'reference_no' => $sale->invoice_no, // Use invoice number for payment reference
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
                                // Ensure cheque_status has a default value if not provided
                                $chequeStatus = $paymentData['cheque_status'] ?? 'pending';

                                $servicePaymentData = array_merge($servicePaymentData, [
                                    'cheque_number' => $paymentData['cheque_number'] ?? null,
                                    'cheque_bank_branch' => $paymentData['cheque_bank_branch'] ?? null,
                                    'cheque_received_date' => $paymentData['cheque_received_date'] ?? null,
                                    'cheque_valid_date' => $paymentData['cheque_valid_date'] ?? null,
                                    'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
                                    'cheque_status' => $chequeStatus,
                                    'payment_status' => $chequeStatus === 'cleared' ? 'completed' : 'pending',
                                ]);
                            } else {
                                // For cash, bank_transfer, etc.
                                $servicePaymentData['payment_status'] = 'completed';
                            }

                            // Create individual payment record using PaymentService
                            $payment = $this->paymentService->recordSalePayment($servicePaymentData, $sale);
                        }

                        // ✨ FIXED: Calculate total paid excluding pending cheques
                        $totalPaid = $paymentsToCreate->sum(function($payment) {
                            // Only count completed payments or cleared cheques
                            if ($payment['payment_method'] === 'cheque') {
                                return ($payment['cheque_status'] ?? 'pending') === 'cleared' ? $payment['amount'] : 0;
                            }
                            return $payment['amount'];
                        });

                        // Handle floating balance adjustment
                        if ($request->use_floating_balance && $request->floating_balance_amount > 0) {
                            $this->processFloatingBalanceAdjustment($sale, $request->floating_balance_amount);
                            $totalPaid += $request->floating_balance_amount;
                        }

                        // ✅ NEW: Handle excess payment saved as customer advance
                        if ($request->save_excess_as_advance && $request->excess_amount > 0 && $sale->customer_id != 1) {
                            $excessAmount = floatval($request->excess_amount);

                            // Prepare advance payment data
                            $advancePaymentData = [
                                'payment_date' => now(),
                                'amount' => $excessAmount,
                                'payment_method' => 'cash', // Excess is from cash payment
                                'reference_no' => 'ADV-' . $sale->invoice_no,
                                'notes' => 'Customer advance from excess payment on invoice ' . $sale->invoice_no,
                                'payment_status' => 'completed',
                                'customer_id' => $sale->customer_id,
                                'location_id' => $sale->location_id,
                            ];

                            // Use PaymentService to record advance payment (handles ledger automatically)
                            $this->paymentService->recordCustomerAdvancePayment($advancePaymentData);

                            Log::info('💰 Excess payment saved as customer advance', [
                                'sale_id' => $sale->id,
                                'customer_id' => $sale->customer_id,
                                'advance_amount' => $excessAmount,
                                'invoice_no' => $sale->invoice_no
                            ]);
                        }

                        // ✨ PERFORMANCE FIX: Single update for payment status
                        // ✅ FIX: Calculate payment status for all transaction types (invoice, Normal, etc.)
                        $totalDue = max(0, $sale->final_total - $totalPaid);
                        if ($totalDue <= 0) {
                            $paymentStatus = 'Paid';
                        } elseif ($totalPaid > 0) {
                            $paymentStatus = 'Partial';
                        } else {
                            $paymentStatus = 'Due';
                        }

                        $sale->update([
                            'total_paid' => $totalPaid,
                            'payment_status' => $paymentStatus,
                        ]);

                    } elseif ($isUpdate) {
                        // ✅ FIX: For sale updates without new payments, recalculate from existing payments table
                        // Don't use amount_given to calculate total_paid - this ignores discount payments!
                        $actualTotalPaid = Payment::where('reference_id', $sale->id)
                            ->where('payment_type', 'sale')
                            ->sum('amount');

                        $totalDue = max(0, $sale->final_total - $actualTotalPaid);
                        if ($totalDue <= 0) {
                            $paymentStatus = 'Paid';
                        } elseif ($actualTotalPaid > 0) {
                            $paymentStatus = 'Partial';
                        } else {
                            $paymentStatus = 'Due';
                        }

                        $amountGiven = $request->amount_given ?? $sale->final_total;

                        $sale->update([
                            'total_paid' => $actualTotalPaid,
                            'total_due' => $totalDue,
                            'payment_status' => $paymentStatus,
                            'amount_given' => $amountGiven,
                            'balance_amount' => max(0, $amountGiven - $sale->final_total),
                        ]);
                    }
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
                    // Store original quantities for stock validation during update (paid + free)
                    $originalProducts = [];
                    foreach ($sale->products as $product) {
                        if (!isset($originalProducts[$product->product_id][$product->batch_id])) {
                            $originalProducts[$product->product_id][$product->batch_id] = [
                                'quantity' => 0,
                                'free_quantity' => 0
                            ];
                        }
                        $originalProducts[$product->product_id][$product->batch_id]['quantity'] += $product->quantity;
                        $originalProducts[$product->product_id][$product->batch_id]['free_quantity'] += ($product->free_quantity ?? 0);

                        if (in_array($oldStatus, ['final', 'suspend'])) {
                            $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                        } else {
                            // For non-final statuses, still need to restore IMEI numbers
                            $this->restoreImeiNumbers($product);
                        }
                        $product->delete();
                    }
                }

                // ✨ PERFORMANCE FIX: Batch load all products to avoid N+1 queries
                // ✅ CRITICAL FIX: Don't use cached data for stock-critical operations
                $productIds = collect($request->products)->pluck('product_id')->unique();
                $products = Product::whereIn('id', $productIds)
                    ->select('id', 'product_name', 'sku', 'stock_alert', 'unit_id')
                    ->with('unit:id,allow_decimal')
                    ->get()->keyBy('id');

                foreach ($request->products as $productData) {
                    $product = $products[$productData['product_id']] ?? null;
                    if (!$product) {
                        throw new \Exception("Product ID {$productData['product_id']} not found");
                    }

                    // *** CRITICAL SECURITY FIX: Validate price integrity during edit mode ***
                    if ($isUpdate) {
                        $this->validateEditModePrice($productData, $sale);
                    }

                    // ✅ CRITICAL DEBUG: Log product stock_alert value to verify stock management
                    Log::info("🔍 PRODUCT STOCK CHECK", [
                        'product_id' => $product->id,
                        'product_name' => $product->product_name,
                        'stock_alert' => $product->stock_alert,
                        'stock_alert_type' => gettype($product->stock_alert),
                        'quantity_to_sell' => $productData['quantity'],
                        'batch_id' => $productData['batch_id'] ?? 'all',
                        'location_id' => $request->location_id,
                        'sale_status' => $newStatus,
                        'will_deduct_stock' => $product->stock_alert !== 0
                    ]);

                    // ✅ CRITICAL FIX: Only allow unlimited stock sale if stock_alert is explicitly 0
                    // Warn if product has stock_alert=0 but no batches exist (data integrity issue)
                    if ($product->stock_alert === 0) {
                        Log::warning('⚠️ UNLIMITED STOCK PRODUCT SALE - NO BATCH DEDUCTION', [
                            'product_id' => $product->id,
                            'product_name' => $product->product_name,
                            'quantity' => $productData['quantity'],
                            'stock_alert' => $product->stock_alert
                        ]);
                        $this->processUnlimitedStockProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE);
                    } else {
                        // ✅ STOCK MANAGEMENT ENABLED - Stock will be deducted from batches
                        Log::info("✅ STOCK MANAGED PRODUCT - WILL DEDUCT STOCK", [
                            'product_id' => $product->id,
                            'product_name' => $product->product_name,
                            'stock_alert' => $product->stock_alert,
                            'quantity' => $productData['quantity'],
                            'batch_id' => $productData['batch_id'] ?? 'all',
                            'location_id' => $request->location_id,
                            'sale_status' => $newStatus,
                            'is_update' => $isUpdate
                        ]);

                        // For updates, check stock availability considering the original sale quantities
                        if ($isUpdate && in_array($newStatus, ['final', 'suspend'])) {
                            $this->validateStockForUpdate($productData, $request->location_id, $originalProducts ?? []);
                        }

                        // ✨ For Sale Orders: Deduct stock but use special stock type for allocation tracking
                        if ($transactionType === 'sale_order') {
                            $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE_ORDER, 'sale_order');
                        }
                        // Always process sale for final/suspend status
                        elseif (in_array($newStatus, ['final', 'suspend'])) {
                            Log::info("🔥 CALLING processProductSale for FINAL/SUSPEND sale", [
                                'product_id' => $product->id,
                                'status' => $newStatus
                            ]);
                            $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE, $newStatus);
                        } else {
                            // For non-final statuses, just simulate batch selection
                            Log::info("📋 DRAFT/QUOTATION - Simulating batch selection only", [
                                'product_id' => $product->id,
                                'status' => $newStatus
                            ]);
                            $this->simulateBatchSelection($productData, $sale->id, $request->location_id, $newStatus);
                        }
                    }
                }

                // ----- Ledger (optimized) - Handled BEFORE payments above -----
                // Note: Sale ledger entries are created BEFORE payment processing (lines ~1293-1346)
                // This ensures correct accounting order: Sale reversal → Payment reversal → New sale → New payment
                // Payment ledger entries are automatically created by PaymentService

                // *** CRITICAL FIX: Always recalculate customer balance after sale edits ***
                if ($isUpdate && $request->customer_id != 1) {
                    $this->recalculateCustomerBalance($request->customer_id);
                }

                // ✅ CRITICAL FIX: Clear product cache after stock changes to prevent stale data
                Cache::forget('all_products');
                foreach ($productIds as $productId) {
                    Cache::forget("product_stock_{$productId}");
                    Cache::forget("product_batches_{$productId}");
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

            // ✨ PERFORMANCE FIX: Skip heavy receipt generation for Walk-In customers and other optimizations
        $isWalkInCustomer = $sale->customer_id == 1;
        $shouldGenerateReceipt = !$request->header('X-Skip-Receipt') &&
                               $sale->status !== 'jobticket' &&
                               !($isWalkInCustomer && $request->status === 'final'); // Skip for Walk-In final sales

        if ($shouldGenerateReceipt) {
            // ✨ PERFORMANCE FIX: Eager load all related data in single queries
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

            // ✨ PERFORMANCE FIX: Get products and payments in parallel
            [$products, $payments] = [
                SalesProduct::with(['product:id,product_name,sku', 'imeis:id,sale_product_id,imei_number'])
                    ->where('sale_id', $sale->id)->get(),
                Payment::where('reference_id', $sale->id)
                    ->where('payment_type', 'sale')
                    ->select('id', 'amount', 'payment_method', 'payment_date', 'reference_no', 'notes')
                    ->get()
            ];

            $user = $sale->user;
            $location = $sale->location;
        } else {
            // Minimal data for non-receipt responses
            $customer = null;
            $products = collect();
            $payments = collect();
            $user = null;
            $location = null;
        }

            // Calculate customer's total outstanding balance from ledger for non-walk-in customers
            $customerOutstandingBalance = 0;
            if ($customer && $customer->id != 1) {
                $customerOutstandingBalance = $customer->calculateBalanceFromLedger();
            }

            $viewData = [
                'sale' => $sale,
                'customer' => $customer,
                'products' => $products,
                'payments' => $payments,
                'total_discount' => $request->discount_amount ?? 0,
                'amount_given' => $sale->amount_given,
                'balance_amount' => $sale->balance_amount,
                'customer_outstanding_balance' => $customerOutstandingBalance,
                'user' => $user,
                'location' => $location,
                'receiptConfig' => $location ? $location->getReceiptConfig() : [],
            ];

            // ✨ PERFORMANCE FIX: Only render receipt HTML if needed
            $html = '';
            if ($shouldGenerateReceipt) {
                // Get location-specific receipt view
                $receiptView = $location ? $location->getReceiptViewName() : 'sell.receipt';
                $html = view($receiptView, $viewData)->render();
            }

            // SEND WHATSAPP MESSAGE ASYNCHRONOUSLY (NON-BLOCKING) - Skip for Walk-In customers
            if ($sale->customer_id != 1) {
                $this->sendWhatsAppAsync($customer, $sale, $viewData);
            }

            // ✨ PERFORMANCE FIX: For Walk-In customers, return minimal response faster
            if ($isWalkInCustomer && $request->status === 'final') {
                return response()->json([
                    'message' => 'Sale recorded successfully.',
                    'invoice_html' => '', // Empty for Walk-In to speed up response
                    'data' => [
                        'sale' => $sale,
                        'customer' => (object) ['id' => 1, 'first_name' => 'Walk-In', 'last_name' => 'Customer'],
                        'products' => collect(),
                        'payments' => collect(),
                        'total_discount' => $request->discount_amount ?? 0,
                        'amount_given' => $sale->amount_given,
                        'balance_amount' => $sale->balance_amount,
                    ],
                    'sale' => [
                        'id' => $sale->id,
                        'invoice_no' => $sale->invoice_no,
                        'order_number' => $sale->order_number,
                        'transaction_type' => $sale->transaction_type,
                        'order_status' => $sale->order_status,
                    ],
                ], 200);
            }

            // Customize success message based on sale status and type
            $message = '';
            if ($id) {
                $message = 'Sale updated successfully.';
            } else {
                if ($sale->transaction_type === 'sale_order') {
                    $message = 'Sale Order created successfully!';
                } elseif ($sale->status === 'suspend') {
                    $message = 'Sale suspended successfully.';
                } else {
                    $message = 'Sale recorded successfully.';
                }
            }

            $totalTime = microtime(true) - $startTime;
            Log::info('Sale processing completed', [
                'customer_id' => $request->customer_id,
                'total_time' => $totalTime,
                'sale_id' => $sale->id
            ]);

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
                    // Add customer outstanding balance to viewData for WhatsApp receipt
                    $customerOutstandingBalance = 0;
                    if ($customer && $customer->id != 1) {
                        $customerOutstandingBalance = $customer->calculateBalanceFromLedger();
                    }
                    $viewData['customer_outstanding_balance'] = $customerOutstandingBalance;

                    // Get location from sale for receipt template selection
                    $location = $sale->location;
                    $receiptView = $location ? $location->getReceiptViewName() : 'sell.receipt';

                    // Add receipt config to view data
                    $viewData['receiptConfig'] = $location ? $location->getReceiptConfig() : [];

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
        // ✅ CRITICAL FIX: Log product sale processing for debugging
        $freeQuantity = floatval($productData['free_quantity'] ?? 0);
        Log::info("🛒 PROCESSING PRODUCT SALE", [
            'sale_id' => $saleId,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'free_quantity' => $freeQuantity,
            'batch_id' => $productData['batch_id'] ?? 'all',
            'batch_id_type' => gettype($productData['batch_id'] ?? null),
            'batch_id_empty' => empty($productData['batch_id']),
            'batch_id_equals_all' => ($productData['batch_id'] ?? null) == 'all',
            'batch_id_strict_equals_all' => ($productData['batch_id'] ?? null) === 'all',
            'location_id' => $locationId,
            'stock_type' => $stockType,
            'status' => $newStatus
        ]);

        // Total quantity to deduct from inventory = paid + free
        $totalQuantity = $productData['quantity'] + $freeQuantity;
        $remainingQuantity = $totalQuantity;

        // We'll store info about each batch deduction
        $batchDeductions = [];

        // ✅ CRITICAL DEBUG: Log the exact condition check
        $batchIdValue = $productData['batch_id'] ?? null;
        $isNotEmpty = !empty($batchIdValue);
        $isNotAll = $batchIdValue != 'all';
        Log::info("🔍 BATCH SELECTION DEBUG", [
            'batch_id_value' => $batchIdValue,
            'is_not_empty' => $isNotEmpty,
            'is_not_all' => $isNotAll,
            'will_use_specific_batch' => ($isNotEmpty && $isNotAll)
        ]);

        if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
            // Specific batch selected
            Log::info("📦 Processing specific batch", ['batch_id' => $productData['batch_id']]);

            $batch = Batch::findOrFail($productData['batch_id']);
            $locationBatch = LocationBatch::where('batch_id', $batch->id)
                ->where('location_id', $locationId)
                ->firstOrFail();

            // Check both paid and free stock separately
            $availablePaidStock = $locationBatch->qty ?? 0;
            $availableFreeStock = $locationBatch->free_qty ?? 0;

            if ($availablePaidStock < $productData['quantity']) {
                Log::error("❌ INSUFFICIENT PAID STOCK in selected batch", [
                    'batch_id' => $productData['batch_id'],
                    'available_paid' => $availablePaidStock,
                    'requested_paid' => $productData['quantity']
                ]);
                throw new \Exception("Batch ID {$productData['batch_id']} does not have enough paid stock. Available: {$availablePaidStock}, Requested: {$productData['quantity']}");
            }

            if ($availableFreeStock < $freeQuantity) {
                Log::error("❌ INSUFFICIENT FREE STOCK in selected batch", [
                    'batch_id' => $productData['batch_id'],
                    'available_free' => $availableFreeStock,
                    'requested_free' => $freeQuantity
                ]);
                throw new \Exception("Batch ID {$productData['batch_id']} does not have enough free stock. Available: {$availableFreeStock}, Requested: {$freeQuantity}");
            }

            $this->deductBatchStock($productData['batch_id'], $locationId, $totalQuantity, $stockType, $productData['quantity'], $freeQuantity);
            $batchDeductions[] = [
                'batch_id' => $batch->id,
                'quantity' => $remainingQuantity,
                'paid_qty' => $productData['quantity'],
                'free_qty' => $freeQuantity
            ];
        } else {
            // ✨ PERFORMANCE FIX: All batches selected — apply FIFO with optimized query
            // ✅ CRITICAL FIX: Enhanced logging for FIFO batch selection
            Log::info("📦 Processing FIFO batch selection (all batches)", [
                'product_id' => $productData['product_id'],
                'location_id' => $locationId,
                'paid_quantity_needed' => $productData['quantity'],
                'free_quantity_needed' => $freeQuantity,
                'total_quantity_needed' => $totalQuantity
            ]);

            // Track remaining paid and free quantities separately
            $remainingPaidQty = $productData['quantity'];
            $remainingFreeQty = $freeQuantity;

            // Use lockForUpdate to prevent race conditions when reading batch quantities
            $batches = DB::transaction(function () use ($productData, $locationId) {
                return DB::table('location_batches')
                    ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                    ->where('batches.product_id', $productData['product_id'])
                    ->where('location_batches.location_id', $locationId)
                    ->where(function($q) {
                        // Include batches with either paid or free stock available
                        $q->where('location_batches.qty', '>', 0)
                          ->orWhere('location_batches.free_qty', '>', 0);
                    })
                    ->orderBy('batches.created_at')
                    ->select('location_batches.batch_id', 'location_batches.qty', 'location_batches.free_qty', 'location_batches.id as loc_batch_id')
                    ->lockForUpdate() // Lock rows to prevent concurrent reads
                    ->get();
            });

            Log::info("📦 Available batches for FIFO", [
                'batch_count' => $batches->count(),
                'total_available_paid' => $batches->sum('qty'),
                'total_available_free' => $batches->sum('free_qty'),
                'batches' => $batches->map(fn($b) => ['batch_id' => $b->batch_id, 'paid_qty' => $b->qty, 'free_qty' => $b->free_qty ?? 0])->toArray()
            ]);

            foreach ($batches as $batch) {
                if ($remainingPaidQty <= 0 && $remainingFreeQty <= 0) break;

                // Deduct paid quantity from this batch (FIFO)
                $deductPaidQty = min($batch->qty ?? 0, $remainingPaidQty);
                // Deduct free quantity from this batch (FIFO) - Try free stock first
                $deductFreeQty = min($batch->free_qty ?? 0, $remainingFreeQty);
                $deductTotalQty = $deductPaidQty + $deductFreeQty;

                if ($deductTotalQty <= 0) continue; // Skip batches with no stock to deduct

                Log::info("📦 Deducting from batch via FIFO", [
                    'batch_id' => $batch->batch_id,
                    'available_paid' => $batch->qty ?? 0,
                    'available_free' => $batch->free_qty ?? 0,
                    'deducting_paid' => $deductPaidQty,
                    'deducting_free' => $deductFreeQty,
                    'remaining_paid_after' => $remainingPaidQty - $deductPaidQty,
                    'remaining_free_after' => $remainingFreeQty - $deductFreeQty
                ]);

                $this->deductBatchStock($batch->batch_id, $locationId, $deductTotalQty, $stockType, $deductPaidQty, $deductFreeQty);
                $batchDeductions[] = [
                    'batch_id' => $batch->batch_id,
                    'quantity' => $deductTotalQty,
                    'paid_qty' => $deductPaidQty,
                    'free_qty' => $deductFreeQty
                ];

                $remainingPaidQty -= $deductPaidQty;
                $remainingFreeQty -= $deductFreeQty;
            }

            // ✨ FLEXIBLE APPROACH: If free stock ran out, use paid stock instead
            if ($remainingFreeQty > 0 && in_array($newStatus, ['final', 'suspend'])) {
                Log::info("🔄 FREE STOCK EXHAUSTED - Using paid stock for remaining free quantity", [
                    'product_id' => $productData['product_id'],
                    'remaining_free_qty' => $remainingFreeQty,
                    'will_deduct_from_paid_stock' => true
                ]);

                // Loop through batches again to use paid stock for free items
                foreach ($batches as $batch) {
                    if ($remainingFreeQty <= 0) break;

                    // Check if this batch has available paid stock (after previous deductions)
                    $batchRemainingPaid = $batch->qty ?? 0;
                    // Subtract what we already deducted in the first loop
                    foreach ($batchDeductions as &$deduction) {
                        if ($deduction['batch_id'] === $batch->batch_id) {
                            $batchRemainingPaid -= $deduction['paid_qty'];
                        }
                    }

                    if ($batchRemainingPaid <= 0) continue;

                    // Deduct from paid stock to cover free quantity shortage
                    $deductFromPaidForFree = min($batchRemainingPaid, $remainingFreeQty);

                    Log::info("📦 Using paid stock for free items", [
                        'batch_id' => $batch->batch_id,
                        'deducting_paid_for_free' => $deductFromPaidForFree,
                        'remaining_free_after' => $remainingFreeQty - $deductFromPaidForFree
                    ]);

                    // Update batch stock
                    $this->deductBatchStock($batch->batch_id, $locationId, 0, $stockType, $deductFromPaidForFree, 0);

                    // Update or add to batch deductions
                    $existingIndex = array_search($batch->batch_id, array_column($batchDeductions, 'batch_id'));
                    if ($existingIndex !== false) {
                        $batchDeductions[$existingIndex]['paid_qty'] += $deductFromPaidForFree;
                        $batchDeductions[$existingIndex]['quantity'] += $deductFromPaidForFree;
                    } else {
                        $batchDeductions[] = [
                            'batch_id' => $batch->batch_id,
                            'quantity' => $deductFromPaidForFree,
                            'paid_qty' => $deductFromPaidForFree,
                            'free_qty' => 0
                        ];
                    }

                    $remainingFreeQty -= $deductFromPaidForFree;
                }
            }

            // Only validate stock if the sale status is final/suspend
            if (in_array($newStatus, ['final', 'suspend'])) {
                // Check total shortage (paid + remaining free that couldn't be covered)
                $totalShortage = $remainingPaidQty + $remainingFreeQty;

                if ($totalShortage > 0) {
                    Log::error("❌ INSUFFICIENT TOTAL STOCK across all batches", [
                        'product_id' => $productData['product_id'],
                        'location_id' => $locationId,
                        'requested_paid' => $productData['quantity'],
                        'requested_free' => $freeQuantity,
                        'unfulfilled_paid' => $remainingPaidQty,
                        'unfulfilled_free' => $remainingFreeQty,
                        'total_shortage' => $totalShortage,
                        'batches_checked' => $batches->count()
                    ]);
                    throw new \Exception("Not enough stock across all batches to fulfill the sale. Product ID: {$productData['product_id']}, Total Required: " . ($productData['quantity'] + $freeQuantity) . ", Short: {$totalShortage}");
                }
            }
        }

        Log::info("✅ BATCH DEDUCTIONS COMPLETE", [
            'sale_id' => $saleId,
            'product_id' => $productData['product_id'],
            'total_quantity' => $totalQuantity,
            'batch_count' => count($batchDeductions),
            'deductions' => $batchDeductions
        ]);

        // Loop through batch deductions
        foreach ($batchDeductions as $deduction) {
            // Create sales_product record for this batch
            // Note: subtotal = quantity × price (calculated, not stored)
            // Calculate proportional free quantity for this batch deduction
            $proportionalFreeQty = ($freeQuantity > 0 && $productData['quantity'] > 0)
                ? ($deduction['quantity'] / $totalQuantity) * $freeQuantity
                : 0;

            $paidQtyForBatch = $deduction['quantity'] - $proportionalFreeQty;

            $saleProduct = SalesProduct::create([
                'sale_id' => $saleId,
                'product_id' => $productData['product_id'],
                'quantity' => $paidQtyForBatch,
                'free_quantity' => $proportionalFreeQty,
                'price' => $productData['unit_price'], // price column stores unit price
                'batch_id' => $deduction['batch_id'],
                'location_id' => $locationId,
                'price_type' => $productData['price_type'],
                'discount_amount' => $productData['discount_amount'] ?? 0,
                'discount_type' => $productData['discount_type'] ?? 'fixed',
                'tax' => $productData['tax'] ?? 0,
            ]);

            // ✨ PERFORMANCE FIX: Optimized IMEI processing
            if (!empty($productData['imei_numbers']) && is_array($productData['imei_numbers'])) {
                $requiredImeiCount = min(count($productData['imei_numbers']), $deduction['quantity']);
                $imeiNumbers = array_slice($productData['imei_numbers'], 0, $requiredImeiCount);

                if (!empty($imeiNumbers)) {
                    // Single batch update for IMEI status
                    ImeiNumber::whereIn('imei_number', $imeiNumbers)
                        ->where('product_id', $productData['product_id'])
                        ->where('batch_id', $deduction['batch_id'])
                        ->where('location_id', $locationId)
                        ->update(['status' => 'sold']);

                    // Prepare batch insert data
                    $saleImeiInserts = array_map(function($imei) use ($saleId, $saleProduct, $productData, $deduction, $locationId) {
                        return [
                            'sale_id' => $saleId,
                            'sale_product_id' => $saleProduct->id,
                            'product_id' => $productData['product_id'],
                            'batch_id' => $deduction['batch_id'],
                            'location_id' => $locationId,
                            'imei_number' => $imei,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $imeiNumbers);

                    // Single batch insert for sale IMEIs
                    SaleImei::insert($saleImeiInserts);
                }
            }
        }
    }
    private function deductBatchStock($batchId, $locationId, $quantity, $stockType, $paidQty = null, $freeQty = null)
    {
        // ✅ NEW: Support separate paid and free quantity deduction
        // If not specified, assume all quantity is paid (backward compatibility)
        if ($paidQty === null && $freeQty === null) {
            $paidQty = $quantity;
            $freeQty = 0;
        }

        // ✅ CRITICAL FIX: Enhanced logging for stock deduction tracking
        Log::info("🔻 STOCK DEDUCTION INITIATED", [
            'batch_id' => $batchId,
            'location_id' => $locationId,
            'total_quantity_to_deduct' => $quantity,
            'paid_qty' => $paidQty,
            'free_qty' => $freeQty,
            'stock_type' => $stockType,
            'timestamp' => now()->toDateTimeString()
        ]);

        // Use database transaction with proper locking to prevent race conditions
        return DB::transaction(function () use ($batchId, $locationId, $quantity, $stockType, $paidQty, $freeQty) {
            // First, get current stock with row-level locking
            $locationBatch = DB::table('location_batches')
                ->where('batch_id', $batchId)
                ->where('location_id', $locationId)
                ->lockForUpdate() // This prevents concurrent modifications
                ->first();

            if (!$locationBatch) {
                Log::error("❌ STOCK DEDUCTION FAILED: Batch not found", [
                    'batch_id' => $batchId,
                    'location_id' => $locationId
                ]);
                throw new \Exception("Batch ID $batchId not found at location $locationId");
            }

            // Use round to handle decimal precision issues (4 decimal places)
            $currentPaidStock = round((float) $locationBatch->qty, 4);
            $currentFreeStock = round((float) ($locationBatch->free_qty ?? 0), 4);
            $requestedPaidQty = round((float) $paidQty, 4);
            $requestedFreeQty = round((float) $freeQty, 4);

            Log::info("📊 STOCK CHECK", [
                'batch_id' => $batchId,
                'current_paid_stock' => $currentPaidStock,
                'current_free_stock' => $currentFreeStock,
                'requested_paid_qty' => $requestedPaidQty,
                'requested_free_qty' => $requestedFreeQty,
                'paid_sufficient' => ($currentPaidStock + 0.0001) >= $requestedPaidQty,
                'free_sufficient' => ($currentFreeStock + 0.0001) >= $requestedFreeQty
            ]);

            // Add small tolerance for floating point comparison (0.0001)
            if (($currentPaidStock + 0.0001) < $requestedPaidQty) {
                Log::error("❌ INSUFFICIENT PAID STOCK", [
                    'batch_id' => $batchId,
                    'location_id' => $locationId,
                    'available_paid' => $currentPaidStock,
                    'requested_paid' => $requestedPaidQty,
                    'shortfall' => $requestedPaidQty - $currentPaidStock
                ]);
                throw new \Exception("Insufficient paid stock in batch ID $batchId at location $locationId. Available: $currentPaidStock, Requested: $requestedPaidQty");
            }

            if (($currentFreeStock + 0.0001) < $requestedFreeQty) {
                Log::error("❌ INSUFFICIENT FREE STOCK", [
                    'batch_id' => $batchId,
                    'location_id' => $locationId,
                    'available_free' => $currentFreeStock,
                    'requested_free' => $requestedFreeQty,
                    'shortfall' => $requestedFreeQty - $currentFreeStock
                ]);
                throw new \Exception("Insufficient free stock in batch ID $batchId at location $locationId. Available: $currentFreeStock, Requested: $requestedFreeQty");
            }

            // Now safely deduct both paid and free stock
            $affected = DB::table('location_batches')
                ->where('batch_id', $batchId)
                ->where('location_id', $locationId)
                ->update([
                    'qty' => DB::raw("qty - $requestedPaidQty"),
                    'free_qty' => DB::raw("free_qty - $requestedFreeQty")
                ]);

            if ($affected === 0) {
                Log::error("❌ STOCK UPDATE FAILED", [
                    'batch_id' => $batchId,
                    'location_id' => $locationId,
                    'affected_rows' => $affected
                ]);
                throw new \Exception("Failed to update stock for batch ID $batchId at location $locationId");
            }

            // ✨ PERFORMANCE FIX: Create stock history record (will be batched)
            if ($locationBatch) {
                StockHistory::create([
                    'loc_batch_id' => $locationBatch->id,
                    'quantity' => -$quantity,
                    'stock_type' => $stockType,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info("✅ STOCK DEDUCTION SUCCESSFUL", [
                'batch_id' => $batchId,
                'location_id' => $locationId,
                'deducted_total_quantity' => $quantity,
                'deducted_paid_qty' => $requestedPaidQty,
                'deducted_free_qty' => $requestedFreeQty,
                'previous_paid_stock' => $currentPaidStock,
                'previous_free_stock' => $currentFreeStock,
                'new_paid_stock' => $currentPaidStock - $requestedPaidQty,
                'new_free_stock' => $currentFreeStock - $requestedFreeQty,
                'stock_type' => $stockType
            ]);

            return $locationBatch;
        });
    }

    private function processUnlimitedStockProductSale($productData, $saleId, $locationId, $stockType)
    {
        // ✅ CRITICAL FIX: Enhanced logging for unlimited stock sales to track potential issues
        Log::warning('Processing unlimited stock product sale - stock NOT deducted from batches', [
            'sale_id' => $saleId,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'location_id' => $locationId,
            'stock_type' => $stockType,
        ]);

        // Record the sales product for unlimited stock product
        // Note: subtotal = quantity × price (calculated, not stored)
        SalesProduct::create([
            'sale_id' => $saleId,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'price' => $productData['unit_price'], // price column stores unit price
            'batch_id' => null,
            'location_id' => $locationId,
            'price_type' => $productData['price_type'],
            'discount_amount' => $productData['discount_amount'] ?? 0,
            'discount_type' => $productData['discount_type'] ?? 'fixed',
            'tax' => $productData['tax'] ?? 0,
        ]);

        // Add stock history for unlimited stock product (for reporting purposes only)
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
                $batches = DB::transaction(function () use ($productData, $locationId) {
                    return DB::table('location_batches')
                        ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                        ->where('batches.product_id', $productData['product_id'])
                        ->where('location_batches.location_id', $locationId)
                        ->where('location_batches.qty', '>', 0)
                        ->orderBy('batches.created_at')
                        ->select('location_batches.batch_id', 'location_batches.qty')
                        ->lockForUpdate()
                        ->get();
                });

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
        // ✅ INCLUDE FREE QUANTITY: Total = paid + free
        $freeQuantity = floatval($productData['free_quantity'] ?? 0);
        $totalQuantity = $productData['quantity'] + $freeQuantity;
        $productId = $productData['product_id'];
        $batchId = $productData['batch_id'];

        // Get original quantity sold for this product/batch combination (paid + free)
        $originalQuantity = 0;
        $originalFreeQuantity = 0;
        if (isset($originalProducts[$productId])) {
            if ($batchId === 'all') {
                // For 'all' batches, sum all original quantities for this product
                foreach ($originalProducts[$productId] as $batchData) {
                    $originalQuantity += $batchData['quantity'] ?? 0;
                    $originalFreeQuantity += $batchData['free_quantity'] ?? 0;
                }
            } else {
                // For specific batch, get original quantity for this batch
                $batchData = $originalProducts[$productId][$batchId] ?? [];
                $originalQuantity = $batchData['quantity'] ?? 0;
                $originalFreeQuantity = $batchData['free_quantity'] ?? 0;
            }
        }
        $originalTotalQuantity = $originalQuantity + $originalFreeQuantity;

        if (!empty($batchId) && $batchId != 'all') {
            // Specific batch selected
            $currentStock = Sale::getAvailableStock($batchId, $locationId);
            $availableStock = $currentStock + $originalTotalQuantity;

            if ($totalQuantity > $availableStock) {
                throw new \Exception("Batch ID {$batchId} does not have enough stock. Available: {$availableStock}, Requested: {$totalQuantity} (paid: {$productData['quantity']} + free: {$freeQuantity})");
            }
        } else {
            // All batches selected - check total available stock
            $currentTotalStock = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productId)
                ->where('location_batches.location_id', $locationId)
                ->sum('location_batches.qty');

            $availableStock = $currentTotalStock + $originalTotalQuantity;

            if ($totalQuantity > $availableStock) {
                throw new \Exception("Not enough stock available. Available: {$availableStock}, Requested: {$totalQuantity} (paid: {$productData['quantity']} + free: {$freeQuantity})");
            }
        }
    }

    private function restoreStock($product, $stockType)
    {
        // ✅ CRITICAL FIX: Restore paid and free quantities separately
        $paidQuantity = floatval($product->quantity ?? 0);
        $freeQuantity = floatval($product->free_quantity ?? 0);
        $totalQuantityToRestore = $paidQuantity + $freeQuantity;

        Log::info("Restoring stock for product ID {$product->product_id} from batch ID {$product->batch_id} at location {$product->location_id}", [
            'paid_quantity' => $paidQuantity,
            'free_quantity' => $freeQuantity,
            'total_quantity_to_restore' => $totalQuantityToRestore
        ]);

        // Check if batch_id is null - skip stock restoration for unlimited stock products
        if (is_null($product->batch_id)) {
            Log::info("Skipping stock restoration for unlimited stock product (batch_id is null)");
            return;
        }

        // ✅ FIX: Restore paid and free quantities separately
        $affected = DB::table('location_batches')
            ->where('batch_id', $product->batch_id)
            ->where('location_id', $product->location_id)
            ->update([
                'qty' => DB::raw("qty + {$paidQuantity}"),
                'free_qty' => DB::raw("free_qty + {$freeQuantity}")
            ]);

        Log::info("Stock restoration result", [
            'affected_rows' => $affected,
            'batch_id' => $product->batch_id,
            'location_id' => $product->location_id,
            'paid_quantity_restored' => $paidQuantity,
            'free_quantity_restored' => $freeQuantity,
            'total_quantity_restored' => $totalQuantityToRestore
        ]);

        if ($affected > 0) {
            // Get location batch for stock history
            $locationBatch = LocationBatch::where('batch_id', $product->batch_id)
                ->where('location_id', $product->location_id)
                ->first();

            if ($locationBatch) {
                // ✅ FIX: Record total quantity (paid + free) in stock history
                StockHistory::create([
                    'loc_batch_id' => $locationBatch->id,
                    'quantity' => $totalQuantityToRestore,
                    'stock_type' => $stockType,
                ]);
                Log::info("Stock history created successfully", [
                    'stock_history_type' => $stockType,
                    'paid_quantity' => $paidQuantity,
                    'free_quantity' => $freeQuantity,
                    'total_quantity' => $totalQuantityToRestore
                ]);
            } else {
                Log::warning("LocationBatch not found after successful stock update", [
                    'batch_id' => $product->batch_id,
                    'location_id' => $product->location_id
                ]);
            }
        } else {
            Log::error("Failed to restore stock - no rows affected", [
                'batch_id' => $product->batch_id,
                'location_id' => $product->location_id,
                'paid_quantity' => $paidQuantity,
                'free_quantity' => $freeQuantity,
                'total_quantity' => $totalQuantityToRestore
            ]);
        }

        // Restore IMEI numbers only for actual sales, not for sale orders
        // Sale orders don't "sell" IMEI numbers, they just allocate stock
        if ($stockType === StockHistory::STOCK_TYPE_SALE_REVERSAL) {
            $this->restoreImeiNumbers($product);
            Log::info("IMEI numbers restored for actual sale reversal");
        } else {
            Log::info("Skipping IMEI restoration for sale order cancellation - IMEIs were not sold");
        }
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
                    'sale_notes', // Include sale notes
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
                            'free_quantity' => $product->free_quantity ?? 0,
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

                    // ✅ FIX: For editing, max allowed = current stock + (paid qty + free qty) from this sale
                    // This represents what would be available if we "undo" this sale completely
                    $freeQuantity = $product->free_quantity ?? 0;
                    $totalAllowedQuantity = $currentStock + $product->quantity + $freeQuantity;

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
                        'free_quantity' => $product->free_quantity ?? 0,
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
                    'location_id',
                    'customer_type' // Include customer type for price validation
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

            // Pass price permissions to view (same as pos() method)
            /** @var \App\Models\User|null $user */
            $user = auth()->user();

            // Determine which batch price types the user can access based on permissions
            $allowedPriceTypes = [];

            if ($user && $user->can('select retail price')) {
                $allowedPriceTypes[] = 'retail';
            }
            if ($user && $user->can('select wholesale price')) {
                $allowedPriceTypes[] = 'wholesale';
            }
            if ($user && $user->can('select special price')) {
                $allowedPriceTypes[] = 'special';
            }
            if ($user && $user->can('select max retail price')) {
                $allowedPriceTypes[] = 'max_retail';
            }

            // Check editing permissions
            $canEditUnitPrice = $user && $user->can('edit unit price in pos');
            $canEditDiscount = $user && $user->can('edit discount in pos');

            // Get price validation setting from database
            // 1 = STRICT mode (enforce permissions - only users with permission can edit)
            // 0 = FLEXIBLE mode (free editing - all users can edit regardless of permissions)
            $priceValidationEnabled = (int)(\App\Models\Setting::value('enable_price_validation') ?? 1);

            return view('sell.pos', [
                'saleDetails' => $saleDetails,
                'allowedPriceTypes' => $allowedPriceTypes,
                'canEditUnitPrice' => $canEditUnitPrice,
                'canEditDiscount' => $canEditDiscount,
                'priceValidationEnabled' => $priceValidationEnabled
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 404, 'message' => 'Sale not found.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    public function deleteSuspendedSale($id)
    {
        // Add debug logging
        Log::info('Attempting to delete suspended sale', [
            'sale_id' => $id,
            'user_id' => auth()->id(),
            'request_method' => request()->method(),
            'has_csrf_token' => request()->hasHeader('X-CSRF-TOKEN'),
            'csrf_token' => request()->header('X-CSRF-TOKEN') ? 'present' : 'missing'
        ]);

        try {
            $sale = Sale::findOrFail($id);
            if ($sale->status !== 'suspend') {
                return response()->json(['message' => 'Sale is not suspended.'], 400);
            }

            DB::transaction(function () use ($sale) {
                // 1. Restore stock first
                foreach ($sale->products as $product) {
                    $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                    $product->delete();
                }

                // 2. Clean up ledger entries to maintain accounting accuracy
                // This ensures customer balance is corrected when suspended sale is deleted
                if ($sale->customer_id && $sale->customer_id != 1) {
                    $deletedLedgerEntries = $this->unifiedLedgerService->deleteSaleLedger($sale);
                    Log::info("Deleted {$deletedLedgerEntries} ledger entries for suspended sale deletion", [
                        'sale_id' => $sale->id,
                        'customer_id' => $sale->customer_id,
                        'invoice_no' => $sale->invoice_no
                    ]);
                }

                // 3. Delete the sale record
                $sale->delete();
            });

            Log::info('Successfully deleted suspended sale', ['sale_id' => $id]);
            return response()->json(['message' => 'Suspended sale deleted, stock restored, and customer balance updated successfully.'], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting suspended sale', [
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['message' => 'An error occurred while deleting the sale: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);

        DB::transaction(function () use ($sale) {
            // 1. Restore stock ONLY if it was deducted (for final/suspend sales, NOT for draft/quotation)
            // Draft and quotation sales don't affect stock, so we shouldn't restore stock when deleting them
            $shouldRestoreStock = !in_array($sale->status, ['draft', 'quotation']);

            foreach ($sale->products as $product) {
                if ($shouldRestoreStock) {
                    $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                }
                $product->delete();
            }

            // 2. Clean up ledger entries for non-Walk-In customers
            // Only delete ledger entries for sales that would have created them
            if ($sale->customer_id && $sale->customer_id != 1 &&
                !in_array($sale->status, ['draft', 'quotation'])) {

                $deletedLedgerEntries = $this->unifiedLedgerService->deleteSaleLedger($sale);
                Log::info("Deleted {$deletedLedgerEntries} ledger entries for sale deletion", [
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'invoice_no' => $sale->invoice_no,
                    'status' => $sale->status
                ]);
            }

            // 3. Delete the sale record
            $sale->delete();
        });

        $message = in_array($sale->status, ['draft', 'quotation'])
            ? 'Sale deleted successfully.'
            : 'Sale deleted, stock restored, and customer balance updated successfully.';

        return response()->json([
            'status' => 200,
            'message' => $message
        ], 200);
    }

    public function printRecentTransaction($id, Request $request)
    {
        try {
            $sale = Sale::findOrFail($id);
            $customer = Customer::withoutLocationScope()->findOrFail($sale->customer_id);
            $products = SalesProduct::with(['product', 'imeis', 'batch'])->where('sale_id', $sale->id)->get();
            $payments = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->get();
            $totalDiscount = array_reduce($products->toArray(), function ($carry, $product) {
                return $carry + ($product['discount'] ?? 0);
            }, 0);

            // Fetch the user associated with the sale
            $user = User::find($sale->user_id);

            // Use the location from the sale, not from user's first location
            $location = $sale->location;

            // Calculate customer's total outstanding balance from ledger for non-walk-in customers
            $customerOutstandingBalance = 0;
            if ($customer && $customer->id != 1) {
                $customerOutstandingBalance = $customer->calculateBalanceFromLedger();
            }

            $viewData = [
                'sale' => $sale,
                'customer' => $customer,
                'products' => $products,
                'payments' => $payments,
                'total_discount' => 0, // Fix: use 0 instead of undefined $request variable
                'amount_given' => $sale->amount_given,
                'balance_amount' => $sale->balance_amount,
                'customer_outstanding_balance' => $customerOutstandingBalance,
                'user' => $user,
                'location' => $location,
                'receiptConfig' => $location ? $location->getReceiptConfig() : [],
            ];

            // Get receipt view based on layout parameter or location default
            $layout = $request->query('layout'); // Get layout from query parameter
            $receiptView = 'sell.receipt'; // Default 80mm

            if ($layout) {
                // Map layout parameter to view name
                $layoutMap = [
                    '80mm' => 'sell.receipt',
                    'a4' => 'sell.receipt_a4',
                    'dot_matrix' => 'sell.receipt_dot_matrix',
                    'dot_matrix_full' => 'sell.receipt_dot_matrix_full',
                ];
                $receiptView = $layoutMap[$layout] ?? $receiptView;
            } else {
                // Use location default if no layout specified
                $receiptView = $location ? $location->getReceiptViewName() : 'sell.receipt';
            }

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

    /**
     * Process floating balance adjustment against sale
     *
     * @param Sale $sale
     * @param float $adjustmentAmount
     * @return void
     */
    private function processFloatingBalanceAdjustment($sale, $adjustmentAmount)
    {
        // Create a floating balance adjustment payment record
        $payment = new Payment([
            'customer_id' => $sale->customer_id,
            'reference_id' => $sale->id,
            'payment_type' => 'sale',
            'payment_method' => 'floating_balance_adjustment',
            'amount' => $adjustmentAmount,
            'payment_date' => now(),
            'notes' => 'Floating balance adjustment against sale #' . $sale->invoice_no,
            'payment_status' => 'completed',
            'created_by' => auth()->id()
        ]);

        $payment->save();

        // Record in unified ledger as floating balance recovery
        $this->unifiedLedgerService->recordFloatingBalanceRecovery(
            $sale->customer_id,
            -$adjustmentAmount, // Negative amount to reduce floating balance
            'floating_balance_adjustment',
            'Floating balance adjustment against sale #' . $sale->invoice_no
        );

        Log::info('Floating balance adjustment processed', [
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'adjustment_amount' => $adjustmentAmount,
            'payment_id' => $payment->id
        ]);
    }


     public function getCustomerPreviousPrice(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|integer|exists:customers,id',
                'product_id' => 'required|integer|exists:products,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customerId = $request->customer_id;
            $productId = $request->product_id;

            // Get the last 3 sales of this product for this customer with price and date
            $previousPrices = collect(DB::select("
                SELECT sp.price, sp.quantity, s.created_at, s.invoice_no
                FROM sales_products sp
                JOIN sales s ON sp.sale_id = s.id
                WHERE s.customer_id = ? AND sp.product_id = ? AND s.status = 'final'
                ORDER BY s.created_at DESC
                LIMIT 3
            ", [$customerId, $productId]))
                ->map(function ($saleProduct) {
                    $unitPrice = floatval($saleProduct->price);
                    $quantity = floatval($saleProduct->quantity);
                    $total = $unitPrice * $quantity;

                    return [
                        'sale_date' => \Carbon\Carbon::parse($saleProduct->created_at)->format('Y-m-d'),
                        'invoice_no' => $saleProduct->invoice_no,
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'total' => $total
                    ];
                });

            // Calculate average price if there are previous purchases
            $averagePrice = $previousPrices->isNotEmpty()
                ? $previousPrices->avg('unit_price')
                : null;

            return response()->json([
                'status' => 200,
                'data' => [
                    'has_previous_purchases' => $previousPrices->isNotEmpty(),
                    'previous_prices' => $previousPrices,
                    'average_price' => $averagePrice,
                    'last_price' => $previousPrices->first()['unit_price'] ?? null,
                    'last_purchase_date' => $previousPrices->first()['sale_date'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get customer previous price', [
                'error' => $e->getMessage(),
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve customer previous price'
            ], 500);
        }
    }

    /**
     * Validate price integrity during edit mode to prevent manipulation
     *
     * @param array $productData
     * @param Sale $sale
     * @throws \Exception
     */
    private function validateEditModePrice($productData, $sale)
    {
        try {
            // Find the original sale product for this product_id and batch_id
            $originalSaleProduct = $sale->products()
                ->where('product_id', $productData['product_id'])
                ->where('batch_id', $productData['batch_id'] ?? 'all')
                ->first();

            if (!$originalSaleProduct) {
                // This is a new product being added during edit - allow normal pricing logic
                Log::info('New product added during sale edit', [
                    'sale_id' => $sale->id,
                    'product_id' => $productData['product_id'],
                    'batch_id' => $productData['batch_id'] ?? 'all'
                ]);
                return;
            }

            // Compare incoming price with original sale price
            $originalPrice = (float) $originalSaleProduct->price;
            $incomingPrice = (float) $productData['unit_price'];
            $priceDifference = abs($originalPrice - $incomingPrice);
            $allowedVariance = 0.01; // Allow 1 cent variance for floating point precision

            if ($priceDifference > $allowedVariance) {
                Log::warning('Price manipulation attempt detected during sale edit', [
                    'sale_id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'product_id' => $productData['product_id'],
                    'batch_id' => $productData['batch_id'] ?? 'all',
                    'original_price' => $originalPrice,
                    'attempted_price' => $incomingPrice,
                    'difference' => $priceDifference,
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()->email ?? 'unknown'
                ]);

                throw new \Exception(
                    "Price modification detected for product ID {$productData['product_id']}. " .
                    "Original price: Rs {$originalPrice}, attempted price: Rs {$incomingPrice}. " .
                    "Price changes during edit are not allowed for data integrity."
                );
            }

            // Validate discount integrity as well
            $originalDiscountAmount = (float) ($originalSaleProduct->discount_amount ?? 0);
            $incomingDiscountAmount = (float) ($productData['discount_amount'] ?? 0);
            $discountDifference = abs($originalDiscountAmount - $incomingDiscountAmount);

            if ($discountDifference > $allowedVariance) {
                Log::warning('Discount manipulation attempt detected during sale edit', [
                    'sale_id' => $sale->id,
                    'product_id' => $productData['product_id'],
                    'original_discount' => $originalDiscountAmount,
                    'attempted_discount' => $incomingDiscountAmount,
                    'difference' => $discountDifference
                ]);

                throw new \Exception(
                    "Discount modification detected for product ID {$productData['product_id']}. " .
                    "Discount changes during edit are not allowed."
                );
            }

            Log::info('Price validation passed for edit mode', [
                'sale_id' => $sale->id,
                'product_id' => $productData['product_id'],
                'validated_price' => $originalPrice
            ]);

        } catch (\Exception $e) {
            // Re-throw validation errors
            if (strpos($e->getMessage(), 'modification detected') !== false) {
                throw $e;
            }

            // Log unexpected errors but don't block the edit
            Log::error('Unexpected error during edit mode price validation', [
                'sale_id' => $sale->id,
                'product_id' => $productData['product_id'],
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Price validation failed. Please try again or contact administrator.');
        }
    }

    /**
     * Recalculate and update customer balance based on actual outstanding dues
     * This prevents balance discrepancies after sale edits
     *
     * @param int $customerId
     */
    private function recalculateCustomerBalance($customerId)
    {
        try {
            // Calculate total outstanding dues from all final sales
            $totalDue = Sale::withoutGlobalScopes()
                ->where('customer_id', $customerId)
                ->where('status', 'final')
                ->sum('total_due');

            // Update customer balance
            Customer::withoutGlobalScopes()
                ->where('id', $customerId)
                ->update(['current_balance' => $totalDue]);

            Log::info('Customer balance recalculated after sale edit', [
                'customer_id' => $customerId,
                'new_balance' => $totalDue,
                'recalculated_by' => auth()->id()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to recalculate customer balance', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception to avoid blocking sale edit, just log the error
        }
    }

}
