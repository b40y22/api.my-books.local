<?php

namespace App\Providers;

use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\EmailVerificationServiceInterface;
use App\Services\Auth\LoginService;
use App\Services\Auth\LoginServiceInterface;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\PasswordResetServiceInterface;
use App\Services\Auth\RegisterService;
use App\Services\Auth\RegisterServiceInterface;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmailVerificationServiceInterface::class, EmailVerificationService::class);
        $this->app->bind(LoginServiceInterface::class, LoginService::class);
        $this->app->bind(PasswordResetServiceInterface::class, PasswordResetService::class);
        $this->app->bind(RegisterServiceInterface::class, RegisterService::class);
    }
}
