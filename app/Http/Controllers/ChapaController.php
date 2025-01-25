<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderPayment;

class ChapaController extends Controller
{
    public function handleCallback(Request $request)
    {
        \Log::info('Payment callback received', ['tx_ref' => $request->input('tx_ref')]);

        try {
            $txnRef = $request->input('tx_ref');
            \Log::debug('Verifying transaction with Chapa', ['tx_ref' => $txnRef]);

            $verificationResponse = Chapa::verifyTransaction($txnRef);
            \Log::info('Chapa verification response', ['response' => $verificationResponse]);

            if ($verificationResponse['status'] === 'success') {
                \Log::info('Payment verification successful', ['tx_ref' => $txnRef]);

                $payment = OrderPayment::where('tx_ref', $txnRef)->firstOrFail();
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

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment verified successfully',
                    'data' => $verificationResponse['data']
                ]);
            }

            \Log::warning('Payment verification failed', ['tx_ref' => $txnRef]);
            return response()->json([
                'status' => 'error',
                'message' => 'Payment verification failed'
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Payment callback error', [
                'tx_ref' => $txnRef ?? null,
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
        \Log::info('Payment return endpoint hit', ['tx_ref' => $request->input('tx_ref')]);

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

        if ($payment->status === 'completed') {
            return redirect()->to(env('FRONTEND_URL') . '/product/checkout?step=3');
        } else {
            return redirect()->to(env('FRONTEND_URL') . '/product/checkout?step=4');
        }
    }
}
