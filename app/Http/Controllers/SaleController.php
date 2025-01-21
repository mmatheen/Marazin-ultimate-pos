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
            'customer_id' => 'required|integer|exists:customers,id',
            'location_id' => 'required|integer|exists:locations,id',
            'sales_date' => 'required|date',
            'status' => 'required|string',
            'invoice_no' => 'nullable|string',
            // 'additional_notes' => 'nullable|string',
            // 'shipping_details' => 'nullable|string',
            // 'shipping_address' => 'nullable|string',
            // 'shipping_charges' => 'nullable|numeric',
            // 'shipping_status' => 'nullable|string',
            // 'delivered_to' => 'nullable|string',
            // 'delivery_person' => 'nullable|string',
            // 'attach_document' => 'nullable|file|max:5120|mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.subtotal' => 'required|numeric|min:0',
            'products.*.batch_id' => 'nullable|string|max:255',
            'products.*.price_type' => 'required|string|in:retail,wholesale,special',
            'products.*.discount' => 'nullable|numeric|min:0',
            'products.*.tax' => 'nullable|numeric|min:0',

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
                // 'additional_notes' => $request->additional_notes,
                // 'shipping_details' => $request->shipping_details,
                // 'shipping_address' => $request->shipping_address,
                // 'shipping_charges' => $request->shipping_charges,
                // 'shipping_status' => $request->shipping_status,
                // 'delivered_to' => $request->delivered_to,
                // 'delivery_person' => $request->delivery_person,
                // 'attach_document' => $attachDocument,
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
        $batchIds = [];

        if (!empty($productData['batch_id']) && $productData['batch_id'] != 'all') {
            // Check if the batch has enough stock
            $batch = Batch::findOrFail($productData['batch_id']);
            $locationBatch = LocationBatch::where('batch_id', $batch->id)
                ->where('location_id', $locationId)
                ->firstOrFail();

            if ($locationBatch->qty < $quantityToDeduct) {
                throw new \Exception("Batch ID {$productData['batch_id']} does not have enough stock.");
            }

            // Deduct stock from a specific batch
            $this->deductBatchStock($productData['batch_id'], $locationId, $quantityToDeduct);
            $batchIds[] = $batch->id;
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
                $this->deductBatchStock($batch->batch_id, $locationId, $deductQuantity);
                $batchIds[] = $batch->batch_id;
                $remainingQuantity -= $deductQuantity;
            }
        }

        // Create sales product records for each batch used in FIFO
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

    /**
     * Deduct stock from a specific batch.
     */
    private function deductBatchStock($batchId, $locationId, $quantity)
    {
        Log::info("Deducting $quantity from batch ID $batchId at location $locationId");

        $batch = Batch::findOrFail($batchId);
        $locationBatch = LocationBatch::where('batch_id', $batch->id)
            ->where('location_id', $locationId)
            ->firstOrFail();

        Log::info("Before deduction: LocationBatch qty: {$locationBatch->qty}, Batch qty: {$batch->qty}");

        // Deduct stock from location batch using query builder
        DB::table('location_batches')
            ->where('id', $locationBatch->id)
            ->update(['qty' => DB::raw("GREATEST(qty - $quantity, 0)")]);

        // Deduct stock from batch using query builder
        DB::table('batches')
            ->where('id', $batch->id)
            ->update(['qty' => DB::raw("GREATEST(qty - $quantity, 0)")]);

        // Reload the updated quantities
        $locationBatch->refresh();
        $batch->refresh();

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



    // public function getSaleByInvoiceNo($invoiceNo)
    // {
    //     $sale = Sale::with('products.product')->where('invoice_no', $invoiceNo)->first();



    //     if (!$sale) {
    //         return response()->json(['error' => 'Sale not found'], 404);
    //     }

    //     return response()->json([
    //         'sale_id' => $sale->id,
    //         'customer_id' => $sale->customer_id,
    //         'location_id' => $sale->location_id,
    //         'products' => $sale->products,
    //         'stock_history' => $sale->stockHistory,

    //     ]);
    // }

    public function getSaleByInvoiceNo($invoiceNo)
    {
        $sale = Sale::with('products.product')->where('invoice_no', $invoiceNo)->first();

        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        // Calculate current quantity for each product
        $products = $sale->products->map(function($product) use ($sale) {
            $currentQuantity = $sale->getCurrentSaleQuantity($product->product_id);
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
}
