<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\FrontendLogController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

// Публичные маршруты
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/log-error', [FrontendLogController::class, 'store']);

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/availability', [RoomController::class, 'availability']);
Route::get('/rooms/calendar', [RoomController::class, 'calendar']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);

Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{news}', [NewsController::class, 'show']);

Route::get('/services', [ServiceController::class, 'index']);

Route::get('/gallery', [GalleryController::class, 'index']);

Route::get('/reviews', [ReviewController::class, 'index']);

// Только авторизованным
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::get('/my-bookings', [BookingController::class, 'myBookings']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'cancel']);

    Route::post('/reviews', [ReviewController::class, 'store']);
});

// Только для персонала (admin, director, developer)
Route::middleware(['auth:sanctum', 'role:admin|director|developer'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::patch('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
        Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::post('/bookings', [BookingController::class, 'storeAdmin']);
    });
