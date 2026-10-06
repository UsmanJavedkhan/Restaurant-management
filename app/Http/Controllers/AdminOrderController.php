<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminOrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(OrderService::STATUSES)], 'search' => ['nullable', 'string', 'max:100']]);
        $query = Order::with(['items', 'payment']);
        if (! empty($data['status'])) {
            $query->where('order_status', $data['status']);
        }
        if (! empty($data['search'])) {
            $query->where(fn ($query) => $query->where('order_number', 'like', '%'.$data['search'].'%')->orWhere('customer_name', 'like', '%'.$data['search'].'%'));
        }

        return OrderResource::collection($query->latest('id')->paginate(20));
    }

    public function update(Request $request, Order $order, OrderService $service): OrderResource
    {
        $data = $request->validate(['order_status' => ['required', Rule::in(OrderService::STATUSES)]]);

        return new OrderResource($service->changeStatus($order, $data['order_status']));
    }

    public function payments(): JsonResponse
    {
        return response()->json(Payment::with('order:id,order_number,customer_name,order_status')->latest('id')->paginate(20));
    }

    public function payment(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate(['payment_status' => ['required', Rule::in(['pending', 'paid', 'refunded'])]]);

        return DB::transaction(function () use ($order, $data): JsonResponse {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if (($order->order_status === 'cancelled' && $data['payment_status'] === 'paid') || ($data['payment_status'] === 'refunded' && $order->payment_status !== 'paid')) {
                throw ValidationException::withMessages(['payment_status' => 'This payment status change is not allowed.']);
            }
            $order->update(['payment_status' => $data['payment_status']]);
            $order->payment()->update(['payment_status' => $data['payment_status'], 'paid_at' => $data['payment_status'] === 'paid' ? now() : null]);

            return response()->json(['data' => $order->payment()->first()]);
        });
    }
}
