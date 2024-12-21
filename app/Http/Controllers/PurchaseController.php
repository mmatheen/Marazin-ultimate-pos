<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\Product;
use App\Models\PaymentInfo;  // Ensure this is included at the top of your controller

use App\Models\PurchaseProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function listPurchase(){
        return view('purchase.list_purchase');
    }

    public function AddPurchase(){
        return view('purchase.add_purchase');
    }

    public function store(Request $request)
    {
        // Validate incoming data
        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'location_id' => 'required|exists:locations,id',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_amount' => 'nullable|numeric',
            'payment_method' => 'required|string',
            'payment_note' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer',
            'products.*.price' => 'required|numeric',
            'products.*.total' => 'required|numeric',
        ]);

        // Generate reference number
        $referenceNo = 'PUR-' . strtoupper(Str::random(8));

        // Calculate total and final total
        $total = array_sum(array_column($validated['products'], 'total'));
        $finalTotal = $total - ($validated['discount_amount'] ?? 0);

        // If you want to parse the date in case it's not in the expected format
    try {
        // In case the date is in 'd-m-Y', convert it to 'Y-m-d'
        $purchaseDate = \Carbon\Carbon::createFromFormat('Y-m-d', $validated['purchase_date'])->format('Y-m-d');
    } catch (\Exception $e) {
        // Log error if parsing fails
        \Log::error('Date parsing failed: ' . $e->getMessage());
        return response()->json(['error' => 'Invalid date format.'], 400);
    }

        // Create the purchase record
        $purchase = Purchase::create([
            'reference_no' => $referenceNo,
            'purchase_date' => $purchaseDate,
            'supplier_id' => $validated['supplier_id'],
            'location_id' => $validated['location_id'],
            'discount_type' => $validated['discount_type'] ?? null,
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'total' => $total,
            'final_total' => $finalTotal,
            'payment_status' => 'Due', // Set default payment status
        ]);

        // Store the purchase products
        foreach ($validated['products'] as $product) {
            PurchaseProduct::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product['product_id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'total' => $product['total'],
            ]);
        }

        // Ensure that you are passing `sell_detail_id` if it's part of your data
        if ($request->has('sell_detail_id')) {
            // Store the payment information if available
            PaymentInfo::create([
                'purchase_id' => $purchase->id,
                'sell_detail_id' => $request->sell_detail_id,  // Pass sell_detail_id here
                'payment_method' => $validated['payment_method'],
                'amount' => $finalTotal, // Set payment amount to the final total
                'payment_note' => $validated['payment_note'],
                'payment_status' => 'Pending',  // Set default payment status
            ]);
        }

        // Return a response
        return response()->json(['message' => 'Purchase added successfully!', 'purchase' => $purchase], 201);
    }



    public function getAllPurchaseProduct()
    {
        // Fetch all purchases with related products and payment info
        $purchases = Purchase::with(['purchaseProducts', 'paymentInfo'])->get();

        // Check if purchases are found
        if ($purchases->isEmpty()) {
            return response()->json(['message' => 'No purchases found.'], 404);
        }

        // Return the purchases along with related purchase products and payment info
        return response()->json(['purchases' => $purchases], 200);
    }

}
