<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONArray;

final class MongoRequestAnalyzer extends Command
{
    protected $signature = 'requests:analyze
                           {request_id? : Specific request ID to analyze}
                           {--user= : Filter by user ID}
                           {--method= : Filter by HTTP method (GET, POST, etc.)}
                           {--status= : Filter by status code}
                           {--url= : Filter by URL pattern}
                           {--slow=1000 : Show requests slower than X ms}
                           {--errors : Show only failed requests}
                           {--from= : From date (Y-m-d H:i:s or Y-m-d)}
                           {--to= : To date (Y-m-d H:i:s or Y-m-d)}
                           {--limit=50 : Limit results}
                           {--format=table : Output format (table, json, detailed)}
                           {--stats : Show statistics instead of individual requests}
                           {--export= : Export results to file}';

    protected $description = 'Analyze HTTP requests stored in MongoDB';

    private ?Client $mongo = null;

    public function handle(): int
    {
        try {
            $this->mongo = new Client(config('database.connections.mongodb.dsn'));
            $collection = $this->getCollection();

            if ($requestId = $this->argument('request_id')) {
                return $this->analyzeSpecificRequest($collection, $requestId);
            }

            if ($this->option('stats')) {
                return $this->showStatistics($collection);
            }

            if ($this->input->isInteractive()) {
                $requestId = $this->ask('🔍 Enter Request ID to analyze (or press Enter to show recent requests)');

                if ($requestId) {
                    if ($this->isValidUuid($requestId)) {
                        return $this->analyzeSpecificRequest($collection, $requestId);
                    } else {
                        $this->error('❌ Invalid UUID format. Please provide a valid Request ID.');

                        return 1;
                    }
                }
            }

            return $this->analyzeRequests($collection);

        } catch (Exception $e) {
            $this->error('MongoDB connection failed: '.$e->getMessage());

            return 1;
        }

    }

    private function extractValidationReason(string $message): string
    {
        if (preg_match('/Form Validation Failed in .+?: (.+)/', $message, $matches)) {
            return $matches[1];
        }

        return $message;
    }

    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    private function analyzeSpecificRequest($collection, string $requestId): int
    {
        $request = $collection->findOne(['_id' => $requestId]);

        if (! $request) {
            $this->error("Request {$requestId} not found");

            return 1;
        }

        $this->displayDetailedRequest($request);

        return 0;
    }

    private function displayDetailedRequest($request): void
    {
        $this->info('📋 Request Details: '.$request['_id']);
        $this->newLine();

        $this->line('<comment>Basic Information:</comment>');
        $this->table(['Field', 'Value'], [
            ['Request ID', $request['_id']],
            ['Method', $request['method'] ?? 'N/A'],
            ['URL', $request['url'] ?? 'N/A'],
            ['Status', $this->getStatusIcon($request['status'] ?? 0).' '.($request['status'] ?? 'N/A')],
            ['User ID', $request['user_id'] ?? 'Guest'],
            ['IP Address', $request['ip'] ?? 'N/A'],
            ['User Agent', $this->truncateString($request['user_agent'] ?? 'N/A', 50)],
            ['Started At', $this->formatDateTime($request['started_at'] ?? null)],
            ['Finished At', $this->formatDateTime($request['finished_at'] ?? null)],
            ['Duration', ($request['duration_ms'] ?? 0).' ms'],
            ['Query Count', $request['query_count'] ?? 0],
            ['DB Time', ($request['db_time_ms'] ?? 0).' ms'],
        ]);

        if (! empty($request['input'])) {
            $this->newLine();
            $this->line('<comment>Request Input:</comment>');
            $input = $this->convertBsonToArray($request['input']);
            $this->line(json_encode($input, JSON_PRETTY_PRINT));
        }

        if (! empty($request['events'])) {
            $this->newLine();
            $this->line('<comment>Events Timeline:</comment>');
            $eventData = [];
            foreach ($request['events'] as $event) {
                $eventData[] = [
                    $this->formatDateTime($event['timestamp'] ?? null, 'H:i:s'),
                    $event['event'] ?? 'unknown',
                    $this->formatEventData($event['data'] ?? [], 120),
                ];
            }
            $this->table(['Time', 'Event', 'Data'], $eventData);
        }

        if (! empty($request['queries'])) {
            $this->newLine();
            $this->line('<comment>Database Queries:</comment>');
            $queryData = [];
            foreach ($request['queries'] as $index => $query) {
                $sql = $query['sql'] ?? 'N/A';
                $bindings = $query['bindings'] ?? [];

                $formattedBindings = $this->formatBindings($bindings);

                $queryData[] = [
                    $index + 1,
                    $this->truncateString($sql, 60),
                    $formattedBindings,
                    ($query['time_ms'] ?? 0).' ms',
                ];
            }
            $this->table(['#', 'SQL Query', 'Bindings', 'Time'], $queryData);
        }

        if (! empty($request['errors'])) {
            $this->newLine();
            $this->line('<comment>Errors/Exceptions:</comment>');

            $errorData = [];
            foreach ($request['errors'] as $index => $error) {
                $message = $error['message'] ?? 'N/A';

                $cleanMessage = $this->extractValidationReason($message);

                $errorData[] = [
                    $index + 1,
                    $error['class'] ?? 'Unknown',
                    $this->truncateString($cleanMessage, 60),
                    ($error['file'] ?? 'N/A').':'.($error['line'] ?? 'N/A'),
                ];
            }
            $this->table(['#', 'Exception Class', 'Message', 'Location'], $errorData);
        }

        $this->newLine();
        $this->line('<comment>Performance Summary:</comment>');
        $performanceData = [
            ['Total Duration', ($request['duration_ms'] ?? 0).' ms'],
            ['Database Time', ($request['db_time_ms'] ?? 0).' ms'],
            ['Application Time', (($request['duration_ms'] ?? 0) - ($request['db_time_ms'] ?? 0)).' ms'],
            ['Query Count', $request['query_count'] ?? 0],
            ['Event Count', count($request['events'] ?? [])],
            ['Error Count', count($request['errors'] ?? [])],
        ];
        $this->table(['Metric', 'Value'], $performanceData);

        $status = $request['status'] ?? 0;
        $this->newLine();
        if ($status >= 200 && $status < 300) {
            $this->info('✅ Request completed successfully');
        } elseif ($status >= 400 && $status < 500) {
            $this->warn('⚠️  Client error response');
        } elseif ($status >= 500) {
            $this->error('❌ Server error response');
        } else {
            $this->line('❓ Unknown status code');
        }
    }

    /**
     * Format SQL bindings for display (truncates long values)
     */
    private function formatBindings($bindings): string
    {
        if (empty($bindings)) {
            return '-';
        }

        if ($bindings instanceof BSONArray) {
            $bindings = iterator_to_array($bindings);
        }

        if (! is_array($bindings)) {
            return json_encode($bindings);
        }

        $formatted = [];
        foreach ($bindings as $binding) {
            if (is_string($binding) && strlen($binding) > 50) {
                $formatted[] = '"'.substr($binding, 0, 30).'..." ('.strlen($binding).' chars)';
            } elseif (is_string($binding)) {
                $formatted[] = '"'.$binding.'"';
            } else {
                $formatted[] = json_encode($binding);
            }
        }

        return '['.implode(', ', $formatted).']';
    }

    /**
     * Show statistics from requests
     */
    private function showStatistics($collection): int
    {
        $this->info('📊 Request Statistics');
        $this->newLine();

        $filter = $this->buildFilter();

        $totalRequests = $collection->countDocuments($filter);
        $this->line("Total Requests: <comment>{$totalRequests}</comment>");
        $this->newLine();

        $this->showStatusDistribution($collection, $filter);

        $this->showMethodDistribution($collection, $filter);

        $this->showPerformanceStats($collection, $filter);

        $this->showErrorStats($collection, $filter);

        return 0;
    }

    /**
     * Show status code distribution
     */
    private function showStatusDistribution($collection, array $filter): void
    {
        $pipeline = [
            ['$match' => $filter],
            ['$group' => [
                '_id' => '$status',
                'count' => ['$sum' => 1],
            ]],
            ['$sort' => ['_id' => 1]],
        ];

        $results = $collection->aggregate($pipeline);
        $statusData = [];

        foreach ($results as $result) {
            $statusData[] = [
                $result['_id'] ?? 'Unknown',
                $result['count'],
                $this->getStatusDescription($result['_id'] ?? 0),
            ];
        }

        $this->line('<comment>Status Code Distribution:</comment>');
        $this->table(['Status Code', 'Count', 'Description'], $statusData);
        $this->newLine();
    }

    /**
     * Show HTTP method distribution
     */
    private function showMethodDistribution($collection, array $filter): void
    {
        $pipeline = [
            ['$match' => $filter],
            ['$group' => [
                '_id' => '$method',
                'count' => ['$sum' => 1],
                'avg_duration' => ['$avg' => '$duration_ms'],
            ]],
            ['$sort' => ['count' => -1]],
        ];

        $results = $collection->aggregate($pipeline);
        $methodData = [];

        foreach ($results as $result) {
            $methodData[] = [
                $result['_id'] ?? 'Unknown',
                $result['count'],
                round($result['avg_duration'] ?? 0, 2).' ms',
            ];
        }

        $this->line('<comment>HTTP Method Distribution:</comment>');
        $this->table(['Method', 'Count', 'Avg Duration'], $methodData);
        $this->newLine();
    }

    /**
     * Show performance statistics
     */
    private function showPerformanceStats($collection, array $filter): void
    {
        $pipeline = [
            ['$match' => $filter],
            ['$group' => [
                '_id' => null,
                'avg_duration' => ['$avg' => '$duration_ms'],
                'max_duration' => ['$max' => '$duration_ms'],
                'min_duration' => ['$min' => '$duration_ms'],
                'avg_queries' => ['$avg' => '$query_count'],
                'max_queries' => ['$max' => '$query_count'],
                'avg_db_time' => ['$avg' => '$db_time_ms'],
            ]],
        ];

        $results = $collection->aggregate($pipeline)->toArray();

        if (! empty($results)) {
            $stats = $results[0];

            $performanceData = [
                ['Avg Duration', round($stats['avg_duration'] ?? 0, 2).' ms'],
                ['Max Duration', round($stats['max_duration'] ?? 0, 2).' ms'],
                ['Min Duration', round($stats['min_duration'] ?? 0, 2).' ms'],
                ['Avg Queries', round($stats['avg_queries'] ?? 0, 2)],
                ['Max Queries', $stats['max_queries'] ?? 0],
                ['Avg DB Time', round($stats['avg_db_time'] ?? 0, 2).' ms'],
            ];

            $this->line('<comment>Performance Statistics:</comment>');
            $this->table(['Metric', 'Value'], $performanceData);
            $this->newLine();
        }
    }

    /**
     * Show error statistics
     */
    private function showErrorStats($collection, array $filter): void
    {
        $errorFilter = array_merge($filter, ['errors' => ['$exists' => true, '$ne' => []]]);
        $errorCount = $collection->countDocuments($errorFilter);

        $pipeline = [
            ['$match' => $errorFilter],
            ['$unwind' => '$errors'],
            ['$group' => [
                '_id' => '$errors.class',
                'count' => ['$sum' => 1],
            ]],
            ['$sort' => ['count' => -1]],
            ['$limit' => 10],
        ];

        $results = $collection->aggregate($pipeline);
        $errorData = [];

        foreach ($results as $result) {
            $errorData[] = [
                $result['_id'] ?? 'Unknown',
                $result['count'],
            ];
        }

        $this->line('<comment>Error Statistics:</comment>');
        $this->line("Total Requests with Errors: <comment>{$errorCount}</comment>");

        if (! empty($errorData)) {
            $this->table(['Exception Class', 'Count'], $errorData);
        }
        $this->newLine();
    }

    /**
     * Analyze requests based on filters
     */
    private function analyzeRequests($collection): int
    {
        $filter = $this->buildFilter();
        $options = [
            'sort' => ['started_at' => -1],
            'limit' => (int) $this->option('limit'),
        ];

        $requests = $collection->find($filter, $options);
        $format = $this->option('format');

        switch ($format) {
            case 'json':
                $this->outputJson($requests);
                break;
            case 'detailed':
                $this->outputDetailed($requests);
                break;
            default:
                $this->outputTable($requests);
                break;
        }

        return 0;
    }

    /**
     * Build MongoDB filter from command options
     */
    private function buildFilter(): array
    {
        $filter = [];

        if ($userId = $this->option('user')) {
            $filter['user_id'] = (int) $userId;
        }

        if ($method = $this->option('method')) {
            $filter['method'] = strtoupper($method);
        }

        if ($status = $this->option('status')) {
            $filter['status'] = (int) $status;
        }

        if ($url = $this->option('url')) {
            $filter['url'] = ['$regex' => $url, '$options' => 'i'];
        }

        if ($slow = $this->option('slow')) {
            $filter['duration_ms'] = ['$gte' => (float) $slow];
        }

        if ($this->option('errors')) {
            $filter['errors'] = ['$exists' => true, '$ne' => []];
        }

        if ($from = $this->option('from')) {
            $filter['started_at']['$gte'] = new UTCDateTime(strtotime($from) * 1000);
        }

        if ($to = $this->option('to')) {
            $filter['started_at']['$lte'] = new UTCDateTime(strtotime($to) * 1000);
        }

        return $filter;
    }

    /**
     * Output requests in table format
     */
    private function outputTable($requests): void
    {
        $tableData = [];

        foreach ($requests as $request) {
            $tableData[] = [
                substr($request['_id'], 0, 8).'...',
                $this->formatDateTime($request['started_at'] ?? null, 'H:i:s'),
                $request['method'] ?? 'N/A',
                $this->getStatusIcon($request['status'] ?? 0).' '.($request['status'] ?? 'N/A'),
                round($request['duration_ms'] ?? 0, 1).'ms',
                $request['query_count'] ?? 0,
                $this->truncateString($request['url'] ?? 'N/A', 40),
            ];
        }

        $this->table([
            'Request ID', 'Time', 'Method', 'Status', 'Duration', 'Queries', 'URL',
        ], $tableData);
    }

    /**
     * Get MongoDB collection
     */
    private function getCollection(): Collection
    {
        $database = config('database.connections.mongodb.database');

        return $this->mongo
            ->selectDatabase($database)
            ->selectCollection(
                config('database.connections.mongodb.request_tracking_collection')
            );
    }

    /**
     * Format DateTime for display
     */
    private function formatDateTime($datetime, string $format = 'Y-m-d H:i:s'): string
    {
        if ($datetime instanceof UTCDateTime) {
            return $datetime->toDateTime()->format($format);
        }

        return 'N/A';
    }

    /**
     * Format event data for display
     */
    private function formatEventData($data, int $maxLength = 120): string
    {
        if (empty($data)) {
            return '-';
        }

        $data = $this->convertBsonToArray($data);

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->truncateString($json, $maxLength);
    }

    /**
     * Convert BSONDocument to array recursively
     */
    private function convertBsonToArray($data): array
    {
        if ($data instanceof \MongoDB\Model\BSONDocument) {
            return iterator_to_array($data);
        }

        if ($data instanceof \MongoDB\Model\BSONArray) {
            return iterator_to_array($data);
        }

        if (is_array($data)) {
            return array_map([$this, 'convertBsonToArray'], $data);
        }

        return $data;
    }

    /**
     * Truncate string to specified length
     */
    private function truncateString(string $string, int $length): string
    {
        return strlen($string) > $length ? substr($string, 0, $length - 3).'...' : $string;
    }

    /**
     * Get status code description
     */
    private function getStatusDescription(int $status): string
    {
        $descriptions = [
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation Error',
            500 => 'Server Error',
        ];

        return $descriptions[$status] ?? 'Unknown';
    }

    /**
     * Get status icon for display
     */
    private function getStatusIcon(int $status): string
    {
        if ($status >= 200 && $status < 300) {
            return '✅';
        }
        if ($status >= 300 && $status < 400) {
            return '🔄';
        }
        if ($status >= 400 && $status < 500) {
            return '❌';
        }
        if ($status >= 500) {
            return '💥';
        }

        return '❓';
    }

    /**
     * Output requests in JSON format
     */
    private function outputJson($requests): void
    {
        $data = [];
        foreach ($requests as $request) {
            $data[] = $request;
        }

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Output requests in detailed format
     */
    private function outputDetailed($requests): void
    {
        foreach ($requests as $request) {
            $this->displayDetailedRequest($request);
            $this->line(str_repeat('-', 80));
        }
    }
}
