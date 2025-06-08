<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\HttpRequestException;
use App\Exceptions\Error;
use App\Exceptions\ValidationException;
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
            $error = new Error(__('auth.invalid_verification_link'));

            throw new HttpRequestException($error);
        }

        $user = $this->userRepository->findOrFail($userId);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            $error = new Error(__('auth.invalid_verification_link'));

            throw new ValidationException($error);
        }

    }
}
