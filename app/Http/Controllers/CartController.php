<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function index(): JsonResponse
    {
        $cart = Cart::with('items.product')->where('user_id', auth()->id())->firstOrFail();
        return response()->json($cart);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);
        $product = Product::findOrFail($validated['product_id']);
        $cartItem = $cart->items()->updateOrCreate(
            ['product_id' => $product->id],
            ['quantity' => $validated['quantity'], 'price' => $product->price]
        );
        $cartItem->calculateSubtotal();
        $cart->calculateTotals();

        return response()->json($cart, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::findOrFail($id);
        $cartItem->update($validated);
        $cartItem->calculateSubtotal();
        $cartItem->cart->calculateTotals();

        return response()->json($cartItem);
    }

    public function destroy(string $id): JsonResponse
    {
        $cartItem = CartItem::findOrFail($id);
        $cartItem->delete();
        $cartItem->cart->calculateTotals();

        return response()->json(['message' => 'Cart item deleted successfully']);
    }
}