<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\ValidationException;
use App\Http\Dto\Request\Auth\ForgotPasswordDto;

interface PasswordResetServiceInterface
{
    /**
     * @param ForgotPasswordDto $forgotPasswordDto
     * @return array
     */
    public function sendResetLink(ForgotPasswordDto $forgotPasswordDto): array;
}
