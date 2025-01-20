<?php

use App\Http\Controllers\API\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('auth/register', 'register');
        Route::post('auth/login', 'login');
        Route::post('auth/logout', 'logout')->middleware('auth:api');
        Route::post('auth/refresh', 'refresh')->middleware('auth:api');
    });
});
