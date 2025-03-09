<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderProductItem;
use App\Models\OrderCustomer;
use App\Models\OrderShippingAddress;
use App\Models\OrderHistory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Chapa\Chapa\Facades\Chapa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\EmailVerificationService;
use Illuminate\Support\Str;
use App\Models\Product;

class FlutterChapaController extends Controller
{
    protected $emailVerificationService;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    /**
     * Verify Chapa payment and create order if successful
     */
    public function verifyAndCreateOrder(Request $request)
    {
        Log::info('Flutter Chapa verification request received', ['request_data' => $request->all()]);

        try {
            // Validate the request
            $validated = $request->validate([
                'payment' => 'required|array',
                'payment.amount' => 'required|numeric',
                'payment.tx_ref' => 'nullable|string',

                'status' => 'required|string',
                'amount' => 'required|numeric',
                'currency' => 'required|string',
                // Order data
                'items' => 'required|array',
                'items.*.id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric',
                'items.*.name' => 'required|string',
                'items.*.coverUrl' => 'nullable|string',
                // Billing information
                'billing' => 'required|array',
                'billing.name' => 'required|string',
                'billing.email' => 'required|email',
                'billing.phoneNumber' => 'required|string',
                'billing.fullAddress' => 'required|string',
                'billing.addressType' => 'nullable|string',
                // Shipping information
                'shipping' => 'required|array',
                'shipping.address' => 'required|string',
                'shipping.method.description' => 'nullable|string',
                'shipping.method.label' => 'required|string',
                'shipping.method.value' => 'required|numeric',

                // Additional information
                'discount' => 'nullable|numeric',
                'total' => 'required|numeric',
                'subtotal' => 'required|numeric',
            ]);

            // Verify the transaction with Chapa
            try {
                $verificationData = Chapa::verifyTransaction($validated['payment']['tx_ref']);

                // Check if verification was successful
                if ($verificationData['status'] !== 'success' ||
                    $verificationData['data']['status'] !== 'success') {

                    Log::error('Chapa verification failed', [
                        'tx_ref' => $validated['payment']['tx_ref'],
                        'verification_data' => $verificationData
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment verification failed',
                        'data' => $verificationData
                    ], 400);
                }

                // Verify amount matches
                if ((float)$verificationData['data']['amount'] !== (float)$validated['payment']['amount']) {
                    Log::error('Amount mismatch in Chapa verification', [
                        'expected' => $validated['payment']['amount'],
                        'received' => $verificationData['data']['amount']
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment amount mismatch',
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error('Error verifying Chapa transaction', [
                    'tx_ref' => $validated['payment']['tx_ref'],
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Error verifying payment: ' . $e->getMessage()
                ], 500);
            }

            // If we get here, payment is verified. Create the order.
            DB::beginTransaction();

            $totalQuantity = collect($validated['items'])->sum('quantity');
            $taxes = collect($validated['items'])->sum(function ($item) {
                return ($item['price'] * $item['quantity']) * 0.15;
            });

            // 1. Create the order
            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => strtoupper(uniqid('ORD-')),
                'status' => 'completed', // Since payment is already verified
                'total_amount' => $validated['payment']['amount'],
                'total_quantity' => $totalQuantity,
                'shipping' => $validated['shipping']['method']['value'],
                'subtotal' => collect($validated['items'])->sum(function ($item) {
                    return $item['price'] * $item['quantity'];
                }),
                'taxes' => $taxes,
                'discount' => $validated['discount'] ?? 0,
            ]);

            // 2. Create order payment
            $payment = $order->payment()->create([
                'order_id' => $order->id,
                'payment_method' => 'chapa',
                'amount' => $validated['payment']['amount'],
                'currency' => "ETB",
                'status' => 'completed',
                'tx_ref' => $validated['payment']['tx_ref'],
                'transaction_id' => $validated['payment']['transaction_id'] ?? null,
                'payment_date' => now(),
            ]);

            // 3. Create order items
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['id']);
                $orderItem =  new OrderProductItem([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'sku' => $product->sku ?? null,
                    'price' => $item['price'],
                    'name' => $item['name'],
                    'cover_url' => $item['coverUrl'] ?? null,
                ]);
                $order->items()->save($orderItem);

                // Update product inventory
                $product = Product::find($item['id']);
                if ($product) {
                    $product->quantity = max(0, $product->quantity - $item['quantity']);
                    $product->save();
                }
            }

            // 4. Create customer information
            $customer = $order->customer()->create([
                'order_id' => $order->id,
                'name' => $validated['billing']['name'],
                'email' => $validated['billing']['email'],
                'avatar_url' => null,
                'ip_address' => $request->ip(),
                'full_address' => $validated['billing']['fullAddress'],
                'phone_number' => $validated['billing']['phoneNumber'],
                'company' => $validated['billing']['company'] ?? null,
                'address_type' => $validated['billing']['addressType'] ?? null,
            ]);

            // 5. Create shipping address
            $shipping = $order->shippingAdd()->create([
                'order_id' => $order->id,
                'phone_number' => $validated['billing']['phoneNumber'],
                'full_address' => $validated['shipping']['address'],
            ]);


            // 6. Create delivery information if provided
            if (!empty($validated['delivery'])) {
                $delivery = new \App\Models\OrderDelivery();
                $delivery->order_id = $order->id;
                $delivery->method = $validated['delivery']['method'] ?? 'standard';
                $delivery->fee = $validated['delivery']['fee'] ?? 0;
                $delivery->save();
            }

            // 7. Create order history
            $history = new OrderHistory();
            $history->order_id = $order->id;
            $timeline = [
                [
                    'title' => 'Order Placed',
                    'time' => now()->toISOString()
                ],
                [
                    'title' => 'Payment Completed',
                    'time' => now()->toISOString()
                ]
            ];
            $history->timeline = json_encode($timeline);
            $history->payment_time = now();
            $history->save();

            // 8. Create invoice
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'sent' => 1,
                'taxes' => $taxes ?? 0,
                'status' => 'paid',
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'shipping' => $order->shipping,
                'total_amount' => $order->total_amount,
                'invoice_number' => 'INV-' . strtoupper(bin2hex(random_bytes(8))),
                'create_date' => now(),
                'due_date' => now()->addDays(30),
            ]);


            // 9. Create invoice items
            foreach ($validated['items'] as $item) {
                $invoiceItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'title' => 'Product ' . $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                    'service' => 'product',
                    'description' => $product->subDescription,
                ]);
            }

            DB::commit();

            // Send invoice email
            if ($invoice->user->email && $invoice->user->email_verified_at) {
                $this->emailVerificationService->sendInvoiceEmail($invoice);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified and order created successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_number' => $invoice->invoice_number,
                    'total' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error processing Flutter Chapa payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
