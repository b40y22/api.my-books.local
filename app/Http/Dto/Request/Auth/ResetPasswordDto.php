<?php

declare(strict_types=1);

namespace App\Http\Dto\Request\Auth;

use App\Http\Dto\BaseDto;

final class ResetPasswordDto extends BaseDto
{
    public string $token;

    public string $email;

    public string $password;

    public function __construct(array $data)
    {
        $this->token = $data['token'];
        $this->email = $data['email'];
        $this->password = $data['password'];
    }
}
