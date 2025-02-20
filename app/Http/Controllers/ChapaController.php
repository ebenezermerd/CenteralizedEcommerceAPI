<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderPayment;
use App\Models\Invoice;
use Chapa\Chapa\Facades\Chapa as Chapa;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ChapaController extends Controller
{
    /**
     * The payment reference
     * @var string
     */
    protected $reference;

    public function __construct()
    {
        // Initialize reference on construction
        $this->reference = Chapa::generateReference();
    }

    public function handleCallback(string $reference): JsonResponse
    {
        \Log::info('Payment callback received', ['reference' => $reference]);

        try {
            $verificationResponse = Chapa::verifyTransaction($reference);
            \Log::info('Chapa verification response', ['response' => $verificationResponse]);

            if ($verificationResponse['status'] === 'success') {
                \Log::info('Payment verification successful', ['reference' => $reference]);

                // Only verify and return success - actual updates happen in webhook
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment verified successfully',
                    'data' => $verificationResponse['data']
                ]);
            }

            \Log::warning('Payment verification failed', ['reference' => $reference]);
            return response()->json([
                'status' => 'error',
                'message' => 'Payment verification failed'
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Payment callback error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error processing callback'
            ], 500);
        }
    }

    public function handleReturn(Request $request)
    {
        \Log::info('Payment return endpoint hit', ['tx_ref' => $request->input('tx_ref')]);

        $txRef = $request->input('tx_ref');
        $payment = OrderPayment::where('tx_ref', $txRef)->first();

        if (!$payment) {
            \Log::warning('Payment not found', ['tx_ref' => $txRef]);
            return redirect()->to(config('app.frontend_url') . '/e-commerce/payment/failed'
                . '?error=payment-not-found'
                . '&tx_ref=' . $txRef);
        }
        \Log::info('Payment found', ['payment' => $payment->toArray()]);

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

    public function initializePayment(Request $request): JsonResponse
    {
        \Log::info('Initiating Chapa payment', ['request_data' => $request->all()]);

        try {
            // Always use the generated reference for new payments
            $reference = $this->reference;

            // Build the return URL with the reference
            $chapaReturnUrl = route('chapa.return', ['tx_ref' => $reference], true); // true for absolute URL

            // Build the callback URL - make sure it's absolute and matches the route
            $callbackUrl = route('callback', ['reference' => $reference], true); // true for absolute URL

            \Log::debug('URLs generated for Chapa', [
                'return_url' => $chapaReturnUrl,
                'callback_url' => $callbackUrl
            ]);

            $chapaResponse = Chapa::initializePayment([
                'amount' => $request->amount,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => str_replace(' ', '', $request->phone_number),
                'currency' => $request->currency ?? 'ETB',
                'email' => $request->email,
                'tx_ref' => $reference,
                'callback_url' => $callbackUrl,
                'return_url' => $chapaReturnUrl,
                'customization' => [
                    'title' => $request->title ?? 'Order Payment',
                    'description' => $request->description ?? 'Payment for order',
                ],
            ]);

            \Log::info('Chapa payment response', [
                'response' => $chapaResponse,
                'callback_url' => $callbackUrl,
                'return_url' => $chapaReturnUrl
            ]);

            if ($chapaResponse['status'] !== 'success') {
                \Log::error('Chapa payment initialization failed', ['response' => $chapaResponse]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment initialization failed',
                    'error' => $chapaResponse['message']
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'tx_ref' => $reference,
                'checkout_url' => $chapaResponse['data']['checkout_url']
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

            $txnRef = $request->input('tx_ref');
            $status = $request->input('status');

            DB::transaction(function () use ($txnRef, $status, $request) {
                // 1. Update Payment
                $payment = OrderPayment::where('tx_ref', $txnRef)->firstOrFail();
                $payment->update([
                    'status' => $status === 'success' ? 'completed' : 'failed',
                    'transaction_id' => $request->input('transaction_id'),
                    'payment_date' => now(),
                ]);

                \Log::info('Payment updated', ['payment' => $payment->toArray()]);

                if ($status === 'success') {
                    // 2. Update Order
                    $order = $payment->order;
                    $order->update(['status' => 'completed']);

                    // 3. Update Order History
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

                    // 4. Update Invoice
                    $invoice = Invoice::where('order_id', $order->id)->first();
                    if ($invoice) {
                        $invoice->update([
                            'status' => 'paid',
                            'paid_at' => now()
                        ]);
                    }
                }
            });

            return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully']);
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
