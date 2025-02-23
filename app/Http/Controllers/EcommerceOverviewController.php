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
use Illuminate\Support\Facades\Storage;

class EcommerceOverviewController extends Controller
{
    private const CHART_CATEGORIES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    public function getOverviewData(): JsonResponse
    {
        try {
            $user = auth()->user();
            $isSupplier = $user->hasRole('supplier');
            $vendorId = $isSupplier ? $user->id : null;

            $data = [
                'widgetSummary' => $this->getWidgetSummary($vendorId),
                'salesOverview' => $this->getSalesOverview($vendorId),
                'yearlySales' => $this->getYearlySales($vendorId),
                'latestProducts' => $this->getLatestProducts($vendorId),
                'saleByGender' => $this->getSaleByGender($vendorId),
                'currentBalance' => $this->getCurrentBalance($vendorId),
                'userInfo' => [
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'email' => $user->email,
                    'avatarUrl' => $user->image ? url(Storage::url($user->image)) : null,
                ]
            ];

            // Only include bestSalesman for admin
            if (!$isSupplier) {
                $data['bestSalesman'] = $this->getBestSalesman();
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error in ecommerce overview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch overview data'], 500);
        }
    }

    private function getWidgetSummary(?string $vendorId): array
    {
        $share = $vendorId ? config('ecommerce.shares.vendor') : config('ecommerce.shares.company');
        
        // Product Sold Data
        $productSoldData = $this->getProductSoldData($vendorId);
        
        // Total Balance Data
        $balanceData = $this->getBalanceData($vendorId, $share);
        
        // Sales Profit Data
        $profitData = $this->getProfitData($vendorId, $share);

        return [
            'productSold' => [
                'total' => $productSoldData['total'],
                'percent' => $productSoldData['percent'],
                'chart' => [
                    'series' => $productSoldData['chart']['series'],
                    'categories' => self::CHART_CATEGORIES
                ]
            ],
            'totalBalance' => [
                'total' => $balanceData['total'],
                'percent' => $balanceData['percent'],
                'chart' => [
                    'series' => $balanceData['chart']['series'],
                    'categories' => self::CHART_CATEGORIES
                ]
            ],
            'salesProfit' => [
                'total' => $profitData['total'],
                'percent' => $profitData['percent'],
                'chart' => [
                    'series' => $profitData['chart']['series'],
                    'categories' => self::CHART_CATEGORIES
                ]
            ]
        ];
    }

    private function getSalesOverview(?string $vendorId): array
    {
        $share = $vendorId ? config('ecommerce.shares.vendor') : 1;
        $currentYear = Carbon::now()->year;

        $monthlyData = collect(range(1, 12))->map(function($month) use ($vendorId, $currentYear, $share) {
            $query = Order::where('status', 'completed')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month);

            if ($vendorId) {
                $query->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId));
            }

            $amount = $query->sum('total_amount') * $share;
            $total = $amount;  // Store total for percentage calculation
            
            return [
                'income' => $amount,
                'expenses' => $amount * 0.7,
                'profit' => $amount * 0.3,
                'total' => $total
            ];
        });

        $totalAmount = $monthlyData->sum('total');

        return [
            [
                'label' => 'Total Income',
                'value' => $monthlyData->sum('income'),
                'totalAmount' => $monthlyData->sum('income'),
                'percentage' => $totalAmount > 0 ? ($monthlyData->sum('income') / $totalAmount) * 100 : 0,
                'data' => $monthlyData->pluck('income')->toArray()
            ],
            [
                'label' => 'Total Expenses',
                'value' => $monthlyData->sum('expenses'),
                'totalAmount' => $monthlyData->sum('expenses'),
                'percentage' => $totalAmount > 0 ? ($monthlyData->sum('expenses') / $totalAmount) * 100 : 0,
                'data' => $monthlyData->pluck('expenses')->toArray()
            ],
            [
                'label' => 'Total Profit',
                'value' => $monthlyData->sum('profit'),
                'totalAmount' => $monthlyData->sum('profit'),
                'percentage' => $totalAmount > 0 ? ($monthlyData->sum('profit') / $totalAmount) * 100 : 0,
                'data' => $monthlyData->pluck('profit')->toArray()
            ]
        ];
    }

    private function getYearlySales(?string $vendorId): array
    {
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;
        $share = $vendorId ? config('ecommerce.shares.vendor') : 1;

        // Get data for both years
        $previousYearData = $this->getYearlyData($previousYear, $vendorId, $share);
        $currentYearData = $this->getYearlyData($currentYear, $vendorId, $share);

        // Only include years that have data
        $series = [];
        if (array_sum($previousYearData) > 0) {
            $series[] = [
                'name' => (string)$previousYear,
                'data' => [
                    [
                        'name' => 'Sales',
                        'data' => $previousYearData
                    ]
                ]
            ];
        }
        
        if (array_sum($currentYearData) > 0) {
            $series[] = [
                'name' => (string)$currentYear,
                'data' => [
                    [
                        'name' => 'Sales',
                        'data' => $currentYearData
                    ]
                ]
            ];
        }

        return [
            'categories' => self::CHART_CATEGORIES,
            'series' => $series
        ];
    }

    private function getCurrentBalance(?string $vendorId): array
    {
        $share = $vendorId ? config('ecommerce.shares.vendor') : 1;
        
        $query = Order::query();
        if ($vendorId) {
            $query->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId));
        }

        $completedOrders = (clone $query)->where('status', 'completed');
        $refundedOrders = (clone $query)->where('status', 'refunded');

        return [
            'currentBalance' => $completedOrders->sum('total_amount') * $share,
            'totalEarning' => $completedOrders->sum('total_amount') * $share,
            'totalRefunded' => $refundedOrders->sum('total_amount') * $share,
            'totalOrders' => $query->count()
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
            return $vendorId ? $amount * config('ecommerce.shares.vendor') : $amount;
        })->toArray();

        return [
            'series' => $data,
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        ];
    }

    private function getLatestProducts(?string $vendorId): array
    {
        return Product::query()
            ->when($vendorId, fn($q) => $q->where('vendor_id', $vendorId))
            ->where('publish', 'published')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'coverUrl' => $product->coverUrl,
                    'price' => (float) $product->price,
                    'priceSale' => (float) $product->priceSale,
                    'colors' => $product->colors ?? []
                ];
            })
            ->toArray();
    }

    private function getBestSalesman(): array
    {
        return User::role('supplier')
            ->withCount(['products as total_products'])
            ->withSum(['orders as total_sales' => function($query) {
                $query->where('status', 'completed');
            }], 'total_amount')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get()
            ->map(function ($vendor, $index) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->firstName . ' ' . $vendor->lastName,
                    'email' => $vendor->email,
                    'avatarUrl' => $vendor->image ? url(Storage::url($vendor->image)) : null,
                    'category' => 'Supplier',
                    'totalAmount' => (float) $vendor->total_sales,
                    'rank' => 'Top ' . ($index + 1),
                    'countryCode' => $vendor->country ?? 'ET'
                ];
            })
            ->toArray();
    }

    private function getSaleByGender(?string $vendorId): array
    {
        $query = DB::table('order_product_items')
            ->join('products', 'order_product_items.product_id', '=', 'products.id')
            ->join('orders', 'order_product_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed');

        if ($vendorId) {
            $query->where('products.vendor_id', $vendorId);
        }

        $genderSales = $query
            ->select('products.gender', DB::raw('SUM(order_product_items.quantity) as total_sold'))
            ->whereNotNull('products.gender')
            ->groupBy('products.gender')
            ->get()
            ->flatMap(function ($sale) {
                $genders = json_decode($sale->gender, true) ?? [];
                return collect($genders)->map(fn($gender) => [
                    'gender' => $gender,
                    'count' => $sale->total_sold
                ]);
            })
            ->groupBy('gender')
            ->map(fn($items) => $items->sum('count'));

        $total = $genderSales->sum();

        return [
            'total' => $total,
            'series' => collect(config('ecommerce.analytics.gender_categories'))
                ->map(function($gender) use ($genderSales, $total) {
                    $value = $genderSales->get($gender, 0);
                    return [
                        'label' => $gender,
                        'value' => $total > 0 ? round(($value / $total) * 100, 1) : 0
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

        $currentMonth = (clone $query)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        $lastMonth = (clone $query)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->count();

        return $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;
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
            return $total * ($vendorId ? config('ecommerce.shares.vendor') : config('ecommerce.shares.company'));
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

            $netProfit = ($gross - $costs) * ($vendorId ? config('ecommerce.shares.vendor') : config('ecommerce.shares.company'));

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

    private function getProductSoldData(?string $vendorId): array
    {
        $query = DB::table('order_product_items')
            ->join('products', 'order_product_items.product_id', '=', 'products.id')
            ->join('orders', 'order_product_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed');

        if ($vendorId) {
            $query->where('products.vendor_id', $vendorId);
        }

        // Current month total
        $currentMonthTotal = (clone $query)
            ->whereMonth('orders.created_at', Carbon::now()->month)
            ->sum('order_product_items.quantity');

        // Last month total for growth calculation
        $lastMonthTotal = (clone $query)
            ->whereMonth('orders.created_at', Carbon::now()->subMonth()->month)
            ->sum('order_product_items.quantity');

        // Calculate growth percentage
        $percent = $lastMonthTotal > 0 ? 
            (($currentMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : 0;

        // Get monthly data for chart
        $monthlyData = collect(range(1, 12))->map(function($month) use ($query) {
            return (clone $query)
                ->whereMonth('orders.created_at', $month)
                ->sum('order_product_items.quantity');
        });

        return [
            'total' => (int) $currentMonthTotal,
            'percent' => round($percent, 2),
            'chart' => [
                'series' => $monthlyData->toArray(),
                'categories' => self::CHART_CATEGORIES
            ]
        ];
    }

    private function getBalanceData(?string $vendorId, float $share): array
    {
        $query = Order::where('status', 'completed');
        
        if ($vendorId) {
            $query->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId));
        }

        $currentMonthTotal = (clone $query)
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('total_amount') * $share;

        $lastMonthTotal = (clone $query)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->sum('total_amount') * $share;

        $percent = $lastMonthTotal > 0 ? 
            (($currentMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : 0;

        $monthlyData = collect(range(1, 12))->map(function($month) use ($query, $share) {
            return (clone $query)
                ->whereMonth('created_at', $month)
                ->sum('total_amount') * $share;
        });

        return [
            'total' => round($currentMonthTotal, 2),
            'percent' => round($percent, 2),
            'chart' => [
                'series' => $monthlyData->toArray(),
                'categories' => self::CHART_CATEGORIES
            ]
        ];
    }

    private function getProfitData(?string $vendorId, float $share): array
    {
        $query = Order::where('status', 'completed');
        
        if ($vendorId) {
            $query->whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId));
        }

        // Get current month data
        $currentMonthQuery = (clone $query)->whereMonth('created_at', Carbon::now()->month);
        $currentMonthRevenue = $currentMonthQuery->sum('total_amount');
        $currentMonthCosts = DB::table('order_product_items')
            ->join('products', 'order_product_items.product_id', '=', 'products.id')
            ->join('orders', 'order_product_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->when($vendorId, fn($q) => $q->where('products.vendor_id', $vendorId))
            ->whereMonth('orders.created_at', Carbon::now()->month)
            ->sum(DB::raw('order_product_items.quantity * products.price'));
        
        $currentMonthProfit = ($currentMonthRevenue - $currentMonthCosts) * $share;

        // Get last month data for growth calculation
        $lastMonthQuery = (clone $query)->whereMonth('created_at', Carbon::now()->subMonth()->month);
        $lastMonthRevenue = $lastMonthQuery->sum('total_amount');
        $lastMonthCosts = DB::table('order_product_items')
            ->join('products', 'order_product_items.product_id', '=', 'products.id')
            ->join('orders', 'order_product_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->when($vendorId, fn($q) => $q->where('products.vendor_id', $vendorId))
            ->whereMonth('orders.created_at', Carbon::now()->subMonth()->month)
            ->sum(DB::raw('order_product_items.quantity * products.price'));
        
        $lastMonthProfit = ($lastMonthRevenue - $lastMonthCosts) * $share;

        // Calculate growth percentage
        $percent = $lastMonthProfit != 0 ? 
            (($currentMonthProfit - $lastMonthProfit) / abs($lastMonthProfit)) * 100 : 0;

        // Get monthly data for chart
        $monthlyData = collect(range(1, 12))->map(function($month) use ($query, $vendorId, $share) {
            $monthlyRevenue = (clone $query)
                ->whereMonth('created_at', $month)
                ->sum('total_amount');
            
            $monthlyCosts = DB::table('order_product_items')
                ->join('products', 'order_product_items.product_id', '=', 'products.id')
                ->join('orders', 'order_product_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->when($vendorId, fn($q) => $q->where('products.vendor_id', $vendorId))
                ->whereMonth('orders.created_at', $month)
                ->sum(DB::raw('order_product_items.quantity * products.price'));

            return max(($monthlyRevenue - $monthlyCosts) * $share, 0); // Ensure no negative profits
        });

        return [
            'total' => round(max($currentMonthProfit, 0), 2), // Ensure no negative profits
            'percent' => round($percent, 2),
            'chart' => [
                'series' => $monthlyData->toArray(),
                'categories' => self::CHART_CATEGORIES
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
}
