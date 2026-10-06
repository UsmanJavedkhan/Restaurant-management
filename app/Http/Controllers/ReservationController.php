<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\RestaurantService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function store(Request $request, RestaurantService $restaurant): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'phone' => ['required', 'regex:/^[+0-9 ()-]{7,20}$/'], 'date' => ['required', 'date_format:Y-m-d'], 'time' => ['required', 'date_format:H:i'], 'guests' => ['required', 'integer', 'min:1', 'max:8']]);
        $settings = $restaurant->settings();
        $moment = CarbonImmutable::parse($data['date'].' '.$data['time'], $settings->timezone);
        if ($moment->isPast() || ! $restaurant->isOpen($settings, $moment)) {
            throw ValidationException::withMessages(['date' => 'Please select a future time during our opening hours.']);
        }

        return response()->json(['data' => Reservation::create($data), 'message' => 'Your reservation request has been sent. The restaurant will confirm availability.'], 201);
    }

    public function index(): JsonResponse
    {
        return response()->json(Reservation::latest('id')->paginate(20));
    }

    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,confirmed,cancelled']]);
        $reservation->update($data);

        return response()->json(['data' => $reservation]);
    }
}
