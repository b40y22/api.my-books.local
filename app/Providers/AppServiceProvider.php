<?php

namespace App\Providers;

use App\Services\Auth\RegisterService;
use App\Services\Auth\RegisterServiceInterface;
use App\Services\Translation\Email\EmailTranslationService;
use App\Services\Translation\Email\EmailTranslationServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmailTranslationServiceInterface::class, EmailTranslationService::class);
        $this->app->bind(RegisterServiceInterface::class, RegisterService::class);
    }
}
