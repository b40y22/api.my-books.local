<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Events\Auth\UserRegistered;
use App\Exceptions\HttpRequestException;
use App\Exceptions\NotFoundException;
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
     * @throws NotFoundException
     */
    public function verifyEmail(Request $request, int $userId, string $hash): array
    {
        if (! $request->hasValidSignature()) {
            throw new HttpRequestException(__('auth.invalid_verification_link'));
        }

        $user = $this->userRepository->findOrFail($userId);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw new ValidationException(__('auth.invalid_verification_link'));
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'message' => __('auth.email_already_verified'),
                'verified' => true,
            ];
        }

        $user->markEmailAsVerified();

        return [
            'message' => __('auth.email_verified'),
            'verified' => true,
        ];
    }

    public function resendVerificationEmail(int $userId): array
    {
        /** @var User $user */
        $user = $this->userRepository->findOrFail($userId);

        if ($user->hasVerifiedEmail()) {
            return [
                'message' => __('auth.email_already_verified'),
                'already_verified' => true,
            ];
        }

        $user->sendEmailVerificationNotification();

        event(new UserRegistered($user));

        return [
            'message' => __('auth.verification_link_sent'),
        ];
    }
}
