<?php

namespace App\Providers;

use App\Services\Auth\RegisterService;
use App\Services\Auth\RegisterServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RegisterServiceInterface::class, RegisterService::class);
    }
}
