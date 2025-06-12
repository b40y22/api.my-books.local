<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\ValidationException;
use App\Http\Dto\Request\Auth\ForgotPasswordDto;
use App\Repositories\Users\UserRepositoryInterface;
use Illuminate\Support\Facades\Password;

final readonly class PasswordResetService implements PasswordResetServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * @param ForgotPasswordDto $forgotPasswordDto
     * @return array
     * @throws ValidationException
     */
    public function sendResetLink(ForgotPasswordDto $forgotPasswordDto): array
    {
        $user = $this->userRepository->findByEmail($forgotPasswordDto->email);

        if (!$user) {
            throw new ValidationException(__('passwords.user'));
        }

        if (!$user->hasVerifiedEmail()) {
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
}
