<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return ['order_id' => Order::factory(), 'payment_method' => 'cash_on_pickup', 'amount' => 850, 'payment_status' => 'pending'];
    }
}
