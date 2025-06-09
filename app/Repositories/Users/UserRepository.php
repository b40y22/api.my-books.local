<?php

declare(strict_types=1);

namespace App\Repositories\Users;

use App\Events\Auth\UserRegistered;
use App\Http\Dto\Request\Auth\RegisterDto;
use App\Models\User;
use App\Repositories\AbstractRepository;

final class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public function __construct() {
        parent::__construct(new User);
    }

    public function store(RegisterDto $registerData): User
    {
        $user = $this->model->create($registerData->toArray());

        event(new UserRegistered($user));

        return $user;
    }
}
