<?php

declare(strict_types=1);

namespace App\Repositories\Users;

use App\Events\Auth\UserRegistered;
use App\Http\Dto\Request\Auth\RegisterDto;
use App\Models\User;
use App\Repositories\AbstractRepository;
use App\Services\RequestLogger;

final class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new User);
    }

    public function store(RegisterDto $registerData): User
    {
        RequestLogger::addEvent('[repository] user_creation_started', [
            'email' => $registerData->email,
        ]);

        $user = $this->model->create($registerData->toArray());

        RequestLogger::addEvent('[repository] user_created_successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        event(new UserRegistered($user));

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function existsByEmail(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }
}
