<?php

namespace App\Http\Middleware;

use App\Services\RequestLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class RequestTrackingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = RequestLogger::startRequest();

        $request->headers->set('X-Request-ID', $requestId);

        $response = $next($request);

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        RequestLogger::finishRequest($response->getStatusCode());
    }
}
