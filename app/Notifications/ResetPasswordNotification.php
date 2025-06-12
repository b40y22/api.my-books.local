<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Services\RequestLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        RequestLogger::addEvent('password_reset_email_generated', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'token_preview' => substr($this->token, 0, 8) . '...',
        ]);

        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Reset your password')
            ->view('emails.reset-password', [
                'user' => $notifiable,
                'resetUrl' => $resetUrl,
                'token' => $this->token,
            ]);
    }

    /**
     * Generate the password reset URL for API
     */
    protected function resetUrl($notifiable): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        return "{$frontendUrl}/reset-password?token={$this->token}&email={$notifiable->email}";
    }
}
