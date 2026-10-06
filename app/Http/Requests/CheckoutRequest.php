<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->is_active;
    }

    public function rules(): array
    {
        $required = $this->routeIs('orders.store') ? 'required' : 'nullable';

        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.variation' => ['nullable', 'string', 'max:80'],
            'items.*.addons' => ['sometimes', 'array', 'max:10'],
            'items.*.addons.*' => ['string', 'max:80'],
            'order_type' => ['required', Rule::in(['delivery', 'pickup'])],
            'delivery_area_id' => ['required_if:order_type,delivery', 'nullable', 'integer'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'customer_name' => [$required, 'string', 'max:80'],
            'customer_email' => [$required, 'email', 'max:255'],
            'customer_phone' => [$required, 'regex:/^[+0-9 ()-]{7,20}$/'],
            'delivery_address' => [$this->routeIs('orders.store') ? 'required_if:order_type,delivery' : 'nullable', 'nullable', 'array'],
            'delivery_address.address' => ['required_with:delivery_address', 'string', 'max:500'],
            'delivery_address.landmark' => ['nullable', 'string', 'max:150'],
            'delivery_address.instructions' => ['nullable', 'string', 'max:500'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => [$required, Rule::in(['cash_on_delivery', 'cash_on_pickup'])],
            'request_id' => [$required, 'uuid'],
        ];
    }
}
