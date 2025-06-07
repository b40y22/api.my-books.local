<?php

declare(strict_types=1);

namespace App\Http\Dto\Request\Auth;

use App\Http\Dto\Request\BaseDto;
use App\Http\Requests\Auth\RegisterRequest;

final class RegisterDto extends BaseDto
{
    public string $firstname;

    public ?string $lastname;

    public string $email;

    public string $password;

    public string $locale;

    public function __construct(array $data)
    {
        $this->firstname = $data['firstname'];
        $this->lastname = $data['lastname'] ?? null;
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->locale = $data['locale'] ?? config('app.locale');
    }

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self([
            'firstname' => $request->validated('firstname'),
            'lastname' => $request->validated('lastname'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'locale' => $request->getAcceptLanguage(),
        ]);
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
            'locale' => $this->locale,
        ];
    }
}
