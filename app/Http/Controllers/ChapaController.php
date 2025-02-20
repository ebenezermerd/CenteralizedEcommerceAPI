<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderPayment;
use App\Models\Invoice;
use Chapa\Chapa\Facades\Chapa as Chapa;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Services\EmailVerificationService;
class ChapaController extends Controller
{
    /**
     * The payment reference
     * @var string
     */
    protected $reference;
    protected $emailVerificationService;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        // Initialize reference on construction
        $this->reference = Chapa::generateReference();
        $this->emailVerificationService = $emailVerificationService;
    }

    public function initializePayment(Request $request): JsonResponse
    {
        \Log::info('Initiating Chapa payment', ['request_data' => $request->all()]);

        try {
            // Always use the generated reference for new payments
            $reference = $this->reference;

            $data = [
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'ETB',
                'email' => $request->email,
                'tx_ref' => $reference,
                'callback_url' => route('callback', [$reference]),
                'return_url' => route('chapa.return', ['tx_ref' => $reference]),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => str_replace(' ', '', $request->phone_number),
                'customization' => [
                    'title' => $request->title ?? 'Order Payment',
                    'description' => $request->description ?? 'Payment for order'
                ]
            ];

            \Log::info('Chapa payment data', ['data' => $data]);

            $payment = Chapa::initializePayment($data);

            \Log::info('Chapa payment response', ['response' => $payment]);

            if ($payment['status'] !== 'success') {
                \Log::error('Chapa payment initialization failed', ['response' => $payment]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment initialization failed',
                    'error' => $payment['message']
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'tx_ref' => $reference,
                'checkout_url' => $payment['data']['checkout_url']
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Chapa payment initialization error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error initializing payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle the callback from Chapa
     */
    public function callback($reference)
    {
        \Log::info('Payment callback received', ['reference' => $reference]);

        try {
            $data = Chapa::verifyTransaction($reference);
            \Log::info('Chapa verification response', ['response' => $data]);

            if ($data['status'] === 'success') {
                $payment = OrderPayment::where('tx_ref', $reference)->first();

                if ($payment) {
                    $payment->update([
                        'status' => 'completed',
                        'transaction_id' => $data['data']['transaction_id'] ?? null,
                        'payment_date' => now()
                    ]);

                    $order = $payment->order;
                    if ($order) {
                        $order->update(['status' => 'completed']);

                        // Update order history
                        if ($order->history) {
                            $timeline = json_decode($order->history->timeline, true) ?? [];
                            $timeline[] = [
                                'title' => 'Payment Confirmed',
                                'time' => now()->toISOString()
                            ];
                            $order->history->update([
                                'timeline' => json_encode($timeline),
                                'payment_time' => now()
                            ]);
                        }
                    }
                    // update order items quantity
                    foreach ($order->items as $item) {
                        $item->product->update([
                            'quantity' => $item->product->quantity - $item->quantity
                        ]);
                    }
                    // update invoice status
                    $invoice = Invoice::where('order_id', $order->id)->first();
                    $invoice->update([
                        'status' => 'paid'
                    ]);
                    //send email to customer
                    $this->emailVerificationService->sendInvoiceEmail($invoice);
                }

                return redirect()->to(config('app.frontend_url') . '/e-commerce/payment/success'
                    . '?tx_ref=' . $reference
                    . '&transaction_id=' . ($data['data']['transaction_id'] ?? '')
                    . '&amount=' . ($data['data']['amount'] ?? '')
                    . '&currency=' . ($data['data']['currency'] ?? '')
                    . '&status=success');
            }

            return redirect()->to(config('app.frontend_url') . '/e-commerce/payment/failed'
                . '?tx_ref=' . $reference
                . '&status=failed');
        } catch (\Exception $e) {
            \Log::error('Payment callback error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return redirect()->to(config('app.frontend_url') . '/e-commerce/payment/failed'
                . '?tx_ref=' . $reference
                . '&error=system-error');
        }
    }

    public function handleReturn(Request $request)
    {
        \Log::info('Payment return endpoint hit', [
            'tx_ref' => $request->input('tx_ref'),
            'all_params' => $request->all()
        ]);

        $txRef = $request->input('tx_ref');

        try {
            // Verify the transaction when returning from receipt
            $data = Chapa::verifyTransaction($txRef);
            \Log::info('Verification on return', ['data' => $data]);

            if ($data['status'] === 'success') {
                return redirect()->to(config('app.frontend_url') . '/e-commerce/payment/success'
                    . '?tx_ref=' . $txRef
                    . '&transaction_id=' . ($data['data']['transaction_id'] ?? '')
                    . '&amount=' . ($data['data']['amount'] ?? '')
                    . '&currency=' . ($data['data']['currency'] ?? '')
                    . '&status=success');
            }
        } catch (\Exception $e) {
            \Log::error('Return verification failed', [
                'error' => $e->getMessage(),
                'tx_ref' => $txRef
            ]);
        }

        // If verification fails or throws error, check payment record
        $payment = OrderPayment::where('tx_ref', $txRef)->first();

        if (!$payment) {
            return redirect()->to(config('app.frontend_url') . '/e-commerce/payment/failed'
                . '?error=payment-not-found'
                . '&tx_ref=' . $txRef);
        }

        if ($payment->status === 'completed') {
            return redirect()->to(config('app.frontend_url') . '/e-commerce/payment/success'
                . '?tx_ref=' . $payment->tx_ref
                . '&transaction_id=' . $payment->transaction_id
                . '&amount=' . $payment->amount
                . '&currency=' . $payment->currency
                . '&status=success');
        } else {
            return redirect()->to(config('app.frontend_url') . '/e-commerce/payment/failed'
                . '?tx_ref=' . $payment->tx_ref
                . '&amount=' . $payment->amount
                . '&currency=' . $payment->currency
                . '&status=pending');
        }
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        \Log::info('Chapa webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all()
        ]);

        try {
            // Verify webhook signature
            $signature = strtolower($request->header('X-Chapa-Signature'));
            $payload = $request->getContent();
            $secret = config('chapa.webhookSecret');

            $calculatedSignature = strtolower(hash_hmac('sha256', $payload, $secret));

            if (!hash_equals($signature, $calculatedSignature)) {
                \Log::error('Invalid webhook signature');
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
            }

            // Let Chapa SDK handle the email notifications
            // We just need to acknowledge receipt of the webhook
            return response()->json(['status' => 'success', 'message' => 'Webhook received']);
        } catch (\Exception $e) {
            \Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error processing webhook'
            ], 500);
        }
    }
}
