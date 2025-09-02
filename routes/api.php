<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeatureClickController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemOptionController;
use App\Http\Controllers\ItemOptionIssueController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UnitsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get(
  '/storage/{path}',
  function ($path) {
    if (! Storage::disk('public')->exists($path)) {
      abort(404, 'File not found');
    }

    return response()->file(storage_path("app/public/$path"));
  }
)->where('path', '.*');
Route::prefix('/auth')->group(function () {
  Route::post('login', [AuthController::class, 'login']);
  Route::post('register', [AuthController::class, 'register']);
  Route::post('whoami', [AuthController::class, 'whoAmI']);
  Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::prefix('/admin')->middleware('jwt:admin')->group(function () {
  Route::get('users', [UserController::class, 'getPaginatedUsers']);
  Route::post('users', [UserController::class, 'createUserWithGroup']);
  Route::get('users/exists', [UserController::class, 'checkUserExists']);

  Route::get('items', [ItemsController::class, 'index']);
  Route::get('items/categories', [ItemCategoryController::class, 'index']);
  Route::post('items/categories', [ItemCategoryController::class, 'store']);
  Route::patch('items/categories/{category:id}', [ItemCategoryController::class, 'update']);
  Route::delete('items/categories/{category:id}', [ItemCategoryController::class, 'destroy']);

  Route::post('items', [ItemsController::class, 'createItem']);
  Route::post('items/images', [ItemsController::class, 'uploadFile']);
  Route::delete('items/{item:id}', [ItemsController::class, 'destroy']);
  Route::put('items/{item:id}', [ItemsController::class, 'update']);

  Route::post('items/{item:id}/options', [ItemOptionController::class, 'createOption']);
  Route::get('items/{item:id}/options', [ItemOptionIssueController::class, 'getOptionsWithIssues']);
  Route::put('items/{item:id}/options/{option:id}', [ItemOptionController::class, 'updateOption']);
  Route::delete('items/{item:id}/options/{option:id}', [ItemOptionController::class, 'deleteOption']);
  Route::get('items/{item:id}/options/{option:id}/issues', [ItemOptionIssueController::class, 'getIssues']);
  Route::get('items/{item:id}/options/{option:id}/issues/{optionIssue:id}/comments', [ItemOptionIssueController::class, 'getComments']);
  Route::post('items/{item:id}/options/{option:id}/issues/{optionIssue:id}/comments', [ItemOptionIssueController::class, 'createComment']);

  Route::get('issues/open', action: [ItemOptionIssueController::class, 'getPaginatedOpenedIssues']);

  Route::get('units', [UnitsController::class, 'getUnits']);
  Route::post('units', [UnitsController::class, 'createUnit']);
  Route::patch('units/{unit:id}', [UnitsController::class, 'updateUnit']);

  Route::post('groups/users', [GroupController::class, 'addUserToGroup']);
});

Route::prefix('/items')->middleware('jwt')->group(function () {
  Route::get('/', [ItemsController::class, 'index']);
  Route::get('/available', [ItemsController::class, 'getAvailableItems']);
  Route::get('/categories', [ItemsController::class, 'getCategories']);

  Route::get('/{item:id}', [ItemsController::class, 'show']);
  Route::get('/{item:id}/options', [ItemOptionController::class, 'getOptions']);
  Route::post('/{item:id}/options/{option:id}/issues', [ItemOptionIssueController::class, 'createIssue']);
  Route::get('/{item:id}/options/{option:id}/issues', [ItemOptionIssueController::class, 'getIssues']);

  Route::prefix('uses')->group(function () {
    Route::get('/', [SubscriptionController::class, 'getSubscriptions']);
    Route::post('/', [SubscriptionController::class, 'createSubscription']);
    Route::get('{subscription:id}', [SubscriptionController::class, 'getSubscription']);
  });
});

Route::prefix('/options')->middleware('jwt')->group(function () {
  Route::prefix('/{option:id}')->group(function () {
    Route::get('/', [ItemOptionController::class, 'getOption']);
    Route::get('/issues/{optionIssue:id}/comments', [ItemOptionIssueController::class, 'getComments']);
  });
});

Route::prefix('events')->middleware('jwt')->group(function () {
  Route::get('/', [EventController::class, 'getEventsForUserForUnit']);
  Route::post('/', [EventController::class, 'create']);
  Route::delete('/{event:id}', [EventController::class, 'delete']);
  Route::patch('/{event:id}', [EventController::class, 'update']);

  Route::get('/actual', [EventController::class, 'getActualEvents']);
  Route::get('/{event:id}', [EventController::class, 'getEventById']);
});

Route::prefix('/features')->middleware('jwt')->group(function () {
  Route::post('/{feature:slug}/click', [FeatureClickController::class, 'click']);
});

Route::prefix('/backoffice')->middleware('jwt:admin:app')->group(function () {
  Route::get('/users', [UserController::class, 'getBackofficePaginatedUsers']);
  Route::post('/users', [UserController::class, 'createUser']);
  Route::get('/users/{user:id}/groups', [UserController::class, 'getUserGroups']);
  Route::put('/users/{user:id}/groups', [UserController::class, 'updateUserGroups']);

  Route::get('/groups', [GroupController::class, 'getGroups']);
  Route::post('/groups', [GroupController::class, 'createGroup']);
  Route::put('/groups/{group:id}', [GroupController::class, 'updateGroup']);
  Route::post('/groups/image', [GroupController::class, 'uploadFile']);
});
