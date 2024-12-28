<?php

namespace App\Http\Controllers;

use App\Models\SellDetail;
use App\Models\ProductOrder;
use App\Models\OpeningStock;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Stock;
use App\Models\Batch;
use App\Models\PaymentInfo;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellController extends Controller
{
    // public function store(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'invoice_no' => 'required|string|max:255',
    //             'customer_id' => 'required|exists:customers,id',
    //             'items' => 'required|array|min:1',
    //             'items.*.product_id' => 'required|exists:products,id',
    //             'items.*.quantity' => 'required|integer|min:1',
    //             'items.*.unit_price' => 'required|numeric|min:0',
    //             'items.*.subtotal' => 'required|numeric|min:0',
    //             'items.*.location_id' => 'required|exists:locations,id',
    //             'amount' => 'required|numeric|min:0',
    //         ]);

    //         // Create the SellDetail record
    //         $sellDetail = SellDetail::create([
    //             'invoice_no' => $request->invoice_no,
    //             'cust_id' => $request->customer_id,
    //             'added_date' => now(),
    //             'added_by' => auth()->user()->id,
    //         ]);

    //         // Loop through items to create ProductOrder records and update stock
    //         foreach ($request->items as $order) {
    //             ProductOrder::create([
    //                 'sell_detail_id' => $sellDetail->id,
    //                 'product_id' => $order['product_id'],
    //                 'quantity' => $order['quantity'],
    //                 'unit_price' => $order['unit_price'],
    //                 'discount' => $order['discount'] ?? 0,
    //                 'subtotal' => $order['subtotal'],
    //                 'location_id' => $order['location_id'],
    //             ]);

    //             // Reduce stock quantity in OpeningStock
    //             $openingStock = OpeningStock::where('product_id', $order['product_id'])
    //                 ->where('location_id', $order['location_id'])
    //                 ->first();

    //             if ($openingStock) {
    //                 if ($openingStock->quantity < $order['quantity']) {
    //                     throw new \Exception('Insufficient stock for product ID: ' . $order['product_id'] . ' at location ID: ' . $order['location_id']);
    //                 }

    //                 $openingStock->quantity -= $order['quantity'];
    //                 $openingStock->save();
    //             } else {
    //                 throw new \Exception('No stock found for product ID: ' . $order['product_id'] . ' at location ID: ' . $order['location_id']);
    //             }
    //         }

    //         // Create PaymentInfo record
    //         PaymentInfo::create([
    //             'sell_detail_id' => $sellDetail->id,
    //             'payment_date' => now(),
    //             'reference_num' => $request->payment_reference,
    //             'amount' => $request->amount,
    //             'payment_mode' => $request->payment_mode,
    //             'payment_status' => $request->payment_status,
    //         ]);

    //         // Fetch the customer and validate it exists
    //         $customer = Customer::find($request->customer_id);
    //         if (!$customer) {
    //             throw new \Exception('Customer not found with ID: ' . $request->customer_id);
    //         }



    //         // Fetch product order details
    //         $invoiceItems = ProductOrder::with('product')->where('sell_detail_id', $sellDetail->id)->get();
    //         // Concatenate the first name and last name in the controller
    //         $customerName = $customer->first_name . ' ' . $customer->last_name;



    //         // Generate invoice HTML
    //         $html = view('sell.invoice1', [
    //             'invoice' => $sellDetail,
    //             'customer' => $customer,
    //             'items' => $invoiceItems,
    //             'amount' => $request->amount,
    //             'payment_mode' => $request->payment_mode,
    //             'payment_status' => $request->payment_status,
    //             'payment_reference' => $request->payment_reference,
    //             'payment_date' => now(),
    //         ])->render();

    //         // Return JSON response
    //         return response()->json([
    //             'message' => 'Invoice created successfully',
    //             'html' => $html,
    //             'invoice_no' => $sellDetail->invoice_no,
    //             'invoice_date' => $sellDetail->created_at,
    //             'amount' => $request->amount,
    //             'customer_name' => $customer->customerName,
    //             'payment_details' => [
    //                 'payment_mode' => $request->payment_mode,
    //                 'payment_status' => $request->payment_status,

    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Error occurred: ' . $e->getMessage());
    //         return response()->json([
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

//     public function store(Request $request)
// {
//     try {
//         $request->validate([
//             'invoice_no' => 'required|string|max:255',
//             'customer_id' => 'required|exists:customers,id',
//             'items' => 'required|array|min:1',
//             'items.*.product_id' => 'required|exists:products,id',
//             'items.*.quantity' => 'required|integer|min:1',
//             'items.*.unit_price' => 'required|numeric|min:0',
//             'items.*.subtotal' => 'required|numeric|min:0',
//             'items.*.location_id' => 'required|exists:locations,id',
//             'amount' => 'required|numeric|min:0',
//         ]);

//         // Create the SellDetail record
//         $sellDetail = SellDetail::create([
//             'invoice_no' => $request->invoice_no,
//             'cust_id' => $request->customer_id,
//             'added_date' => now(),
//             'added_by' => auth()->user()->id,
//         ]);

//         // Loop through items to create ProductOrder records and update stock
//         foreach ($request->items as $order) {

//             // Validate location_id exists
//             if (!Location::where('id', $order['location_id'])->exists()) {
//                 throw new \Exception('Invalid location ID: ' . $order['location_id']);
//             }
//             ProductOrder::create([
//                 'sell_detail_id' => $sellDetail->id,
//                 'product_id' => $order['product_id'],
//                 'quantity' => $order['quantity'],
//                 'unit_price' => $order['unit_price'],
//                 'discount' => $order['discount'] ?? 0,
//                 'subtotal' => $order['subtotal'],
//                 'location_id' => $order['location_id'],
//             ]);

//             $remainingQuantity = $order['quantity'];

//             // Reduce stock quantity in OpeningStock first
//             $openingStock = OpeningStock::where('product_id', $order['product_id'])
//                 ->where('location_id', $order['location_id'])
//                 ->first();

//             if ($openingStock) {
//                 $deduct = min($openingStock->quantity, $remainingQuantity);
//                 $openingStock->quantity -= $deduct;
//                 $remainingQuantity -= $deduct;
//                 $openingStock->save();
//             }

//             // Reduce from purchased stock if there's still remaining quantity
//         // Reduce from purchased stock if there's still remaining quantity
// if ($remainingQuantity > 0) {
//     // Fetch all related purchase products for the given product and location
//     $purchaseProducts = PurchaseProduct::whereHas('purchase', function ($query) use ($order) {
//         $query->where('location_id', $order['location_id']);
//     })
//     ->where('product_id', $order['product_id'])
//     ->where('quantity', '>', 0) // Only consider stocks with available quantity
//     ->orderBy('id') // FIFO method
//     ->get();

//     foreach ($purchaseProducts as $purchaseProduct) {
//         if ($remainingQuantity <= 0) {
//             break; // Stop once the required quantity is fulfilled
//         }

//         // Deduct from current purchase product stock
//         $deduct = min($purchaseProduct->quantity, $remainingQuantity);
//         $purchaseProduct->quantity -= $deduct;
//         $remainingQuantity -= $deduct;
//         $purchaseProduct->save();
//     }

//     // If there is still remaining quantity, it means insufficient stock
//     if ($remainingQuantity > 0) {
//         throw new \Exception('Insufficient purchased stock for product ID: ' . $order['product_id'] . ' at location ID: ' . $order['location_id']);
//     }
// }

//         }

//         // Create PaymentInfo record
//         PaymentInfo::create([
//             'sell_detail_id' => $sellDetail->id,
//             'payment_date' => now(),
//             'reference_num' => $request->payment_reference,
//             'amount' => $request->amount,
//             'payment_mode' => $request->payment_mode,
//             'payment_status' => $request->payment_status,
//         ]);

//         // Fetch customer and invoice details
//         $customer = Customer::findOrFail($request->customer_id);
//         $invoiceItems = ProductOrder::with('product')->where('sell_detail_id', $sellDetail->id)->get();
//         $customerName = $customer->first_name . ' ' . $customer->last_name;

//         // Generate invoice HTML
//         $html = view('sell.invoice1', [
//             'invoice' => $sellDetail,
//             'customer' => $customer,
//             'items' => $invoiceItems,
//             'amount' => $request->amount,
//             'payment_mode' => $request->payment_mode,
//             'payment_status' => $request->payment_status,
//             'payment_reference' => $request->payment_reference,
//             'payment_date' => now(),
//         ])->render();

//         // Return JSON response
//         return response()->json([
//             'message' => 'Invoice created successfully',
//             'html' => $html,
//             'invoice_no' => $sellDetail->invoice_no,
//             'invoice_date' => $sellDetail->created_at,
//             'amount' => $request->amount,
//             'customer_name' => $customerName,
//             'payment_details' => [
//                 'payment_mode' => $request->payment_mode,
//                 'payment_status' => $request->payment_status,
//             ],
//         ]);
//     } catch (\Exception $e) {
//         \Log::error('Error occurred: ' . $e->getMessage());
//         return response()->json([
//             'message' => 'Something went wrong',
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// }

public function store(Request $request)
{
    try {
        // Validation rules
        $request->validate([
            'invoice_no' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'items.*.location_id' => 'required|exists:locations,id',
            'amount' => 'required|numeric|min:0',
        ]);

        // Create the SellDetail record
        $sellDetail = SellDetail::create([
            'invoice_no' => $request->invoice_no,
            'cust_id' => $request->customer_id,
            'added_date' => now(),
            'added_by' => auth()->user()->id,
        ]);

        // Loop through items to create ProductOrder records and update stock
        foreach ($request->items as $order) {
            // Validate location_id exists
            if (!Location::where('id', $order['location_id'])->exists()) {
                throw new \Exception('Invalid location ID: ' . $order['location_id']);
            }

            ProductOrder::create([
                'sell_detail_id' => $sellDetail->id,
                'product_id' => $order['product_id'],
                'quantity' => $order['quantity'],
                'unit_price' => $order['unit_price'],
                'discount' => $order['discount'] ?? 0,
                'subtotal' => $order['subtotal'],
                'location_id' => $order['location_id'],
            ]);

            $remainingQuantity = $order['quantity'];

            // Reduce stock quantity batch-wise if required
            if ($remainingQuantity > 0) {
                // Fetch all related batches for the given product and location
                $batches = Batch::where('product_id', $order['product_id'])
                    ->where('quantity', '>', 0) // Only consider batches with available quantity
                    ->orderBy('batch_id') // FIFO method
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingQuantity <= 0) {
                        break; // Stop once the required quantity is fulfilled
                    }

                    // Deduct from current batch stock
                    $deduct = min($batch->quantity, $remainingQuantity);
                    $batch->quantity -= $deduct;
                    $remainingQuantity -= $deduct;
                    $batch->save();

                    // Update the Stock table
                    Stock::updateOrCreate(
                        [
                            'product_id' => $order['product_id'],
                            'location_id' => $order['location_id'],
                            'batch_id' => $batch->id,
                        ],
                        [
                            'quantity' => $batch->quantity,
                            'stock_type' => 'sold',
                        ]
                    );
                }

                // If there is still remaining quantity, it means insufficient batch stock
                if ($remainingQuantity > 0) {
                    throw new \Exception('Insufficient batch stock for product ID: ' . $order['product_id'] . ' at location ID: ' . $order['location_id']);
                }
            }
        }

        // Create PaymentInfo record
        PaymentInfo::create([
            'sell_detail_id' => $sellDetail->id,
            'payment_date' => now(),
            'reference_num' => $request->payment_reference,
            'amount' => $request->amount,
            'payment_mode' => $request->payment_mode,
            'payment_status' => $request->payment_status,
        ]);

        // Fetch customer and invoice details
        $customer = Customer::findOrFail($request->customer_id);
        $invoiceItems = ProductOrder::with('product')->where('sell_detail_id', $sellDetail->id)->get();
        $customerName = $customer->first_name . ' ' . $customer->last_name;

        // Generate invoice HTML
        $html = view('sell.invoice1', [
            'invoice' => $sellDetail,
            'customer' => $customer,
            'items' => $invoiceItems,
            'amount' => $request->amount,
            'payment_mode' => $request->payment_mode,
            'payment_status' => $request->payment_status,
            'payment_reference' => $request->payment_reference,
            'payment_date' => now(),
        ])->render();

        // Return JSON response
        return response()->json([
            'message' => 'Invoice created successfully',
            'html' => $html,
            'invoice_no' => $sellDetail->invoice_no,
            'invoice_date' => $sellDetail->created_at,
            'amount' => $request->amount,
            'customer_name' => $customerName,
            'payment_details' => [
                'payment_mode' => $request->payment_mode,
                'payment_status' => $request->payment_status,
            ],
        ]);
    } catch (\Exception $e) {
        \Log::error('Error occurred: ' . $e->getMessage());
        return response()->json([
            'message' => 'Something went wrong',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    public function getAllPosDetails()
{
    // Get the currently authenticated user
    $user = Auth::user();

    // Initialize the $getValue variable for products
    if ($user->location_id !== null) {
        // Filter products by the user's location or products with null locations
        $locationId = $user->location_id;

        $getValue = Product::whereHas('locations', function ($query) use ($locationId) {
            $query->where('locations.id', $locationId);
        })
        ->orWhereDoesntHave('locations') // Include products with null locations
        ->with('locations')
        ->get();
    } else {
        // Fetch all products, including those with null locations, if the user has no location_id
        $getValue = Product::with('locations')->get();
    }

    // Fetch the sell details
    $sellDetails = SellDetail::with([
        'customer',
        'productOrders.product',
        'productOrders.location',
        'paymentInfo',
    ])->get();

    // Check if any sell details were found
    if ($sellDetails->count() > 0) {
        return response()->json([
            'status' => 200,
            'message' => 'Records found',
            'sell_details' => $sellDetails // Return the sell details as 'sell_details'
        ]);
    } else {
        return response()->json([
            'status' => 404,
            'message' => "No Records Found!"
        ]);
    }
}



    }



