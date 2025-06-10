<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\RequestLogger;
use Exception;
use Illuminate\Http\JsonResponse;

class BaseException extends Exception
{
    protected int $statusCode = 500;

    public function __construct(
        protected $message
    ) {
        parent::__construct();
    }

    public function render(): JsonResponse
    {
        $requestId = RequestLogger::getRequestId();

        return new JsonResponse([
            'data' => [],
            'errors' => [$this->message],
            'request_id' => $requestId,
        ],
            $this->statusCode
        )->withHeaders([
            'X-Request-ID' => $requestId,
            'X-Error-Type' => 'exception',
        ]);
    }
}
