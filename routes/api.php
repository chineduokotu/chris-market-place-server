<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminCategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReviewController as AdminReviewController;
use App\Http\Controllers\Admin\AdminServiceController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/services/{id}/reviews', [ReviewController::class, 'indexByService']);
Route::get('/providers/{id}/reviews', [ReviewController::class, 'indexByProvider']);

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
    Route::post('/reviews', [ReviewController::class, 'store'])->middleware('throttle:20,1');

    // Chat routes
    Route::get('/conversations', [ChatController::class, 'index']);
    Route::post('/conversations', [ChatController::class, 'store']);
    Route::get('/conversations/{id}', [ChatController::class, 'show']);
    Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage'])->middleware('throttle:60,1');
    Route::post('/messages/{id}/read', [ChatController::class, 'markRead'])->middleware('throttle:120,1');
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus']);
    Route::patch('/users/{user}/admin-access', [AdminUserController::class, 'updateAdminAccess']);

    Route::get('/services', [AdminServiceController::class, 'index']);
    Route::get('/services/{service}', [AdminServiceController::class, 'show']);
    Route::patch('/services/{service}/status', [AdminServiceController::class, 'updateStatus']);
    Route::delete('/services/{service}', [AdminServiceController::class, 'destroy']);

    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::get('/categories/{category}', [AdminCategoryController::class, 'show']);
    Route::patch('/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

    Route::get('/bookings', [AdminBookingController::class, 'index']);
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show']);
    Route::patch('/bookings/{booking}/admin-note', [AdminBookingController::class, 'updateAdminNote']);

    Route::get('/reviews', [AdminReviewController::class, 'index']);
    Route::get('/reviews/{review}', [AdminReviewController::class, 'show']);
    Route::patch('/reviews/{review}/status', [AdminReviewController::class, 'updateStatus']);

    Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);
});
