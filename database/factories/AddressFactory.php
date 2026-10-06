<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Address> */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'label' => 'Home', 'address' => fake()->streetAddress(), 'city' => 'Lahore', 'area' => 'Johar Town', 'phone' => '03001234567', 'is_default' => false];
    }
}
