<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()->is_active;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'], 'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('products')->ignore($this->route('record'))],
            'category_id' => ['required', 'integer', 'exists:categories,id'], 'description' => ['required', 'string', 'max:3000'],
            'image' => ['nullable', 'string', 'max:500', 'regex:#^(photo-[a-zA-Z0-9-]+|/storage/[a-zA-Z0-9/_.-]+|https://[^\s]+)$#'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lte:price', 'decimal:0,2'],
            'preparation_time' => ['required', 'integer', 'min:1', 'max:180'],
            'is_active' => ['required', 'boolean'], 'is_available' => ['required', 'boolean'], 'is_featured' => ['required', 'boolean'], 'is_deal' => ['required', 'boolean'],
            'deal_contents' => ['nullable', 'array', 'max:20'], 'deal_contents.*' => ['string', 'max:150'],
            'variations' => ['sometimes', 'array', 'max:10'], 'addons' => ['sometimes', 'array', 'max:10'],
            'variations.*.name' => ['required', 'string', 'max:80', 'distinct'], 'variations.*.price' => ['required', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'], 'variations.*.is_active' => ['sometimes', 'boolean'],
            'addons.*.name' => ['required', 'string', 'max:80', 'distinct'], 'addons.*.price' => ['required', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'], 'addons.*.is_active' => ['sometimes', 'boolean'],
        ];
    }
}
