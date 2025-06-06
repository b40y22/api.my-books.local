<?php

namespace App\Services\Auth;

use App\Http\Dto\Request\Auth\RegisterDto;
use App\Models\User;

interface RegisterServiceInterface
{
    public function store(RegisterDto $registerDto): User;
}
