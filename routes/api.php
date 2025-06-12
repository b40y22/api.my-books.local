<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResendEmailVerificationController;
use App\Http\Middleware\Auth\LoginAttemptMiddleware;
use App\Http\Middleware\Auth\LogoutAttemptMiddleware;
use Illuminate\Support\Facades\Route;

Route::name('auth.')->prefix('auth')->group(function () {
    Route::post('register', [RegisterController::class, '__invoke'])
        ->name('register');

    Route::post('login', [LoginController::class, '__invoke'])
        ->middleware([LoginAttemptMiddleware::class, 'throttle:5,1'])
        ->name('login');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('logout', [LogoutController::class, '__invoke'])
            ->middleware(LogoutAttemptMiddleware::class)
            ->name('logout');

        Route::post('logout-all', [LogoutController::class, 'logoutAll'])
            ->middleware(LogoutAttemptMiddleware::class)
            ->name('logout.all');
    });

    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, '__invoke'])
        ->middleware(['signed'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [ResendEmailVerificationController::class, '__invoke'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.resend');
});
