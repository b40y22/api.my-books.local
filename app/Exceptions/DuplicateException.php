<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DuplicateException extends Exception
{
    protected int $statusCode = Response::HTTP_CONFLICT;

    protected $message;

    public function __construct(
        protected string $field,
    ) {
        $this->message = __('validation.duplicate', ['attribute' => $this->field]);

        parent::__construct($this->message);
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
