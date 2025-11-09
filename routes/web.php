<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Authentication routes
Route::get('/login', function () {
    return view('login');
})->name('login')->middleware('guest');

Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes
Route::middleware('auth')->group(function () {
    // Views
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    // Admin-only routes
    Route::get('/statuses', function () {
        return view('statuses');
    })->name('statuses')->middleware('admin');

    // API endpoints for SPA
    Route::prefix('api')->group(function () {
        // Authentication
        Route::get('/user', [App\Http\Controllers\AuthController::class, 'user']);

        // Filters
        Route::get('/filters', [App\Http\Controllers\FilterController::class, 'index']);

        // Users
        Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
        Route::post('/users', [App\Http\Controllers\UserController::class, 'store'])
             ->middleware('admin');

        // Task Statuses
        Route::get('/statuses', [App\Http\Controllers\TaskStatusController::class, 'index']);
        Route::post('/statuses', [App\Http\Controllers\TaskStatusController::class, 'store'])
             ->middleware('admin');
        Route::put('/statuses/{status}', [App\Http\Controllers\TaskStatusController::class, 'update'])
             ->middleware('admin');
        Route::delete('/statuses/{status}', [App\Http\Controllers\TaskStatusController::class, 'destroy'])
             ->middleware('admin');

        // Tasks
        Route::get('/tasks', [App\Http\Controllers\TaskController::class, 'index']);
        Route::post('/tasks', [App\Http\Controllers\TaskController::class, 'store'])
             ->middleware('admin');
        Route::put('/tasks/{task}', [App\Http\Controllers\TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [App\Http\Controllers\TaskController::class, 'destroy'])
             ->middleware('admin');
    });
});
