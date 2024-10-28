<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\SubscriptionController;

Route::prefix('/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware('jwt')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('whoami', [AuthController::class, 'whoAmI']);
    });
});

Route::prefix('users')->group(function () {
    Route::get('/', [AuthController::class, 'index'])->middleware('jwt:admin');
    // Route::get('/me/permanentTokenForICS', [AuthController::class, 'createJwtAlwaysAdmin'])->middleware('jwt');
});


Route::prefix('/items')->middleware('jwt')->group(function () {
    Route::get('/', [ItemsController::class, 'index']);
    Route::post('/', [ItemsController::class, 'createItem'])->middleware('jwt:admin');

    Route::prefix('/{item:id}')->group(function () {
        Route::get('/', [ItemsController::class, 'show']);
        Route::put('/', [ItemsController::class, 'update'])->middleware('jwt:admin');
        Route::delete('/', [ItemsController::class, 'destroy'])->middleware('jwt:admin');

        Route::get('/uses', [SubscriptionController::class, 'getSubscriptions']);
        Route::post('/uses', [SubscriptionController::class, 'createSubscription'])->middleware('jwt:admin');
    });
});

// Route::get(('subscriptions/ICS'), [SubscriptionController::class, 'getICS'])->middleware('jwt:always:admin');
