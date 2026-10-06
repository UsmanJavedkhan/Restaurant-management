<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return ['category_id' => Category::factory(), 'name' => fake()->words(3, true), 'slug' => fake()->unique()->slug(), 'description' => fake()->sentence(), 'price' => 850, 'preparation_time' => 20, 'is_active' => true, 'is_available' => true];
    }
}
