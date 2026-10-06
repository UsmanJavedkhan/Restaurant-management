<?php

namespace App\Models;

use Database\Factories\RestaurantSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'logo', 'phone', 'email', 'address', 'currency', 'timezone', 'minimum_order', 'delivery_charge', 'tax_percentage', 'accepting_orders', 'opening_hours'])]
class RestaurantSetting extends Model
{
    /** @use HasFactory<RestaurantSettingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'accepting_orders' => 'boolean',
            'minimum_order' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
        ];
    }
}
