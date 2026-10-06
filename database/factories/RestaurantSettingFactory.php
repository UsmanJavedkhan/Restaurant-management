<?php

namespace Database\Factories;

use App\Models\RestaurantSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RestaurantSetting> */
class RestaurantSettingFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Ember & Oak', 'opening_hours' => array_fill(0, 7, ['open' => '00:00', 'close' => '23:59', 'enabled' => true]), 'accepting_orders' => true, 'minimum_order' => 700, 'delivery_charge' => 150, 'tax_percentage' => 0, 'timezone' => 'Asia/Karachi'];
    }
}
