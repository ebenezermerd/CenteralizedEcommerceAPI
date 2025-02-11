<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Review;
use App\Models\Analytics;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    public function getWidgetSummary(): JsonResponse
    {
        try {
            $now = Carbon::now();
            $lastMonth = Carbon::now()->subMonth();

            Log::info('Fetching widget summary data', [
                'now' => $now,
                'lastMonth' => $lastMonth
            ]);

            $result = [
                'weeklySales' => [
                    'title' => 'Weekly Sales',
                    'total' => $this->getSalesData($now, $lastMonth)['total'],
                    'percent' => $this->getSalesData($now, $lastMonth)['percent'],
                    'chart' => [
                        'series' => $this->getMonthlySeries('total_amount'),
                        'categories' => $this->getMonthlyCategories()
                    ]
                ],
                'newUsers' => [
                    'title' => 'New Users',
                    'total' => $this->getUsersData($now, $lastMonth)['total'],
                    'percent' => $this->getUsersData($now, $lastMonth)['percent'],
                    'chart' => [
                        'series' => $this->getMonthlySeries('users'),
                        'categories' => $this->getMonthlyCategories()
                    ]
                ],
                'purchaseOrders' => [
                    'title' => 'Purchase Orders',
                    'total' => $this->getOrdersData($now, $lastMonth)['total'],
                    'percent' => $this->getOrdersData($now, $lastMonth)['percent'],
                    'chart' => [
                        'series' => $this->getMonthlySeries('orders'),
                        'categories' => $this->getMonthlyCategories()
                    ]
                ],
                'messages' => [
                    'title' => 'Messages',
                    'total' => $this->getProductsData($now, $lastMonth)['total'],
                    'percent' => $this->getProductsData($now, $lastMonth)['percent'],
                    'chart' => [
                        'series' => $this->getMonthlySeries('messages'),
                        'categories' => $this->getMonthlyCategories()
                    ]
                ]
            ];

            Log::info('Widget summary data fetched successfully', ['result' => $result]);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Analytics error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch analytics data'], 500);
        }
    }

    public function getCurrentVisits(): JsonResponse
    {
        Log::info('Fetching current visits data');

        try {
            $visits = DB::table('order_shippings')
                ->join('orders', 'orders.id', '=', 'order_shippings.order_id')
                ->whereDate('orders.created_at', '>=', Carbon::now()->subDays(30))
                ->select(DB::raw('
                    SUBSTRING_INDEX(
                        SUBSTRING_INDEX(full_address, ",", 2),
                        ",",
                        -1
                    ) as region,
                    COUNT(*) as value
                '))
                ->groupBy('region')
                ->get()
                ->map(function ($visit) {
                    return [
                        'label' => trim($visit->region), // Remove any extra spaces
                        'value' => $visit->value
                    ];
                })
                ->filter(function ($visit) {
                    return !empty($visit['label']); // Filter out empty cities
                })
                ->values();

            Log::info('Current visits data fetched', ['visits' => $visits]);
            return response()->json(['series' => $visits]);

        } catch (\Exception $e) {
            Log::error('Error fetching current visits', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['series' => []], 500);
        }
    }

    public function getWebsiteVisits(): JsonResponse
    {
        Log::info('Fetching website visits data');

        $months = collect(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
        $completed = $this->getOrdersByStatus('completed');
        $pending = $this->getOrdersByStatus('pending');

        Log::info('Website visits data fetched', [
            'completed' => $completed,
            'pending' => $pending
        ]);

        return response()->json([
            'categories' => $months,
            'series' => [
                ['name' => 'Completed Orders', 'data' => array_values($completed)],
                ['name' => 'Pending Orders', 'data' => array_values($pending)],
            ]
        ]);
    }

    public function getOrderTimeline(): JsonResponse
    {
        Log::info('Fetching order timeline');

        $orders = Order::with(['customer', 'productItems'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'type' => $order->status,
                    'title' => "Order #{$order->order_number}",
                    'customer' => $order->customer->name,
                    'amount' => $order->total_amount,
                    'time' => $order->created_at->diffForHumans(),
                    'items' => $order->productItems->count()
                ];
            });

        Log::info('Order timeline fetched', ['orders' => $orders]);
        return response()->json(['list' => $orders]);
    }

    private function getSalesData($now, $lastMonth): array
    {
        try {
            $currentSales = Order::whereBetween('created_at', [$lastMonth, $now])
                ->where('status', 'completed')
                ->sum('total_amount');

            $previousSales = Order::whereBetween('created_at', [
                $lastMonth->copy()->subMonth(),
                $lastMonth
            ])->where('status', 'completed')
                ->sum('total_amount');

            $percentChange = $previousSales > 0
                ? (($currentSales - $previousSales) / $previousSales) * 100
                : ($currentSales > 0 ? 100 : 0);

            return [
                'total' => round($currentSales, 2),
                'percent' => round($percentChange, 2)
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating sales data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['total' => 0, 'percent' => 0];
        }
    }

    private function getUsersData($now, $lastMonth): array
    {
        try {
            $currentUsers = User::whereBetween('created_at', [$lastMonth, $now])->count();
            $previousUsers = User::whereBetween('created_at', [
                $lastMonth->copy()->subMonth(),
                $lastMonth
            ])->count();

            $percentChange = $previousUsers > 0
                ? (($currentUsers - $previousUsers) / $previousUsers) * 100
                : ($currentUsers > 0 ? 100 : 0);

            return [
                'total' => $currentUsers,
                'percent' => round($percentChange, 2)
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating users data', ['error' => $e->getMessage()]);
            return ['total' => 0, 'percent' => 0];
        }
    }

    private function getOrdersData($now, $lastMonth): array
    {
        try {
            $currentOrders = Order::whereBetween('created_at', [$lastMonth, $now])->count();
            $previousOrders = Order::whereBetween('created_at', [
                $lastMonth->copy()->subMonth(),
                $lastMonth
            ])->count();

            $percentChange = $previousOrders > 0
                ? (($currentOrders - $previousOrders) / $previousOrders) * 100
                : ($currentOrders > 0 ? 100 : 0);

            return [
                'total' => $currentOrders,
                'percent' => round($percentChange, 2)
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating orders data', ['error' => $e->getMessage()]);
            return ['total' => 0, 'percent' => 0];
        }
    }

    private function getProductsData($now, $lastMonth): array
    {
        try {
            $currentProducts = Product::whereBetween('created_at', [$lastMonth, $now])->count();
            $previousProducts = Product::whereBetween('created_at', [
                $lastMonth->copy()->subMonth(),
                $lastMonth
            ])->count();

            $percentChange = $previousProducts > 0
                ? (($currentProducts - $previousProducts) / $previousProducts) * 100
                : ($currentProducts > 0 ? 100 : 0);

            // Get total active products
            $totalActive = Product::where('publish', 'published')
                ->where('inventoryType', '!=', 'out_of_stock')
                ->count();

            return [
                'total' => $totalActive,
                'percent' => round($percentChange, 2)
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating products data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['total' => 0, 'percent' => 0];
        }
    }

    private function getMonthlySeries(string $type): array
    {
        try {
            $months = collect(range(1, 12))->map(function ($month) use ($type) {
                $startDate = now()->startOfYear()->addMonths($month - 1);
                $endDate = $startDate->copy()->endOfMonth();

                switch ($type) {
                    case 'total_amount':
                        return Order::whereBetween('created_at', [$startDate, $endDate])
                            ->where('status', 'completed')
                            ->sum('total_amount');
                    case 'users':
                        return User::whereBetween('created_at', [$startDate, $endDate])->count();
                    case 'orders':
                        return Order::whereBetween('created_at', [$startDate, $endDate])->count();
                    case 'messages':
                        // Implement message count if you have a messages table
                        return 0;
                    default:
                        return 0;
                }
            })->values()->all();

            return $months;
        } catch (\Exception $e) {
            Log::error('Error getting monthly series', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return array_fill(0, 12, 0);
        }
    }

    private function getMonthlyCategories(): array
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    }

    private function getOrdersByStatus(string $status): array
    {
        try {
            return Order::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->where('status', $status)
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->pluck('count', 'month')
                ->map(function ($count, $month) {
                    return (int) $count;
                })
                ->all();
        } catch (\Exception $e) {
            Log::error('Error getting orders by status', [
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            return array_fill(0, 12, 0);
        }
    }

    // Similar logging can be added to other private methods as needed
    // The rest of the methods remain unchanged
    // ...
}

