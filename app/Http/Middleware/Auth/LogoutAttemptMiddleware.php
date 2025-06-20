<?php

declare(strict_types=1);

namespace App\Http\Middleware\Auth;

use App\Services\RequestLogger;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class LogoutAttemptMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Log logout attempt start
        $this->logLogoutAttemptStart($request);

        // Process the request
        $response = $next($request);

        // Log logout attempt completion
        $this->logLogoutAttemptCompletion($request, $response);

        return $response;
    }

    private function logLogoutAttemptStart(Request $request): void
    {
        $user = null;

        try {
            $user = Auth::user();

            // Simplified - don't call currentAccessToken for now
            $currentToken = null;
            if ($user) {
                try {
                    $currentToken = $user->currentAccessToken();
                } catch (Exception $tokenException) {
                    // If getting token fails, continue without it
                    $currentToken = null;
                }
            }

            RequestLogger::addEvent('logout_attempt_started', [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'token_name' => $currentToken?->name,
                'token_id' => $currentToken?->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'logout_type' => $this->determineLogoutType($request),
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to log logout attempt start', [
                'exception' => $e->getMessage(),
                'user_id' => $user?->id,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
        }
    }

    private function logLogoutAttemptCompletion(Request $request, Response $response): void
    {
        $user = null;

        try {
            $user = Auth::user();
            $statusCode = $response->getStatusCode();
            $success = $statusCode >= 200 && $statusCode < 300;

            RequestLogger::addEvent('logout_attempt_completed', [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'ip' => $request->ip(),
                'logout_type' => $this->determineLogoutType($request),
                'success' => $success,
                'status_code' => $statusCode,
                'duration_ms' => $this->calculateDuration(),
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to log logout attempt completion', [
                'exception' => $e->getMessage(),
                'user_id' => $user?->id,
                'ip' => $request->ip(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
            ]);
        }
    }

    private function determineLogoutType(Request $request): string
    {
        $path = $request->path();

        if (str_contains($path, 'logout-all')) {
            return 'all_devices';
        }

        return 'current_device';
    }

    private function calculateDuration(): float
    {
        // Simple duration calculation - in real implementation you might want
        // to store start time in request attributes for more accurate measurement
        return round((microtime(true) * 1000)) % 1000; // Simplified for demo
    }
}
