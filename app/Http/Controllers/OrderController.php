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
use App\Models\Product;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;
use App\Services\EmailVerificationService;


class OrderController extends Controller
{
    protected $emailVerificationService;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::with([
            'history',
            'payment',
            'shippingAdd',
            'customer',
            'delivery',
            'productItems',
        ])->paginate(10);



        return response()->json([
            'orders' => OrderResource::collection($orders) ?? [],
            'pagination' => [
                'total' => $orders->total() ?? 0,
                'per_page' => $orders->perPage() ?? 10,
                'current_page' => $orders->currentPage() ?? 1,
                'last_page' => $orders->lastPage() ?? 1
            ]
        ], 200);
    }

    //method for updating the status of the order
    public function updateStatus(Request $request, $id): JsonResponse
    {
        \Log::info('Order update status request received', ['id' => $id, 'status' => $request->status]);
        $validated = $request->validate([
            'status' => 'required|string|in:pending,completed,cancelled,refunded',
        ]);

        $order = Order::with([
            'history',
            'payment',
            'shippingAdd',
            'customer',
            'delivery',
            'productItems',
        ])->findOrFail($id);

        if (!$order) {
            \Log::error('Order not found', ['id' => $id]);
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        \Log::info('Order found', ['order' => $order->toArray()]);
        DB::transaction(function () use ($order, $request) {
            // Update order status
            $order->status = $request->status;
            $order->save();

            // Update order history timeline
            if ($order->history) {
                $timeline = json_decode($order->history->timeline, true);

                switch ($request->status) {
                    case 'completed':
                        $timeline[] = [
                            'title' => 'Order Delivered',
                            'time' => now()->toISOString()
                        ];
                        $order->history->update([
                            'timeline' => json_encode($timeline),
                            'completion_time' => now()
                        ]);
                        break;

                    case 'cancelled':
                        $timeline[] = [
                            'title' => 'Order Cancelled',
                            'time' => now()->toISOString()
                        ];
                        $order->history->update([
                            'timeline' => json_encode($timeline)
                        ]);
                        break;

                    case 'refunded':
                        $timeline[] = [
                            'title' => 'Order Refunded',
                            'time' => now()->toISOString()
                        ];
                        $order->history->update([
                            'timeline' => json_encode($timeline)
                        ]);
                        break;
                }
            }
        });



        return response()->json(new OrderResource($order), 200);
    }

    public function show(Request $request, $id): JsonResponse
    {
        \Log::info('Order show request received', ['id' => $id]);
        $order = Order::with([
            'history',
            'payment',
            'shippingAdd',
            'customer',
            'delivery',
            'productItems',
        ])->findOrFail($id);

        if (!$order) {
            \Log::error('Order not found', ['id' => $id]);
            return response()->json([
                'message' => 'Order not found',
                'order' => null
            ], 200);
        }


        return response()->json(new OrderResource($order), 200);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $orders = Order::with([
            'history',
            'payment',
            'shippingAdd',
            'customer',
            'delivery',
            'productItems',
        ])->where('user_id', auth()->id())->get();

        if (!$orders) {
            return response()->json([
                'message' => 'No orders found',
                'orders' => []
            ], 200);
        }

        return response()->json(new OrderResource($orders), 200);
    }


   /**
 * @group Orders
 *
 * Checkout for placing an order.
 *
 * This endpoint allows a user to initiate the checkout process. It requires
 * detailed information about the items being purchased, billing, shipping,
 * and payment details. The user must be authenticated to access this endpoint.
 *
 * @bodyParam items array required The items to be ordered.
 * @bodyParam items.*.id string required The ID of the product.
 * @bodyParam items.*.quantity integer required The quantity of the product.
 * @bodyParam items.*.price float required The price of the product.
 * @bodyParam items.*.name string required The name of the product.
 * @bodyParam items.*.coverUrl string optional The URL of the product cover image.
 * @bodyParam billing array required The billing information.
 * @bodyParam billing.name string required The name of the customer.
 * @bodyParam billing.email string required The email of the customer.
 * @bodyParam billing.fullAddress string required The full billing address.
 * @bodyParam billing.phoneNumber string required The phone number of the customer.
 * @bodyParam billing.company string optional The company name.
 * @bodyParam billing.addressType string optional The type of address.
 * @bodyParam shipping array required The shipping information.
 * @bodyParam shipping.address string required The shipping address.
 * @bodyParam shipping.method.description string optional The description of the shipping method.
 * @bodyParam shipping.method.label string required The label of the shipping method.
 * @bodyParam shipping.method.value float required The cost of the shipping method.
 * @bodyParam payment array required The payment information.
 * @bodyParam payment.method string required The payment method (e.g., 'chapa').
 * @bodyParam payment.amount float required The amount to be paid.
 * @bodyParam payment.currency string required The currency of the payment.
 * @bodyParam payment.tx_ref string optional The transaction reference.
 * @bodyParam status string required The order status (e.g., 'pending', 'completed').
 * @bodyParam total float required The total amount for the order.
 * @bodyParam subtotal float required The subtotal amount for the order.
 *
 * @response 201 {
 *  "order": {
 *      "id": "string",
 *      "user_id": "string",
 *      "taxes": "float",
 *      "status": "string",
 *      "shipping": "float",
 *      "discount": "float",
 *      "subtotal": "float",
 *      "order_number": "string",
 *      "total_amount": "float",
 *      "total_quantity": "int",
 *      "created_at": "string",
 *      "updated_at": "string"
 *  },
 *  "checkout_url": "string|null" // Only present if payment method is 'chapa'
 * }
 *
 * @response 401 {
 *  "message": "Unauthenticated."
 * }
 *
 * @response 422 {
 *  "message": "The given data was invalid.",
 *  "errors": {
 *      "items": ["The items field is required."],
 *      ...
 *  }
 * }
 *
 * @response 500 {
 *  "message": "Error processing order",
 *  "error": "string"
 * }
 */
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
        'items.*.name' => 'required|string',
        'items.*.coverUrl' => 'nullable|string',
        'billing' => 'required|array',
        'billing.name' => 'required|string',
        'billing.email' => 'required|email',
        'billing.fullAddress' => 'required|string',
        'billing.phoneNumber' => 'required|string',
        'billing.company' => 'nullable|string',
        'billing.addressType' => 'nullable|string',
        'shipping' => 'required|array',
        'shipping.address' => 'required|string',
        'shipping.method.description' => 'nullable|string',
        'shipping.method.label' => 'required|string',
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


        if (!$validated) {
            \Log::error('Validation failed', ['errors' => $request->validator->errors()]);
        } else {
            \Log::info('Validation passed', ['validated_data' => $validated]);
        }

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

            // Log order Itmes information
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['id']);
                $orderProductItem = new OrderProductItem([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'name' => $item['name'],
                    'sku' => $product->sku,
                    'cover_url' => $item['coverUrl'],
                ]);
                $order->items()->save($orderProductItem);
                \Log::info('Order item created', ['item' => $orderProductItem->toArray()]);
            }

            // Shipping information
            $shipping = $order->shippingAdd()->create([
                'order_id' => $order->id,
                'phone_number' => $validated['billing']['phoneNumber'],
                'full_address' => $validated['shipping']['address'],
            ]);


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
                    'return_url' => route('chapa.return') . '?tx_ref=' . $validated['payment']['tx_ref'],
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


            $payment = $order->payment()->create($paymentData);
            \Log::info('Payment record created', ['payment' => $payment->toArray()]);

            // OrderDelivery information
            $orderDelivery = $order->delivery()->create([
                'order_id' => $order->id,
                'ship_by' => $validated['shipping']['method']['label'],
                'speedy' => $validated['shipping']['method']['description'] ?? null,
                'tracking_number' => 'TRK-' . strtoupper(bin2hex(random_bytes(8))),
            ]);
            \Log::info('Order delivery created', ['delivery' => $orderDelivery->toArray()]);


            // Create order history
            $orderHistory = $order->history()->create([
                'order_id' => $order->id,
                'payment_time' => $payment->created_at,
                'delivery_date' => match ($validated['shipping']['method']['label']) {
                    'Express' => now()->addDays(rand(2, 3)),
                    'Standard' => now()->addDays(rand(5, 7)),
                    'Free' => now()->addDays(7),
                    default => now()->addDays(7)
                },
                'completion_time' => null, // Will be updated when order is completed
                'timeline' => json_encode([
                    [
                        'title' => 'Order Placed',
                        'time' => now()->toISOString()
                    ],
                    [
                        'title' => 'Payment Initiated',
                        'time' => now()->toISOString()
                    ],
                    [
                        'title' => 'Estimated Delivery Time',
                        'time' => now()->setTime(4, 0)->toISOString(),
                        'end_time' => now()->setTime(9, 0)->toISOString()
                    ]
                ])
            ]);
            \Log::info('Order history created', ['history' => $orderHistory->toArray()]);


            // Invoice information
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'sent' => 1,
                'taxes' => $taxes,
                'status' => 'pending',
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'shipping' => $order->shipping,
                'total_amount' => $order->total_amount,
                'invoice_number' => 'INV-' . strtoupper(bin2hex(random_bytes(8))),
                'create_date' => now(),
                'due_date' => now()->addDays(30),
            ]);


            // Create invoice from details
            $invoice->billFrom()->create([
                'name' => config('app.company_name', 'Company Name'),
                'full_address' => config('app.company_address', 'Company Address'),
                'phone_number' => config('app.company_phone', 'Company Phone')
            ]);

            // Create invoice to details
            $invoice->billTo()->create([
                'name' => $order->customer->name,
                'full_address' => $order->shippingAdd->full_address,
                'phone_number' => $order->customer->phone_number
            ]);

            $this->emailVerificationService->sendInvoiceEmail($invoice);

            \Log::info('Invoice created', ['invoice' => $invoice->toArray()]);
            // Create invoice items
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['id']);
                $invoiceItem = new InvoiceItem([
                    'invoice_id' => $invoice->id,
                    'title' => 'Product ' . $item['name'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                    'service' => 'product',
                    'quantity' => $item['quantity'],
                    'description' => $product->description,
                ]);
                $invoice->items()->save($invoiceItem);
                \Log::info('Invoice item created', ['item' => $invoiceItem->toArray()]);
            }

            DB::commit();
            \Log::info('Order process completed successfully', ['order_id' => $order->id]);

            if ($validated['payment']['method'] === 'chapa') {
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
