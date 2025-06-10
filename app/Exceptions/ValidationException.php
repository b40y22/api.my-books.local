<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

final class ValidationException extends BaseException
{
    protected int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function __construct(string|array $errors)
    {
        if (is_array($errors)) {
            $message = implode(', ', $errors);
        } else {
            $message = $errors;
        }

        parent::__construct($message);
    }
}
