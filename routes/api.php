<?php

use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth-register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth-login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('me', [MeController::class, 'show']);
    Route::put('me/profile', [MeController::class, 'updateProfile']);

    Route::get('cart', [CartController::class, 'show']);
    Route::put('cart/items', [CartController::class, 'sync']);
    Route::post('cart/checkout', [CartController::class, 'checkout']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);

    Route::middleware('role:admin')->prefix('admin')->group(function (): void {
        Route::post('orders/{order}/payments/mock', [PaymentController::class, 'processMock']);
        Route::post('payments/{payment}/refund', [PaymentController::class, 'refund']);
    });
});
