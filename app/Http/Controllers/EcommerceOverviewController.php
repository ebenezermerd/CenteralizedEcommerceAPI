<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\EcommerceAnalytics;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EcommerceOverviewController extends Controller
{
    private const COMPANY_SHARE = 0.10; // 10% for company
    private const VENDOR_SHARE = 0.90;  // 90% for vendor

    public function getOverviewData(): JsonResponse
    {
        try {
            $user = auth()->user();
            $isSupplier = $user->role === 'supplier';
            $vendorId = $isSupplier ? $user->id : null;

            return response()->json([
                'widgetSummary' => $this->getWidgetSummary($vendorId),
                'salesOverview' => $this->getSalesOverview($vendorId),
                'yearlySales' => $this->getYearlySales($vendorId),
                'latestProducts' => $this->getLatestProducts($vendorId),
                'bestSalesman' => $isSupplier ? [] : $this->getBestSalesman(), // Only for admin
                'saleByGender' => $this->getSaleByGender($vendorId),
                'currentBalance' => $this->getCurrentBalance($vendorId),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ecommerce overview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch overview data'], 500);
        }
    }

    private function getWidgetSummary(?string $vendorId = null): array
    {
        // Product Sold Calculation
        $productSoldQuery = DB::table('order_product_items')
            ->join('products', 'order_product_items.product_id', '=', 'products.id')
            ->where('products.vendor_id', $vendorId);

        $currentMonthSold = (clone $productSoldQuery)->whereMonth('order_product_items.created_at', Carbon::now()->month)
            ->sum('order_product_items.quantity');
        $lastMonthSold = (clone $productSoldQuery)->whereMonth('order_product_items.created_at', Carbon::now()->subMonth()->month)
            ->sum('order_product_items.quantity');
        $productSoldGrowth = $lastMonthSold ? round(($currentMonthSold - $lastMonthSold) / $lastMonthSold * 100, 2) : 0;

        // Total Balance Calculation
        $totalSalesQuery = Order::where('status', 'completed');
        if ($vendorId) {
            $totalSalesQuery->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId));
        }
        $totalSales = $totalSalesQuery->sum('total_amount');
        $balanceShare = $vendorId ? self::VENDOR_SHARE : self::COMPANY_SHARE;
        $currentBalance = $totalSales * $balanceShare;

        // Sales Profit Calculation (using same base as balance but different growth metric)
        $profitGrowth = $this->calculateRevenueGrowth($totalSalesQuery, $vendorId);

        return [
            'productSold' => [
                'total' => (int) $currentMonthSold,
                'percent' => $productSoldGrowth,
                'chart' => $this->getProductSoldChart($vendorId)
            ],
            'totalBalance' => [
                'total' => round($currentBalance, 2),
                'percent' => $this->calculateBalanceGrowth($vendorId),
                'chart' => $this->getBalanceChart($vendorId)
            ],
            'salesProfit' => [
                'total' => round($currentBalance, 2),
                'percent' => $profitGrowth,
                'chart' => $this->getProfitChart($vendorId)
            ]
        ];
    }

    private function getSalesOverview(?string $vendorId = null): array
    {
        $currentYear = Carbon::now()->year;
        $share = $vendorId ? self::VENDOR_SHARE : 1;

        $data = collect(range(1, 12))->map(function($month) use ($vendorId, $currentYear, $share) {
            $query = Order::where('status', 'completed')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month);

            if ($vendorId) {
                $query->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                });
            }

            $amount = $query->sum('total_amount');
            return [
                'income' => round($amount * $share, 2),
                'expenses' => round($amount * 0.7 * $share, 2), // Example: 70% of revenue goes to expenses
                'profit' => round($amount * 0.3 * $share, 2)  // Example: 30% profit margin
            ];
        });

        return [
            [
                'label' => 'Income',
                'value' => $data->sum('income'),
                'data' => $data->pluck('income')->toArray()
            ],
            [
                'label' => 'Expenses',
                'value' => $data->sum('expenses'),
                'data' => $data->pluck('expenses')->toArray()
            ],
            [
                'label' => 'Profit',
                'value' => $data->sum('profit'),
                'data' => $data->pluck('profit')->toArray()
            ]
        ];
    }

    private function getYearlySales(?string $vendorId = null): array
    {
        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;
        $share = $vendorId ? self::VENDOR_SHARE : 1;

        return [
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'series' => [
                [
                    'name' => (string)$lastYear,
                    'data' => $this->getYearlyData($lastYear, $vendorId, $share)
                ],
                [
                    'name' => (string)$currentYear,
                    'data' => $this->getYearlyData($currentYear, $vendorId, $share)
                ]
            ]
        ];
    }

    private function getYearlyData(int $year, ?string $vendorId, float $share): array
    {
        return collect(range(1, 12))->map(function($month) use ($year, $vendorId, $share) {
            $query = Order::where('status', 'completed')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month);

            if ($vendorId) {
                $query->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                });
            }

            return round($query->sum('total_amount') * $share, 2);
        })->toArray();
    }

    private function getCurrentBalance(?string $vendorId = null): array
    {
        $query = Order::where('status', 'completed');
        if ($vendorId) {
            $query->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId));
        }

        $totalAmount = $query->sum('total_amount');
        $share = $vendorId ? self::VENDOR_SHARE : 1;

        return [
            'currentBalance' => round($totalAmount * $share, 2),
            'earning' => round($this->calculateEarnings($vendorId), 2),
            'refunded' => round($this->calculateRefunds($vendorId), 2),
            'orderTotal' => $this->calculateOrderTotal($vendorId)
        ];
    }

    private function calculateProductTotal(?string $vendorId = null): int
    {
        $query = Product::query();
        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        return $query->count();
    }

    private function calculateGrowthPercent($query, $field): float
    {
        $currentMonth = $query->whereMonth('created_at', Carbon::now()->month)->sum($field);
        $lastMonth = $query->whereMonth('created_at', Carbon::now()->subMonth()->month)->sum($field);

        if ($lastMonth == 0) return 0;
        return (($currentMonth - $lastMonth) / $lastMonth) * 100;
    }

    private function getChartData($query, $field, ?string $vendorId = null): array
    {
        $data = collect(range(1, 12))->map(function($month) use ($query, $field, $vendorId) {
            $amount = clone $query;
            $amount = $amount->whereMonth('created_at', $month)->sum($field);
            return $vendorId ? $amount * self::VENDOR_SHARE : $amount;
        })->toArray();

        return [
            'series' => $data,
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        ];
    }

    private function getLatestProducts(?string $vendorId = null): array
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
        return DB::table('users')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.avatar_url',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total_amount) as total_sales')
            )
            ->where('users.role', 'customer')
            ->groupBy('users.id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(function ($item, $index) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'email' => $item->email,
                    'avatarUrl' => $item->avatar_url,
                    'category' => 'Customer',
                    'totalAmount' => (float) $item->total_sales,
                    'rank' => 'Top ' . ($index + 1),
                    'countryCode' => 'ET' // Default country code
                ];
            })->toArray();
    }

    private function getSaleByGender(?string $vendorId = null): array
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
                $genders = is_string($sale->gender) ? json_decode($sale->gender, true) : [];
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $genders = [];
                }
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

    private function calculateEarnings(?string $vendorId): float
    {
        return Order::where('status', 'completed')
            ->when($vendorId, fn($q) => $q->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId)))
            ->sum('total_amount');
    }

    private function calculateRefunds(?string $vendorId): float
    {
        return Order::where('status', 'refunded')
            ->when($vendorId, fn($q) => $q->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId)))
            ->sum('total_amount');
    }

    private function calculateOrderTotal(?string $vendorId = null): int
    {
        $query = Order::query();
        if ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }

        return $query->count();
    }

    private function calculateOrderGrowth(?string $vendorId = null): float
    {
        $query = Order::query();
        if ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }

        $currentMonth = $query->whereMonth('created_at', Carbon::now()->month)->count();
        $lastMonth = $query->whereMonth('created_at', Carbon::now()->subMonth()->month)->count();

        if ($lastMonth == 0) return 0;
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    private function calculateProductGrowth(?string $vendorId = null): float
    {
        $query = Product::query();
        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        $currentMonth = $query->whereMonth('created_at', Carbon::now()->month)->count();
        $lastMonth = $query->whereMonth('created_at', Carbon::now()->subMonth()->month)->count();

        if ($lastMonth == 0) return 0;
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    private function getOrderChartData(?string $vendorId = null): array
    {
        $query = Order::query();
        if ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }

        $data = collect(range(1, 12))->map(function($month) use ($query) {
            $monthlyQuery = clone $query;
            return $monthlyQuery->whereMonth('created_at', $month)->count();
        })->toArray();

        return [
            'series' => $data,
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        ];
    }

    private function getProductChartData(?string $vendorId = null): array
    {
        $query = Product::query();
        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        $data = collect(range(1, 12))->map(function($month) use ($query) {
            $monthlyQuery = clone $query;
            return $monthlyQuery->whereMonth('created_at', $month)->count();
        })->toArray();

        return [
            'series' => $data,
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        ];
    }

    private function getProductSoldChart(?string $vendorId): array
    {
        $data = collect(range(1, 12))->map(function($month) use ($vendorId) {
            return DB::table('order_product_items')
                ->join('products', 'order_product_items.product_id', '=', 'products.id')
                ->when($vendorId, fn($q) => $q->where('products.vendor_id', $vendorId))
                ->whereMonth('order_product_items.created_at', $month)
                ->sum('order_product_items.quantity');
        });

        return ['series' => $data->toArray(), 'categories' => $this->monthlyCategories()];
    }

    private function getBalanceChart(?string $vendorId): array
    {
        $data = collect(range(1, 12))->map(function($month) use ($vendorId) {
            $total = Order::where('status', 'completed')
                ->when($vendorId, fn($q) => $q->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId)))
                ->whereMonth('created_at', $month)
                ->sum('total_amount');
            return $total * ($vendorId ? self::VENDOR_SHARE : self::COMPANY_SHARE);
        });

        return ['series' => $data->toArray(), 'categories' => $this->monthlyCategories()];
    }

    private function getProfitChart(?string $vendorId): array
    {
        $data = collect(range(1, 12))->map(function($month) use ($vendorId) {
            $gross = Order::where('status', 'completed')
                ->when($vendorId, fn($q) => $q->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId)))
                ->whereMonth('created_at', $month)
                ->sum('total_amount');

            $costs = DB::table('order_product_items')
                ->join('products', 'order_product_items.product_id', '=', 'products.id')
                ->when($vendorId, fn($q) => $q->where('products.vendor_id', $vendorId))
                ->whereMonth('order_product_items.created_at', $month)
                ->sum(DB::raw('products.price * order_product_items.quantity'));

            $netProfit = ($gross - $costs) * ($vendorId ? self::VENDOR_SHARE : self::COMPANY_SHARE);

            return max($netProfit, 0); // Ensure no negative profits
        });

        return ['series' => $data->toArray(), 'categories' => $this->monthlyCategories()];
    }

    private function monthlyCategories(): array
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    }

    private function calculateBalanceGrowth(?string $vendorId): float
    {
        $current = Order::where('status', 'completed')
            ->when($vendorId, fn($q) => $q->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId)))
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('total_amount');

        $previous = Order::where('status', 'completed')
            ->when($vendorId, fn($q) => $q->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId)))
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->sum('total_amount');

        return $previous ? round(($current - $previous) / $previous * 100, 2) : 0;
    }

    private function calculateRevenueGrowth($query, ?string $vendorId): float
    {
        $current = (clone $query)
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('total_amount');

        $previous = (clone $query)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->sum('total_amount');

        return $previous ? round(($current - $previous) / $previous * 100, 2) : 0;
    }
}
