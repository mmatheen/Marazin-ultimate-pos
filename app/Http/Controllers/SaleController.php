<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\SellDetail;
use App\Models\ProductOrder;
use App\Models\OpeningStock;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Stock;
use App\Models\Batch;
use App\Models\PaymentInfo;
use App\Models\Customer;
use App\Models\LocationBatch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\SalesPayment;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\FuncCall;
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

        // Retrieve the user's associated location, or null if not assigned
        $location = Location::find($user->location_id);

        return view('sell.pos', compact('location'));
    }
    public function posList()
    {

        // $user = Auth::user();

        // // Retrieve the user's associated location, or null if not assigned
        // $location = Location::find($user->location_id);

        return view('sell.pos_list');
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Validation rules
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        $sale = DB::transaction(function () use ($request) {
            $attachDocument = $this->handleAttachedDocument($request);

            $referenceNo = $this->generateReferenceNo();

            // Create sale record
            $sale = Sale::create([
                'customer_id' => $request->customer_id,
                'reference_no' => $referenceNo,
                'location_id' => $request->location_id,
                'sales_date' => $request->sales_date,
                'status' => $request->status,
                'invoice_no' => $request->invoice_no,
                'additional_notes' => $request->additional_notes,
                'shipping_details' => $request->shipping_details,
                'shipping_address' => $request->shipping_address,
                'shipping_charges' => $request->shipping_charges,
                'shipping_status' => $request->shipping_status,
                'delivered_to' => $request->delivered_to,
                'delivery_person' => $request->delivery_person,
                'attach_document' => $attachDocument,
            ]);

            // Process each sold product
            foreach ($request->products as $productData) {
                $this->processProductSale($productData, $sale->id, $request->location_id);
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

        return response()->json(['status' => 200, 'message' => 'Sale recorded successfully!', 'invoice_html' => $html]);
    }
/**
 * Handle product sale with batch-wise or FIFO stock deduction.
 */
private function processProductSale($productData, $saleId, $locationId)
{
    $quantityToDeduct = $productData['quantity'];

    if (!empty($productData['batch_no'])) {
        // Check if the batch has enough stock
        $batch = Batch::where('batch_no', $productData['batch_no'])->firstOrFail();
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->firstOrFail();

        if ($locationBatch->qty < $quantityToDeduct) {
            throw new \Exception("Batch {$productData['batch_no']} does not have enough stock.");
        }

        // Deduct stock from a specific batch
        $this->deductBatchStock($productData['batch_no'], $locationId, $quantityToDeduct);
        $batchId = $batch->id;
    } else {
        // Check if there is enough stock across all batches using FIFO
        $totalAvailableQty = DB::table('location_batches')
            ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
            ->where('batches.product_id', $productData['product_id'])
            ->where('location_batches.location_id', $locationId)
            ->where('location_batches.qty', '>', 0)
            ->sum('location_batches.qty');

        if ($totalAvailableQty < $quantityToDeduct) {
            throw new \Exception('Insufficient stock to complete the sale.');
        }

        // Deduct stock using FIFO
        $this->deductStockFIFO($productData['product_id'], $locationId, $quantityToDeduct);
        $batchId = null;
    }

    // Create sales product record
    SalesProduct::create([
        'sale_id' => $saleId,
        'product_id' => $productData['product_id'],
        'quantity' => $productData['quantity'],
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

/**
 * Deduct stock from a specific batch.
 */


private function deductBatchStock($batchNo, $locationId, $quantity)
{
    Log::info("Deducting $quantity from batch $batchNo at location $locationId");

    $batch = Batch::where('batch_no', $batchNo)->firstOrFail();
    $locationBatch = LocationBatch::where('batch_id', $batch->id)
        ->where('location_id', $locationId)
        ->firstOrFail();

    Log::info("Before deduction: LocationBatch qty: {$locationBatch->qty}, Batch qty: {$batch->qty}");

    // Deduct stock from location batch
    $locationBatch->update(['qty' => DB::raw("GREATEST(qty - $quantity, 0)")]);

    // Deduct stock from batch
    $batch->decrement('qty', $quantity);

    Log::info("After deduction: LocationBatch qty: {$locationBatch->qty}, Batch qty: {$batch->qty}");

    // Create stock history record
    StockHistory::create([
        'loc_batch_id' => $locationBatch->id,
        'quantity' => -$quantity,
        'stock_type' => 'Sale',
    ]);
}

private function deductStockFIFO($productId, $locationId, $quantity)
{
    Log::info("Deducting $quantity from product $productId at location $locationId using FIFO");

    $batches = Batch::where('product_id', $productId)
        ->whereHas('locationBatches', function ($query) use ($locationId) {
            $query->where('location_id', $locationId)->where('qty', '>', 0);
        })
        ->orderBy('created_at')
        ->get();

    foreach ($batches as $batch) {
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->firstOrFail();

        if ($quantity <= 0) {
            break;
        }

        $deductQuantity = min($quantity, $locationBatch->qty);

        Log::info("Deducting $deductQuantity from batch {$batch->batch_no}");

        // Deduct stock from batch and location batch
        $batch->decrement('qty', $deductQuantity);
        $locationBatch->decrement('qty', $deductQuantity);

        // Create stock history record
        StockHistory::create([
            'loc_batch_id' => $locationBatch->id,
            'quantity' => -$deductQuantity,
            'stock_type' => 'Sale',
        ]);

        $quantity -= $deductQuantity;
    }

    if ($quantity > 0) {
        throw new \Exception('Insufficient stock to complete the sale.');
    }
}

private function generateReferenceNo()
{
    return 'PUR-' . now()->format('YmdHis') . '-' . strtoupper(uniqid());
}

private function handleAttachedDocument($request)
{
    if ($request->hasFile('attached_document')) {
        return $request->file('attached_document')->store('documents');
    }
    return null;
}
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'location_id' => 'required|exists:locations,id',
            'sales_date' => 'required|date',
            'status' => 'required|string',
            'invoice_no' => 'nullable|string|max:255',
            'additional_notes' => 'nullable|string',
            'shipping_details' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'shipping_charges' => 'nullable|numeric',
            'shipping_status' => 'nullable|string',
            'delivered_to' => 'nullable|string',
            'delivery_person' => 'nullable|string',

            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.batch_id' => 'nullable|exists:batches,id',
            'products.*.discount' => 'nullable|numeric|min:0',
            'products.*.tax' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_account' => 'nullable|string',
            'payment_note' => 'nullable|string',
        ]);

        $sale = null;
        DB::transaction(function () use ($validated, $id, &$sale) {
            $sale = Sale::findOrFail($id);

            // Update the Sale record
            $sale->update([
                'customer_id' => $validated['customer_id'],
                'location_id' => $validated['location_id'],
                'sales_date' => $validated['sales_date'],
                'status' => $validated['status'],
                'invoice_no' => $validated['invoice_no'],
                'additional_notes' => $validated['additional_notes'],
                'shipping_details' => $validated['shipping_details'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_charges' => $validated['shipping_charges'],
                'shipping_status' => $validated['shipping_status'],
                'delivered_to' => $validated['delivered_to'],
                'delivery_person' => $validated['delivery_person'],
            ]);

            // Remove existing SalesProduct records and restore stock
            foreach ($sale->salesProducts as $salesProduct) {
                $batch = Batch::findOrFail($salesProduct->batch_id);
                $batch->quantity += $salesProduct->quantity;
                $batch->save();

                Stock::create([
                    'product_id' => $salesProduct->product_id,
                    'location_id' => $salesProduct->location_id,
                    'batch_id' => $salesProduct->batch_id,
                    'quantity' => $salesProduct->quantity,
                    'stock_type' => 'Restock',
                ]);

                $salesProduct->delete();
            }

            // Loop through products to create new SalesProduct records and update stock
            foreach ($validated['products'] as $product) {
                $remainingQuantity = $product['quantity'];

                $batches = Batch::where('product_id', $product['product_id'])
                    ->where('quantity', '>', 0)
                    ->orderBy('created_at')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingQuantity <= 0) {
                        break;
                    }

                    $deduct = min($batch->quantity, $remainingQuantity);
                    $batch->quantity -= $deduct;
                    $remainingQuantity -= $deduct;
                    $batch->save();

                    Stock::create([
                        'product_id' => $product['product_id'],
                        'location_id' => $validated['location_id'],
                        'batch_id' => $batch->id,
                        'quantity' => -$deduct,
                        'stock_type' => 'Sale',
                    ]);

                    SalesProduct::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product['product_id'],
                        'batch_id' => $batch->id,
                        'location_id' => $validated['location_id'],
                        'quantity' => $deduct,
                        'unit_price' => $product['unit_price'],
                        'discount' => $product['discount'] ?? 0,
                        'tax' => $product['tax'] ?? 0,
                    ]);
                }

                if ($remainingQuantity > 0) {
                    throw new \Exception('Insufficient batch stock for product ID: ' . $product['product_id']);
                }
            }

            $totalAmount = array_sum(array_map(function ($product) {
                return $product['quantity'] * $product['unit_price'];
            }, $validated['products']));

            $sale->salesPayment->update([
                'payment_method' => $validated['payment_method'],
                'payment_account' => $validated['payment_account'],
                'amount' => $totalAmount,
                'payment_note' => $validated['payment_note'],
            ]);
        });

        return response()->json(['message' => 'Sale updated successfully!', 'sale' => $sale], 200);
    }
}
