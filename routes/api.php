<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskStatusController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and "/api" prefix.
|
*/

// All API routes require authentication
Route::middleware(['auth'])->group(function () {
    // Authentication
    Route::get('/user', [AuthController::class, 'user']);

    // Filters
    Route::get('/filters', [FilterController::class, 'index']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store'])
         ->middleware('admin');

    // Task Statuses
    Route::get('/statuses', [TaskStatusController::class, 'index']);
    Route::post('/statuses', [TaskStatusController::class, 'store'])
         ->middleware('admin');
    Route::put('/statuses/{status}', [TaskStatusController::class, 'update'])
         ->middleware('admin');
    Route::delete('/statuses/{status}', [TaskStatusController::class, 'destroy'])
         ->middleware('admin');

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store'])
         ->middleware('admin');
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
         ->middleware('admin');
});
