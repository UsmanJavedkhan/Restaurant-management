<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'order_number' => 'ORD-'.fake()->unique()->numerify('########'), 'request_id' => fake()->uuid(), 'customer_name' => fake()->name(), 'customer_email' => fake()->safeEmail(), 'customer_phone' => '03001234567', 'order_type' => 'pickup', 'payment_method' => 'cash_on_pickup', 'subtotal' => 850, 'total' => 850, 'status_history' => [['status' => 'pending', 'at' => now()->toIso8601String()]], 'order_status' => 'pending', 'payment_status' => 'pending'];
    }
}
