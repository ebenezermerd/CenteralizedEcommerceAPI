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

        // Current period calculations
        $currentSales = Order::whereBetween('created_at', [$lastMonth, $now])
            ->where('status', 'completed')
            ->sum('total_amount');

        // Previous period calculations
        $previousStart = $lastMonth->copy()->subMonth();
        $previousEnd = $lastMonth;

        $previousSales = Order::whereBetween('created_at', [$previousStart, $previousEnd])
            ->where('status', 'completed')
            ->sum('total_amount');

        // Calculate percentages safely
        $salesPercent = $this->calculatePercentageChange($currentSales, $previousSales);
        $balancePercent = $this->calculatePercentageChange($currentSales * 0.9, $previousSales * 0.9);
        $profitPercent = $this->calculatePercentageChange($currentSales * 0.1, $previousSales * 0.1);

        return [
            'productSold' => [
                'total' => Order::where('status', 'completed')->sum('total_amount'),
                'percent' => $salesPercent,
                'chart' => $this->getMonthlySalesData()
            ],
            'totalBalance' => [
                'total' => Order::where('status', 'completed')->sum(DB::raw('total_amount * 0.9')),
                'percent' => $balancePercent,
                'chart' => $this->getMonthlySalesData()
            ],
            'salesProfit' => [
                'total' => Order::where('status', 'completed')->sum(DB::raw('total_amount * 0.1')),
                'percent' => $profitPercent,
                'chart' => $this->getMonthlySalesData()
            ]
        ];
    }

    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
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
                $query->select(DB::raw('COALESCE(SUM(order_product_items.quantity), 0)'))
                    ->join('order_product_items', 'products.id', '=', 'order_product_items.product_id')
                    ->join('orders', 'order_product_items.order_id', '=', 'orders.id')
                    ->where('orders.status', 'completed');
            }])
            ->withSum(['products as total_amount' => function ($query) {
                $query->select(DB::raw('COALESCE(SUM(order_product_items.quantity * order_product_items.price), 0)'))
                    ->join('order_product_items', 'products.id', '=', 'order_product_items.product_id')
                    ->join('orders', 'order_product_items.order_id', '=', 'orders.id')
                    ->where('orders.status', 'completed');
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
                    'totalAmount' => $user->total_amount ?? 0,
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
        $totalSales = Order::where('status', 'completed')->sum('total_amount');
        $platformFee = 0.1; // 10% platform fee

        return [
            [
                'label' => 'Total Income',
                'value' => 100,
                'totalAmount' => $totalSales
            ],
            [
                'label' => 'Vendor Payments',
                'value' => round((1 - $platformFee) * 100),
                'totalAmount' => $totalSales * (1 - $platformFee)
            ],
            [
                'label' => 'Platform Profit',
                'value' => round($platformFee * 100),
                'totalAmount' => $totalSales * $platformFee
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
                    'name' => (string)$lastYear,
                    'data' => [
                        [
                            'name' => 'Total Income',
                            'data' => $this->getMonthlyData($lastYear, 'income')
                        ],
                        [
                            'name' => 'Total Expenses',
                            'data' => $this->getMonthlyData($lastYear, 'expenses')
                        ]
                    ]
                ],
                [
                    'name' => (string)$currentYear,
                    'data' => [
                        [
                            'name' => 'Total Income',
                            'data' => $this->getMonthlyData($currentYear, 'income')
                        ],
                        [
                            'name' => 'Total Expenses',
                            'data' => $this->getMonthlyData($currentYear, 'expenses')
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getSaleByGender(): array
    {
        // Define standard gender categories
        $genderCategories = ['Women', 'Men', 'Kids'];

        $sales = Order::where('status', 'completed')
            ->join('order_product_items', 'orders.id', '=', 'order_product_items.order_id')
            ->join('products', 'order_product_items.product_id', '=', 'products.id')
            ->select('products.gender', DB::raw('COUNT(*) as count'))
            ->groupBy('products.gender')
            ->get()
            ->flatMap(function ($sale) {
                // Handle JSON array of genders
                $genders = json_decode($sale->gender, true) ?? [];
                // Map each gender to its count
                return collect($genders)->mapWithKeys(function ($gender) use ($sale) {
                    return [$gender => $sale->count];
                });
            })
            ->groupBy(function ($count, $gender) {
                return $gender;
            })
            ->map(function ($counts) {
                return $counts->sum();
            });

        // Ensure all standard categories exist with at least 0 count
        foreach ($genderCategories as $category) {
            if (!$sales->has($category)) {
                $sales[$category] = 0;
            }
        }

        $total = $sales->sum();

        return [
            'total' => $total,
            'series' => $sales
                ->only($genderCategories) // Only include standard categories
                ->map(function ($count, $gender) use ($total) {
                    return [
                        'label' => $gender,
                        'value' => $total > 0 ? round(($count / $total) * 100, 1) : 0
                    ];
                })
                ->values()
                ->toArray()
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

    private function getMonthlyData(int $year, string $type): array
    {
        return collect(range(1, 12))->map(function ($month) use ($year, $type) {
            $amount = Order::where('status', 'completed')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total_amount');

            $value = match($type) {
                'expenses' => $amount * 0.9, // 90% to vendors
                'income' => $amount,
                default => $amount * 0.1 // 10% platform profit
            };

            return round($value, 2); // Round to 2 decimal places
        })->toArray();
    }
}
