<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Order\OrderController;

Route::get('/cart', [CartController::class, 'show']);

Route::post('/cart/apply-promocode', [CartController::class, 'applyPromocode']);

Route::put('/cart/products', [CartController::class, 'setProduct']);

Route::post('/orders', [OrderController::class, 'store']);

