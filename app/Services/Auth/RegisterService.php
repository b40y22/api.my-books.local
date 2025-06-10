<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Http\Dto\Request\Auth\RegisterDto;
use App\Models\User;
use App\Repositories\Users\UserRepositoryInterface;
use App\Services\RequestLogger;

final readonly class RegisterService implements RegisterServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function store(RegisterDto $registerDto): User
    {
        RequestLogger::addEvent('[service] registration_process_started', [
            'email' => $registerDto->email,
        ]);

        $user = $this->userRepository->store($registerDto);

        RequestLogger::addEvent('[service] registration_completed', [
            'user_id' => $user->id,
        ]);

        return $user;
    }
}
