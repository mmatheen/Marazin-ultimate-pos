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
use App\Models\StockHistory;
use App\Models\Transaction;
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
        $salesDetails = Sale::with('products.product', 'customer', 'location','payments')->findOrFail($id);
        return response()->json(['salesDetails' => $salesDetails], 200);
    }

    public function edit($id)
    {
        $sale = Sale::with('products.product.batches', 'customer', 'location','payments')->findOrFail($id);

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
                // Determine if we are updating or creating a new sale
                $isUpdate = $id !== null;
                $sale = $isUpdate ? Sale::findOrFail($id) : new Sale();

                // Generate the reference number if creating a new sale
                $referenceNo = $isUpdate ? $sale->reference_no : $this->generateReferenceNo();

                // Calculate the final total
                $finalTotal = array_reduce($request->products, function ($carry, $product) {
                    return $carry + $product['subtotal'];
                }, 0);

                // Calculate the total due
                $totalPaid = $request->total_paid ?? 0;
                $totalDue = $finalTotal - $totalPaid;

                // Update or create the sale
                $sale->fill([
                    'customer_id' => $request->customer_id,
                    'location_id' => $request->location_id,
                    'sales_date' => $request->sales_date,
                    'status' => $request->status,
                    'invoice_no' => $request->invoice_no,
                    'reference_no' => $referenceNo,
                    'final_total' => $finalTotal,
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue, // Ensure total_due is set
                ])->save();

                // Restore stock for existing sale products if updating
                if ($isUpdate) {
                    foreach ($sale->products as $product) {
                        $this->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                        $product->delete();
                    }
                }

                // Process each sold product and handle stock
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

                // Handle payment and ledger updates
                if ($totalPaid > 0) {
                    $this->handlePayment($request, $sale);
                }

                return $sale;
            });

            // Fetch the customer object from the database
            $customer = Customer::findOrFail($sale->customer_id);

            // Fetch the sale products with their related product details
            $invoiceItems = $sale->products()->with('product')->get();

            // Calculate the net total
            $netTotal = $invoiceItems->sum(function ($item) {
                return $item->subtotal;
            });

            // Render the invoice view
            $html = view('sell.invoice1', [
                'invoice' => $sale,
                'customer' => $customer,
                'items' => $invoiceItems,
                'amount' => $netTotal,
                'payment_mode' => $request->payment_mode,
                'payment_status' => $request->payment_status,
                'payment_reference' => $request->payment_reference,
                'payment_date' => now(),
            ])->render();

            return response()->json(['message' => $id ? 'Sale updated successfully.' : 'Sale recorded successfully.', 'invoice_html' => $html], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    private function handlePayment($request, $sale)
    {
        // Calculate the total due and total paid for the sale
        $totalPaid = Payment::where('reference_id', $sale->id)->where('payment_type', 'sale')->sum('amount');
        $totalDue = $sale->final_total - $totalPaid;

        // If the paid amount exceeds total due, adjust it
        $paidAmount = min($request->total_paid, $totalDue);

        $payment = Payment::create([
            'payment_date' => $request->payment_date ? \Carbon\Carbon::parse($request->payment_date)->format('Y-m-d') : now()->format('Y-m-d H:i:s'),
            'amount' => $paidAmount,
            'payment_method' => $request->payment_mode,
            'reference_no' => $sale->reference_no,
            'notes' => $request->payment_note,
            'payment_type' => 'sale',
            'reference_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'card_number' => $request->card_number,
            'card_holder_name' => $request->card_holder_name,
            'card_expiry_month' => $request->card_expiry_month,
            'card_expiry_year' => $request->card_expiry_year,
            'card_security_code' => $request->card_security_code,
            'cheque_number' => $request->cheque_number,
            'cheque_bank_branch' => $request->cheque_bank_branch,
            'cheque_received_date' => $request->cheque_received_date,
            'cheque_valid_date' => $request->cheque_valid_date,
            'cheque_given_by' => $request->cheque_given_by,
        ]);

        Transaction::create([
            'transaction_date' => $payment->payment_date,
            'amount' => $payment->amount,
            'transaction_type' => $payment->payment_type,
            'reference_id' => $payment->id,
        ]);

        // Update sale payment status based on total due
        if ($totalDue - $paidAmount <= 0) {
            $sale->payment_status = 'Paid';
        } elseif ($totalPaid + $paidAmount < $sale->final_total) {
            $sale->payment_status = 'Partial';
        } else {
            $sale->payment_status = 'Due';
        }

        $sale->save();
    }

    private function updateCustomerBalance($customerId)
    {
        $customer = Customer::find($customerId);

        if ($customer) {
            $totalSales = Sale::where('customer_id', $customerId)->sum('final_total');
            $totalPayments = Payment::where('customer_id', $customerId)->where('payment_type', 'sale')->sum('amount');

            $customer->current_balance = $customer->opening_balance + $totalSales - $totalPayments;
            $customer->save();
        }
    }

    private function getTotalDue($request, $paymentId = null)
    {
        $totalPaid = $paymentId ? Payment::where('id', '!=', $paymentId)->where('reference_id', $request->reference_id)->where('payment_type', $request->payment_type)->sum('amount') : 0;

        if ($request->payment_type === 'sale') {
            $sale = Sale::find($request->reference_id);
            return $sale ? $sale->final_total - $totalPaid : 0;
        }

        return 0;
    }

    private function processProductSale($productData, $saleId, $locationId, $stockType)
    {
        $quantityToDeduct = $productData['quantity'];
        $batchIds = [];

        if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
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
            $totalAvailableQty = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productData['product_id'])
                ->where('location_batches.location_id', $locationId)
                ->where('location_batches.qty', '>', 0)
                ->sum('location_batches.qty');

            if ($totalAvailableQty < $quantityToDeduct) {
                throw new \Exception('Insufficient stock to complete the sale.');
            }

            $remainingQuantity = $quantityToDeduct;
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
            }
        }

        foreach ($batchIds as $batchId) {
            SalesProduct::create([
                'sale_id' => $saleId,
                'product_id' => $productData['product_id'],
                'quantity' => $quantityToDeduct,
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
            $currentQuantity = $this->getCurrentSaleQuantity($product->product_id);
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
        $sale = Sale::with([
            'products.product',
            'products.batch.locationBatches',
            'payments',
            'customer',
            'location',
        ])->findOrFail($id);

        // Fetch all related records
        $products = $sale->products;
        $payments = $sale->payments;
        $customer = $sale->customer;
        $location = $sale->location;

        // Ensure authenticated user's location is retrieved separately if needed
        $user = Auth::user();
        $userLocation = Location::find($user->location_id);

        $response = [
            'message' => 'Sale details fetched successfully.',
            'sale' => $sale,
            'products' => $products,
            'payments' => $payments,
            'customer' => $customer,
            'location' => $location,
            'user_location' => $userLocation
        ];

        if (request()->ajax() || request()->is('api/*')) {
            return response()->json(['status' => 200, 'sale' => $response], 200);
        }

        return view('sell.pos', compact('sale', 'products', 'payments', 'customer', 'location', 'userLocation'));
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
