<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get()]);
    }

    private function save(Request $request, ?Address $address = null): Address
    {
        $data = $request->validate(['label' => ['required', 'string', 'max:50'], 'address' => ['required', 'string', 'max:500'], 'city' => ['required', 'string', 'max:100'], 'area' => ['required', 'string', 'max:100'], 'landmark' => ['nullable', 'string', 'max:150'], 'phone' => ['required', 'regex:/^[+0-9 ()-]{7,20}$/'], 'is_default' => ['required', 'boolean']]);

        return DB::transaction(function () use ($request, $address, $data): Address {
            $request->user()->newQuery()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($data['is_default']) {
                $request->user()->addresses()->update(['is_default' => false]);
            }
            if ($address) {
                $address->update($data);

                return $address;
            }

            return $request->user()->addresses()->create($data);
        });
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->save($request)], 201);
    }

    public function update(Request $request, int $address): JsonResponse
    {
        return response()->json(['data' => $this->save($request, $request->user()->addresses()->findOrFail($address))]);
    }

    public function destroy(Request $request, int $address): JsonResponse
    {
        $request->user()->addresses()->findOrFail($address)->delete();

        return response()->json(['message' => 'Address removed.']);
    }
}
