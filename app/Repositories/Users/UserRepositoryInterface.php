<?php

namespace App\Repositories\Users;

use App\Http\Dto\Request\Auth\RegisterDto;
use App\Models\User;

interface UserRepositoryInterface
{
    public function store(RegisterDto $registerData): User;
}
