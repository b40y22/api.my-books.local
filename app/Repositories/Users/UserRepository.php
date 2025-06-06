<?php

declare(strict_types=1);

namespace App\Repositories\Users;

use App\Exceptions\DuplicateException;
use App\Http\Dto\Request\Auth\RegisterDto;
use App\Models\User;
use Exception;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        public User $model
    ) {}

    /**
     * @throws Exception
     */
    public function store(RegisterDto $registerData): User
    {
        $user = $this->model->where('email', $registerData->email)->exists();

        if ($user) {
            throw new DuplicateException('email');
        }

        return $this->model->create($registerData->toArray());
    }
}
