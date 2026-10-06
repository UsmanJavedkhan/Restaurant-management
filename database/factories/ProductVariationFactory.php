<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariation> */
class ProductVariationFactory extends Factory
{
    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'name' => fake()->unique()->word(), 'price' => 700, 'is_active' => true];
    }
}
