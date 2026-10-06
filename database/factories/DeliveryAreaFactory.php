<?php

namespace Database\Factories;

use App\Models\DeliveryArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliveryArea> */
class DeliveryAreaFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->city(), 'city' => 'Lahore', 'fee' => 150, 'is_active' => true];
    }
}
