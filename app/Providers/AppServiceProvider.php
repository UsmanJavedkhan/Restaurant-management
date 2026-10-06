<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('restaurant-login', fn (Request $request) => Limit::perMinute(5)->by(hash('sha256', mb_strtolower((string) $request->input('email')).'|'.$request->ip())));
        ResetPassword::createUrlUsing(fn (object $user, string $token) => url('/reset-password/'.$token).'?email='.urlencode($user->email));
    }
}
