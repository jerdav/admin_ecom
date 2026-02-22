<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingProviderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthController::class, 'store'])
            ->middleware('throttle:admin-login')
            ->name('login.store');
    });

    Route::middleware(['admin.web'])->group(function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('commandes', [OrderController::class, 'index'])->name('orders');
        Route::get('commandes/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('commandes/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status.update');
        Route::post('commandes/{order}/payments/{payment}/refund', [OrderController::class, 'refundPayment'])->name('orders.payments.refund');
        Route::get('catalogue/produits', [ProductController::class, 'index'])->name('products');
        Route::get('catalogue/produits/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('catalogue/produits', [ProductController::class, 'store'])->name('products.store');
        Route::get('catalogue/produits/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::post('catalogue/produits/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('catalogue/produits/{product}/images/{image}/delete', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::post('catalogue/produits/{product}/delete', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::get('catalogue/categories', [CategoryController::class, 'index'])->name('categories');
        Route::get('catalogue/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('catalogue/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('catalogue/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::post('catalogue/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::post('catalogue/categories/{category}/delete', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('parametres', [SettingController::class, 'edit'])->name('settings');
        Route::post('parametres', [SettingController::class, 'update'])->name('settings.update');

        Route::post('parametres/transporteurs', [ShippingProviderController::class, 'store'])->name('shipping-providers.store');
        Route::post('parametres/transporteurs/{provider}', [ShippingProviderController::class, 'update'])->name('shipping-providers.update');
        Route::post('parametres/transporteurs/{provider}/delete', [ShippingProviderController::class, 'destroy'])->name('shipping-providers.destroy');

        Route::get('audit', [PageController::class, 'audit'])->name('audit');
        Route::post('logout', [AdminAuthController::class, 'destroy'])->name('logout');
    });
});
