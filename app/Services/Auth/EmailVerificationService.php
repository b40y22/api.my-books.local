<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Events\Auth\UserRegistered;
use App\Exceptions\HttpRequestException;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Repositories\Users\UserRepository;
use Illuminate\Http\Request;

final readonly class EmailVerificationService implements EmailVerificationServiceInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * @throws HttpRequestException
     * @throws ValidationException
     */
    public function verifyEmail(Request $request, int $userId, string $hash): array
    {
        if (! $request->hasValidSignature()) {
            throw new HttpRequestException('The verification link is invalid.');
        }

        /** @var User $user */
        $user = $this->userRepository->findOrFail($userId);

        if (! $user) {
            throw new ValidationException('User not found.');
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'message' => 'Email already verified.'
            ];
        }

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw new ValidationException('The verification link is invalid.');
        }

        $user->markEmailAsVerified();

        return [
            'message' => 'Email verified successfully.'
        ];
    }

    /**
     * @return string[]
     *
     * @throws ValidationException
     */
    public function resendVerificationEmail(int $userId): array
    {
        /** @var User $user */
        $user = $this->userRepository->findOrFail($userId);

        if ($user->hasVerifiedEmail()) {
            throw new ValidationException('Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        event(new UserRegistered($user));

        return [
            'message' => 'Verification email sent.'
        ];
    }

    /**
     * @return string[]
     *
     * @throws ValidationException
     */
    public function resendVerificationEmailForUser($user): array
    {
        if ($user->hasVerifiedEmail()) {
            throw new ValidationException('Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        return [
            'message' => 'Verification email sent.'
        ];
    }
}
