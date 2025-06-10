<?php

declare(strict_types=1);

namespace App\Http\Dto\Request\Auth;

use App\Http\Dto\BaseDto;

final class RegisterDto extends BaseDto
{
    public string $firstname;

    public ?string $lastname;

    public string $email;

    public string $password;

    public function __construct(array $data)
    {
        $this->firstname = $data['firstname'];
        $this->lastname = $data['lastname'] ?? null;
        $this->email = $data['email'];
        $this->password = $data['password'];
    }

    public function getFullName(): string
    {
        return $this->firstname.' '.$this->lastname;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getFullName(),
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
