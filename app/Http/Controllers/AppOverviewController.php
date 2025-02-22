<?php

namespace App\Http\Controllers;

use App\Models\AppAnalytics;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AppOverviewController extends Controller
{
    public function getOverviewData(): JsonResponse
    {
        try {
            return response()->json([
                'activeUsers' => $this->getActiveUsers(),
                'installations' => $this->getInstallations(),
                'downloads' => $this->getDownloads(),
                'platformStats' => $this->getPlatformStats(),
                'featured' => $this->getFeaturedApps(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch app overview data'], 500);
        }
    }

    private function getActiveUsers(): array
    {
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $currentUsers = AppAnalytics::where('type', 'active_users')
            ->whereBetween('recorded_at', [$lastMonth, $now])
            ->sum('count');

        $previousUsers = AppAnalytics::where('type', 'active_users')
            ->whereBetween('recorded_at', [$lastMonth->copy()->subMonth(), $lastMonth])
            ->sum('count');

        $percent = $previousUsers > 0 ?
            (($currentUsers - $previousUsers) / $previousUsers) * 100 :
            0;

        return [
            'total' => $currentUsers,
            'percent' => round($percent, 1),
            'chart' => $this->getMonthlyData('active_users')
        ];
    }

    private function getInstallations(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        $current = AppAnalytics::where('type', 'installations')
            ->whereBetween('recorded_at', [$lastMonth, $now])
            ->sum('count');

        $previous = AppAnalytics::where('type', 'installations')
            ->whereBetween('recorded_at', [$lastMonth->copy()->subMonth(), $lastMonth])
            ->sum('count');

        $percent = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'total' => $current,
            'percent' => round($percent, 1),
            'chart' => $this->getMonthlyData('installations')
        ];
    }

    private function getDownloads(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        $current = AppAnalytics::where('type', 'downloads')
            ->whereBetween('recorded_at', [$lastMonth, $now])
            ->sum('count');

        $previous = AppAnalytics::where('type', 'downloads')
            ->whereBetween('recorded_at', [$lastMonth->copy()->subMonth(), $lastMonth])
            ->sum('count');

        $percent = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'total' => $current,
            'percent' => round($percent, 1),
            'chart' => $this->getMonthlyData('downloads')
        ];
    }

    private function getPlatformStats(): array
    {
        $android = AppAnalytics::where('platform', 'android')->sum('count');
        $ios = AppAnalytics::where('platform', 'ios')->sum('count');

        return [
            'series' => [
                ['label' => 'Android', 'value' => $android],
                ['label' => 'iOS', 'value' => $ios],
            ]
        ];
    }

    private function getFeaturedApps(): array
    {
        return Product::where('newLabel->enabled', true)
            ->limit(3)
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'title' => $product->name,
                'description' => $product->subDescription,
                'coverUrl' => $product->coverUrl
            ])->toArray();
    }

    private function getMonthlyData(string $type): array
    {
        $data = collect(range(1, 8))->map(function ($month) use ($type) {
            return AppAnalytics::where('type', $type)
                ->whereYear('recorded_at', Carbon::now()->year)
                ->whereMonth('recorded_at', $month)
                ->sum('count');
        })->toArray();

        return [
            'series' => $data,
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug']
        ];
    }
}
