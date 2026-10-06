<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdminCatalogController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RestaurantSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function (): void {
    Route::get('session', [AuthController::class, 'session']);
    Route::get('configuration', [CatalogController::class, 'configuration']);
    Route::get('products', [CatalogController::class, 'index']);
    Route::get('products/{product}', [CatalogController::class, 'show']);
    Route::post('register', [AuthController::class, 'register'])->middleware(['guest', 'throttle:5,1']);
    Route::post('login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:restaurant-login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('reservations', [ReservationController::class, 'store'])->middleware('throttle:5,1');
    Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:3,1');

    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth');

    Route::middleware(['auth', 'active-user'])->group(function (): void {
        Route::get('cart', [CartController::class, 'show']);
        Route::put('cart', [CartController::class, 'update']);
        Route::patch('profile', [ProfileController::class, 'update']);
        Route::put('password', [ProfileController::class, 'password']);
        Route::apiResource('addresses', AddressController::class)->except('show');
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders/quote', [OrderController::class, 'quote']);
        Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/read', [NotificationController::class, 'update']);

        Route::prefix('admin')->middleware('admin')->group(function (): void {
            Route::get('reports', [AdminReportController::class, 'index']);
            Route::get('orders', [AdminOrderController::class, 'index']);
            Route::get('orders/{order}', [OrderController::class, 'show']);
            Route::patch('orders/{order}/status', [AdminOrderController::class, 'update']);
            Route::patch('orders/{order}/payment', [AdminOrderController::class, 'payment']);
            Route::get('payments', [AdminOrderController::class, 'payments']);
            Route::get('customers', [AdminCustomerController::class, 'index']);
            Route::patch('customers/{user}', [AdminCustomerController::class, 'update']);
            Route::get('settings', [RestaurantSettingController::class, 'show']);
            Route::put('settings', [RestaurantSettingController::class, 'update']);
            Route::post('images', [AdminCatalogController::class, 'upload']);
            Route::get('reservations', [ReservationController::class, 'index']);
            Route::patch('reservations/{reservation}', [ReservationController::class, 'update']);
            Route::get('messages', [ContactController::class, 'index']);
            Route::patch('messages/{message}', [ContactController::class, 'update']);
            Route::get('{resource}', [AdminCatalogController::class, 'index'])->whereIn('resource', ['categories', 'products', 'deals', 'coupons', 'delivery-areas']);
            Route::post('{resource}', [AdminCatalogController::class, 'store'])->whereIn('resource', ['categories', 'products', 'deals', 'coupons', 'delivery-areas']);
            Route::get('{resource}/{record}', [AdminCatalogController::class, 'show'])->whereIn('resource', ['categories', 'products', 'deals', 'coupons', 'delivery-areas'])->whereNumber('record');
            Route::put('{resource}/{record}', [AdminCatalogController::class, 'update'])->whereIn('resource', ['categories', 'products', 'deals', 'coupons', 'delivery-areas'])->whereNumber('record');
            Route::delete('{resource}/{record}', [AdminCatalogController::class, 'destroy'])->whereIn('resource', ['categories', 'products', 'deals', 'coupons', 'delivery-areas'])->whereNumber('record');
        });
    });
});
Route::any('api/{any}', fn () => abort(404))->where('any', '.*');
Route::view('/login', 'app')->name('login');
Route::view('/reset-password/{token}', 'app')->name('password.reset');
Route::get('/{any}', fn () => view('app'))->where('any', '.*');
