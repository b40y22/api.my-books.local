<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Http\Dto\Request\Auth\ForgotPasswordDto;
use App\Http\Dto\Request\Auth\ResetPasswordDto;

interface PasswordResetServiceInterface
{
    public function sendResetLink(ForgotPasswordDto $forgotPasswordDto): array;

    public function resetPassword(ResetPasswordDto $resetPasswordDto): array;
}
