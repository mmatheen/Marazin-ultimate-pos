<?php

namespace App\Http\Controllers;

use App\Models\OpeningStock;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnProduct;
use App\Models\PurchaseProduct;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PurchaseReturnController extends Controller
{
    public function purchaseReturn()
    {
        return view('purchase.purchase_return');
    }

    public function addPurchaseReturn()
    {
        return view('purchase.add_purchase_return');
    }

    public function store(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'location_id' => 'required|exists:locations,id',
                'reference_no' => 'nullable|string|max:255',
                'return_date' => 'required|date',
                'attach_document' => 'nullable|mimes:jpeg,png,jpg,gif,pdf|max:5120',
                'products' => 'required|array',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.unit_price' => 'required|numeric|min:0',
                'products.*.subtotal' => 'required|numeric|min:0',
            ]);

            // File upload
            $fileName = $request->hasFile('attach_document') ? time() . '.' . $request->file('attach_document')->extension() : null;
            if ($fileName) {
                $request->file('attach_document')->move(public_path('/assets/documents'), $fileName);
            }

            // Generate a reference number if not provided
            $referenceNo = $validated['reference_no'] ?? 'PR-' . strtoupper(uniqid());

            // Format the return date
            $returnDate = Carbon::parse($validated['return_date'])->format('Y-m-d');

            // Save the purchase return
            $purchaseReturn = PurchaseReturn::create([
                'supplier_id' => $validated['supplier_id'],
                'reference_no' => $referenceNo,
                'location_id' => $validated['location_id'],
                'return_date' => $returnDate,
                'attach_document' => $fileName,
            ]);

            // Save purchase return products and update stock quantities
            foreach ($validated['products'] as $product) {
                // Check if the product exists in the purchase_product and opening_stock tables
                $purchaseProduct = PurchaseProduct::where('product_id', $product['product_id'])->first();
                $openingStockProduct = OpeningStock::where('product_id', $product['product_id'])->first();

                // Handle case where the product is not found in either table
                if (!$purchaseProduct && !$openingStockProduct) {
                    return response()->json([
                        'error' => 'Product not found in purchase or opening stock.',
                    ], 400);
                }

                // Create purchase return product
                PurchaseReturnProduct::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'subtotal' => $product['subtotal'],
                ]);

                // Reduce the quantity of the product in the purchase or opening stock
                $remainingQuantityToReduce = $product['quantity'];

                if ($purchaseProduct && $purchaseProduct->quantity >= $remainingQuantityToReduce) {
                    // Reduce from purchase product only
                    $purchaseProduct->quantity -= $remainingQuantityToReduce;
                    $purchaseProduct->save();
                } elseif ($openingStockProduct && $openingStockProduct->quantity >= $remainingQuantityToReduce) {
                    // Reduce from opening stock only
                    $openingStockProduct->quantity -= $remainingQuantityToReduce;
                    $openingStockProduct->save();
                } else {
                    // Reduce from both purchase product and opening stock
                    if ($purchaseProduct) {
                        $remainingQuantityToReduce -= $purchaseProduct->quantity;
                        $purchaseProduct->quantity = 0;
                        $purchaseProduct->save();
                    }

                    if ($openingStockProduct->quantity >= $remainingQuantityToReduce) {
                        $openingStockProduct->quantity -= $remainingQuantityToReduce;
                        $openingStockProduct->save();
                    } else {
                        return response()->json([
                            'error' => 'Insufficient stock in opening stock.',
                        ], 400);
                    }
                }
            }

            return response()->json(['message' => 'Purchase return saved successfully!', 'reference_no' => $referenceNo], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors for debugging
            return response()->json([
                'error' => 'Validation failed.',
                'messages' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            // Log any other errors
            return response()->json(['error' => 'Failed to save purchase return. Please try again.'], 500);
        }
    }

public function getAllPurchaseReturns()
{
    // Fetch all purchases with related products and payment info
    $purchasesReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product'])
    ->get();

    // Check if purchases are found
    if ($purchasesReturn->isEmpty()) {
        return response()->json(['message' => 'No purchases found.'], 404);
    }

    // Return the purchases along with related purchase products and payment info
    return response()->json(['purchases_Return' => $purchasesReturn], 200);
}


    public function getPurchaseReturns($id)
    {
        $purchaseReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product'])->findOrFail($id);

        return response()->json(['purchase_return' => $purchaseReturn], 200);
    }


public function edit($id)
{
    // Fetch the purchase return data using the ID
    $purchaseReturn = PurchaseReturn::with(['supplier', 'location', 'purchaseReturnProducts.product'])->findOrFail($id);

    if (request()->ajax() || request()->is('api/*')) {
        return response()->json(['purchase_return' => $purchaseReturn], 200);
    }
    // Return the view with the purchase return data
    return view('purchase.add_purchase_return', compact('purchaseReturn'));
}


// public function update(Request $request, $id)
// {
//     // Validate the request
//     $request->validate([
//         'reference_no' => 'required|string',
//         'supplier_name' => 'nullable|string',
//         'location_name' => 'required|string',
//         'return_date' => 'required|date',
//         'document' => 'nullable|file|mimes:png,jpg,pdf|max:2048',
//         'products' => 'required|array',
//         'products.*.product_id' => 'required|integer|exists:products,id',
//         'products.*.quantity' => 'required|numeric|min:1',
//         'products.*.unit_price' => 'required|numeric|min:0',
//     ]);

//     // Find the purchase return
//     $purchaseReturn = PurchaseReturn::findOrFail($id);

//     // Update basic fields
//     $purchaseReturn->reference_no = $request->input('reference_no');
//     $purchaseReturn->supplier_name = $request->input('supplier_name');
//     $purchaseReturn->location_name = $request->input('location_name');
//     $purchaseReturn->return_date = $request->input('return_date');

//     // Update the document if uploaded
//     if ($request->hasFile('document')) {
//         $document = $request->file('document');
//         $documentPath = $document->store('documents', 'public');
//         $purchaseReturn->document = $documentPath;
//     }

//     $purchaseReturn->save();

//     // Update products
//     $purchaseReturn->products()->delete(); // Clear old products
//     foreach ($request->input('products') as $product) {
//         $purchaseReturn->products()->create([
//             'product_id' => $product['product_id'],
//             'quantity' => $product['quantity'],
//             'unit_price' => $product['unit_price'],
//             'subtotal' => $product['quantity'] * $product['unit_price'],
//         ]);
//     }

//     return response()->json([
//         'message' => 'Purchase return updated successfully!',
//         'data' => $purchaseReturn->load('products'),
//     ]);
// }



}
