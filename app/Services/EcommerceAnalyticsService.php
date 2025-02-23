<?php

namespace App\Services;

use App\Models\EcommerceAnalytics;
use Carbon\Carbon;

class EcommerceAnalyticsService
{
    public function recordSale(float $amount, array $metadata = []): void
    {
        EcommerceAnalytics::create([
            'type' => 'sale',
            'amount' => $amount,
            'count' => 1,
            'metadata' => $metadata,
            'recorded_at' => Carbon::now()
        ]);
    }

    public function recordRevenue(float $amount, array $metadata = []): void
    {
        EcommerceAnalytics::create([
            'type' => 'revenue',
            'amount' => $amount,
            'metadata' => $metadata,
            'recorded_at' => Carbon::now()
        ]);
    }

    public function getAnalytics(string $type, ?string $vendorId = null, Carbon $startDate = null, Carbon $endDate = null)
    {
        $query = EcommerceAnalytics::where('type', $type);

        if ($vendorId) {
            $query->whereJsonContains('metadata->vendor_id', $vendorId);
        }

        if ($startDate) {
            $query->where('recorded_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('recorded_at', '<=', $endDate);
        }

        return $query->get();
    }
} 