<?php

namespace App\Models;

use Database\Factories\DeliveryAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'city', 'fee', 'is_active'])]
class DeliveryArea extends Model
{
    /** @use HasFactory<DeliveryAreaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
