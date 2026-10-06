<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['order_number', 'user_id', 'request_id', 'customer_name', 'customer_email', 'customer_phone', 'delivery_address', 'order_type', 'subtotal', 'discount', 'delivery_fee', 'tax', 'total', 'coupon_id', 'coupon_code', 'payment_method', 'payment_status', 'order_status', 'customer_note', 'status_history'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'delivery_address' => 'array',
            'status_history' => 'array',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
