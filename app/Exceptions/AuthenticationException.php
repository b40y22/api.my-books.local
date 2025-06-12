<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\RequestLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationException extends BaseException
{
    protected int $statusCode = Response::HTTP_UNAUTHORIZED;

    public static function handle(\Illuminate\Auth\AuthenticationException $e, Request $request): ?JsonResponse
    {
        if ($request->expectsJson()) {
            $requestId = RequestLogger::getRequestId();

            RequestLogger::addEvent('[exception] authentication_failed_response', [
                'guards' => $e->guards(),
                'intended_url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'data' => [],
                'errors' => [__('auth.unauthenticated')],
                'request_id' => $requestId,
            ], Response::HTTP_UNAUTHORIZED)->withHeaders([
                'X-Request-ID' => $requestId,
                'X-Error-Type' => 'authentication',
            ]);
        }

        return null;
    }
}
