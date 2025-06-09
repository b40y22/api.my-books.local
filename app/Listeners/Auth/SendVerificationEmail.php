<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Events\Auth\UserRegistered;
use App\Jobs\SendVerificationEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendVerificationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserRegistered $event): void
    {
        dispatch(new SendVerificationEmailJob($event->user))->onQueue('emails');
    }

    public function failed(UserRegistered $event, Throwable $exception): void
    {
        Log::error('Failed to queue verification email', [
            'user_id' => $event->user->id,
            'error' => $exception->getMessage()
        ]);
    }
}
