<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryArea;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCatalogController extends Controller
{
    private function model(string $resource): string
    {
        return match ($resource) {
            'categories' => Category::class, 'products', 'deals' => Product::class, 'coupons' => Coupon::class, 'delivery-areas' => DeliveryArea::class,
            default => abort(404),
        };
    }

    public function index(Request $request, string $resource): JsonResponse
    {
        $query = ($this->model($resource))::query();
        if (in_array($resource, ['products', 'deals'], true)) {
            $query->with(['category', 'variations', 'addons']);
            if ($resource === 'deals') {
                $query->where('is_deal', true);
            }
        }
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? '';
        if ($search !== '') {
            $query->where($resource === 'coupons' ? 'code' : 'name', 'like', '%'.$search.'%');
        }

        return response()->json($query->latest('id')->paginate(20));
    }

    public function show(string $resource, int $record): JsonResponse
    {
        $model = ($this->model($resource))::findOrFail($record);
        if ($model instanceof Product) {
            $model->load(['variations', 'addons']);
        }

        return response()->json(['data' => $model]);
    }

    /** @return array<string, mixed> */
    private function data(Request $request, string $resource, ?int $record = null): array
    {
        if (in_array($resource, ['products', 'deals'], true)) {
            $form = ProductRequest::createFrom($request);
            $form->setContainer(app())->setRedirector(app('redirect'));
            $form->validateResolved();

            return $form->validated();
        }
        $rules = match ($resource) {
            'categories' => ['name' => ['required', 'string', 'max:100'], 'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('categories')->ignore($record)], 'description' => ['nullable', 'string', 'max:1000'], 'image' => ['nullable', 'string', 'max:500'], 'sort_order' => ['required', 'integer', 'min:0', 'max:1000'], 'is_active' => ['required', 'boolean']],
            'delivery-areas' => ['name' => ['required', 'string', 'max:100', Rule::unique('delivery_areas')->ignore($record)], 'city' => ['required', 'string', 'max:100'], 'fee' => ['nullable', 'numeric', 'min:0', 'max:99999', 'decimal:0,2'], 'is_active' => ['required', 'boolean']],
            'coupons' => ['code' => ['required', 'regex:/^[A-Z0-9_-]{3,50}$/', Rule::unique('coupons')->ignore($record)], 'discount_type' => ['required', Rule::in(['percentage', 'fixed'])], 'discount_value' => ['required', 'numeric', 'min:0', $request->input('discount_type') === 'percentage' ? 'max:100' : 'max:999999', 'decimal:0,2'], 'minimum_order' => ['required', 'numeric', 'min:0', 'max:999999'], 'maximum_discount' => ['nullable', 'numeric', 'min:0', 'max:999999'], 'start_date' => ['nullable', 'date'], 'expiry_date' => ['nullable', 'date', ...($request->filled('start_date') ? ['after_or_equal:start_date'] : [])], 'usage_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'], 'is_active' => ['required', 'boolean']],
        };

        return $request->validate($rules);
    }

    private function save(Request $request, string $resource, ?int $record): Model
    {
        $data = $this->data($request, $resource, $record);

        return DB::transaction(function () use ($data, $resource, $record): Model {
            $class = $this->model($resource);
            $model = $record ? $class::lockForUpdate()->findOrFail($record) : new $class;
            $model->fill(collect($data)->except(['variations', 'addons'])->all())->save();
            if ($model instanceof Product) {
                foreach (['variations', 'addons'] as $relation) {
                    $model->{$relation}()->delete();
                    $model->{$relation}()->createMany(collect($data[$relation] ?? [])->map(fn ($item) => collect($item)->only(['name', 'price', 'is_active'])->all())->all());
                }
                $model->load(['variations', 'addons']);
            }

            return $model;
        });
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        return response()->json(['data' => $this->save($request, $resource, null)], 201);
    }

    public function update(Request $request, string $resource, int $record): JsonResponse
    {
        return response()->json(['data' => $this->save($request, $resource, $record)]);
    }

    public function destroy(string $resource, int $record): JsonResponse
    {
        $model = ($this->model($resource))::findOrFail($record);
        if ($model instanceof Category && $model->products()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['category' => 'This category contains products. Disable it or move its products first.']);
        }
        $model->delete();

        return response()->json(['message' => 'Record deleted.']);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $path = $request->file('image')->store('products', 'public');

        return response()->json(['image' => '/storage/'.$path], 201);
    }
}
