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
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\SalesPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\FuncCall;

class SaleController extends Controller
{
    public function listSale(){
        return view('sell.sale');
    }
    public function addSale(){
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

            DB::beginTransaction();

            try {
                // Create the Sale record
                $sale = Sale::create([
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

                // Loop through products to create SalesProduct records and update stock
                foreach ($validated['products'] as $product) {
                    $remainingQuantity = $product['quantity'];

                    if ($product['batch_id'] === 'all') {
                        // Reduce stock quantity based on FIFO method
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

                            // Add a new row with negative quantity in the Stock table
                            Stock::create([
                                'product_id' => $product['product_id'],
                                'location_id' => $validated['location_id'],
                                'batch_id' => $batch->id,
                                'quantity' => -$deduct,
                                'stock_type' => 'Sale',
                            ]);

                            // Create SalesProduct record
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
                    } else {
                        // Reduce stock quantity based on specific batch_id
                        $batch = Batch::findOrFail($product['batch_id']);

                        if ($batch->quantity < $remainingQuantity) {
                            throw new \Exception('Insufficient batch stock for product ID: ' . $product['product_id']);
                        }

                        // Deduct quantity from batch
                        $batch->quantity -= $remainingQuantity;
                        $batch->save();

                        // Add a new row with negative quantity in the Stock table
                        Stock::create([
                            'product_id' => $product['product_id'],
                            'location_id' => $validated['location_id'],
                            'batch_id' => $batch->id,
                            'quantity' => -$remainingQuantity,
                            'stock_type' => 'Sale',
                        ]);

                        // Create SalesProduct record
                        SalesProduct::create([
                            'sale_id' => $sale->id,
                            'product_id' => $product['product_id'],
                            'batch_id' => $batch->id,
                            'location_id' => $validated['location_id'],
                            'quantity' => $remainingQuantity,
                            'unit_price' => $product['unit_price'],
                            'discount' => $product['discount'] ?? 0,
                            'tax' => $product['tax'] ?? 0,
                        ]);
                    }
                }

                // Calculate the total amount
                $totalAmount = array_sum(array_map(function($product) {
                    return $product['quantity'] * $product['unit_price'];
                }, $validated['products']));

                // Create SalesPayment record
                SalesPayment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $validated['customer_id'],
                    'payment_method' => $validated['payment_method'],
                    'payment_account' => $validated['payment_account'],
                    'amount' => $totalAmount,
                    'payment_date' => now(),
                    'payment_note' => $validated['payment_note'],
                ]);

                DB::commit();

                return response()->json(['message' => 'Sale added successfully!', 'sale' => $sale], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error occurred: ' . $e->getMessage());
                return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
            }
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

            $totalAmount = array_sum(array_map(function($product) {
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
