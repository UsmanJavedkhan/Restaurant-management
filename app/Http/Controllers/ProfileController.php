<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $request->merge(['email' => mb_strtolower(trim($request->string('email')->toString()))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)], 'phone' => ['required', 'regex:/^[+0-9 ()-]{7,20}$/']]);
        $data['email'] = mb_strtolower($data['email']);
        $request->user()->update($data);

        return response()->json(['user' => $request->user()->fresh()]);
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]]);
        $request->user()->update(['password' => Hash::make($data['password'])]);
        $request->session()->regenerate();

        return response()->json(['message' => 'Password updated.', 'csrf_token' => csrf_token()]);
    }
}
