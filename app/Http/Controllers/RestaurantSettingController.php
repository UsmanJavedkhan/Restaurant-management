<?php

namespace App\Http\Controllers;

use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantSettingController extends Controller
{
    public function show(RestaurantService $restaurant): JsonResponse
    {
        return response()->json(['data' => $restaurant->settings()]);
    }

    public function update(Request $request, RestaurantService $restaurant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'logo' => ['nullable', 'string', 'max:500', 'regex:#^(/storage/[a-zA-Z0-9/_.-]+|https://[^\s]+)$#'],
            'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'address' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', Rule::in(['PKR', 'USD', 'GBP', 'AED'])], 'timezone' => ['required', 'timezone'],
            'minimum_order' => ['required', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'], 'delivery_charge' => ['required', 'numeric', 'min:0', 'max:99999', 'decimal:0,2'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'], 'accepting_orders' => ['required', 'boolean'],
            'opening_hours' => ['required', 'array', 'size:7'], 'opening_hours.*.enabled' => ['required', 'boolean'], 'opening_hours.*.open' => ['required', 'date_format:H:i'], 'opening_hours.*.close' => ['required', 'date_format:H:i'],
        ]);
        $settings = $restaurant->settings();
        $settings->update($data);

        return response()->json(['data' => $settings]);
    }
}
