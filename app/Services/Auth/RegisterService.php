<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Http\Dto\Request\Auth\RegisterDto;
use App\Models\User;
use App\Repositories\Users\UserRepositoryInterface;

final readonly class RegisterService implements RegisterServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function store(RegisterDto $registerDto): User
    {
        return $this->userRepository->store($registerDto);
    }
}
