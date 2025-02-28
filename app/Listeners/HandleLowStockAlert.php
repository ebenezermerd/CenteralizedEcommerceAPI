<?php

namespace App\Listeners;

use App\Events\LowStockEvent;
use App\Mail\LowStockAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class HandleLowStockAlert
{
    public function handle(LowStockEvent $event): void
    {
        try {
            // Send email to vendor
            Mail::to($event->product->vendor->email)
                ->queue(new LowStockAlert(collect([$event->product]), 10));

            Log::info('Low stock alert sent', [
                'product_id' => $event->product->id,
                'vendor_id' => $event->product->vendor_id,
                'current_stock' => $event->product->available
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send low stock alert', [
                'error' => $e->getMessage(),
                'product_id' => $event->product->id
            ]);
        }
    }
}
