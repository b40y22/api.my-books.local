<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\ValidationException;
use App\Http\Dto\Request\Auth\ForgotPasswordDto;
use App\Http\Dto\Request\Auth\ResetPasswordDto;
use App\Repositories\Users\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

final readonly class PasswordResetService implements PasswordResetServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * @throws ValidationException
     */
    public function sendResetLink(ForgotPasswordDto $forgotPasswordDto): array
    {
        $user = $this->userRepository->findByEmail($forgotPasswordDto->email);

        if (! $user) {
            throw new ValidationException(__('passwords.user'));
        }

        if (! $user->hasVerifiedEmail()) {
            throw new ValidationException(__('auth.email_not_verified'));
        }

        $status = Password::sendResetLink(['email' => $forgotPasswordDto->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new ValidationException(__($status));
        }

        return [
            'message' => __('passwords.sent'),
        ];
    }

    /**
     * @throws ValidationException
     */
    public function resetPassword(ResetPasswordDto $resetPasswordDto): array
    {
        $user = $this->userRepository->findByEmail($resetPasswordDto->email);

        if (! $user) {
            throw new ValidationException(__('passwords.user'));
        }

        // Attempt to reset the password
        $status = Password::reset(
            [
                'email' => $resetPasswordDto->email,
                'password' => $resetPasswordDto->password,
                'password_confirmation' => $resetPasswordDto->password,
                'token' => $resetPasswordDto->token,
            ],
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();

                // Revoke all existing tokens for security
                $user->tokens()->delete();
            }
        );

        // Compare with the correct constant
        if ($status !== Password::PASSWORD_RESET) {
            throw new ValidationException(__($status));
        }

        return [
            'message' => __('passwords.reset'),
        ];
    }
}
