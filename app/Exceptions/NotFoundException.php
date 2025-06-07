<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class NotFoundException extends Exception
{
    protected int $statusCode = Response::HTTP_NOT_FOUND;

    public function __construct(
        protected $message
    ) {
        parent::__construct($message ?? 'Not found');
    }

    public function render(): JsonResponse
    {
        return new JsonResponse([
            'data' => [],
            'errors' => [$this->message],
        ],
            $this->statusCode
        );
    }
}
