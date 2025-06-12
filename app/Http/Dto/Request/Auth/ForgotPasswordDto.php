<?php

declare(strict_types=1);

namespace App\Http\Dto\Request\Auth;

use App\Http\Dto\BaseDto;

final class ForgotPasswordDto extends BaseDto
{
    public string $email;

    public function __construct(array $data)
    {
        $this->email = $data['email'];
    }
}
