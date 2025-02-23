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
        Log::info('Getting overview data');
        $user = auth()->user();
        
        $isSupplier = $user->hasRole('supplier');

        Log::info('User is supplier: ' . $isSupplier);
        try {
            return response()->json([
                'widgetSummary' => $this->getWidgetSummary($isSupplier ? $user->id : null),
                'salesOverview' => $this->getSalesOverview($isSupplier ? $user->id : null),
                'yearlySales' => $this->getYearlySales($isSupplier ? $user->id : null),
                'latestProducts' => $this->getLatestProducts($isSupplier ? $user->id : null),
                'bestSalesman' => $this->getBestSalesman($isSupplier ? $user->id : null),
                'saleByGender' => $this->getSaleByGender($isSupplier ? $user->id : null),
                'currentBalance' => $this->getCurrentBalance($isSupplier ? $user->id : null),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching ecommerce overview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch overview data'], 500);
        }
    }

    private function getWidgetSummary(?string $vendorId = null): array
    {
        $query = Order::query();
        if ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }

        $totalSales = $query->sum('total_amount');
        $vendorShare = $vendorId ? ($totalSales * self::VENDOR_SHARE) : $totalSales;

        return [
            'totalSales' => [
                'total' => $vendorShare,
                'percent' => $this->calculateGrowthPercent($query, 'total_amount'),
                'chart' => $this->getChartData($query, 'total_amount', $vendorId)
            ],
            'totalOrders' => [
                'total' => $this->calculateOrderTotal($vendorId),
                'percent' => $this->calculateGrowthPercent($query, 'total_amount'),
                'chart' => $this->getChartData($query, 'total_amount', $vendorId)
            ],
            'totalProducts' => [
                'total' => $this->calculateProductTotal($vendorId),
                'percent' => $this->calculateGrowthPercent($query, 'total_amount'),
                'chart' => $this->getChartData($query, 'total_amount', $vendorId)
            ],
        ];
    }

    private function getSalesOverview(?string $vendorId = null): array
    {
        $types = ['expenses', 'income', 'profit'];
        $months = range(1, 12);
        
        return array_map(function($type) use ($months, $vendorId) {
            $data = collect($months)->map(function($month) use ($type, $vendorId) {
                $query = Order::whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', $month);

                if ($vendorId) {
                    $query->whereHas('items.product', function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    });
                }

                $amount = $query->sum('total_amount');
                
                return match($type) {
                    'expenses' => $amount * ($vendorId ? self::VENDOR_SHARE : 0.9),
                    'income' => $amount,
                    'profit' => $amount * ($vendorId ? self::VENDOR_SHARE : self::COMPANY_SHARE)
                };
            })->toArray();

            return [
                'label' => ucfirst($type),
                'value' => array_sum($data),
                'data' => $data
            ];
        }, $types);
    }

    private function getCurrentBalance(?string $vendorId = null): array
    {
        $query = Order::query();
        if ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }

        $totalAmount = $query->sum('total_amount');
        $share = $vendorId ? self::VENDOR_SHARE : 1;

        return [
            'currentBalance' => $totalAmount * $share,
            'earning' => $this->calculateEarnings($vendorId),
            'refunded' => $this->calculateRefunds($vendorId),
            'orderTotal' => $this->calculateOrderTotal($vendorId)
        ];
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

    private function getBestSalesman(?string $vendorId = null): array
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

    private function getYearlySales(?string $vendorId = null): array
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
                            'data' => $this->getMonthlyData($lastYear, 'income', $vendorId)
                        ],
                        [
                            'name' => 'Total Expenses',
                            'data' => $this->getMonthlyData($lastYear, 'expenses', $vendorId)
                        ]
                    ]
                ],
                [
                    'name' => (string)$currentYear,
                    'data' => [
                        [
                            'name' => 'Total Income',
                            'data' => $this->getMonthlyData($currentYear, 'income', $vendorId)
                        ],
                        [
                            'name' => 'Total Expenses',
                            'data' => $this->getMonthlyData($currentYear, 'expenses', $vendorId)
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getMonthlyData(int $year, string $type, ?string $vendorId = null): array
    {
        return collect(range(1, 12))->map(function ($month) use ($year, $type, $vendorId) {
            $amount = Order::where('status', 'completed')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month);

            if ($vendorId) {
                $amount->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                });
            }

            $amount = $amount->sum('total_amount');

            $value = match($type) {
                'expenses' => $amount * 0.9, // 90% to vendors
                'income' => $amount,
                default => $amount * 0.1 // 10% platform profit
            };

            return round($value, 2); // Round to 2 decimal places
        })->toArray();
    }

    private function calculateEarnings(?string $vendorId = null): float
    {
        $query = Order::query();
        if ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }

        return $query->sum('total_amount') * ($vendorId ? self::VENDOR_SHARE : 1);
    }

    private function calculateRefunds(?string $vendorId = null): float
    {
        $query = Order::where('status', 'refunded');
        if ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }

        return $query->sum('total_amount');
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
}
