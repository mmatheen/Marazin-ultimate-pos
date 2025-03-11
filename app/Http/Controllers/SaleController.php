<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Location;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\LocationBatch;
use App\Models\Payment;
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

class SaleController extends Controller
{
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
        $sales = Sale::with('products.product', 'customer', 'location', 'payments')->get();
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
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $sale = DB::transaction(function () use ($request, $id) {
                $isUpdate = $id !== null;
                $sale = $isUpdate ? Sale::findOrFail($id) : new Sale();
                $referenceNo = $isUpdate ? $sale->reference_no : $this->generateReferenceNo();

                $finalTotal = array_reduce($request->products, function ($carry, $product) {
                    return $carry + $product['subtotal'];
                }, 0);

                $totalPaid = $request->total_paid ?? 0;
                $totalDue = $finalTotal - $totalPaid;

                $sale->fill([
                    'customer_id' => $request->customer_id,
                    'location_id' => $request->location_id,
                    'sales_date' => $request->sales_date,
                    'sale_type' => $request->sale_type,
                    'status' => $request->status,
                    'invoice_no' => Sale::generateInvoiceNo(),
                    'reference_no' => $referenceNo,
                    'final_total' => $finalTotal,
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                ])->save();

                if ($isUpdate) {
                    foreach ($sale->products as $product) {
                        $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                        $product->delete();
                    }
                }

                foreach ($request->products as $productData) {
                    $availableStock = $sale->getBatchQuantityPlusSold(
                        $productData['batch_id'],
                        $request->location_id,
                        $productData['product_id']
                    );

                    if ($productData['quantity'] > $availableStock) {
                        throw new \Exception("Insufficient stock for Product ID {$productData['product_id']} in Batch ID {$productData['batch_id']}.");
                    }

                    $this->processProductSale($productData, $sale->id, $request->location_id, StockHistory::STOCK_TYPE_SALE);
                }

                // Insert ledger entry for the sale
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

                // Insert ledger entries and create payment records for each payment
                if (!empty($request->payments)) {
                    foreach ($request->payments as $paymentData) {
                        Ledger::create([
                            'transaction_date' => $paymentData['payment_date'] ? date('Y-m-d H:i:s', strtotime($paymentData['payment_date'])) : now(),
                            'reference_no' => $referenceNo,
                            'transaction_type' => 'payments',
                            'debit' => $paymentData['amount'],
                            'credit' => 0,
                            'balance' => $this->calculateNewBalance($request->customer_id, $paymentData['amount'], 'debit'),
                            'contact_type' => 'customer',
                            'user_id' => $request->customer_id,
                        ]);

                        Payment::create([
                            'payment_date' => Carbon::parse($paymentData['payment_date'])->format('Y-m-d'),
                            'amount' => $paymentData['amount'],
                            'payment_method' => $paymentData['payment_method'],
                            'reference_no' => $sale->reference_no,
                            'notes' => $paymentData['notes'] ?? '',
                            'payment_type' => 'sale',
                            'reference_id' => $sale->id,
                            'customer_id' => $sale->customer_id,
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
                    }

                    $this->updatePaymentStatus($sale);
                } else {
                    $sale->updateTotalDue();
                }

                return $sale;
            });

            $customer = Customer::findOrFail($sale->customer_id);
            $products = SalesProduct::where('sale_id', $sale->id)->get();
            $payments = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->get();
            $totalDiscount = array_reduce($request->products, function ($carry, $product) {
                return $carry + ($product['discount'] ?? 0);
            }, 0);

            $html = view('sell.receipt', [
                'sale' => $sale,
                'customer' => $customer,
                'products' => $products,
                'payments' => $payments,
                'total_discount' => $totalDiscount,
            ])->render();

            return response()->json(['message' => $id ? 'Sale updated successfully.' : 'Sale recorded successfully.', 'invoice_html' => $html], 200);
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
        $totalPaid = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->sum('amount');
        $sale->total_paid = $totalPaid;
        $sale->total_due = $sale->final_total - $totalPaid;

        if ($sale->total_due <= 0) {
            $sale->payment_status = 'Paid';
        } elseif ($totalPaid < $sale->final_total) {
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
            // Calculate total available quantity across all batches
            $totalAvailableQty = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productData['product_id'])
                ->where('location_batches.location_id', $locationId)
                ->where('location_batches.qty', '>', 0)
                ->sum('location_batches.qty');

            if ($totalAvailableQty < $quantityToDeduct) {
                throw new \Exception('Insufficient stock to complete the sale.');
            }

            // Deduct from batches using FIFO method
            $batches = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productData['product_id'])
                ->where('location_batches.location_id', $locationId)
                ->where('location_batches.qty', '>', 0)
                ->orderBy('batches.created_at')
                ->select('location_batches.batch_id', 'location_batches.qty')
                ->get();

            foreach ($batches as $batch) {
                if ($remainingQuantity <= 0) {
                    break;
                }

                $deductQuantity = min($remainingQuantity, $batch->qty);
                $this->deductBatchStock($batch->batch_id, $locationId, $deductQuantity, $stockType);
                $batchIds[] = $batch->batch_id;
                $remainingQuantity -= $deductQuantity;
                $totalDeductedQuantity += $deductQuantity; // Accumulate the total deducted quantity
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

    // public function editSale($id)
    // {
    //     try {
    //         $sale = Sale::with([
    //             'products.product',
    //             'products.batch.locationBatches',
    //             'payments',
    //             'customer',
    //             'location',
    //         ])->findOrFail($id);

    //         // Fetch all related records
    //         $products = $sale->products;
    //         $payments = $sale->payments;
    //         $customer = $sale->customer;
    //         $location = $sale->location;

    //         // Ensure authenticated user's location is retrieved separately if needed
    //         $user = Auth::user();
    //         $userLocation = Location::find($user->location_id);

    //         $response = [
    //             'message' => 'Sale details fetched successfully.',
    //             'sale' => $sale,
    //             'products' => $products,
    //             'payments' => $payments,
    //             'customer' => $customer,
    //             'location' => $location,
    //             // 'user_location' => $userLocation
    //         ];

    //         if (request()->ajax() || request()->is('api/*')) {
    //             return response()->json(['status' => 200, 'sale' => $response], 200);
    //         }

    //         return view('sell.pos', compact('sale', 'products', 'payments', 'customer', 'location', 'userLocation'));
    //     } catch (ModelNotFoundException $e) {
    //         if (request()->ajax() || request()->is('api/*')) {
    //             return response()->json(['message' => 'Sale not found'], 404);
    //         }
    //         return redirect()->route('list-sale')->with('error', 'Sale not found.');
    //     }
    // }

    public function show($id)
{
    try {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized access'
            ], 401);
        }

        // Load user's location
        $location = Location::find($user->location_id);
        if (!$location) {
            return response()->json([
                'status' => 400,
                'message' => 'User location not found'
            ], 400);
        }

        // Load sale with relationships
        $sale = Sale::with([
            'products.product',
            'customer',
            'products.batch.locationBatches' => function($query) use ($user) {
                $query->where('location_id', $user->location_id);
            },
            'payments' // Include payments for complete sale information
        ])->findOrFail($id);

        // Check if user has permission to access this sale
        if ($sale->location_id !== $user->location_id) {
            return response()->json([
                'status' => 403,
                'message' => 'You do not have permission to access this sale.'
            ], 403);
        }

        // Format the sale data for response
        $formattedSale = [
            'id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'location_id' => $sale->location_id,
            'invoice_no' => $sale->invoice_no,
            'reference_no' => $sale->reference_no,
            'sales_date' => $sale->sales_date,
            'status' => $sale->status,
            'sale_type' => $sale->sale_type,
            'final_total' => $sale->final_total,
            'total_paid' => $sale->total_paid,
            'total_due' => $sale->total_due,
            'payment_status' => $sale->payment_status,
            'customer' => $sale->customer,
            'products' => $sale->products->map(function($product) {
                return [
                    'id' => $product->id,
                    'product_id' => $product->product_id,
                    'product' => [
                        'id' => $product->product->id,
                        'product_name' => $product->product->product_name,
                        'sku' => $product->product->sku,
                        'product_image' => $product->product->product_image,
                        'description' => $product->product->description,
                    ],
                    'batch_id' => $product->batch_id,
                    'quantity' => $product->quantity,
                    'unit_price' => $product->unit_price,
                    'price' => $product->price,
                    'subtotal' => $product->subtotal,
                    'discount' => $product->discount,
                    'tax' => $product->tax,
                    'batch' => [
                        'id' => $product->batch_id,
                        'location_batches' => $product->batch ? $product->batch->locationBatches : []
                    ]
                ];
            }),
            'payments' => $sale->payments,
            'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $sale->updated_at->format('Y-m-d H:i:s'),
        ];

        // Handle different types of requests
        if (request()->ajax() || request()->is('api/*')) {
            return response()->json([
                'status' => 200,
                'sale' => $formattedSale,
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    // Add other necessary location fields
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'location_id' => $user->location_id,
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'timezone' => config('app.timezone')
            ]);
        }

        // For web requests, return view with data
        return view('sell.pos', compact('sale', 'location'));

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => 404,
            'message' => 'Sale not found'
        ], 404);
    } catch (\Exception $e) {
        // \Log::error('Sale show error: ' . $e->getMessage(), [
        //     'user' => Auth::user()->id ?? 'unknown',
        //     'sale_id' => $id,
        //     'timestamp' => now()->format('Y-m-d H:i:s'),
        //     'trace' => $e->getTraceAsString()
        // ]);

        return response()->json([
            'status' => 400,
            'message' => 'An error occurred while fetching the sale details',
            'debug' => config('app.debug') ? $e->getMessage() : null
        ], 400);
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
}
