<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeatureClickController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemIssueController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\SubscriptionController;
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
  Route::post('{structure:id}/select-structure', [AuthController::class, 'generateTokenForSelectedStructure'])->middleware('jwt');
});

Route::post('users/send-reset-password', [UserController::class, 'sendResetPassword']);

Route::prefix('/admin')->middleware('jwt:admin')->group(function () {
  Route::get('users', [UserController::class, 'getPaginatedUsers']);
  Route::post('users', [UserController::class, 'createUserWithStructure']);
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

  Route::get('items/{item:id}/issues', [ItemIssueController::class, 'getIssues']);
  Route::get('items/{item:id}/issues/{issue:id}/comments', [ItemIssueController::class, 'getComments']);
  Route::post('items/{item:id}/issues/{issue:id}/comments', [ItemIssueController::class, 'createComment']);
  Route::patch('items/{item:id}/issues/{issue:id}/resolve', [ItemIssueController::class, 'resolveIssue']);

  Route::get('issues/open', action: [ItemIssueController::class, 'getPaginatedOpenedIssues']);

  Route::get('structures', [StructureController::class, 'getStructuresWithMembers']);
  Route::post('structures', [StructureController::class, 'store']);
  Route::post('structures/image', [StructureController::class, 'uploadFile']);
  Route::patch('structures/{structure:id}', [StructureController::class, 'update']);

  Route::post('structures/users', [StructureController::class, 'addUserToStructure']);
});

Route::prefix('/items')->middleware('jwt')->group(function () {
  Route::get('/', [ItemsController::class, 'index']);
  Route::get('/available', [ItemsController::class, 'getAvailableItems']);
  Route::get('/categories', [ItemsController::class, 'getCategories']);

  Route::get('/{item:id}', [ItemsController::class, 'show']);
  Route::post('/{item:id}/issues', [ItemIssueController::class, 'createIssue']);
  Route::get('/{item:id}/issues', [ItemIssueController::class, 'getIssues']);

  Route::prefix('uses')->group(function () {
    Route::get('/', [SubscriptionController::class, 'getSubscriptions']);
    Route::post('/', [SubscriptionController::class, 'createSubscription']);
    Route::get('{subscription:id}', [SubscriptionController::class, 'getSubscription']);
  });
});

Route::prefix('events')->middleware('jwt')->group(function () {
  Route::get('/', [EventController::class, 'getEventsForUserForStructure']);
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
  Route::get('/users/{user:id}/structures', [UserController::class, 'getUserStructures']);
  Route::put('/users/{user:id}/structures', [UserController::class, 'updateUserStructures']);

  Route::get('/structures', [StructureController::class, 'getStructures']);
  Route::post('/structures', [StructureController::class, 'createGroup']);
  Route::put('/structures/{structure:id}', [StructureController::class, 'updateGroup']);
  Route::post('/structures/image', [StructureController::class, 'uploadFile']);
});
