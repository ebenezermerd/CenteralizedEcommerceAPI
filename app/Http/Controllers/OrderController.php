<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping' => 'required|numeric',
            'discount' => 'nullable|numeric',
        ]);

        $cart = Cart::with('items.product')->where('user_id', auth()->id())->firstOrFail();

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => auth()->id(),
                'shipping' => $validated['shipping'],
                'discount' => $validated['discount'] ?? 0,
                'subtotal' => $cart->subtotal,
                'taxes' => $cart->tax,
                'total_amount' => $cart->total,
                'total_quantity' => $cart->items->sum('quantity'),
                'status' => 'pending',
                'order_number' => strtoupper(uniqid('ORD-'))
            ]);

            foreach ($cart->items as $cartItem) {
                $orderItem = new OrderItem([
                    'product_id' => $cartItem->product_id,
                    'price' => $cartItem->price,
                    'quantity' => $cartItem->quantity,
                ]);
                $orderItem->calculateSubtotal();
                $order->items()->save($orderItem);
            }

            $order->calculateTotals();
            $cart->items()->delete();
            $cart->delete();

            DB::commit();
            return response()->json($order, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error processing order', 'error' => $e->getMessage()], 500);
        }
    }

    public function uploadPaymentProof(Request $request, $orderId): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'proof_of_payment' => 'required|file|mimes:jpg,png,pdf|max:2048',
        ]);

        $order = Order::findOrFail($orderId);

        $path = $request->file('proof_of_payment')->store('payments', 'public');

        $payment = new OrderPayment([
            'order_id' => $order->id,
            'payment_method' => $validated['payment_method'],
            'proof_of_payment' => $path,
            'status' => 'pending'
        ]);

        $order->payments()->save($payment);

        return response()->json(['message' => 'Payment proof uploaded successfully']);
    }
}