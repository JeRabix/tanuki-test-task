<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Order\OrderController;

Route::get('/cart', [CartController::class, 'show'])
    ->name('cart.show');

Route::post('/cart/apply-promocode', [CartController::class, 'applyPromocode'])
    ->name('cart.apply-promocode');

Route::put('/cart/products', [CartController::class, 'setProduct'])
    ->name('cart.set-product');

Route::post('/orders', [OrderController::class, 'store'])
    ->name('orders.store');

