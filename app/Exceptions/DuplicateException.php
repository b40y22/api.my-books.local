<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

final class DuplicateException extends BaseException
{
    protected int $statusCode = Response::HTTP_CONFLICT;
}
