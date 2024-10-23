<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('whoami', [AuthController::class, 'whoAmI']);
    });
});
