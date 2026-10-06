<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\Product;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:100'], 'deals' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1'], 'sort' => ['nullable', 'in:popular,low,high']]);
        $query = Product::with(['category', 'variations', 'addons'])->where('is_active', true)->whereHas('category', fn ($query) => $query->where('is_active', true));
        if (! empty($data['search'])) {
            $search = '%'.$data['search'].'%';
            $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('description', 'like', $search)->orWhereHas('category', fn ($query) => $query->where('name', 'like', $search)));
        }
        if (! empty($data['category'])) {
            $query->whereHas('category', fn ($query) => $query->where('slug', $data['category']));
        }
        if (! empty($data['deals'])) {
            $query->where('is_deal', true);
        }
        $sort = $data['sort'] ?? 'popular';
        if ($sort === 'popular') {
            $query->orderByDesc('is_featured');
        } else {
            $query->orderByRaw('COALESCE(discount_price, price) '.($sort === 'low' ? 'asc' : 'desc'));
        }

        return ProductResource::collection($query->orderBy('id')->paginate(24));
    }

    public function show(Product $product): ProductResource
    {
        $product->load(['category', 'variations', 'addons']);
        abort_unless($product->is_active && $product->category->is_active, 404);

        return new ProductResource($product);
    }

    public function configuration(RestaurantService $restaurant): JsonResponse
    {
        $settings = $restaurant->settings();

        return response()->json(['settings' => $settings, 'is_open' => $restaurant->isOpen($settings), 'categories' => Category::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(), 'delivery_areas' => DeliveryArea::where('is_active', true)->orderBy('name')->get()]);
    }
}
