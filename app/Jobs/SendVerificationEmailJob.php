<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;
    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 10;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        if ($this->user->hasVerifiedEmail()) {
            return;
        }

        $verifyUrl = $this->generateVerificationUrl();
        $data = [
            'user' => $this->user,
            'verifyUrl' => $verifyUrl,
            'userName' => $this->user->name ?? 'User',
            'locale' => $this->determineUserLocale()
        ];

        Mail::send('emails.verify-email', $data, function ($message) {
                $message->to($this->user->email)->subject('Verify your email address, please');
            }
        );
    }

    protected function determineUserLocale(): string
    {
        return request()->header('Accept-Language') ?? 'en';
    }

    /**
     * Generate verification URL for the user
     */
    protected function generateVerificationUrl(): string
    {
        $data = [
            'id' => $this->user->getKey(),
            'hash' => sha1($this->user->getEmailForVerification()),
        ];

        return URL::temporarySignedRoute(
            'auth.verification.verify',
            Carbon::now()->addMinutes(60),
            $data
        );
    }

    /**
     * Handle a job failure
     */
    public function failed(Throwable $exception): void
    {
        $data = [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ];

        Log::error('Failed to send email verification', $data);
    }
}
