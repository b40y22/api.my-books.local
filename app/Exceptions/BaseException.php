<?php

declare(strict_types=1);

namespace App\Exceptions;

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
        return new JsonResponse([
            'data' => [],
            'errors' => [$this->message],
        ],
            $this->statusCode
        );
    }
}
