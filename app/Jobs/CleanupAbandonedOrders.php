<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupAbandonedOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Find orders that have been pending for more than 24 hours
        $cutoffTime = now()->subHours(24);

        $abandonedOrders = Order::where('status', 'pending')
            ->where('created_at', '<', $cutoffTime)
            ->whereHas('payment', function($query) {
                $query->where('payment_method', 'chapa')
                      ->where('status', 'initiated');
            })
            ->get();

        foreach ($abandonedOrders as $order) {
            Log::info('Cleaning up abandoned order', ['order_id' => $order->id]);

            // Release any inventory reservations
            // This depends on your inventory system implementation

            // Mark the order as cancelled
            $order->update(['status' => 'cancelled']);

            // Update the payment status
            if ($order->payment) {
                $order->payment->update(['status' => 'cancelled']);
            }

            // Update order history
            if ($order->history) {
                $timeline = json_decode($order->history->timeline, true) ?? [];
                $timeline[] = [
                    'title' => 'Order Cancelled - Payment Timeout',
                    'time' => now()->toISOString()
                ];
                $order->history->update([
                    'timeline' => json_encode($timeline)
                ]);
            }
        }

        Log::info('Abandoned order cleanup complete', [
            'orders_processed' => $abandonedOrders->count()
        ]);
    }
}
