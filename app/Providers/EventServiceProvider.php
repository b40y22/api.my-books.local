<?php

namespace App\Providers;

use App\Services\RequestLogger;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

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

        Queue::before(function (JobProcessing $event) {
            $this->logQueueEvent('queue_job_started', $event);
        });

        Queue::after(function (JobProcessed $event) {
            $this->logQueueEvent('queue_job_completed', $event);
        });

        Queue::failing(function (JobFailed $event) {
            $this->logQueueEvent('queue_job_failed', $event, [
                'exception' => $event->exception->getMessage(),
            ]);
        });
    }

    private function logQueueEvent(string $eventName, $event, array $extraData = []): void
    {
        $jobData = [
            'job_id' => $event->job->getJobId(),
            'queue_name' => $event->job->getQueue(),
            'attempts' => $event->job->attempts(),
        ];

        RequestLogger::addEvent($eventName, array_merge($jobData, $extraData));
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
