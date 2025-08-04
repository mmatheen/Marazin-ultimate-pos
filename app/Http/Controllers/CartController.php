<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Darryldecode\Cart\Facades\CartFacade as Cart; // Use the proper Cart facade

class CartController extends Controller
{
    public function index()
    {
        return view('cart.index', [
            'cartItems' => Cart::getContent(),
        ]);
    }

    public function add(Request $request)
    {
        $productId = $request->input('product_id');
        $batchId = $request->input('batch_id');
        $quantity = $request->input('quantity', 1);

        $product = \App\Models\Product::findOrFail($productId);

        $batch = \App\Models\Batch::where('id', $batchId)->first();

        if (!$batch || $batch->quantity < $quantity) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient stock in selected batch.'], 400);
        }

        Cart::add([
            'id' => $product->id,
            'name' => $product->product_name,
            'price' => $batch->retail_price,
            'quantity' => $quantity,
            'attributes' => [
                'sku' => $product->sku,
                'description' => $product->description,
                'batch' => $batch->batch_no,
                'discount_type' => $product->discount_type,
                'discount_amount' => $product->discount_amount,
                'batch_id' => $batch->id,
            ],
            'associatedModel' => $product,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Product added to cart successfully.']);
    }

    public function remove($rowId)
    {
        Cart::remove($rowId);

        return response()->json(['status' => 'success', 'message' => 'Product removed from cart.']);
    }

    public function update(Request $request, $rowId)
    {
        $quantity = $request->input('quantity');

        if ($quantity < 1) {
            return response()->json(['status' => 'error', 'message' => 'Quantity must be at least 1.'], 400);
        }

        Cart::update($rowId, [
            'quantity' => $quantity,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Cart updated successfully.']);
    }
}
