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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\JobTicket;

class SaleController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view sale', ['only' => ['listSale']]);
        $this->middleware('permission:add sale', ['only' => ['addSale']]);
        $this->middleware('permission:pos page', ['only' => ['pos']]);
        $this->middleware('permission:edit sale', ['only' => ['editSale']]);

        // Middleware for sale permissions
        // If user has 'own sale', restrict to their own sales; otherwise, allow all sales
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user && $user->can('own sale') && !$user->can('all sale')) {
                // Only allow access to own sales
                Sale::addGlobalScope('own_sale', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            }
            return $next($request);
        })->only(['index']);
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

    // public function dailyReport(Request $request)
    // {
    //     try {
    //         // Get start and end date from request or default to today
    //         $startDate = $request->input('start_date', Carbon::today()->startOfDay());
    //         $endDate = $request->input('end_date', Carbon::today()->endOfDay());

    //         // Convert inputs to Carbon instances if they are strings
    //         $startDate = Carbon::parse($startDate)->startOfDay();
    //         $endDate = Carbon::parse($endDate)->endOfDay();

    //         // Build the base query
    //         $salesQuery = Sale::with(['customer', 'location', 'user', 'payments', 'products'])
    //             ->whereBetween('sales_date', [$startDate, $endDate]);

    //         // Apply customer filter if provided
    //         if ($request->has('customer_id') && $request->customer_id) {
    //             $salesQuery->where('customer_id', $request->customer_id);
    //         }

    //         // Apply user filter if provided
    //         if ($request->has('user_id') && $request->user_id) {
    //             $salesQuery->where('user_id', $request->user_id);
    //         }

    //         // Apply location filter if provided
    //         if ($request->has('location_id') && $request->location_id) {
    //             $salesQuery->where('location_id', $request->location_id);
    //         }

    //         $sales = $salesQuery->get();

    //         // Initialize totals
    //         $cashPayments = 0;
    //         $chequePayments = 0;
    //         $bankTransferPayments = 0;
    //         $cardPayments = 0;
    //         $creditTotal = 0;

    //         foreach ($sales as $sale) {
    //             foreach ($sale->payments as $payment) {
    //                 switch ($payment->payment_method) {
    //                     case 'cash':
    //                         $cashPayments += $payment->amount;
    //                         break;
    //                     case 'cheque':
    //                         $chequePayments += $payment->amount;
    //                         break;
    //                     case 'bank_transfer':
    //                         $bankTransferPayments += $payment->amount;
    //                         break;
    //                     case 'card':
    //                         $cardPayments += $payment->amount;
    //                         break;
    //                 }
    //             }
    //             $creditTotal += $sale->total_due;
    //         }

    //         // Calculate sales returns for the filtered sales
    //         $salesReturnsQuery = SalesReturn::whereBetween('return_date', [$startDate, $endDate]);

    //         if ($request->has('customer_id') && $request->customer_id) {
    //             $salesReturnsQuery->where('customer_id', $request->customer_id);
    //         }

    //         if ($request->has('location_id') && $request->location_id) {
    //             $salesReturnsQuery->where('location_id', $request->location_id);
    //         }

    //         $salesReturns = $salesReturnsQuery->sum('return_total');
    //         $salesReturnsDetails = SalesReturn::with(['customer', 'location', 'returnProducts'])
    //             ->whereBetween('return_date', [$startDate, $endDate])
    //             ->whereIn('sale_id', $sales->pluck('id'))
    //             ->get();

    //         $salesReturnsDetails = SalesReturn::with(['customer', 'location', 'returnProducts'])
    //             ->whereBetween('return_date', [$startDate, $endDate])
    //             ->whereIn('sale_id', $sales->pluck('id'))
    //             ->whereHas('sale', function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('sales_date', [$startDate, $endDate]);
    //             })
    //             ->get();

    //         // Summaries
    //         $summaries = [
    //             'billTotal' => $sales->sum('final_total'),
    //             'discounts' => $sales->sum('discount_amount'),
    //             'cashPayments' => $cashPayments,
    //             'chequePayments' => $chequePayments,
    //             'bankTransfer' => $bankTransferPayments,
    //             'cardPayments' => $cardPayments,
    //             'salesReturns' => $salesReturns,
    //             'paymentTotal' => ($cashPayments + $chequePayments + $bankTransferPayments + $cardPayments),
    //             'creditTotal' => $creditTotal,
    //             'netIncome' => ($sales->sum('final_total') - $salesReturns),
    //             'cashInHand' => ($cashPayments - $salesReturns),
    //         ];

    //         return response()->json([
    //             'sales' => $sales,
    //             'summaries' => $summaries,
    //             'salesReturns' => $salesReturnsDetails
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'An error occurred while fetching sales data.',
    //             'details' => $e->getMessage()
    //         ], 500);
    //     }
    // }


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


    // public function storeOrUpdate(Request $request, $id = null)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'customer_id' => 'required|integer|exists:customers,id',
    //         'location_id' => 'required|integer|exists:locations,id',
    //         'sales_date' => 'required|date',
    //         'status' => 'required|string',
    //         'invoice_no' => 'nullable|string|unique:sales,invoice_no',
    //         'products' => 'required|array',
    //         'products.*.product_id' => 'required|integer|exists:products,id',
    //         'products.*.quantity' => 'required|integer|min:1',
    //         'products.*.unit_price' => 'required|numeric|min:0',
    //         'products.*.subtotal' => 'required|numeric|min:0',
    //         'products.*.batch_id' => 'nullable|string|max:255',
    //         'products.*.price_type' => 'required|string|in:retail,wholesale,special',
    //         'products.*.discount' => 'nullable|numeric|min:0',
    //         'products.*.tax' => 'nullable|numeric|min:0',
    //         'products.*.imei_numbers' => 'nullable|array',
    //         'products.*.imei_numbers.*' => 'string|max:255',
    //         'payments' => 'nullable|array',
    //         'payments.*.payment_method' => 'required_with:payments|string',
    //         'payments.*.payment_date' => 'required_with:payments|date',
    //         'payments.*.amount' => 'required_with:payments|numeric|min:0',
    //         'total_paid' => 'nullable|numeric|min:0',
    //         'payment_mode' => 'nullable|string',
    //         'payment_status' => 'nullable|string',
    //         'payment_reference' => 'nullable|string',
    //         'payment_date' => 'nullable|date',
    //         'total_amount' => 'nullable|numeric|min:0',
    //         'discount_type' => 'required|string|in:fixed,percentage',
    //         'discount_amount' => 'nullable|numeric|min:0',
    //         'amount_given' => 'nullable|numeric|min:0',
    //         'balance_amount' => 'nullable|numeric|min:0',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'errors' => $validator->messages()]);
    //     }

    //     try {
    //         $sale = DB::transaction(function () use ($request, $id) {
    //             $isUpdate = $id !== null;
    //             $sale = $isUpdate ? Sale::findOrFail($id) : new Sale();
    //             $referenceNo = $isUpdate ? $sale->reference_no : $this->generateReferenceNo();

    //             // Detect status change from draft/quotation to final/suspend
    //             $statusChangingToFinal = false;
    //             $oldStatus = $isUpdate ? $sale->getOriginal('status') : null;
    //             $newStatus = $request->status;

    //             // Generate Invoice Number Based on Status and Status Change
    //             if (!$isUpdate) {
    //                 if (in_array($newStatus, ['quotation', 'draft'])) {
    //                     $prefix = $newStatus === 'quotation' ? 'Q/' : 'D/';
    //                     $year = now()->format('Y');
    //                     $lastSale = Sale::whereYear('created_at', now())
    //                         ->where('invoice_no', 'like', "$prefix$year%")
    //                         ->latest()
    //                         ->first();
    //                     $number = $lastSale ? ((int)substr($lastSale->invoice_no, -4)) + 1 : 1;
    //                     $invoiceNo = "$prefix$year/" . str_pad($number, 4, '0', STR_PAD_LEFT);
    //                 } else {
    //                     $invoiceNo = Sale::generateInvoiceNo($request->location_id);
    //                 }
    //             } else {
    //                 // If updating and status is changing from draft/quotation to final/suspend, generate new invoice number
    //                 if (
    //                     in_array($oldStatus, ['draft', 'quotation']) &&
    //                     in_array($newStatus, ['final', 'suspend']) &&
    //                     !preg_match('/^\d+$/', $sale->invoice_no)
    //                 ) {
    //                     // Only generate if not already a numeric invoice_no
    //                     $invoiceNo = Sale::generateInvoiceNo($request->location_id);
    //                     $statusChangingToFinal = true;
    //                 } else {
    //                     $invoiceNo = $sale->invoice_no;
    //                 }
    //             }

    //             // Calculate amounts
    //             $subtotal = array_reduce($request->products, fn($carry, $p) => $carry + $p['subtotal'], 0);
    //             $discount = $request->discount_amount ?? 0;
    //             $finalTotal = $request->discount_type === 'percentage'
    //                 ? $subtotal - ($subtotal * $discount / 100)
    //                 : $subtotal - $discount;

    //             // Create the sale record first to get the ID
    //             $sale->fill([
    //                 'customer_id' => $request->customer_id,
    //                 'location_id' => $request->location_id,
    //                 'sales_date' => Carbon::parse($sale->created_at)
    //                     ->setTimezone('Asia/Colombo')
    //                     ->format('Y-m-d H:i:s'),
    //                 'sale_type' => $request->sale_type ?? 'retail',
    //                 'status' => $newStatus,
    //                 'invoice_no' => $invoiceNo,
    //                 'reference_no' => $referenceNo,
    //                 'subtotal' => $subtotal,
    //                 'final_total' => $finalTotal,
    //                 'discount_type' => $request->discount_type,
    //                 'discount_amount' => $discount,
    //                 'user_id' => auth()->id(),
    //                 'total_paid' => 0,
    //                 'total_due' => $finalTotal,
    //                 'amount_given' => 0,
    //                 'balance_amount' => 0,
    //             ])->save();




    //             if ($sale->status === 'jobticket') {
    //                 // Get advance and balance from request
    //                 $advanceAmount = floatval($request->advance_amount ?? 0);
    //                 $finalTotal = floatval($sale->final_total); // from your earlier calculation

    //                 // Payment logic
    //                 if ($advanceAmount >= $finalTotal) {
    //                     // Full payment or excess (change to return)
    //                     $totalPaid = $finalTotal;
    //                     $amountGiven = $advanceAmount;
    //                     $balanceAmount = $advanceAmount - $finalTotal;
    //                 } else {
    //                     // Partial payment, balance to be paid later
    //                     $totalPaid = $advanceAmount;
    //                     $amountGiven = $advanceAmount;
    //                     $balanceAmount = $finalTotal - $advanceAmount;
    //                 }

    //                 // Update sale payment fields accordingly
    //                 $sale->update([
    //                     'total_paid'     => $totalPaid,
    //                     'amount_given'   => $amountGiven,
    //                     'balance_amount' => $balanceAmount,
    //                 ]);

    //                 // Create or update the job ticket record
    //                 $jobTicket = JobTicket::updateOrCreate(
    //                     ['sale_id' => $sale->id],
    //                     [
    //                         'customer_id'      => $sale->customer_id,
    //                         // job_ticket_no auto-generated in model (see below)
    //                         'description'      => $request->jobticket_description ?? null,
    //                         'job_ticket_date'  => Carbon::now('Asia/Colombo'),
    //                         'status'           => 'open',
    //                         'advance_amount'   => $advanceAmount,
    //                         'balance_amount'   => $balanceAmount,
    //                     ]
    //                 );
    //             }

    //             // Handle payments
    //             $totalPaid = 0;
    //             if (!empty($request->payments)) {
    //                 $totalPaid = $request->has('payments')
    //                     ? array_sum(array_column($request->payments, 'amount'))
    //                     : $sale->final_total;

    //                 if ($isUpdate) {
    //                     Payment::where('reference_id', $sale->id)->delete();
    //                     Ledger::where('reference_no', $referenceNo)
    //                         ->where('transaction_type', 'payments')
    //                         ->delete();
    //                 }

    //                 foreach ($request->payments as $paymentData) {
    //                     $payment = Payment::create([
    //                         'payment_date' => Carbon::parse($paymentData['payment_date'])->format('Y-m-d'),
    //                         'amount' => $paymentData['amount'],
    //                         'payment_method' => $paymentData['payment_method'],
    //                         'reference_no' => $referenceNo,
    //                         'notes' => $paymentData['notes'] ?? '',
    //                         'payment_type' => 'sale',
    //                         'reference_id' => $sale->id,
    //                         'customer_id' => $request->customer_id,
    //                         'card_number' => $paymentData['card_number'] ?? null,
    //                         'card_holder_name' => $paymentData['card_holder_name'] ?? null,
    //                         'card_expiry_month' => $paymentData['card_expiry_month'] ?? null,
    //                         'card_expiry_year' => $paymentData['card_expiry_year'] ?? null,
    //                         'card_security_code' => $paymentData['card_security_code'] ?? null,
    //                         'cheque_number' => $paymentData['cheque_number'] ?? null,
    //                         'cheque_bank_branch' => $paymentData['cheque_bank_branch'] ?? null,
    //                         'cheque_received_date' => isset($paymentData['cheque_received_date']) ? Carbon::parse($paymentData['cheque_received_date'])->format('Y-m-d') : null,
    //                         'cheque_valid_date' => isset($paymentData['cheque_valid_date']) ? Carbon::parse($paymentData['cheque_valid_date'])->format('Y-m-d') : null,
    //                         'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
    //                     ]);

    //                     Ledger::create([
    //                         'transaction_date' => $payment->payment_date,
    //                         'reference_no' => $referenceNo,
    //                         'transaction_type' => 'payments',
    //                         'debit' => $payment->amount,
    //                         'credit' => 0,
    //                         'balance' => $this->calculateNewBalance($request->customer_id, $payment->amount, 'debit'),
    //                         'contact_type' => 'customer',
    //                         'user_id' => $request->customer_id,
    //                     ]);
    //                 }
    //             } elseif ($isUpdate) {
    //                 $totalPaid = $sale->total_paid;
    //             }

    //             $amountGiven = $request->amount_given ?? $sale->final_total;
    //             $sale->update([
    //                 'total_paid' => $amountGiven,
    //                 'total_due' => max(0, $sale->final_total - $amountGiven),
    //                 'amount_given' => $amountGiven,
    //                 'balance_amount' => max(0, $amountGiven - $sale->final_total),
    //             ]);

    //             // Check for partial payments for Walk-In Customer
    //             if ($request->customer_id == 1 && $amountGiven < $sale->final_total) {
    //                 throw new \Exception("Partial payment is not allowed for Walk-In Customer.");
    //             }

    //             // Handle products
    //             if ($isUpdate) {
    //                 foreach ($sale->products as $product) {
    //                     // Only restore stock if previous status was 'final' or 'suspend'
    //                     if (in_array($oldStatus, ['final', 'suspend'])) {
    //                         $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
    //                     }
    //                     $product->delete();
    //                 }
    //             }

    //             foreach ($request->products as $productData) {
    //                 $product = Product::findOrFail($productData['product_id']);

    //                 // Always add sales_product rows for all statuses
    //                 if ($product->stock_alert === 0) {
    //                     $this->processUnlimitedStockProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE);
    //                 } else {
    //                     // If status is final/suspend, or if status is changing from draft/quotation to final/suspend, deduct stock
    //                     if (
    //                         in_array($newStatus, ['final', 'suspend']) &&
    //                         (
    //                             !$isUpdate ||
    //                             in_array($oldStatus, ['draft', 'quotation']) ||
    //                             $statusChangingToFinal
    //                         )
    //                     ) {
    //                         $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE, $newStatus);
    //                     } else {
    //                         $this->simulateBatchSelection($productData, $sale->id, $request->location_id, $newStatus);
    //                     }
    //                 }
    //             }

    //             // Update sale ledger entry
    //             if ($isUpdate) {
    //                 Ledger::where('reference_no', $referenceNo)
    //                     ->where('transaction_type', 'sale')
    //                     ->update([
    //                         'credit' => $finalTotal,
    //                         'balance' => $this->calculateNewBalance($request->customer_id, $finalTotal, 'credit')
    //                     ]);
    //             } else {
    //                 Ledger::create([
    //                     'transaction_date' => $request->sales_date,
    //                     'reference_no' => $referenceNo,
    //                     'transaction_type' => 'sale',
    //                     'debit' => 0,
    //                     'credit' => $finalTotal,
    //                     'balance' => $this->calculateNewBalance($request->customer_id, $finalTotal, 'credit'),
    //                     'contact_type' => 'customer',
    //                     'user_id' => $request->customer_id,
    //                 ]);
    //             }

    //             $this->updatePaymentStatus($sale);
    //             return $sale;
    //         });

    //         // Generate receipt and return response
    //         $customer = Customer::findOrFail($sale->customer_id);
    //         $products = SalesProduct::where('sale_id', $sale->id)->get();
    //         $payments = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->get();

    //         $user = User::find($sale->user_id);
    //         $location = $user ? $user->locations()->first() : null;

    //         $html = view('sell.receipt', [
    //             'sale' => $sale,
    //             'customer' => $customer,
    //             'products' => $products,
    //             'payments' => $payments,
    //             'total_discount' => $request->discount_amount ?? 0,
    //             'amount_given' => $sale->amount_given,
    //             'balance_amount' => $sale->balance_amount,
    //             'user' => $user,
    //             'location' => $location,
    //         ])->render();

    //         return response()->json([
    //             'message' => $id ? 'Sale updated successfully.' : 'Sale recorded successfully.',
    //             'invoice_html' => $html
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => $e->getMessage()], 400);
    //     }
    // }

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
            'products.*.quantity' => 'required|integer|min:1',
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
            'balance_amount' => 'nullable|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0', // add this
            'jobticket_description' => 'nullable|string', // add this
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $sale = DB::transaction(function () use ($request, $id) {
                $isUpdate = $id !== null;
                $sale = $isUpdate ? Sale::findOrFail($id) : new Sale();
                $referenceNo = $isUpdate ? $sale->reference_no : $this->generateReferenceNo();

                $oldStatus = $isUpdate ? $sale->getOriginal('status') : null;
                $newStatus = $request->status;

                // ----- Invoice No Generation -----
                if ($newStatus === 'jobticket') {
                    // Generate J/2025/0001 pattern for job tickets
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
                    if ($finalTotal > $advanceAmount) {
                        // Partial Payment
                        $totalPaid = $advanceAmount;
                        $totalDue = $finalTotal - $totalPaid;
                        $amountGiven = $advanceAmount;
                        $balanceAmount = 0;
                    } else {
                        // Full/extra Payment
                        $totalPaid = $finalTotal;
                        $totalDue = 0;
                        $amountGiven = $advanceAmount;
                        $balanceAmount = $advanceAmount - $finalTotal;
                    }
                } else {
                    // Normal sale logic, default values
                    $amountGiven = $request->amount_given ?? $finalTotal;
                    $totalPaid = $amountGiven;
                    $totalDue = max(0, $finalTotal - $amountGiven);
                    $balanceAmount = max(0, $amountGiven - $finalTotal);
                }

                // ----- Save Sale -----
                $sale->fill([
                    'customer_id' => $request->customer_id,
                    'location_id' => $request->location_id,
                    'sales_date' => Carbon::parse($sale->created_at)
                        ->setTimezone('Asia/Colombo')
                        ->format('Y-m-d H:i:s'),
                    'sale_type' => $request->sale_type ?? 'retail',
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
                    $jobTicket = JobTicket::updateOrCreate(
                        ['sale_id' => $sale->id],
                        [
                            'customer_id'      => $sale->customer_id,
                            // job_ticket_no auto-generated in model
                            'description'      => $request->jobticket_description ?? null,
                            'job_ticket_date'  => Carbon::now('Asia/Colombo'),
                            'status'           => 'open',
                            'advance_amount'   => $advanceAmount,
                            'balance_amount'   => $balanceAmount,
                        ]
                    );
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

                            Ledger::create([
                                'transaction_date' => $payment->payment_date,
                                'reference_no' => $referenceNo,
                                'transaction_type' => 'payments',
                                'debit' => $payment->amount,
                                'credit' => 0,
                                'balance' => $this->calculateNewBalance($request->customer_id, $payment->amount, 'debit'),
                                'contact_type' => 'customer',
                                'user_id' => $request->customer_id,
                            ]);
                        }
                    } elseif ($isUpdate) {
                        $totalPaid = $sale->total_paid;
                    }

                    $amountGiven = $request->amount_given ?? $sale->final_total;
                    $sale->update([
                        'total_paid' => $amountGiven,
                        'total_due' => max(0, $sale->final_total - $amountGiven),
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
                    foreach ($sale->products as $product) {
                        if (in_array($oldStatus, ['final', 'suspend'])) {
                            $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                        }
                        $product->delete();
                    }
                }

                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    if ($product->stock_alert === 0) {
                        $this->processUnlimitedStockProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE);
                    } else {
                        if (
                            in_array($newStatus, ['final', 'suspend']) &&
                            (
                                !$isUpdate ||
                                in_array($oldStatus, ['draft', 'quotation', 'jobticket'])
                            )
                        ) {
                            $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE, $newStatus);
                        } else {
                            $this->simulateBatchSelection($productData, $sale->id, $request->location_id, $newStatus);
                        }
                    }
                }

                // ----- Ledger -----
                if ($isUpdate) {
                    Ledger::where('reference_no', $referenceNo)
                        ->where('transaction_type', 'sale')
                        ->update([
                            'credit' => $finalTotal,
                            'balance' => $this->calculateNewBalance($request->customer_id, $finalTotal, 'credit')
                        ]);
                } else {
                    Ledger::create([
                        'transaction_date' => $request->sales_date,
                        'reference_no' => $referenceNo,
                        'transaction_type' => 'sale',
                        'debit' => 0,
                        'credit' => $finalTotal,
                        'balance' => $this->calculateNewBalance($request->customer_id, $finalTotal, 'credit'),
                        'contact_type' => 'customer',
                        'user_id' => $request->customer_id,
                    ]);
                }

                $this->updatePaymentStatus($sale);
                return $sale;
            });

            // Generate receipt and return response
            $customer = Customer::findOrFail($sale->customer_id);
            $products = SalesProduct::where('sale_id', $sale->id)->get();
            $payments = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->get();

            $user = User::find($sale->user_id);
            $location = $user ? $user->locations()->first() : null;

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

            return response()->json([
                'message' => $id ? 'Sale updated successfully.' : 'Sale recorded successfully.',
                'invoice_html' => $html
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }


    private function calculateNewBalance($userId, $amount, $type)
    {
        $lastLedger = Ledger::where('user_id', $userId)->where('contact_type', 'customer')->orderBy('transaction_date', 'desc')->first();
        $previousBalance = $lastLedger ? $lastLedger->balance : 0;

        return $type === 'debit' ? $previousBalance - $amount : $previousBalance + $amount;
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

        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $product->quantity,
            'stock_type' => $stockType,
        ]);
    }

    private function generateReferenceNo()
    {
        return 'SALE-' . now()->format('YmdHis') . '-' . strtoupper(uniqid());
    }

    private function handleAttachedDocument($request)
    {
        if ($request->hasFile('attached_document')) {
            return $request->file('attached_document')->store('documents');
        }
        return null;
    }

    public function getSaleByInvoiceNo($invoiceNo)
    {
        $sale = Sale::with('products.product')->where('invoice_no', $invoiceNo)->first();

        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        $products = $sale->products->map(function ($product) use ($sale) {
            $currentQuantity = $sale->getCurrentSaleQuantity($product->product_id); // Fixed line
            $product->current_quantity = $currentQuantity;
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
        $suspendedSales = Sale::where('status', 'suspend')->with('products')->get();
        return response()->json($suspendedSales, 200);
    }



    public function editSale($id)
    {
        try {
            // Fetch sale details with related models
            $sale = Sale::with(['products.product', 'products.batch', 'customer', 'location'])
                ->findOrFail($id);
            // Prepare detailed response
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
                    // Handle unlimited stock products
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
                    if ($product->product->stock_alert === 0) {
                        return [
                            'id' => $product->id,
                            'sale_id' => $product->sale_id,
                            'product_id' => $product->product_id,
                            'batch_id' => 'all', // No batches for unlimited stock
                            'location_id' => $product->location_id,
                            'quantity' => $product->quantity,
                            'price_type' => $product->price_type,
                            'price' => $product->price,
                            'discount_type' => $product->discount_type,
                            'discount_amount' => $product->discount_amount,
                            'tax' => $product->tax,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'total_quantity' => 'Unlimited', // Indicate unlimited stock
                            'current_stock' => 'Unlimited',  // Indicate unlimited stock
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
                            'batch' => null, // No batch data for unlimited stock
                            'imei_numbers' => $product->imeis->pluck('imei_number')->toArray(),
                            'imeis' => $imeiDetails, // Full IMEI details
                        ];
                    }
                    // Handle regular products
                    $batchId = $product->batch_id ?? 'all';
                    // Calculate the total allowed quantity (current stock + sold in this sale)
                    $totalAllowedQuantity = $sale->getBatchQuantityPlusSold(
                        $batchId,
                        $product->location_id,
                        $product->product_id
                    );
                    // Get current stock without the sold quantity for reference
                    $currentStock = $batchId === 'all'
                        ? DB::table('location_batches')
                        ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                        ->where('batches.product_id', $product->product_id)
                        ->where('location_batches.location_id', $product->location_id)
                        ->sum('location_batches.qty')
                        : Sale::getAvailableStock($batchId, $product->location_id);
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
                        'total_quantity' => $totalAllowedQuantity, // Stock + sold in this sale
                        'current_stock' => $currentStock,   // Just current stock
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
            // Handle API or AJAX requests
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

            // Fetch amount_given and balance_amount from the sale
            $amount_given = $sale->amount_given;
            $balance_amount = $sale->balance_amount;


            // Fetch the user associated with the sale
            $user = User::find($sale->user_id);

            // Fetch the first location associated with the user
            $location = $user ? $user->locations()->first() : null;

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
}
