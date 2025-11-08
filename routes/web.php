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

    Route::get('/statuses', function () {
        return view('statuses');
    })->name('statuses');

    // API endpoints for SPA
    Route::prefix('api')->group(function () {
        // Authentication
        Route::get('/user', [App\Http\Controllers\AuthController::class, 'user']);

        // Users
        Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
        Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);

        // Task Statuses
        Route::apiResource('statuses', App\Http\Controllers\TaskStatusController::class)
             ->only(['index', 'store', 'update', 'destroy']);

        // Tasks
        Route::apiResource('tasks', App\Http\Controllers\TaskController::class);
    });
});
