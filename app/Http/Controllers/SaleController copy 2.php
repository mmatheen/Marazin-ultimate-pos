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

    public function index()
    {
        $sales = Sale::with('products.product', 'customer', 'location')->get();
        return response()->json(['sales' => $sales], 200);
    }

    public function selesDetails($id)
    {
        $salesDetails = Sale::with('products.product', 'customer', 'location')->findOrFail($id);
        return response()->json(['salesDetails' => $salesDetails], 200);
    }


    // public function edit($id)
    // {
    //     $sale = Sale::with('products.product.batches', 'customer', 'location')->findOrFail($id);

    //     if (request()->ajax() || request()->is('api/*')) {
    //         return response()->json([
    //             'status' => 200,
    //             'sales' => $sale,
    //         ]);
    //     }

    //     return view('sell.add_sale');

    // }

    public function edit($id)
    {
        $sale = Sale::with('products.product.batches', 'customer', 'location')->findOrFail($id);

        // Calculate the current batch quantity plus the sold quantity for each product in the sale
        foreach ($sale->products as $product) {
            $product->batch_quantity_plus_sold = $sale->getBatchQuantityPlusSold($product->batch_id, $sale->location_id, $product->product_id);
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
            // Validate the request
            $request->validate([
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
            ]);

            try {
                $sale = DB::transaction(function () use ($request, $id) {
                    // Determine if we are updating or creating a new sale
                    $sale = $id ? Sale::findOrFail($id) : new Sale();

                    // Generate the reference number if creating a new sale
                    $referenceNo = $id ? $sale->reference_no : $this->generateReferenceNo();

                    // Calculate the final total
                    $finalTotal = array_reduce($request->products, function ($carry, $product) {
                        return $carry + $product['subtotal'];
                    }, 0);

                    // Update or create the sale
                    $sale->fill([
                        'customer_id' => $request->customer_id,
                        'location_id' => $request->location_id,
                        'sales_date' => $request->sales_date,
                        'status' => $request->status,
                        'invoice_no' => $request->invoice_no,
                        'reference_no' => $referenceNo,
                        'final_total' => $finalTotal,
                    ])->save();

                    // Restore stock for existing sale products if updating
                    if ($id) {
                        foreach ($sale->products as $product) {
                            $this->restoreStock($product);
                            $product->delete();
                        }
                    }

                    // Process each sold product
                    foreach ($request->products as $productData) {
                        $availableStock = $sale->getBatchQuantityPlusSold(
                            $productData['batch_id'],
                            $request->location_id,
                            $productData['product_id']
                        );

                        if ($productData['quantity'] > $availableStock) {
                            throw new \Exception("Insufficient stock for Product ID {$productData['product_id']} in Batch ID {$productData['batch_id']}.");
                        }

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

                return response()->json(['message' => $id ? 'Sale updated successfully.' : 'Sale recorded successfully.', 'invoice_html' => $html], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], 400);
            }
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

        /**
         * Restore stock for a specific product.
         */
        private function restoreStock($product)
        {
            Log::info("Restoring stock for product ID {$product->product_id} from batch ID {$product->batch_id} at location {$product->location_id}");

            $locationBatch = LocationBatch::where('batch_id', $product->batch_id)
                ->where('location_id', $product->location_id)
                ->firstOrFail();

            // Restore stock to location batch
            DB::table('location_batches')
                ->where('id', $locationBatch->id)
                ->update(['qty' => DB::raw("qty + {$product->quantity}")]);

            // Restore stock to batch
            DB::table('batches')
                ->where('id', $product->batch_id)
                ->update(['qty' => DB::raw("qty + {$product->quantity}")]);

            Log::info("After restoration: LocationBatch qty: {$locationBatch->qty}, Batch qty: {$product->batch->qty}");
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


    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();

        return response()->json(['status' => 200, 'message' => 'Sale deleted successfully!']);
    }
}
