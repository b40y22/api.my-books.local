<?php

declare(strict_types=1);

namespace App\Http\Dto\Request;

abstract class BaseDto
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
