<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'order_number' => $this->order_number, 'customer_name' => $this->customer_name, 'customer_email' => $this->customer_email, 'customer_phone' => $this->customer_phone,
            'order_type' => $this->order_type, 'order_status' => $this->order_status, 'payment_method' => $this->payment_method, 'payment_status' => $this->payment_status,
            'delivery_address' => $this->delivery_address, 'customer_note' => $this->customer_note, 'status_history' => $this->status_history, 'created_at' => $this->created_at->toIso8601String(),
            'subtotal' => (float) $this->subtotal, 'discount' => (float) $this->discount, 'delivery_fee' => (float) $this->delivery_fee, 'tax' => (float) $this->tax, 'total' => (float) $this->total, 'coupon_code' => $this->coupon_code,
            'items' => $this->whenLoaded('items'), 'payment' => $this->whenLoaded('payment'),
        ];
    }
}
