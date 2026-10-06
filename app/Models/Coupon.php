<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'discount_type', 'discount_value', 'minimum_order', 'maximum_discount', 'start_date', 'expiry_date', 'usage_limit', 'used_count', 'is_active'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_order' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'start_date' => 'date',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
