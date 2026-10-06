<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return ['order_id' => Order::factory(), 'product_id' => Product::factory(), 'product_name' => 'Burger', 'quantity' => 1, 'unit_price' => 850, 'total' => 850, 'addons' => []];
    }
}
