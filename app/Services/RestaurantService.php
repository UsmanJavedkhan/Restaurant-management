<?php

namespace App\Services;

use App\Models\RestaurantSetting;
use Carbon\CarbonImmutable;

class RestaurantService
{
    public function settings(): RestaurantSetting
    {
        return RestaurantSetting::firstOrCreate(['id' => 1], [
            'opening_hours' => array_fill(0, 7, ['enabled' => true, 'open' => '12:00', 'close' => '23:00']),
        ]);
    }

    public function isOpen(?RestaurantSetting $settings = null, ?CarbonImmutable $moment = null): bool
    {
        $settings ??= $this->settings();
        if (! $settings->accepting_orders) {
            return false;
        }
        $now = $moment ?? CarbonImmutable::now($settings->timezone);
        $hours = $settings->opening_hours;
        $today = $hours[$now->dayOfWeek] ?? null;
        $previous = $hours[$now->subDay()->dayOfWeek] ?? null;
        $time = $now->format('H:i');
        if ($today && $today['enabled']) {
            if ($today['open'] === $today['close']) {
                return true;
            }
            if ($today['open'] < $today['close'] && $time >= $today['open'] && $time < $today['close']) {
                return true;
            }
            if ($today['open'] > $today['close'] && $time >= $today['open']) {
                return true;
            }
        }

        return $previous && $previous['enabled'] && $previous['open'] > $previous['close'] && $time < $previous['close'];
    }
}
