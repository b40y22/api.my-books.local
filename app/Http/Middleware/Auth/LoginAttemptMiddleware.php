<?php

declare(strict_types=1);

namespace App\Http\Middleware\Auth;

use App\Services\RequestLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LoginAttemptMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        RequestLogger::addEvent('[middleware] login_attempt_started', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        $response = $next($request);

        $isSuccessful = $response->getStatusCode() === 200;

        RequestLogger::addEvent('[middleware] login_attempt_completed', [
            'email' => $request->input('email'),
            'success' => $isSuccessful,
            'status_code' => $response->getStatusCode(),
            'ip' => $request->ip(),
        ]);

        if (! $isSuccessful) {
            RequestLogger::addEvent('[middleware] login_failed', [
                'email' => $request->input('email'),
                'reason' => $this->getFailureReason($response),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }

    private function getFailureReason(Response $response): string
    {
        return match ($response->getStatusCode()) {
            Response::HTTP_UNAUTHORIZED => 'invalid_credentials',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'validation_error',
            Response::HTTP_TOO_MANY_REQUESTS => 'rate_limited',
            default => 'unknown_error'
        };
    }
}
