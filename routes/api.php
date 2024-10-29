<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemOptionController;
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


        Route::prefix('options')->group(function () {
            Route::get('/', [ItemOptionController::class, 'getOptions']);
            Route::post('/', [ItemOptionController::class, 'createOption'])->middleware('jwt:admin');
            Route::patch('/{option:id}', [ItemOptionController::class, 'updateOption'])->middleware('jwt:admin');
            route::delete('/{option:id}', [ItemOptionController::class, 'deleteOption'])->middleware('jwt:admin');
        });

        Route::prefix('uses')->group(function () {
            Route::get('/', [SubscriptionController::class, 'getSubscriptions']);
            Route::post('/', [SubscriptionController::class, 'createSubscription'])->middleware('jwt:admin');
            Route::get('{subscription:id}', [SubscriptionController::class, 'getSubscription']);
        });
    });
});

// Route::get(('subscriptions/ICS'), [SubscriptionController::class, 'getICS'])->middleware('jwt:always:admin');
