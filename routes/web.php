<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\TournamentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    // Main Admin (super_admin) only
    Route::middleware(['auth', 'role:super_admin'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('switch-tournament', [DashboardController::class, 'switchTournament'])->name('switch-tournament');

        // Players & registrations
        Route::get('players', [PlayerController::class, 'index'])->name('players.index');
        Route::get('players/export', [PlayerController::class, 'export'])->name('players.export');
        Route::get('players/{player}', [PlayerController::class, 'show'])->name('players.show');
        Route::get('players/{player}/edit', [PlayerController::class, 'edit'])->name('players.edit');
        Route::put('players/{player}', [PlayerController::class, 'update'])->name('players.update');
        Route::delete('players/{player}', [PlayerController::class, 'destroy'])->name('players.destroy');
        Route::post('registrations/{registration}/approve', [PlayerController::class, 'approve'])->name('registrations.approve');
        Route::post('registrations/{registration}/reject', [PlayerController::class, 'reject'])->name('registrations.reject');
        Route::post('registrations/{registration}/offline-payment', [PlayerController::class, 'offlinePayment'])->name('registrations.offline-payment');
        Route::post('documents/{document}/verify', [PlayerController::class, 'verifyDocument'])->name('documents.verify');
        Route::get('documents/{document}/{mode?}', [PlayerController::class, 'document'])->whereIn('mode', ['view', 'download'])->name('documents.file');

        // Payments
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');
        Route::get('payments/reports', [PaymentController::class, 'reports'])->name('payments.reports');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
        Route::get('payments/{payment}/screenshot/{mode?}', [PaymentController::class, 'screenshot'])->whereIn('mode', ['view', 'download'])->name('payments.screenshot');

        // Tournaments & payment settings
        Route::get('tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
        Route::get('tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
        Route::post('tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
        Route::get('tournaments/{tournament}/edit', [TournamentController::class, 'edit'])->name('tournaments.edit');
        Route::put('tournaments/{tournament}', [TournamentController::class, 'update'])->name('tournaments.update');
        Route::delete('tournaments/{tournament}', [TournamentController::class, 'destroy'])->name('tournaments.destroy');
        Route::post('tournaments/{tournament}/activate', [TournamentController::class, 'activate'])->name('tournaments.activate');
        Route::post('tournaments/{tournament}/toggle-registration', [TournamentController::class, 'toggleRegistration'])->name('tournaments.toggle');
        Route::get('tournaments/{tournament}/payment', [TournamentController::class, 'payment'])->name('tournaments.payment');
        Route::put('tournaments/{tournament}/payment', [TournamentController::class, 'updatePayment'])->name('tournaments.payment.update');

        // System
        Route::get('history', [SystemController::class, 'history'])->name('history');
        Route::get('activity', [SystemController::class, 'activity'])->name('activity');
        Route::get('settings', [SystemController::class, 'settings'])->name('settings');
        Route::put('settings', [SystemController::class, 'updateSettings'])->name('settings.update');
        Route::put('settings/password', [SystemController::class, 'updatePassword'])->name('settings.password');
    });
});
