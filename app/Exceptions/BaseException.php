<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BaseException extends Exception
{
    private int $statusCode = 500;

    public function __construct(
        private readonly Error $error
    ) {
        parent::__construct();
    }

    public function render(): JsonResponse
    {
        return new JsonResponse([
            'data' => [],
            'errors' => $this->error->getErrorForResponse(),
        ],
            $this->statusCode
        );
    }
}
