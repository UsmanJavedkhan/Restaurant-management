<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->bothify('SAVE####')), 'discount_type' => 'percentage', 'discount_value' => 20, 'minimum_order' => 1500, 'maximum_discount' => 500, 'is_active' => true];
    }
}
