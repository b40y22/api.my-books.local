<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MongoDB\Client;

final class MongoServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register MongoDB client as singleton
        $this->app->singleton('mongodb', function ($app) {
            $config = config('database.connections.mongodb');

            return new Client($config['dsn'], $config['options'] ?? []);
        });

        // Register MongoDB database instance
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
        // Create indexes for better performance on app boot
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

            // Index for request_id lookup (primary usage)
            $collection->createIndex(['_id' => 1]);

            // Index for time-based queries
            $collection->createIndex(['started_at' => -1]);

            // Index for user-based queries
            $collection->createIndex(['user_id' => 1, 'started_at' => -1]);

            // Index for status-based queries (errors, etc.)
            $collection->createIndex(['status' => 1, 'started_at' => -1]);

            // Index for slow queries analysis
            $collection->createIndex(['duration_ms' => -1]);

            // TTL index - automatically delete old requests after 30 days
            $collection->createIndex(
                ['started_at' => 1],
                ['expireAfterSeconds' => 30 * 24 * 60 * 60] // 30 days
            );

        } catch (\Exception $e) {
            // Don't fail app startup if index creation fails
            logger()->warning('Failed to create MongoDB indexes', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
