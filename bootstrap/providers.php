<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\MongoServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    MongoServiceProvider::class,
    RepositoryServiceProvider::class,
    EventServiceProvider::class,
];
