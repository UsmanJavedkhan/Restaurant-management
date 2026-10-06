<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeCustomer;
use App\Models\User;
use App\Services\RestaurantService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function session(Request $request): JsonResponse
    {
        if ($request->user() && ! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['user' => $request->user(), 'csrf_token' => csrf_token()]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => Str::lower(trim($request->string('email')->toString()))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:/^[+0-9 ()-]{7,20}$/'], 'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);
        $data['email'] = Str::lower($data['email']);
        $user = User::create($data);
        event(new Registered($user));
        Mail::to($user->email)->queue(new WelcomeCustomer($user->name, app(RestaurantService::class)->settings()->name));
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user->fresh(), 'csrf_token' => csrf_token()], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'remember' => ['sometimes', 'boolean']]);
        if (! Auth::attempt(['email' => Str::lower($data['email']), 'password' => $data['password'], 'is_active' => true], $data['remember'] ?? false)) {
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect, or this account is disabled.']);
        }
        $request->session()->regenerate();

        return $this->session($request);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['user' => null, 'csrf_token' => csrf_token()]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => Str::lower($request->string('email')->toString())]);

        return response()->json(['message' => 'If an account matches this email, a password reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'token' => ['required', 'string'], 'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()]]);
        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => 'Your password has been reset. You can now sign in.']);
    }
}
