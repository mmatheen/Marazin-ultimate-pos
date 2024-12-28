<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\Product;
use App\Models\PaymentInfo;  // Ensure this is included at the top of your controller
use App\Models\Batch;
use App\Models\Stock;
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
        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'location_id' => 'required|exists:locations,id',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_amount' => 'nullable|numeric',
            'payment_method' => 'required|string',
            'payment_note' => 'nullable|string',
            'attach_document' => 'nullable|mimes:jpeg,png,jpg,gif,pdf|max:5120',

            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer',
            'products.*.price' => 'required|numeric',
            'products.*.total' => 'required|numeric',
            'products.*.expiry_date' => 'nullable|date',
            'products.*.batch_id' => 'nullable|string|max:255', // Allow batch_id to be nullable
        ]);

        $referenceNo = 'PUR-' . strtoupper(Str::random(8));
        $total = array_sum(array_column($validated['products'], 'total'));
        $finalTotal = $total - ($validated['discount_amount'] ?? 0);

        try {
            $purchaseDate = \Carbon\Carbon::createFromFormat('Y-m-d', $validated['purchase_date'])->format('Y-m-d');
        } catch (\Exception $e) {
            \Log::error('Date parsing failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid date format.'], 400);
        }

        $fileName = $request->hasFile('attach_document') ? time() . '.' . $request->file('attach_document')->extension() : null;
        if ($fileName) {
            $request->file('attach_document')->move(public_path('/assets/documents'), $fileName);
        }

        $purchase = Purchase::create([
            'reference_no' => $referenceNo,
            'purchase_date' => $purchaseDate,
            'supplier_id' => $validated['supplier_id'],
            'location_id' => $validated['location_id'],
            'discount_type' => $validated['discount_type'] ?? null,
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'attached_document' => $fileName,
            'total' => $total,
            'final_total' => $finalTotal,
            'payment_status' => 'Due',
        ]);

        foreach ($validated['products'] as $product) {
            $batchId = $product['batch_id'] ?? null;
            $batch = null;

            if ($batchId) {
                // Check if batch exists
                $batch = Batch::where('batch_id', $batchId)
                              ->where('product_id', $product['product_id'])
                              ->first();

                if ($batch) {
                    // Update existing batch
                    $batch->quantity += $product['quantity'];
                    $batch->price = $product['price'];
                    $batch->expiry_date = $product['expiry_date'] ?? $batch->expiry_date;
                    $batch->save();
                } else {
                    // Create new batch
                    $batch = Batch::create([
                        'batch_id' => $batchId,
                        'product_id' => $product['product_id'],
                        'price' => $product['price'],
                        'quantity' => $product['quantity'],
                        'expiry_date' => $product['expiry_date'] ?? null,
                    ]);
                }
            }

            $purchaseProduct = [
                'purchase_id' => $purchase->id,
                'product_id' => $product['product_id'],
                'location_id' => $validated['location_id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'total' => $product['total'],
            ];

            if ($batch) {
                $purchaseProduct['batch_id'] = $batch->id;
            }

            PurchaseProduct::create($purchaseProduct);

            // Update or Create Stock
            $stock = Stock::firstOrNew([
                'product_id' => $product['product_id'],
                'location_id' => $validated['location_id'],
                'batch_id' => $batch ? $batch->id : null,
                'stock_type' => "Purchase Stock"
            ]);

            $stock->quantity += $product['quantity'];
            $stock->save();
        }

        if ($request->has('sell_detail_id')) {
            PaymentInfo::create([
                'purchase_id' => $purchase->id,
                'sell_detail_id' => $request->sell_detail_id,
                'payment_method' => $validated['payment_method'],
                'amount' => $finalTotal,
                'payment_note' => $validated['payment_note'],
                'payment_status' => 'Pending',
            ]);
        }

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
