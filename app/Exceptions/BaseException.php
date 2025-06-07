<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BaseException extends Exception
{
    public function __construct(
        protected TrackableError $error
    ) {
        parent::__construct($this->error->getErrorMessage());
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
