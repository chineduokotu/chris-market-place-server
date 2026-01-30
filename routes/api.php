<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', [UserController::class, 'profile']);
    Route::patch('/user', [UserController::class, 'updateProfile']);
    Route::post('/user/switch-role', [UserController::class, 'switchRole']);

    Route::get('/my-services', [ServiceController::class, 'myServices']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/my-requests', [BookingController::class, 'myRequests']);
    Route::get('/my-jobs', [BookingController::class, 'myJobs']);
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

    // Chat routes
    Route::get('/conversations', [ChatController::class, 'index']);
    Route::post('/conversations', [ChatController::class, 'store']);
    Route::get('/conversations/{id}', [ChatController::class, 'show']);
    Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/messages/{id}/read', [ChatController::class, 'markRead']);
});

