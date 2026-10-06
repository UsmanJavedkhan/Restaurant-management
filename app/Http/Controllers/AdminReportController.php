<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\RestaurantService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminReportController extends Controller
{
    public function index(Request $request, RestaurantService $restaurant): JsonResponse
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $timezone = $restaurant->settings()->timezone;
        $from = CarbonImmutable::parse($data['from'] ?? now($timezone)->subDays(29)->toDateString(), $timezone)->startOfDay();
        $to = CarbonImmutable::parse($data['to'] ?? now($timezone)->toDateString(), $timezone)->endOfDay();
        if ($from->diffInDays($to) > 367) {
            throw ValidationException::withMessages(['from' => 'Please select a date range of one year or less.']);
        }
        $utcFrom = $from->utc();
        $utcTo = $to->utc();
        $orders = Order::whereBetween('created_at', [$utcFrom, $utcTo]);
        $sales = (clone $orders)->whereIn('order_status', ['delivered', 'completed']);
        $daily = (clone $sales)->get(['created_at', 'total'])->groupBy(fn (Order $order) => $order->created_at->timezone($timezone)->toDateString())->map(fn ($group, $date) => ['date' => $date, 'revenue' => (float) $group->sum('total'), 'orders' => $group->count()])->sortBy('date')->values();
        $rankings = OrderItem::whereHas('order', fn ($query) => $query->whereBetween('created_at', [$utcFrom, $utcTo])->whereIn('order_status', ['delivered', 'completed']))->selectRaw('product_id, product_name, SUM(quantity) as quantity, SUM(total) as revenue')->groupBy('product_id', 'product_name')->orderByDesc('quantity')->get();
        $customers = User::where('role', 'customer')->withSum(['orders' => fn ($query) => $query->whereBetween('created_at', [$utcFrom, $utcTo])->whereIn('order_status', ['delivered', 'completed'])], 'total')->orderByDesc('orders_sum_total')->limit(10)->get(['id', 'name', 'email']);
        $soldIds = $rankings->pluck('product_id');

        return response()->json(['data' => [
            'from' => $from->toDateString(), 'to' => $to->toDateString(), 'revenue' => (float) (clone $sales)->sum('total'),
            'cash_collected' => (float) (clone $orders)->where('payment_status', 'paid')->sum('total'),
            'orders' => (clone $orders)->count(), 'cancelled' => (clone $orders)->where('order_status', 'cancelled')->count(),
            'pending' => Order::where('order_status', 'pending')->count(), 'preparing' => Order::where('order_status', 'preparing')->count(),
            'delivered' => (clone $orders)->whereIn('order_status', ['delivered', 'completed'])->count(), 'customers' => User::where('role', 'customer')->count(), 'products' => Product::count(),
            'daily_sales' => $daily, 'best_sellers' => $rankings->take(10)->values(), 'least_sellers' => $rankings->sortBy('quantity')->take(10)->values(),
            'unsold_products' => Product::whereNotIn('id', $soldIds)->orderBy('name')->limit(10)->get(['id', 'name']), 'valuable_customers' => $customers,
            'today_orders' => Order::whereBetween('created_at', [now($timezone)->startOfDay()->utc(), now($timezone)->endOfDay()->utc()])->count(),
            'today_revenue' => (float) Order::whereBetween('created_at', [now($timezone)->startOfDay()->utc(), now($timezone)->endOfDay()->utc()])->whereIn('order_status', ['delivered', 'completed'])->sum('total'),
            'total_sales' => (float) Order::whereIn('order_status', ['delivered', 'completed'])->sum('total'),
        ]]);
    }
}
