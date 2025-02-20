<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderPayment;
use App\Models\Invoice;
use App\Services\Chapa;
use Illuminate\Http\JsonResponse;

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
            \Log::debug('Verifying transaction with Chapa', ['reference' => $reference]);

            $verificationResponse = Chapa::verifyTransaction($reference);
            \Log::info('Chapa verification response', ['response' => $verificationResponse]);

            if ($verificationResponse['status'] === 'success') {
                \Log::info('Payment verification successful', ['reference' => $reference]);

                $payment = OrderPayment::where('tx_ref', $reference)->firstOrFail();
                \Log::debug('Payment record found', ['payment_id' => $payment->id]);

                $order = $payment->order;
                \Log::debug('Associated order found', ['order_id' => $order->id]);

                $payment->update([
                    'status' => 'completed',
                    'transaction_id' => $verificationResponse['data']['transaction_id'] ?? null,
                    'payment_date' => now()
                ]);
                \Log::info('Payment record updated', ['payment_id' => $payment->id, 'new_status' => 'completed']);

                $order->update(['status' => 'completed']);
                \Log::info('Order status updated', ['order_id' => $order->id, 'new_status' => 'completed']);

                // Update order history timeline
                if ($order->history) {
                    $timeline = json_decode($order->history->timeline, true);
                    $timeline[] = [
                        'title' => 'Payment Verified',
                        'time' => now()->toISOString()
                    ];
                    $order->history->update([
                        'timeline' => json_encode($timeline),
                        'payment_time' => now()
                    ]);
                }

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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error processing callback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function handleReturn(Request $request)
    {
        \Log::info('Payment return endpoint hit', ['tx_ref' => $request->all()]);

        $txnRef = $request->input('tx_ref');
        $payment = OrderPayment::where('tx_ref', $txnRef)->first();

        if (!$payment) {
            \Log::warning('Payment not found for return request', ['tx_ref' => $txnRef]);
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found'
            ], 404);
        }

        \Log::info('Payment return status checked', [
            'tx_ref' => $txnRef,
            'payment_id' => $payment->id,
            'order_number' => $payment->order->order_number,
            'status' => $payment->status
        ]);

        if ($payment->status !== 'completed') {
            $payment->update([
                'status' => 'completed',
                'payment_date' => now()
            ]);

            // Update order history timeline
            $orderHistory = $payment->order->history;
            if ($orderHistory) {
                $timeline = json_decode($orderHistory->timeline, true);
                $timeline[] = [
                    'title' => 'Payment Completed',
                    'time' => now()->toISOString()
                ];
                $orderHistory->update([
                    'timeline' => json_encode($timeline),
                    'payment_time' => now()
                ]);
            }

            // Update invoice status to paid
            $invoice = Invoice::where('order_id', $payment->order_id)->first();
            if ($invoice) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);
            }
        }

        if ($payment->status === 'completed') {
            return redirect()->to(env('FRONTEND_URL') . '/product/checkout?step=3');
        } else {
            return redirect()->to(env('FRONTEND_URL') . '/product/checkout?step=2');
        }
    }

    public function initializePayment(Request $request): JsonResponse
    {
        \Log::info('Initiating Chapa payment', ['request_data' => $request->all()]);

        try {
            // Always use the generated reference for new payments
            $reference = $this->reference;

            $chapaResponse = Chapa::initializePayment([
                'amount' => $request->amount,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => str_replace(' ', '', $request->phone_number),
                'currency' => $request->currency ?? 'ETB',
                'email' => $request->email,
                'tx_ref' => $reference,
                'callback_url' => route('callback', ['reference' => $reference]),
                'return_url' => route('chapa.return') . '?tx_ref=' . $reference,
                'customization' => [
                    'title' => $request->title ?? 'Order Payment',
                    'description' => $request->description ?? 'Payment for order',
                ],
            ]);

            \Log::info('Chapa payment response', ['response' => $chapaResponse]);

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
