<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\EcommerceAnalytics;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcommerceOverviewController extends Controller
{
    public function getOverviewData(): JsonResponse
    {
        try {
            return response()->json([
                'widgetSummary' => $this->getWidgetSummary(),
                'latestProducts' => $this->getLatestProducts(),
                'bestSalesman' => $this->getBestSalesman(),
                'currentBalance' => $this->getCurrentBalance(),
                'salesOverview' => $this->getSalesOverview(),
                'yearlySales' => $this->getYearlySales(),
                'saleByGender' => $this->getSaleByGender(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching ecommerce overview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch overview data'], 500);
        }
    }

    private function getWidgetSummary(): array
    {
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // Product Sales
        $currentSales = Order::whereBetween('created_at', [$lastMonth, $now])
            ->where('status', 'completed')
            ->sum('total_amount');

        $previousSales = Order::whereBetween('created_at', [
            $lastMonth->copy()->subMonth(),
            $lastMonth
        ])->where('status', 'completed')
            ->sum('total_amount');

        // Calculate percentages and trends
        $salesPercent = $previousSales > 0
            ? (($currentSales - $previousSales) / $previousSales) * 100
            : 0;

        return [
            'productSold' => [
                'total' => Order::where('status', 'completed')
                    ->sum(DB::raw('total_amount')),
                'percent' => $salesPercent,
                'chart' => $this->getMonthlySalesData()
            ],
            'totalBalance' => [
                'total' => Order::where('status', 'completed')
                    ->sum(DB::raw('total_amount - (total_amount * 0.1)')), // Assuming 10% platform fee
                'percent' => $salesPercent,
                'chart' => $this->getMonthlySalesData()
            ],
            'salesProfit' => [
                'total' => Order::where('status', 'completed')
                    ->sum(DB::raw('total_amount * 0.1')), // Platform fee as profit
                'percent' => $salesPercent,
                'chart' => $this->getMonthlySalesData()
            ]
        ];
    }

    private function getLatestProducts(): array
    {
        return Product::with(['category'])
            ->where('publish', 'published')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'coverUrl' => $product->coverUrl,
                    'price' => $product->price,
                    'priceSale' => $product->priceSale,
                    'colors' => $product->colors
                ];
            })
            ->toArray();
    }

    private function getBestSalesman(): array
    {
        return User::role('supplier')
            ->withCount(['products as total_sales' => function ($query) {
                $query->whereHas('orders', function ($q) {
                    $q->where('status', 'completed');
                });
            }])
            ->withSum(['products as total_amount' => function ($query) {
                $query->whereHas('orders', function ($q) {
                    $q->where('status', 'completed');
                });
            }], 'price')
            ->orderBy('total_amount', 'desc')
            ->take(5)
            ->get()
            ->map(function ($user, $index) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatarUrl' => $user->avatar_url,
                    'category' => 'Supplier',
                    'totalAmount' => $user->total_amount,
                    'rank' => 'Top ' . ($index + 1),
                    'countryCode' => $user->country_code ?? 'ET'
                ];
            })
            ->toArray();
    }

    private function getCurrentBalance(): array
    {
        $completedOrders = Order::where('status', 'completed');

        return [
            'currentBalance' => $completedOrders->sum('total_amount'),
            'totalEarning' => $completedOrders->sum(DB::raw('total_amount * 0.9')), // 90% to vendors
            'totalRefunded' => Order::where('status', 'refunded')->sum('total_amount'),
            'totalOrders' => $completedOrders->count()
        ];
    }

    private function getSalesOverview(): array
    {
        $total = Order::where('status', 'completed')->sum('total_amount');

        return [
            [
                'label' => 'Total Income',
                'value' => 100,
                'totalAmount' => $total
            ],
            [
                'label' => 'Total Expenses',
                'value' => 25,
                'totalAmount' => $total * 0.25
            ],
            [
                'label' => 'Total Profit',
                'value' => 75,
                'totalAmount' => $total * 0.75
            ]
        ];
    }

    private function getYearlySales(): array
    {
        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;

        return [
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'series' => [
                [
                    'name' => $lastYear,
                    'data' => $this->getYearlyData($lastYear)
                ],
                [
                    'name' => $currentYear,
                    'data' => $this->getYearlyData($currentYear)
                ]
            ]
        ];
    }

    private function getSaleByGender(): array
    {
        $sales = Order::where('status', 'completed')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->select('products.gender', DB::raw('COUNT(*) as count'))
            ->groupBy('products.gender')
            ->get();

        $total = $sales->sum('count');

        return [
            'total' => $total,
            'series' => $sales->map(function ($sale) use ($total) {
                return [
                    'label' => $sale->gender,
                    'value' => ($sale->count / $total) * 100
                ];
            })->toArray()
        ];
    }

    private function getMonthlySalesData(): array
    {
        $months = collect(range(1, 12))->map(function ($month) {
            return Order::where('status', 'completed')
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', $month)
                ->sum('total_amount');
        })->toArray();

        return [
            'series' => $months,
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        ];
    }

    private function getYearlyData(int $year): array
    {
        return [
            [
                'name' => 'Total Income',
                'data' => $this->getMonthlyData($year, 'income')
            ],
            [
                'name' => 'Total Expenses',
                'data' => $this->getMonthlyData($year, 'expenses')
            ]
        ];
    }

    private function getMonthlyData(int $year, string $type): array
    {
        return collect(range(1, 12))->map(function ($month) use ($year, $type) {
            $amount = Order::where('status', 'completed')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total_amount');

            return $type === 'expenses' ? $amount * 0.25 : $amount;
        })->toArray();
    }
}
