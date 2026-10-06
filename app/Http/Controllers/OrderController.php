<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($request->user()->orders()->with(['items', 'payment'])->latest('id')->paginate(15));
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load(['items', 'payment']));
    }

    public function quote(CheckoutRequest $request, OrderService $service): JsonResponse
    {
        return response()->json(['data' => $service->quote($request->validated())]);
    }

    public function store(CheckoutRequest $request, OrderService $service): OrderResource
    {
        return new OrderResource($service->create($request->user(), $request->validated()));
    }
}
