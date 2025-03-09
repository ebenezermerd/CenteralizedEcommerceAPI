<?php

namespace App\Services;

use App\Models\Product;
use App\Mail\LowStockAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class StockMonitoringService
{
    private const STOCK_THRESHOLD = 3; // Consistent with InventoryService LOW_STOCK_THRESHOLD

    public function checkLowStock()
    {
        try {
            // Group products by vendor
            $lowStockProducts = Product::where('available', '<=', self::STOCK_THRESHOLD)
                ->where('available', '>', 0) // Only include products that have some stock
                ->with('vendor')
                ->get()
                ->groupBy('vendor_id');

            foreach ($lowStockProducts as $vendorId => $products) {
                $vendor = $products->first()->vendor;

                if ($vendor) {
                    Mail::to($vendor->email)
                        ->queue(new LowStockAlert($products, self::STOCK_THRESHOLD));

                    Log::info('Low stock alert sent', [
                        'vendor_id' => $vendorId,
                        'products_count' => $products->count(),
                        'threshold' => self::STOCK_THRESHOLD
                    ]);
                }
            }
            
            return count($lowStockProducts);
        } catch (\Exception $e) {
            Log::error('Error sending low stock alerts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
