<?php

namespace App\Traits;

use App\Services\EcommerceAnalyticsService;

trait RecordsEcommerceAnalytics
{
    protected function recordAnalytics(string $type, float $amount, array $metadata = []): void
    {
        $analyticsService = app(EcommerceAnalyticsService::class);
        
        if ($type === 'sale') {
            $analyticsService->recordSale($amount, $metadata);
        } else if ($type === 'revenue') {
            $analyticsService->recordRevenue($amount, $metadata);
        }
    }
} 