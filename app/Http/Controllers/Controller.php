<?php

namespace App\Http\Controllers;

use App\Services\RequestLogger;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    public function success(array $data = []): JsonResponse
    {
        return self::makeResponse($data, 200);
    }

    public function created(array $data): JsonResponse
    {
        return self::makeResponse($data, 201);
    }

    public function successEmpty(): JsonResponse
    {
        return self::makeResponse([], 204);
    }

    protected static function makeResponse(array $data, int $statusCode): JsonResponse
    {
        $requestId = RequestLogger::getRequestId();

        return response()->json([
            'data' => $data,
            'errors' => [],
            'request_id' => $requestId,
        ],
            $statusCode,
            [],
            JSON_PRETTY_PRINT
        );
    }
}
