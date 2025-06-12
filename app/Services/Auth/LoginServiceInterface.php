<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Http\Dto\Request\Auth\LoginDto;

interface LoginServiceInterface
{
    /**
     * Authenticate user and return access token with user data
     *
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function authenticate(LoginDto $loginDto): array;

    /**
     * Logout user by revoking current access token
     */
    public function logout(): void;

    /**
     * Logout user from all devices by revoking all tokens
     */
    public function logoutFromAllDevices(): void;
}
