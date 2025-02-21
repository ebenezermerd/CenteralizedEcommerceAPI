<?php

namespace App\Services;

use App\Models\Product;
use App\Mail\LowStockAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class StockMonitoringService
{
    private const STOCK_THRESHOLD = 3; // Default threshold

    public function checkLowStock()
    {
        try {
            // Group products by vendor
            $lowStockProducts = Product::where('quantity', '<=', self::STOCK_THRESHOLD)
                ->with('vendor')
                ->get()
                ->groupBy('vendor_id');

            foreach ($lowStockProducts as $vendorId => $products) {
                $vendor = $products->first()->vendor;

                if ($vendor) {
                    Mail::to($vendor->email)
                        ->send(new LowStockAlert($products, self::STOCK_THRESHOLD));

                    Log::info('Low stock alert sent', [
                        'vendor_id' => $vendorId,
                        'products_count' => $products->count()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error sending low stock alerts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
