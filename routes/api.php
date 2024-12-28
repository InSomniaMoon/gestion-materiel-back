<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeatureClickController;
use App\Http\Controllers\ItemOptionController;
use App\Http\Controllers\ItemOptionIssueController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

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
  Route::get('/categories', [ItemsController::class, 'getCategories']);

  Route::prefix('/{item:id}')->group(function () {
    Route::get('/', [ItemsController::class, 'show']);
    Route::put('/', [ItemsController::class, 'update'])->middleware('jwt:admin');
    Route::delete('/', [ItemsController::class, 'destroy'])->middleware('jwt:admin');

    Route::prefix('options')->group(function () {
      Route::get('/', [ItemOptionController::class, 'getOptions']);
      Route::post('/', [ItemOptionController::class, 'createOption'])->middleware('jwt:admin');
      Route::get('/issues', [ItemOptionIssueController::class, 'getIssues'])->middleware('jwt:admin');

      Route::prefix('/{option:id}')->group(function () {
        Route::get('/', [ItemOptionController::class, 'getOption']);
        Route::put('/', [ItemOptionController::class, 'updateOption'])->middleware('jwt:admin');
        Route::delete('/', [ItemOptionController::class, 'deleteOption'])->middleware('jwt:admin');

        Route::prefix('issues')->group(function () {
          Route::post('/', [ItemOptionIssueController::class, 'createIssue']);
          Route::get('/', [ItemOptionIssueController::class, 'getIssues']);
          Route::put('/{issue:id}', [ItemOptionIssueController::class, 'updateIssue'])->middleware('jwt:admin');
          Route::delete('/{issue:id}', [ItemOptionIssueController::class, 'deleteIssue'])->middleware('jwt:admin');
        });
      });
    });

    Route::prefix('uses')->group(function () {
      Route::get('/', [SubscriptionController::class, 'getSubscriptions']);
      Route::post('/', [SubscriptionController::class, 'createSubscription']);
      Route::get('{subscription:id}', [SubscriptionController::class, 'getSubscription']);
    });
  });
});
Route::prefix('/options')->middleware('jwt')->group(function () {
  Route::get('/issues', [ItemOptionIssueController::class, 'getIssuesForItems'])->middleware('jwt:admin');
  Route::prefix('/{option:id}')->group(function () {
    Route::get('/', [ItemOptionController::class, 'getOption']);
    Route::prefix('/issues')->group(function () {
      Route::prefix('/{optionIssue:id}')->group(function () {
        Route::patch('/resolve', [ItemOptionIssueController::class, 'resolveIssue'])->middleware('jwt:admin');
        Route::post('/comments', [ItemOptionIssueController::class, 'createComment'])->middleware('jwt:admin');
        Route::get('/comments', [ItemOptionIssueController::class, 'getComments']);
      });
    });
  });
});

Route::prefix('/features')->middleware('jwt')->group(function () {
  Route::post('/{feature:id}/click', [FeatureClickController::class, 'click']);
});

// Route::get(('subscriptions/ICS'), [SubscriptionController::class, 'getICS'])->middleware('jwt:always:admin');
