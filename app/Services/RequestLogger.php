<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use Throwable;

class RequestLogger
{
    private static ?string $requestId = null;

    private static array $requestData = [];

    private static ?Client $mongo = null;

    private static float $startTime = 0;

    /**
     * Initialize new request and return unique ID
     */
    public static function startRequest(): string
    {
        self::$requestId = Str::uuid()->toString();
        self::$startTime = microtime(true);

        // Initialize document structure in memory
        self::$requestData = [
            '_id' => self::$requestId,
            'started_at' => new UTCDateTime,
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'input' => request()->except(['password', 'password_confirmation', '_token']),

            // Fields that will be filled later
            'finished_at' => null,
            'status' => null,
            'duration_ms' => null,
            'query_count' => 0,
            'db_time_ms' => 0,

            // Arrays for data collection
            'events' => [],
            'queries' => [],
            'errors' => [],
        ];

        self::addEvent('[middleware] request_started', [
            'method' => request()->method(),
            'url' => request()->fullUrl(),
        ]);

        return self::$requestId;
    }

    /**
     * Add event to current request
     */
    public static function addEvent(string $event, array $data = []): void
    {
        if (! self::$requestId) {
            return;
        }

        self::$requestData['events'][] = [
            'event' => $event,
            'timestamp' => new UTCDateTime,
            'data' => $data,
        ];
    }

    /**
     * Add SQL query to current request
     */
    public static function addQuery(string $sql, array $bindings, float $timeMs): void
    {
        if (! self::$requestId) {
            return;
        }

        self::$requestData['queries'][] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time_ms' => round($timeMs, 2),
            'timestamp' => new UTCDateTime,
        ];

        // Update general statistics
        self::$requestData['query_count'] = count(self::$requestData['queries']);
        self::$requestData['db_time_ms'] = round(
            array_sum(array_column(self::$requestData['queries'], 'time_ms')), 2
        );
    }

    /**
     * Add exception to current request
     */
    public static function addException(Throwable $exception): void
    {
        if (! self::$requestId) {
            return;
        }

        $errorData = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        self::$requestData['errors'][] = $errorData;

        // Debug logging
        if (app()->environment('local')) {
            Log::info('Exception added to RequestLogger', [
                'request_id' => self::$requestId,
                'exception_class' => get_class($exception),
                'errors_count' => count(self::$requestData['errors']),
            ]);
        }

        self::addEvent('[exception] exception_thrown', [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
    }

    /**
     * Finish request and save everything to MongoDB
     */
    public static function finishRequest(int $status): void
    {
        if (! self::$requestId) {
            return;
        }

        $duration = round((microtime(true) - self::$startTime) * 1000, 2);

        // Fill final data
        self::$requestData['finished_at'] = new UTCDateTime;
        self::$requestData['status'] = $status;
        self::$requestData['duration_ms'] = $duration;

        self::addEvent('[middleware] request_completed', [
            'status' => $status,
            'duration_ms' => $duration,
            'query_count' => self::$requestData['query_count'],
            'db_time_ms' => self::$requestData['db_time_ms'],
        ]);

        // Save to MongoDB
        self::saveToMongo();

        // Clear data for next request
        self::resetData();
    }

    /**
     * Return current request ID
     */
    public static function getRequestId(): string
    {
        return self::$requestId ?? 'unknown';
    }

    /**
     * Return current request data (for debugging)
     */
    public static function getCurrentData(): array
    {
        return self::$requestData;
    }

    /**
     * Save document to MongoDB
     */
    private static function saveToMongo(): void
    {
        try {
            $mongo = self::getMongoClient();
            $database = config('database.connections.mongodb.database');
            $collection = $mongo
                ->selectDatabase($database)
                ->selectCollection(
                    config('database.connections.mongodb.request_tracking_collection')
                );

            // Insert or update document
            $result = $collection->replaceOne(
                ['_id' => self::$requestId],
                self::$requestData,
                ['upsert' => true]
            );

            // Log successful save (dev only)
            if (app()->environment('local')) {
                Log::debug('Request saved to MongoDB', [
                    'request_id' => self::$requestId,
                    'upserted' => $result->getUpsertedCount(),
                    'modified' => $result->getModifiedCount(),
                    'database' => $database,
                ]);
            }

        } catch (Exception $e) {
            // If MongoDB is unavailable - log to file as fallback
            Log::error('Failed to save request to MongoDB', [
                'request_id' => self::$requestId,
                'error' => $e->getMessage(),
                'request_data' => self::$requestData, // Save data to file
            ]);
        }
    }

    /**
     * Get MongoDB client (lazy loading)
     */
    private static function getMongoClient(): Client
    {
        if (! self::$mongo) {
            $dsn = config('database.connections.mongodb.dsn');

            if (! $dsn) {
                throw new \Exception('MongoDB DSN not configured. Please set MONGODB_DSN in .env file');
            }

            self::$mongo = new Client($dsn, [
                'connectTimeoutMS' => 3000,    // 3 seconds timeout
                'socketTimeoutMS' => 5000,     // 5 seconds for operations
            ]);
        }

        return self::$mongo;
    }

    /**
     * Clear current request data
     */
    private static function resetData(): void
    {
        self::$requestId = null;
        self::$requestData = [];
        self::$startTime = 0;
    }

    /**
     * Check if MongoDB is available (for health checks)
     */
    public static function isMongoAvailable(): bool
    {
        try {
            $mongo = self::getMongoClient();
            $mongo->selectDatabase('admin')->command(['ping' => 1]);

            return true;
        } catch (\Exception $e) {
            Log::warning('MongoDB availability check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
