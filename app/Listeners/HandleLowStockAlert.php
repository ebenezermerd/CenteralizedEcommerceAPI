<?php

namespace App\Listeners;

use App\Events\LowStockEvent;
use Illuminate\Support\Facades\Mail;
use App\Mail\LowStockAlert;
use Illuminate\Support\Facades\Log;
use App\Services\InventoryService;

class HandleLowStockAlert
{
    private $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function handle(LowStockEvent $event): void
    {
        try {
            // Get threshold from service for consistency
            $threshold = 3; // Same as LOW_STOCK_THRESHOLD in InventoryService
            
            // Don't send alerts for products that are already out of stock
            if ($event->product->available <= 0) {
                Log::info('Skipping low stock alert for out-of-stock product', [
                    'product_id' => $event->product->id,
                    'product_name' => $event->product->name
                ]);
                return;
            }
            
            Log::info('Low stock detected', [
                'product_id' => $event->product->id,
                'product_name' => $event->product->name,
                'available' => $event->product->available,
                'threshold' => $threshold
            ]);
            
            // Send email to vendor
            Mail::to($event->product->vendor->email)
                ->queue(new LowStockAlert(collect([$event->product]), $threshold));
                
        } catch (\Exception $e) {
            Log::error('Error handling low stock alert', [
                'product_id' => $event->product->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
