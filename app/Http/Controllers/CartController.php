<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['items' => Cart::where('user_id', $request->user()->id)->first()?->items ?? []]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['items' => ['present', 'array', 'max:50'], 'items.*.id' => ['required', 'integer'], 'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'], 'items.*.variation' => ['nullable', 'string', 'max:80'], 'items.*.addons' => ['sometimes', 'array', 'max:10'], 'items.*.addons.*' => ['string', 'max:80']]);
        $items = collect($data['items'])->map(fn ($item) => collect($item)->only(['id', 'quantity', 'variation', 'addons'])->all())->all();
        Cart::updateOrCreate(['user_id' => $request->user()->id], ['items' => $items]);

        return response()->json(['items' => $items]);
    }
}
