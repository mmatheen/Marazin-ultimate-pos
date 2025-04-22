<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Location;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\LocationBatch;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\SalesPayment;
use App\Models\SalesReturn;
use App\Models\StockHistory;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class SaleController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view sale', ['only' => ['listSale']]);
        $this->middleware('permission:add sale', ['only' => ['addSale']]);
        $this->middleware('permission:pos page', ['only' => ['pos']]);
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
        $user = Auth::user();
        $location = Location::find($user->location_id);
        return view('sell.pos', compact('location'));
    }

    public function posList()
    {
        return view('sell.pos_list');
    }

    public function index()
    {
        $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')->get();
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


    public function saleDailyReport(){
        return view('reports.daily_sales_report');
    }

    public function dailyReport(Request $request){
        try {
            $date = $request->input('date', Carbon::today()->toDateString());

            $salesQuery = Sale::with('customer', 'location', 'payments', 'products')
                ->whereDate('created_at', $date);

            // Calculate summaries
            $summaries = [
                'billTotal' => $salesQuery->sum('final_total'),
                'discounts' => $salesQuery->sum('discount_amount'),
                'cashPayments' => $salesQuery->clone()->whereHas('payments', function($query) {
                    $query->where('payment_method', 'cash');
                })->sum('total_paid'),
                'chequePayments' => $salesQuery->clone()->whereHas('payments', function($query) {
                    $query->where('payment_method', 'cheque');
                })->sum('total_paid'),
                'onlinePayments' => $salesQuery->clone()->whereHas('payments', function($query) {
                    $query->where('payment_method', 'online');
                })->sum('total_paid'),
                'bankTransfer' => $salesQuery->clone()->whereHas('payments', function($query) {
                    $query->where('payment_method', 'bank_transfer');
                })->sum('total_paid'),
                'cardPayments' => $salesQuery->clone()->whereHas('payments', function($query) {
                    $query->where('payment_method', 'card');
                })->sum('total_paid'),
                'salesReturns' => SalesReturn::whereDate('created_at', $date)->sum('return_total'),
                'paymentTotal' => $salesQuery->sum('total_paid'),
                'creditTotal' => $salesQuery->sum('total_due'),
                'salesReturnsTotal' => SalesReturn::whereDate('created_at', $date)->sum('return_total'),
                'paymentTotalSummary' => $salesQuery->sum('total_paid'),
                'pastSalesReturns' => SalesReturn::whereDate('created_at', '<', $date)->sum('return_total'),
                'expense' => 0, // Assuming expense is not calculated here
                'creditCollectionNew' => 0, // Assuming credit collection is not calculated here
                'creditCollectionOld' => 0, // Assuming credit collection is not calculated here
                'netIncome' => $salesQuery->sum('final_total') - SalesReturn::whereDate('created_at', $date)->sum('return_total'),
                'cashPaymentsSummary' => $salesQuery->clone()->whereHas('payments', function($query) {
                    $query->where('payment_method', 'cash');
                })->sum('total_paid'),
                'creditCollectionNewSummary' => 0, // Assuming credit collection is not calculated here
                'creditCollectionOldSummary' => 0, // Assuming credit collection is not calculated here
                'expenseSummary' => 0, // Assuming expense is not calculated here
                'cashInHand' => $salesQuery->clone()->whereHas('payments', function($query) {
                    $query->where('payment_method', 'cash');
                })->sum('total_paid') - SalesReturn::whereDate('created_at', $date)->sum('return_total'),
            ];

            $sales = $salesQuery->get();

            // Fetch sales return details based on sale id
            $salesReturns = SalesReturn::with('customer', 'location', 'returnProducts')
                                       ->whereIn('sale_id', $sales->pluck('id'))
                                       ->get();

            return response()->json(['sales' => $sales, 'summaries' => $summaries, 'salesReturns' => $salesReturns], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching sales data.', 'details' => $e->getMessage()], 500);
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
            'invoice_no' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.subtotal' => 'required|numeric|min:0',
            'products.*.batch_id' => 'nullable|string|max:255',
            'products.*.price_type' => 'required|string|in:retail,wholesale,special',
            'products.*.discount' => 'nullable|numeric|min:0',
            'products.*.tax' => 'nullable|numeric|min:0',
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
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }
    
        try {
            $sale = DB::transaction(function () use ($request, $id) {
                $isUpdate = $id !== null;
                $sale = $isUpdate ? Sale::findOrFail($id) : new Sale();
                $referenceNo = $isUpdate ? $sale->reference_no : $this->generateReferenceNo();
                $invoiceNo = $isUpdate ? $sale->invoice_no : Sale::generateInvoiceNo();
    
                // Calculate amounts
                $subtotal = array_reduce($request->products, fn($carry, $p) => $carry + $p['subtotal'], 0);
                $discount = $request->discount_amount ?? 0;
                $finalTotal = $request->discount_type === 'percentage' 
                    ? $subtotal - ($subtotal * $discount / 100) 
                    : $subtotal - $discount;
    
                // Create the sale record first to get the ID
                $sale->fill([
                    'customer_id' => $request->customer_id,
                    'location_id' => $request->location_id,
                    'sales_date' => $request->sales_date,
                    'sale_type' => $request->sale_type ?? 'retail',
                    'status' => $request->status,
                    'invoice_no' => $invoiceNo,
                    'reference_no' => $referenceNo,
                    'subtotal' => $subtotal,
                    'final_total' => $finalTotal,
                    'discount_type' => $request->discount_type,
                    'discount_amount' => $discount,
                    'user_id' => auth()->id(),
                    // Initialize payment fields with 0
                    'total_paid' => 0,
                    'total_due' => $finalTotal,
                    'amount_given' => 0,
                    'balance_amount' => 0,
                ])->save();
    
                // Handle payments
                $totalPaid = 0;
                if (!empty($request->payments)) {
                    $totalPaid = array_reduce($request->payments, fn($sum, $p) => $sum + $p['amount'], 0);
                    
                    if ($isUpdate) {
                        // Delete existing payments and ledger entries for updates
                        Payment::where('reference_id', $sale->id)->delete();
                        Ledger::where('reference_no', $referenceNo)
                            ->where('transaction_type', 'payments')
                            ->delete();
                    }
                    
                    // Create new payments
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
                    $totalPaid = $sale->total_paid; // Keep existing payments if none provided
                }
    
                $totalDue = max($finalTotal - $totalPaid, 0);
                $amountGiven = $request->amount_given ?? 0;
                $balanceAmount = $amountGiven - $finalTotal;
    
                // Update sale with payment totals
                $sale->update([
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                    'amount_given' => $amountGiven,
                    'balance_amount' => $balanceAmount,
                ]);
    
                // Handle products
                if ($isUpdate) {
                    foreach ($sale->products as $product) {
                        $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                        $product->delete();
                    }
                }
    
                foreach ($request->products as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    
                    if ($product->stock_alert === 0) {
                        $this->processUnlimitedStockProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE);
                    } else {
                        $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE);
                    }
                }
    
                // Update sale ledger entry
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
            
                        // Fetch the user associated with the sale
                $user = User::find($sale->user_id);

                $html = view('sell.receipt', [
                    'sale' => $sale,
                    'customer' => $customer,
                    'products' => $products,
                    'payments' => $payments,
                    'total_discount' => $request->discount_amount ?? 0,
                    'amount_given' => $sale->amount_given,
                    'balance_amount' => $sale->balance_amount,
                    'user' => $user, // Pass the user to the view
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

    private function processProductSale($productData, $saleId, $locationId, $stockType)
{
    $quantityToDeduct = $productData['quantity'];
    $remainingQuantity = $quantityToDeduct;
    $batchIds = [];
    $totalDeductedQuantity = 0; // Variable to keep track of the total deducted quantity

    // dump('Processing product sale: ' . json_encode($productData));
    // dump('Quantity to deduct: ' . $quantityToDeduct);
    // dump('Remaining quantity: ' . $remainingQuantity);
    // dump('Stock type: ' . $stockType);
    // dump('Location ID: ' . $locationId);
    // dump('Sale ID: ' . $saleId);
    // dump('Batch ID: ' . $productData['batch_id']);

    if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
        // Specific batch selected
        $batch = Batch::findOrFail($productData['batch_id']);
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->firstOrFail();

        if ($locationBatch->qty < $quantityToDeduct) {
            throw new \Exception("Batch ID {$productData['batch_id']} does not have enough stock.");
        }

        $this->deductBatchStock($productData['batch_id'], $locationId, $quantityToDeduct, $stockType);
        $batchIds[] = $batch->id;
    } else {
        // All batches selected
        // Calculate total available quantity across all locations and batches
        $totalAvailableQty = DB::table('location_batches')
            ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
            ->where('batches.product_id', $productData['product_id'])
            ->where('location_batches.qty', '>', 0)
            ->sum('location_batches.qty');

        // dump('Total available quantity: ' . $totalAvailableQty);

        if ($totalAvailableQty < $quantityToDeduct) {
            throw new \Exception('Insufficient stock to complete the sale.');
        }

        // Deduct from batches using FIFO method across the specified location
        $batches = DB::table('location_batches')
            ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
            ->where('batches.product_id', $productData['product_id'])
            ->where('location_batches.qty', '>', 0)
            ->orderBy('batches.created_at')
            ->select('location_batches.batch_id', 'location_batches.qty', 'location_batches.location_id')
            ->get();

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            if ($batch->location_id == $locationId || $locationId == $productData['location_id']) {
                $deductQuantity = min($remainingQuantity, $batch->qty);
                $this->deductBatchStock($batch->batch_id, $batch->location_id, $deductQuantity, $stockType);
                $batchIds[] = $batch->batch_id;
                $remainingQuantity -= $deductQuantity;
                $totalDeductedQuantity += $deductQuantity; // Accumulate the total deducted quantity
            }
        }
    }

    // Record the sales product using the total deducted quantity
    foreach ($batchIds as $batchId) {
        SalesProduct::create([
            'sale_id' => $saleId,
            'product_id' => $productData['product_id'],
            'quantity' => $quantityToDeduct, // Use the original quantity to be deducted
            'price' => $productData['unit_price'],
            'unit_price' => $productData['unit_price'],
            'subtotal' => $productData['subtotal'],
            'batch_id' => $batchId,
            'location_id' => $locationId,
            'price_type' => $productData['price_type'],
            'discount' => $productData['discount'],
            'tax' => $productData['tax'],
        ]);
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

        DB::table('batches')
            ->where('id', $batch->id)
            ->update(['qty' => DB::raw("GREATEST(qty - $quantity, 0)")]);

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
            'discount' => $productData['discount'],
            'tax' => $productData['tax'],
        ]);

        // Add stock history for unlimited stock product
        StockHistory::create([
            'loc_batch_id' => null,
            'quantity' => -$productData['quantity'],
            'stock_type' => $stockType,

        ]);
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

        DB::table('batches')
            ->where('id', $product->batch_id)
            ->update(['qty' => DB::raw("qty + {$product->quantity}")]);

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

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();

        return response()->json(['status' => 200, 'message' => 'Sale deleted successfully!']);
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
                    'id', 'customer_id', 'location_id', 'sales_date', 'sale_type', 'status', 'invoice_no',
                    'subtotal', 'discount_type', 'discount_amount', 'final_total', 'total_paid', 'total_due',
                    'payment_status', 'created_at', 'updated_at'
                ]),
                'sale_products' => $sale->products->map(function ($product) use ($sale) {
                    // Handle unlimited stock products
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
                            'discount' => $product->discount,
                            'tax' => $product->tax,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'total_quantity' => 'Unlimited', // Indicate unlimited stock
                            'current_stock' => 'Unlimited',  // Indicate unlimited stock
                            'product' => optional($product->product)->only([
                                'id', 'product_name', 'sku', 'unit_id', 'brand_id', 'main_category_id', 'sub_category_id',
                                'stock_alert', 'alert_quantity', 'product_image', 'description', 'is_imei_or_serial_no',
                                'is_for_selling', 'product_type', 'pax', 'original_price', 'retail_price',
                                'whole_sale_price', 'special_price', 'max_retail_price'
                            ]),
                            'batch' => null, // No batch data for unlimited stock
                        ];
                    }
    
                    // Handle regular products
                    $batchId = $product->batch_id ?? 'all';
    
                    // Calculate the total available quantity (current stock + sold in this sale)
                    $totalQuantity = $sale->getBatchQuantityPlusSold(
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
                        'discount' => $product->discount,
                        'tax' => $product->tax,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'total_quantity' => $totalQuantity, // Stock + sold in this sale
                        'current_stock' => $currentStock,   // Just current stock
                        'product' => optional($product->product)->only([
                            'id', 'product_name', 'sku', 'unit_id', 'brand_id', 'main_category_id', 'sub_category_id',
                            'stock_alert', 'alert_quantity', 'product_image', 'description', 'is_imei_or_serial_no',
                            'is_for_selling', 'product_type', 'pax', 'original_price', 'retail_price',
                            'whole_sale_price', 'special_price', 'max_retail_price'
                        ]),
                        'batch' => optional($product->batch)->only([
                            'id', 'batch_no', 'product_id', 'qty', 'unit_cost', 'wholesale_price', 'special_price',
                            'retail_price', 'max_retail_price', 'expiry_date'
                        ])
                    ];
                }),
                'customer' => optional($sale->customer)->only([
                    'id', 'prefix', 'first_name', 'last_name', 'mobile_no', 'email', 'address', 'opening_balance',
                    'current_balance', 'location_id'
                ]),
                'location' => optional($sale->location)->only([
                    'id', 'name', 'location_id', 'address', 'province', 'district', 'city', 'email', 'mobile',
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

            $html = view('sell.receipt', [
                'sale' => $sale,
                'customer' => $customer,
                'products' => $products,
                'payments' => $payments,
                'total_discount' => $totalDiscount,
                'amount_given' => $amount_given, // Pass amount_given to the view
                'balance_amount' => $balance_amount, // Pass balance_amount to the view
            ])->render();

            return response()->json(['invoice_html' => $html], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
