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


class AnalyticsController extends Controller
{
    public function getWidgetSummary(): JsonResponse
    {
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        return response()->json([
            'sales' => $this->getSalesData($now, $lastMonth),
            'users' => $this->getUsersData($now, $lastMonth),
            'orders' => $this->getOrdersData($now, $lastMonth),
            'products' => $this->getProductsData($now, $lastMonth),
        ]);
    }

    public function getCurrentVisits(): JsonResponse
    {
        // Get orders by region/city from shipping addresses
        $visits = DB::table('order_shippings')
            ->join('orders', 'orders.id', '=', 'order_shippings.order_id')
            ->whereDate('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('city as region, COUNT(*) as value')
            ->groupBy('city')
            ->get()
            ->map(function ($visit) {
                return [
                    'label' => $visit->region,
                    'value' => $visit->value
                ];
            });

        return response()->json(['series' => $visits]);
    }

    public function getWebsiteVisits(): JsonResponse
    {
        $months = collect(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);

        // Get completed vs pending orders over months
        $completed = $this->getOrdersByStatus('completed');
        $pending = $this->getOrdersByStatus('pending');

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

        return response()->json(['list' => $orders]);
    }

    private function getSalesData($now, $lastMonth): array
    {
        $currentSales = Order::whereBetween('created_at', [$lastMonth, $now])
            ->where('status', 'completed')
            ->sum('total_amount');

        $previousSales = Order::whereBetween(
            'created_at',
            [$lastMonth->copy()->subMonth(), $lastMonth]
        )->where('status', 'completed')
            ->sum('total_amount');

        $percentChange = $previousSales ?
            (($currentSales - $previousSales) / $previousSales) * 100 : 0;

        return [
            'total' => $currentSales,
            'percent' => round($percentChange, 2),
            'chart' => [
                'series' => $this->getMonthlySeries('total_amount'),
                'categories' => $this->getMonthlyCategories(),
            ]
        ];
    }

    private function getUsersData($now, $lastMonth): array
    {
        $currentUsers = User::whereBetween('created_at', [$lastMonth, $now])->count();
        $previousUsers = User::whereBetween(
            'created_at',
            [$lastMonth->copy()->subMonth(), $lastMonth]
        )->count();

        $percentChange = $previousUsers ?
            (($currentUsers - $previousUsers) / $previousUsers) * 100 : 0;

        return [
            'total' => $currentUsers,
            'percent' => round($percentChange, 2),
            'chart' => [
                'series' => $this->getMonthlyUserGrowth(),
                'categories' => $this->getMonthlyCategories(),
            ]
        ];
    }

    private function getOrdersData($now, $lastMonth): array
    {
        $currentOrders = Order::whereBetween('created_at', [$lastMonth, $now])->count();
        $previousOrders = Order::whereBetween(
            'created_at',
            [$lastMonth->copy()->subMonth(), $lastMonth]
        )->count();

        $percentChange = $previousOrders ?
            (($currentOrders - $previousOrders) / $previousOrders) * 100 : 0;

        return [
            'total' => $currentOrders,
            'percent' => round($percentChange, 2),
            'chart' => [
                'series' => $this->getMonthlyOrderCounts(),
                'categories' => $this->getMonthlyCategories(),
            ]
        ];
    }

    private function getProductsData($now, $lastMonth): array
    {
        $currentProducts = Product::whereBetween('created_at', [$lastMonth, $now])->count();
        $previousProducts = Product::whereBetween(
            'created_at',
            [$lastMonth->copy()->subMonth(), $lastMonth]
        )->count();

        $percentChange = $previousProducts ?
            (($currentProducts - $previousProducts) / $previousProducts) * 100 : 0;

        return [
            'total' => $currentProducts,
            'percent' => round($percentChange, 2),
            'chart' => [
                'series' => $this->getMonthlyProductCounts(),
                'categories' => $this->getMonthlyCategories(),
            ]
        ];
    }

    private function getMonthlySeries(string $field): array
    {
        return Order::selectRaw("DATE_FORMAT(created_at, '%b') as month, SUM($field) as total")
            ->whereYear('created_at', date('Y'))
            ->where('status', 'completed')
            ->groupBy('month')
            ->orderBy('created_at')
            ->pluck('total')
            ->toArray();
    }

    private function getMonthlyUserGrowth(): array
    {
        return User::selectRaw("DATE_FORMAT(created_at, '%b') as month, COUNT(*) as total")
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('created_at')
            ->pluck('total')
            ->toArray();
    }

    private function getMonthlyOrderCounts(): array
    {
        return Order::selectRaw("DATE_FORMAT(created_at, '%b') as month, COUNT(*) as total")
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('created_at')
            ->pluck('total')
            ->toArray();
    }

    private function getMonthlyProductCounts(): array
    {
        return Product::selectRaw("DATE_FORMAT(created_at, '%b') as month, COUNT(*) as total")
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('created_at')
            ->pluck('total')
            ->toArray();
    }

    private function getMonthlyCategories(): array
    {
        return Order::selectRaw("DATE_FORMAT(created_at, '%b') as month")
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('created_at')
            ->pluck('month')
            ->toArray();
    }

    private function getOrdersByStatus(string $status): array
    {
        return Order::selectRaw("DATE_FORMAT(created_at, '%b') as month, COUNT(*) as count")
            ->whereYear('created_at', date('Y'))
            ->where('status', $status)
            ->groupBy('month')
            ->orderBy('created_at')
            ->pluck('count', 'month')
            ->toArray();
    }
}
