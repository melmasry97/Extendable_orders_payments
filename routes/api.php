<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\OrderItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth.api')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
        });
    });

    Route::middleware('auth.api')->group(function () {
        // Order Management Routes
        Route::apiResource('orders', OrderController::class);

        // Product Management Routes
        Route::apiResource('products', ProductController::class);

        // Nested routes for order items
        Route::apiResource('orders.items', OrderItemController::class);
    });
});
