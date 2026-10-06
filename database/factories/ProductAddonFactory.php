<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductAddon> */
class ProductAddonFactory extends Factory
{
    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'name' => fake()->unique()->word(), 'price' => 100, 'is_active' => true];
    }
}
