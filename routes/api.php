<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\RegistrationController;
use Illuminate\Support\Facades\Route;

/*
| Player API (consumed by the Flutter app). Bearer-token auth via Sanctum.
| Every authenticated route is scoped to the logged-in player only.
*/

Route::get('tournament', [PlayerController::class, 'tournament']);

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:otp');
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware(['auth:sanctum', 'role:player'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    Route::get('me', [PlayerController::class, 'me']);
    Route::put('profile', [PlayerController::class, 'updateProfile']);
    Route::post('documents/{type}', [PlayerController::class, 'uploadDocument'])->middleware('throttle:uploads');
    Route::get('documents/{type}', [PlayerController::class, 'document']);

    Route::post('registration', [RegistrationController::class, 'store']);
    Route::put('registration', [RegistrationController::class, 'update']);
    Route::post('registration/resubmit', [RegistrationController::class, 'resubmit']);
    Route::get('registration/history', [RegistrationController::class, 'history']);
    Route::get('registration/confirmation', [RegistrationController::class, 'confirmation']);

    Route::get('payment', [RegistrationController::class, 'payment']);
    Route::post('payment', [RegistrationController::class, 'submitPayment'])->middleware('throttle:payment');
    Route::get('payments/{payment}/screenshot', [RegistrationController::class, 'screenshot']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read', [NotificationController::class, 'markAllRead']);
});
