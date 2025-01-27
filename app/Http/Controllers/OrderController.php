<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProductItem;
use App\Models\OrderPayment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderCustomer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Chapa\Chapa\Facades\Chapa;

class OrderController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        // Log the incoming request
        \Log::info('Order checkout initiated', [
            'request_data' => $request->all(),
            'user_id' => auth()->id()
        ]);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
            'billing' => 'required|array',
            'billing.name' => 'required|string',
            'billing.email' => 'required|email',
            'billing.fullAddress' => 'required|string',
            'billing.phoneNumber' => 'required|string',
            'billing.company' => 'nullable|string',
            'billing.addressType' => 'nullable|string',
            'shipping' => 'required|array',
            'shipping.address' => 'required|string',
            'shipping.method.value' => 'required|numeric',
            'payment' => 'required|array',
            'payment.method' => 'required|string',
            'payment.amount' => 'required|numeric',
            'payment.currency' => 'required|string',
            'payment.tx_ref' => 'nullable|string',
            'status' => 'required|string|in:pending,completed,cancelled,refunded',
            'total' => 'required|numeric',
            'subtotal' => 'required|numeric',
        ]);

        \Log::info('Validation passed', ['validated_data' => $validated]);

        DB::beginTransaction();
        try {
            // Log calculation details
            $totalQuantity = collect($validated['items'])->sum('quantity');
            $taxes = collect($validated['items'])->sum(function ($item) {
                return ($item['price'] * $item['quantity']) * 0.15;
            });

            \Log::info('Order calculations', [
                'total_quantity' => $totalQuantity,
                'taxes' => $taxes,
                'shipping_cost' => $validated['shipping']['method']['value']
            ]);

            $order = Order::create([
                'user_id' => auth()->id(),
                'taxes' => $taxes,
                'status' => $validated['status'],
                'shipping' => $validated['shipping']['method']['value'],
                'discount' => $validated['discount'] ?? 0,
                'subtotal' => collect($validated['items'])->sum(function ($item) {
                    return $item['price'] * $item['quantity'];
                }),
                'order_number' => strtoupper(uniqid('ORD-')),
                'total_amount' => collect($validated['items'])->sum(function ($item) {
                    return ($item['price'] * $item['quantity']) * 1.15;
                }) + $validated['shipping']['method']['value'],
                'total_quantity' => $totalQuantity
            ]);

            \Log::info('Order created', ['order' => $order->toArray()]);

            // Log order items creation
            foreach ($validated['items'] as $item) {
                $orderProductItem = new OrderProductItem([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
                $order->items()->save($orderProductItem);
                \Log::info('Order item created', ['item' => $orderProductItem->toArray()]);
            }

            // Log customer information
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

            \Log::info('Customer information saved', ['customer' => $customer->toArray()]);

            // Payment processing
            $paymentData = [
                'payment_method' => $validated['payment']['method'],
                'amount' => $validated['payment']['amount'],
                'currency' => $validated['payment']['currency'],
                'status' => $validated['payment']['status'] ?? 'pending'
            ];

            if ($validated['payment']['method'] === 'chapa') {
                \Log::info('Initiating Chapa payment');
                $paymentData['tx_ref'] = $validated['payment']['tx_ref'];

                $chapaResponse = Chapa::initializePayment([
                    'amount' => $validated['payment']['amount'],
                    'first_name' => explode(' ', $validated['billing']['name'])[0],
                    'last_name' => explode(' ', $validated['billing']['name'])[1] ?? '',
                    'phone_number' => str_replace(' ', '', $validated['billing']['phoneNumber']),
                    'currency' => $validated['payment']['currency'],
                    'email' => $validated['billing']['email'],
                    'tx_ref' => $validated['payment']['tx_ref'],
                    'callback_url' => route('chapa.callback'),
                    'return_url' => route('chapa.return'),
                    'customization' => [
                        'title' => 'Order Payment',
                        'description' => 'Payment for order ' . $order->order_number,
                    ],
                ]);

                \Log::info('Chapa payment response', ['response' => $chapaResponse]);

                if ($chapaResponse['status'] !== 'success') {
                    \Log::error('Chapa payment initialization failed', ['response' => $chapaResponse]);
                    throw new \Exception('Payment initialization failed');
                }

                $paymentData['status'] = 'initiated';
            }

            $payment = $order->payments()->create($paymentData);
            \Log::info('Payment record created', ['payment' => $payment->toArray()]);

            // Create invoice
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'sent' => 0,
                'taxes' => $taxes,
                'status' => 'pending',
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'shipping' => $order->shipping,
                'total_amount' => $order->total_amount,
                'invoice_number' => strtoupper(uniqid('INV-')),
                'create_date' => now(),
                'due_date' => now()->addDays(30),
            ]);

            \Log::info('Invoice created', ['invoice' => $invoice->toArray()]);

            // Create invoice items
            foreach ($validated['items'] as $item) {
                $invoiceItem = new InvoiceItem([
                    'invoice_id' => $invoice->id,
                    'title' => 'Product ' . $item['id'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                    'service' => 'product',
                    'quantity' => $item['quantity'],
                    'description' => 'Product description',
                ]);
                $invoice->items()->save($invoiceItem);
                \Log::info('Invoice item created', ['item' => $invoiceItem->toArray()]);
            }

            DB::commit();
            \Log::info('Order process completed successfully', ['order_id' => $order->id]);
            if($validated['payment']['method'] === 'chapa') {

                return response()->json([
                    'order' => $order,
                    'checkout_url' => $chapaResponse['data']['checkout_url']
                ], 201);
            } else {
                return response()->json([
                    'order' => $order,
                ], 201);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['message' => 'Error processing order', 'error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        \Log::info('Chapa webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all()
        ]);

        try {
            $signature = strtolower($request->header('X-Chapa-Signature'));
            $payload = $request->getContent();
            $secret = config('chapa.webhookSecret');

            \Log::info('Verifying webhook signature', [
                'received_signature' => $signature,
                'payload_length' => strlen($payload),
                'payload' => $payload,
                'secret' => $secret
            ]);

            $calculatedSignature = strtolower(hash_hmac('sha256', $payload, $secret));

            \Log::info('Webhook Verification Debug', [
                'secret' => $secret,
                'payload' => $payload,
                'received_signature' => $signature,
                'calculated_signature' => $calculatedSignature
            ]);

            if (!hash_equals($signature, $calculatedSignature)) {
                \Log::error('Invalid webhook signature', [
                    'received_signature' => $signature,
                    'calculated_signature' => $calculatedSignature
                ]);

                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
            }

            $txnRef = $request->input('tx_ref');
            $status = $request->input('status');

            \Log::info('Processing webhook payment update', [
                'tx_ref' => $txnRef,
                'status' => $status
            ]);

            $payment = OrderPayment::where('tx_ref', $txnRef)->firstOrFail();

            \Log::info('Found payment record', [
                'payment_id' => $payment->id,
                'current_status' => $payment->status,
                'new_status' => $status
            ]);

            if ($status === 'success') {
                $payment->status = 'completed';
                \Log::info('Payment marked as completed', ['payment_id' => $payment->id]);
            } else {
                $payment->status = 'failed';
                \Log::warning('Payment marked as failed', [
                    'payment_id' => $payment->id,
                    'failure_reason' => $request->input('failure_reason')
                ]);
            }

            $payment->save();
            \Log::info('Payment status updated successfully', [
                'payment_id' => $payment->id,
                'final_status' => $payment->status
            ]);

            return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully']);
        } catch (\Exception $e) {
            \Log::error('Webhook processing failed', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Error processing webhook', 'error' => $e->getMessage()], 500);
        }
    }
}
