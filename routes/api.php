<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResendEmailVerificationController;
use Illuminate\Support\Facades\Route;

Route::name('auth.')->prefix('auth')->group(function () {
    Route::post('register', [RegisterController::class, '__invoke'])
        ->name('register');

    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, '__invoke'])
        ->middleware(['signed'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [ResendEmailVerificationController::class, '__invoke'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.resend');
});
