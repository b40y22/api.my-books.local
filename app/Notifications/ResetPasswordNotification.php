<?php

declare(strict_types=1);

namespace App\Notifications;

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
        // For API, you might want to generate a frontend URL
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        return "{$frontendUrl}/reset-password?token={$this->token}&email={$notifiable->email}";
    }
}
