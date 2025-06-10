<?php

namespace App\Providers;

use App\Services\RequestLogger;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        DB::listen(function (QueryExecuted $query) {
            RequestLogger::addQuery(
                $query->sql,
                $query->bindings,
                $query->time
            );
        });
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
