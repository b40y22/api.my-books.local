<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Users\UserRepository;
use App\Repositories\Users\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, concrete: UserRepository::class);
    }
}
