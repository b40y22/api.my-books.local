<?php

declare(strict_types=1);

namespace App\Http\Middleware\Auth;

use App\Services\RequestLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class LogoutAttemptMiddleware
{
    /**
     * Handle an incoming request and log logout attempts for security monitoring
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $currentToken = $user?->currentAccessToken();

        // Log the start of logout attempt
        RequestLogger::addEvent('logout_attempt_started', [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'token_name' => $currentToken?->name,
            'token_id' => $currentToken?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'logout_type' => $this->getLogoutType($request),
        ]);

        $response = $next($request);

        // Determine if logout was successful
        $isSuccessful = $response->getStatusCode() === 200;

        // Log the completion of logout attempt
        RequestLogger::addEvent('logout_attempt_completed', [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'success' => $isSuccessful,
            'status_code' => $response->getStatusCode(),
            'logout_type' => $this->getLogoutType($request),
            'ip' => $request->ip(),
        ]);

        // Additional logging for failed attempts
        if (! $isSuccessful) {
            RequestLogger::addEvent('logout_failed', [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'reason' => $this->getFailureReason($response),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }

    /**
     * Determine the type of logout based on the route
     */
    private function getLogoutType(Request $request): string
    {
        if ($request->route()?->getName() === 'auth.logout.all') {
            return 'all_devices';
        }

        return 'current_device';
    }

    /**
     * Determine the reason for logout failure based on HTTP status code
     */
    private function getFailureReason(Response $response): string
    {
        return match ($response->getStatusCode()) {
            401 => 'unauthenticated',
            403 => 'forbidden',
            500 => 'server_error',
            default => 'unknown_error'
        };
    }
}
