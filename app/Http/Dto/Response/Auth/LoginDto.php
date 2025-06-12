<?php

declare(strict_types=1);

namespace App\Http\Dto\Response\Auth;

use App\Http\Dto\BaseDto;
use App\Models\User;

final class LoginDto extends BaseDto
{
    public string $name;

    public string $email;

    public string $token;

    public function __construct(User $user)
    {
        $this->email = $user->email;
        $this->name = $user->name;
        $this->token = $user->createToken('auth')->plainTextToken;
    }
}
