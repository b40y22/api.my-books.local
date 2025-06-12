<?php

declare(strict_types=1);

namespace App\Http\Dto\Request\Auth;

use App\Http\Dto\BaseDto;

final class LoginDto extends BaseDto
{
    public string $email;
    public string $password;
    public bool $remember;

    public function __construct(array $data)
    {
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->remember = $data['remember'] ?? false;
    }
}
