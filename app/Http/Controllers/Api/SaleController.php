<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\JobTicket;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\UnifiedLedgerService;
use App\Services\PaymentService;


class SaleController extends Controller
{
    protected $unifiedLedgerService;
    protected $paymentService;

    function __construct(UnifiedLedgerService $unifiedLedgerService, PaymentService $paymentService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        $this->paymentService = $paymentService;
        $this->middleware('permission:view sale', ['only' => ['listSale']]);
        $this->middleware('permission:add sale', ['only' => ['addSale']]);
        $this->middleware('permission:pos page', ['only' => ['pos']]);
        $this->middleware('permission:edit sale', ['only' => ['editSale']]);

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
        })->only(['index']);
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
            $errorMessage .= "• Credit Limit: Rs. " . number_format($customer->credit_limit, 2) . "\n";
            $errorMessage .= "• Current Outstanding: Rs. " . number_format($currentBalance, 2) . "\n";
            $errorMessage .= "• Available Credit: Rs. " . number_format($availableCredit, 2) . "\n\n";
            $errorMessage .= "Sale Details:\n";
            $errorMessage .= "• Total Sale Amount: Rs. " . number_format($finalTotal, 2) . "\n";
            $errorMessage .= "• Payment Received: Rs. " . number_format($actualPaymentAmount, 2) . "\n";
            $errorMessage .= "• Credit Amount Required: Rs. " . number_format($remainingBalance, 2) . "\n\n";
            
            if ($availableCredit > 0) {
                $errorMessage .= "Maximum credit sale allowed: Rs. " . number_format($availableCredit, 2) . "\n";
                $errorMessage .= "Exceeds limit by: Rs. " . number_format($remainingBalance - $availableCredit, 2);
            } else {
                $errorMessage .= "No credit available. Please settle previous outstanding amount or pay full amount.";
            }
            
            throw new \Exception($errorMessage);
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

    public function index()
    {

        $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')
            ->get();

        return response()->json(['sales' => $sales], 200);
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

        // Validate walk-in customer payment restrictions
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

        // Validate walk-in customer credit sales restriction (but allow suspended sales)
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
                // Use withoutGlobalScopes to avoid location/route filtering when validating customer
                $customer = Customer::withoutGlobalScopes()->findOrFail($request->customer_id);
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
                        
                        // Record payment in unified ledger
                        $this->unifiedLedgerService->recordSalePayment($payment);
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
                            Ledger::where('reference_no', $referenceNo)
                                ->where('transaction_type', 'payments')
                                ->delete();
                        }

                        foreach ($request->payments as $paymentData) {
                            $payment = Payment::create([
                                'payment_date' => Carbon::parse($paymentData['payment_date'])->format('Y-m-d'),
                                'amount' => $paymentData['amount'],
                                'payment_method' => $paymentData['payment_method'],
                                'reference_no' => $referenceNo,
                                'notes' => $paymentData['notes'] ?? '',
                                'payment_type' => 'sale',
                                'reference_id' => $sale->id,
                                'customer_id' => $request->customer_id,
                                'card_number' => $paymentData['card_number'] ?? null,
                                'card_holder_name' => $paymentData['card_holder_name'] ?? null,
                                'card_expiry_month' => $paymentData['card_expiry_month'] ?? null,
                                'card_expiry_year' => $paymentData['card_expiry_year'] ?? null,
                                'card_security_code' => $paymentData['card_security_code'] ?? null,
                                'cheque_number' => $paymentData['cheque_number'] ?? null,
                                'cheque_bank_branch' => $paymentData['cheque_bank_branch'] ?? null,
                                'cheque_received_date' => isset($paymentData['cheque_received_date']) ? Carbon::parse($paymentData['cheque_received_date'])->format('Y-m-d') : null,
                                'cheque_valid_date' => isset($paymentData['cheque_valid_date']) ? Carbon::parse($paymentData['cheque_valid_date'])->format('Y-m-d') : null,
                                'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
                            ]);

                            // Record payment in unified ledger
                            $this->unifiedLedgerService->recordSalePayment($payment);
                        }
                    } elseif ($isUpdate) {
                        $totalPaid = $sale->total_paid;
                    }

                    // --- FIX: total_paid should be min(amount_given, final_total) ---
                    $amountGiven = $request->amount_given ?? $sale->final_total;
                    $totalPaid = min($amountGiven, $finalTotal);

                    $sale->update([
                        'total_paid' => $totalPaid,
                        'total_due' => max(0, $sale->final_total - $totalPaid),
                        'amount_given' => $amountGiven,
                        'balance_amount' => max(0, $amountGiven - $sale->final_total),
                    ]);
                }

                // Check for partial payments for Walk-In Customer (but allow suspended sales)
                if ($request->customer_id == 1 && $request->status !== 'suspend' && $amountGiven < $sale->final_total) {
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
                if (!$isUpdate) {
                    // Record sale in unified ledger
                    $this->unifiedLedgerService->recordSale($sale);
                }

                $this->updatePaymentStatus($sale);
                return $sale;
            });

            // Use withoutGlobalScopes for receipt generation to avoid scope issues
            $customer = Customer::withoutGlobalScopes()->findOrFail($sale->customer_id);
            $products = SalesProduct::with(['product', 'imeis'])->where('sale_id', $sale->id)->get();
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

        // Get location-specific receipt view
        $receiptView = $location ? $location->getReceiptViewName() : 'sell.receipt';
        
        // Only generate invoice HTML for non-suspended sales
        $html = '';
        if ($sale->status !== 'suspend') {
            $html = view($receiptView, $viewData)->render();
        }

          


            try {
                $mobileNo = ltrim($customer->mobile_no, '0');
                $whatsAppApiUrl = env('WHATSAPP_API_URL'); // load from .env

                if (!empty($mobileNo) && !empty($whatsAppApiUrl)) {



                    // Get location from sale for receipt template selection
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




            // Customize success message based on sale status
            $message = '';
            if ($id) {
                $message = 'Sale updated successfully.';
            } else {
                if ($sale->status === 'suspended') {
                    $message = 'Sale suspended successfully.';
                } else {
                    $message = 'Sale recorded successfully.';
                }
            }

            return response()->json([
                'message' => $message,
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
        $sale->total_due = max($sale->final_total - $totalPaid, 0);

        if ($sale->total_due <= 0) {
            $sale->payment_status = 'Paid';
        } elseif ($totalPaid > 0) {
            $sale->payment_status = 'Partial';
        } else {
            $sale->payment_status = 'Due';
        }

        $sale->save();

        // Credit limit alert
        $customer = $sale->customer;
        if ($customer && $customer->current_balance > $customer->credit_limit) {
            Log::warning("Customer {$customer->id} exceeded credit limit.");
        }
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
                    'quantity' => $remainingQuantity
                ];
            } else {
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
        return 'SALE-' . now()->format('Ymd');
    }

    private function handleAttachedDocument($request)
    {
        if ($request->hasFile('attached_document')) {
            return $request->file('attached_document')->store('documents');
        }
        return null;
    }

    // test

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
            // Use withoutGlobalScopes for printing to avoid scope issues
            $customer = Customer::withoutGlobalScopes()->findOrFail($sale->customer_id);
            $products = SalesProduct::with(['product', 'imeis'])->where('sale_id', $sale->id)->get();
            $payments = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->get();
            // $totalDiscount = array_reduce($products->toArray(), function ($carry, $product) {
            //     return $carry + ($product['discount'] ?? 0);
            // }, 0);

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
}
