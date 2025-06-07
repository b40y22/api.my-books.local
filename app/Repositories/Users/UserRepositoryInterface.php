<?php

namespace App\Repositories\Users;

use App\Http\Dto\Request\Auth\RegisterDto;
use App\Models\User;
use App\Repositories\AbstractRepositoryInterface;

interface UserRepositoryInterface extends AbstractRepositoryInterface
{
    public function store(RegisterDto $registerData): User;
}
