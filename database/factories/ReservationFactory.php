<?php

namespace Database\Factories;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reservation> */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->name(), 'phone' => '03001234567', 'date' => now()->addDay()->toDateString(), 'time' => '19:00', 'guests' => 2];
    }
}
