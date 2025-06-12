<?php

declare(strict_types=1);

namespace App\Providers;

use Exception;
use Illuminate\Support\ServiceProvider;
use MongoDB\Client;

final class MongoServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton('mongodb', function ($app) {
            $config = config('database.connections.mongodb');

            return new Client($config['dsn'], $config['options'] ?? []);
        });

        $this->app->singleton('mongodb.database', function ($app) {
            $client = $app->make('mongodb');
            $database = config('database.connections.mongodb.database');

            return $client->selectDatabase($database);
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            $this->createIndexes();
        }
    }

    /**
     * Create MongoDB indexes for optimal performance
     */
    private function createIndexes(): void
    {
        try {
            $database = $this->app->make('mongodb.database');
            $collection = $database->selectCollection('requests');

            $collection->createIndex(['_id' => 1]);
            $collection->createIndex(['started_at' => -1]);
            $collection->createIndex(['user_id' => 1, 'started_at' => -1]);
            $collection->createIndex(['status' => 1, 'started_at' => -1]);
            $collection->createIndex(['duration_ms' => -1]);
            $collection->createIndex(
                ['started_at' => 1],
                ['expireAfterSeconds' => 30 * 24 * 60 * 60]
            );

        } catch (Exception $e) {
            logger()->warning('Failed to create MongoDB indexes', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
