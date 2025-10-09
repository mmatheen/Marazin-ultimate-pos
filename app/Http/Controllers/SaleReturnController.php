<?php

namespace App\Http\Controllers;

use App\Models\SalesReturn;
use App\Models\SalesReturnProduct;
use App\Models\Batch;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Customer;
use App\Services\UnifiedLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\LocationBatch;
use App\Models\StockHistory;
use App\Models\User;
use Carbon\Carbon;

class SaleReturnController extends Controller
{
    protected $unifiedLedgerService;

    function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        $this->middleware('permission:view sale-return', ['only' => ['listSaleReturn', 'index', 'show']]);
        $this->middleware('permission:create sale-return', ['only' => ['addSaleReturn', 'store']]);
        $this->middleware('permission:edit sale-return', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete sale-return', ['only' => ['destroy']]);
    }

    /**
     * Show the form for adding a new sale return.
     */
    public function addSaleReturn()
    {
        return view('saleReturn.add_sale_return');
    }
    public function listSaleReturn()
    {
        return view('saleReturn.sale_return');
    }

    /**
     * Store a newly created sale return in the database.
     */
    public function storeOrUpdate(Request $request, $id = null)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'location_id' => 'required|exists:locations,id',
            'return_date' => 'required|date',
            'return_total' => 'required|numeric',
            'notes' => 'nullable|string',
            'is_defective' => 'nullable|boolean',
            'products.*.product_id' => 'required|exists:products,id',
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
                            $product = \App\Models\Product::find($productData['product_id']);
                            if ($product && $product->unit && !$product->unit->allow_decimal && floor($value) != $value) {
                                $fail("The quantity must be an integer for this unit.");
                            }
                        }
                    }
                },
            ],
            'products.*.original_price' => 'required|numeric',
            'products.*.return_price' => 'required|numeric',
            'products.*.subtotal' => 'required|numeric',
            'products.*.batch_id' => 'nullable|exists:batches,id',
            'products.*.price_type' => 'required|string',
            'products.*.discount' => 'nullable|numeric',
            'products.*.tax' => 'nullable|numeric',
            // Payment validation rules
            'payments' => 'nullable|array',
            'payments.*.payment_date' => 'required_with:payments|date',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'payments.*.payment_method' => 'required_with:payments|in:cash,card,cheque,bank_transfer,online',
            'payments.*.notes' => 'nullable|string',
            'payments.*.card_number' => 'required_if:payments.*.payment_method,card|nullable|string',
            'payments.*.card_holder_name' => 'required_if:payments.*.payment_method,card|nullable|string',
            'payments.*.cheque_number' => 'required_if:payments.*.payment_method,cheque|nullable|string',
            'payments.*.cheque_bank_branch' => 'required_if:payments.*.payment_method,cheque|nullable|string',
            'payments.*.cheque_valid_date' => 'required_if:payments.*.payment_method,cheque|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        // Check for duplicate returns - only for new returns (not updates)
        if (!$id && $request->sale_id) {
            $existingReturns = SalesReturn::where('sale_id', $request->sale_id)->count();
            if ($existingReturns > 0) {
                return response()->json([
                    'status' => 409, // Conflict status code
                    'errors' => ['This sale has already been returned. Multiple returns for the same invoice are not allowed.']
                ]);
            }
        }

        // Check if the return quantity is greater than the sale quantity
        $sale = Sale::find($request->sale_id);
        $errors = [];
        if ($sale) {
            foreach ($request->products as $productData) {
                $soldProduct = $sale->products->firstWhere('product_id', $productData['product_id']);
                if ($soldProduct && $productData['quantity'] > $soldProduct->quantity) {
                    $errors[] = "Return quantity for product ID {$productData['product_id']} exceeds sold quantity.";
                }
            }
        }

        if (!empty($errors)) {
            return response()->json(['status' => 400, 'errors' => $errors]);
        }

        // Use transaction to ensure atomicity
        DB::transaction(function () use ($request, $id) {
            $stockType = $request->sale_id ? 'with_bill' : 'without_bill';
            $transactionType = $request->sale_id ? 'sale_return_with_bill' : 'sale_return_without_bill';

            // Get the authenticated user ID
            $userId = auth()->id();

            // Create or update the sales return
            $salesReturn = SalesReturn::updateOrCreate(
                ['id' => $id],
                [
                    'sale_id' => $request->sale_id,
                    'customer_id' => $request->customer_id ?? ($request->sale_id ? optional(Sale::find($request->sale_id))->customer_id : null),
                    'location_id' => $request->location_id,
                    // Set return_date as created_at in Asia/Colombo timezone
                    'return_date' => Carbon::now('Asia/Colombo')->format('Y-m-d H:i:s'),
                    'return_total' => $request->return_total,
                    'total_paid' => 0, // Ensure total_paid is set to 0
                    'total_due' => $request->return_total, // Calculate total_due since total_paid is 0
                    'notes' => $request->notes,
                    'is_defective' => $request->is_defective,
                    'stock_type' => $stockType,
                    'user_id' => $userId, // Set the user who created/updated the return
                ]
            );

            // Delete existing products for the sale return if updating
            if ($id) {
                SalesReturnProduct::where('sales_return_id', $id)->delete();
            }

            // Process each returned product
            foreach ($request->products as $productData) {
                $this->processProductReturn($productData, $salesReturn->id, $request->location_id, $stockType);
            }

            // Update total due
            $salesReturn->updateTotalDue();

            // Use unified ledger service to record or update the sale return
            if ($id) {
                // For updates, use updateSaleReturn method
                $this->unifiedLedgerService->updateSaleReturn($salesReturn);
            } else {
                // For new returns, use recordSaleReturn method
                $this->unifiedLedgerService->recordSaleReturn($salesReturn);
            }

            // Process payments if provided
            if ($request->has('payments') && is_array($request->payments)) {
                // Generate reference number for payments
                $referenceNo = 'SRT-' . $salesReturn->id . '-' . time();

                // Delete existing payments if updating
                if ($id) {
                    Payment::where('reference_id', $salesReturn->id)
                        ->whereIn('payment_type', ['sale_return_with_bill', 'sale_return_without_bill'])
                        ->delete();
                }

                // Determine payment type based on whether it's with bill or without bill
                $paymentType = $salesReturn->sale_id ? 'sale_return_with_bill' : 'sale_return_without_bill';

                foreach ($request->payments as $paymentData) {
                    // Prepare payment data with enhanced cheque handling
                    $paymentCreateData = [
                        'payment_date' => Carbon::parse($paymentData['payment_date'])->format('Y-m-d'),
                        'amount' => $paymentData['amount'],
                        'payment_method' => $paymentData['payment_method'],
                        'reference_no' => $referenceNo,
                        'notes' => $paymentData['notes'] ?? '',
                        'payment_type' => $paymentType,
                        'reference_id' => $salesReturn->id,
                        'customer_id' => $salesReturn->customer_id,
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

                    // Use unified ledger service for return payment recording
                    $this->unifiedLedgerService->recordReturnPayment($payment, 'customer');
                }

                // Update total paid and total due for the sale return
                $salesReturn->updateTotalDue();
            }
        });

        return response()->json(['status' => 200, 'message' => 'Sales return recorded successfully!']);
    }


    /**
     * Get all sale returns.
     */
    // Controller method
    public function getAllSaleReturns()
    {
        $salesReturns = SalesReturn::with(['sale.customer', 'sale.location', 'customer', 'payments','user'])->get();
        $totalAmount = $salesReturns->sum('return_total');
        $totalDue = $salesReturns->sum('total_due');

        return response()->json([
            'status' => 200,
            'data' => $salesReturns,
            'totalAmount' => $totalAmount,
            'totalDue' => $totalDue
        ]);
    }

    /**
     * Get a sale return by ID.
     */
    public function getSaleReturnById($id)
    {
        $salesReturn = SalesReturn::with(['returnProducts.product', 'sale.customer', 'sale.location', 'payments'])->find($id);
        if (!$salesReturn) {
            return response()->json(['status' => 404, 'message' => 'Sale return not found']);
        }
        return response()->json(['status' => 200, 'data' => $salesReturn]);
    }


    /**
     * Edit a sale return.
     */
    public function editSaleReturn($id)
    {
        $salesReturn = SalesReturn::find($id);
        if (!$salesReturn) {
            return response()->json(['status' => 404, 'message' => 'Sale return not found']);
        }
        return view('saleReturn.edit_sale_return', compact('salesReturn'));
    }

    private function processProductReturn($productData, $salesReturnId, $locationId, $stockType)
    {
        $quantityToRestock = $productData['quantity'];
        $batchId = $productData['batch_id'];

        if (empty($batchId)) {
            // Create new batch if no batch ID is provided (for returns without a bill)
            $batch = Batch::create([
                'batch_no' => Batch::generateNextBatchNo(),
                'product_id' => $productData['product_id'],
                'unit_cost' => $productData['original_price'],
                'qty' => $quantityToRestock,
                'wholesale_price' => $productData['return_price'],
                'special_price' => $productData['return_price'],
                'retail_price' => $productData['return_price'],
                'max_retail_price' => $productData['return_price'],
                'expiry_date' => now()->addYear(), // Assuming 1 year expiry
            ]);

            // Create location batch record
            $locationBatch = LocationBatch::create([
                'batch_id' => $batch->id,
                'location_id' => $locationId,
                'qty' => $quantityToRestock,
            ]);

            $batchId = $batch->id;

            // Create stock history record
            StockHistory::create([
                'loc_batch_id' => $locationBatch->id,
                'quantity' => $quantityToRestock,
                'stock_type' => $stockType === 'with_bill' ? 'sales_return_with_bill' : 'sales_return_without_bill',
            ]);
        } else {
            // Restock specific batch
            $this->restockBatchStock($batchId, $locationId, $quantityToRestock, $stockType);
        }

        // Create sales return product record
        SalesReturnProduct::create([
            'sales_return_id' => $salesReturnId,
            'product_id' => $productData['product_id'],
            'quantity' => $productData['quantity'],
            'original_price' => $productData['original_price'],
            'return_price' => $productData['return_price'],
            'subtotal' => $productData['subtotal'],
            'batch_id' => $batchId,
            'location_id' => $locationId,
            'price_type' => $productData['price_type'],
            'discount' => $productData['discount'],
            'tax' => $productData['tax'],
        ]);
    }

    private function restockBatchStock($batchId, $locationId, $quantity, $stockType)
    {
        $batch = Batch::findOrFail($batchId);
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->firstOrFail();

        // Restock stock to location batch
        $locationBatch->increment('qty', $quantity);


        // Create stock history record
        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => $quantity,
            'stock_type' => $stockType === 'with_bill' ? 'sales_return_with_bill' : 'sales_return_without_bill',
        ]);
    }

    /**
     * Print the return receipt with user details.
     */
    public function printReturnReceipt($id)
    {
        try {
            // Eager-load all necessary relationships
            $saleReturn = SalesReturn::with([
                'sale' => function ($query) {
                    $query->with(['customer', 'location']);
                },
                'returnProducts.product',
                'payments'
            ])->findOrFail($id);

            // Extract related models with null checks
            $customer = $saleReturn->sale && $saleReturn->sale->customer
                ? $saleReturn->sale->customer
                : ($saleReturn->customer ?? null);

            // Fetch the user associated with the sale return
            $user = $saleReturn->user_id ? User::find($saleReturn->user_id) : null;

            // Fetch the first location associated with the user, or fallback to sale location
            if ($user && method_exists($user, 'locations')) {
                $userLocations = $user->locations();
                $location = $userLocations instanceof \Illuminate\Database\Eloquent\Relations\Relation
                    ? $userLocations->first()
                    : (is_callable([$userLocations, 'first']) ? $userLocations->first() : null);
            } else {
                $location = $saleReturn->sale && $saleReturn->sale->location
                    ? $saleReturn->sale->location
                    : null;
            }

            // Handle payments safely
            $payments = collect($saleReturn->payments ?? []);

            $amount_given = null;
            $balance_amount = null;

            if ($payments->isNotEmpty()) {
                $totalPaid = $payments->sum('amount');
                $amount_given = $totalPaid;
                $balance_amount = $totalPaid - $saleReturn->return_total;
            }

            // Render the Blade view to HTML
            $html = view('saleReturn.sale_return_receipt', compact(
                'saleReturn',
                'location',
                'customer',
                'amount_given',
                'balance_amount',
                'payments',
                'user'
            ))->render();

            // Return JSON response for AJAX
            return response()->json([
                'success' => true,
                'invoice_html' => $html
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Sale return not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error generating sale return receipt: ' . $e->getMessage(), [
                'sale_return_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Unable to generate receipt. Please try again later.'
            ], 500);
        }
    }
}
