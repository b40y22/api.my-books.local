<?php

declare(strict_types=1);

namespace App\Http\Middleware\Auth;

use App\Services\RequestLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LoginAttemptMiddleware
{
    /**
     * Handle an incoming request and log login attempts for security monitoring
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log the start of login attempt with user details and request info
        RequestLogger::addEvent('[middleware] login_attempt_started', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        $response = $next($request);

        // Determine if login was successful based on HTTP status code
        $isSuccessful = $response->getStatusCode() === 200;

        // Log the completion of login attempt with result
        RequestLogger::addEvent('[middleware] login_attempt_completed', [
            'email' => $request->input('email'),
            'success' => $isSuccessful,
            'status_code' => $response->getStatusCode(),
            'ip' => $request->ip(),
        ]);

        // Additional logging for failed attempts to help with security analysis
        if (!$isSuccessful) {
            RequestLogger::addEvent('[middleware] login_failed', [
                'email' => $request->input('email'),
                'reason' => $this->getFailureReason($response),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }

    /**
     * Determine the reason for login failure based on HTTP status code
     */
    private function getFailureReason(Response $response): string
    {
        return match($response->getStatusCode()) {
            401 => 'invalid_credentials',
            422 => 'validation_error',
            429 => 'rate_limited',
            default => 'unknown_error'
        };
    }
}
