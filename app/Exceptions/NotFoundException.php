<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

final class NotFoundException extends BaseException
{
    protected int $statusCode = Response::HTTP_NOT_FOUND;
}
